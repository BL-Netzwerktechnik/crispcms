<?php

/*
 * Copyright (c) 2021. JRB IT, All Rights Reserved
 *
 *  @author Justin René Back <j.back@jrbit.de>
 *  @link https://github.com/jrbit/crispcms
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


namespace crisp\api;

use crisp\core\LogTypes;
use crisp\core\Postgres;
use PDO;
use function serialize;
use function unserialize;
use crisp\core\Bitmask;
use crisp\core\Logger;
use crisp\core\RESTfulAPI;

/**
 * Interact with the key/value storage of the server
 */
class Config
{

    /**
     * The time to live for the cache
     *
     * @var integer
     */
    private static int $TTL = 120;

    /**
     * Database connection
     *
     * @var PDO|null
     */
    private static ?PDO $Database_Connection = null;

    /**
     * Initialize the database connection
     *
     * @return void
     */
    private static function initDB(): void
    {
        $DB = new Postgres();
        self::$Database_Connection = $DB->getDBConnector();
    }

    /**
     * Checks if a Storage items exists using the specified key
     * @param string|int $Key the key to retrieve from the KV Config from
     * @return boolean TRUE if it exists, otherwise FALSE
     */
    public static function exists(string|int $Key): bool
    {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        Logger::getLogger(__METHOD__)->debug("Config::exists: SELECT value FROM Config WHERE key = :ID");
        $statement = self::$Database_Connection->prepare("SELECT value FROM Config WHERE key = :ID");
        $statement->execute(array(":ID" => $Key));
        return $statement->rowCount() > 0;
    }

    /**
     * Retrieves a value from the KV Storage using the specified key
     * @param string $Key the key to retrieve from the KV Config from
     * @param array $UserOptions
     * @return mixed The value as string, on failure FALSE
     */
    public static function get(string $Key): mixed
    {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        Logger::getLogger(__METHOD__)->debug("Getting key $Key");

        if(!Cache::isExpired("Config::get::$Key")){
            Logger::getLogger(__METHOD__)->debug("Cache::Config::get::$Key");
            return self::evaluateRow(json_decode(Cache::get("Config::get::$Key"), true));
        }



        Logger::getLogger(__METHOD__)->debug("Config::get: SELECT value, type FROM Config WHERE key = $Key");

        $statement = self::$Database_Connection->prepare("SELECT value, type FROM Config WHERE key = :ID");
        $statement->execute(array(":ID" => $Key));
        if ($statement->rowCount() > 0) {

            $Result = $statement->fetch(PDO::FETCH_ASSOC);

            Cache::write("Config::get::$Key", json_encode($Result), time() + self::$TTL);

            return self::evaluateRow($Result);
        }
        return false;
    }

    private static function evaluateRow($Result){
        return match ($Result["type"]) {
            'serialized' => unserialize($Result["value"]),
            'boolean' => (bool)$Result["value"],
            'integer' => (int)$Result["value"],
            'double' => (double)$Result["value"],
            default => $Result["value"],
        };
    }

    /**
     * Get the timestamps of a key
     * @param string $Key The KVStorage key
     * @return bool|array Containing last_changed, created_at
     */
    public static function getTimestamp(string $Key): bool|array
    {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        Logger::getLogger(__METHOD__)->debug("Config::getTimestamp: SELECT last_changed, created_at FROM Config WHERE key = $Key");
        $statement = self::$Database_Connection->prepare("SELECT last_changed, created_at FROM Config WHERE key = :ID");
        $statement->execute(array(":ID" => $Key));
        if ($statement->rowCount() > 0) {

            return $statement->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * Create a new KV Storage entry using the specified key and value
     * @param string $Key the key to insert
     * @param mixed $Value the value to insert
     * @return boolean TRUE on success, on failure FALSE
     */
    public static function create(string $Key, mixed $Value): bool
    {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        if (self::exists($Key)) {
            return self::set($Key, $Value);
        }
        Logger::getLogger(__METHOD__)->debug("Config::create: INSERT INTO Config (key) VALUES ($Key)");

        $statement = self::$Database_Connection->prepare("INSERT INTO Config (key) VALUES (:Key)");
        $statement->execute(array(":Key" => $Key));

        return self::set($Key, $Value);
    }

    /**
     * Delete a KV Storage entry using the specified key
     * @param string $Key the key to insert
     * @return boolean TRUE on success, on failure FALSE
     */
    public static function delete(string $Key): bool
    {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        Logger::getLogger(__METHOD__)->debug("Config::delete: DELETE FROM Config WHERE key = $Key");
        $statement = self::$Database_Connection->prepare("DELETE FROM Config WHERE key = :Key");
        self::deleteCache("Config::get::$Key");
        return $statement->execute(array(":Key" => $Key));
    }

    public static function deleteCache(string $Key): bool{
        return Cache::delete("Config::get::$Key");
    }

    /**
     * Updates a value for a key in the KV Storage
     * @param string $Key Existing key to change the value from
     * @param mixed $Value The value to set
     * @return boolean TRUE on success, otherwise FALSE
     */
    public static function set(string $Key, mixed $Value): bool
    {
        if (self::$Database_Connection === null) {
            self::initDB();
        }

        if (!self::exists($Key)) {
            self::create($Key, $Value);
        }

        $Type = match(true){
            is_null($Value) => "NULL",
            Helper::isSerialized($Value), (is_array($Value) || is_object($Value)) => "serialized",
            default => gettype($Value)
        };

        if ($Type == "boolean") {
            $Value = ($Value ? 1 : 0);
        }

        self::deleteCache("Config::get::$Key");
        Logger::getLogger(__METHOD__)->debug("Config::set: UPDATE Config SET value = $Value, type = $Type WHERE key = $Key");
        $statement = self::$Database_Connection->prepare("UPDATE Config SET value = :val, type = :type WHERE key = :key");
        $statement->execute(array(":val" => $Value, ":key" => $Key, ":type" => $Type));

        return ($statement->rowCount() > 0);
    }

    /**
     * Returns all keys and values from the KV Storage
     * @param bool $KV List keys as associative array?
     * @return array
     */
    public static function list(bool $KV = false): array
    {
        if (self::$Database_Connection === null) {
            self::initDB();
        }

        Logger::getLogger(__METHOD__)->debug("Config::list: SELECT key, value FROM Config");
        $statement = self::$Database_Connection->prepare("SELECT key, value FROM Config");
        $statement->execute();

        if (!$KV) {
            $Array = array();

            foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $Item) {
                $Array[$Item["key"]] = self::get($Item["key"]);
            }

            return $Array;
        }


        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

}
