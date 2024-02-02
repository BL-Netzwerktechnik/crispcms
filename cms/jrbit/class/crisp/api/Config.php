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

use crisp\core\Postgres;
use function unserialize;
use crisp\core\Logger;

/**
 * Interact with the key/value storage of the server.
 */
class Config
{

    /**
     * The time to live for the cache.
     *
     * @var int
     */
    private static int $TTL = 120;

    /**
     * Database connection.
     *
     * @var \PDO|null
     */
    private static ?\PDO $Database_Connection = null;

    /**
     * Initialize the database connection.
     */
    private static function initDB(): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);


        $parent = \Sentry\SentrySdk::getCurrentHub()->getSpan();
        $span = null;

        if ($parent) {
            $context = new \Sentry\Tracing\SpanContext();
            $context->setOp(__METHOD__);
            $context->setDescription('Initialize Database');
            $span = $parent->startChild($context);

            \Sentry\SentrySdk::getCurrentHub()->setSpan($span);
        }

        $DB = new Postgres();
        self::$Database_Connection = $DB->getDBConnector();


        if ($span) {
            $span->finish();
            \Sentry\SentrySdk::getCurrentHub()->setSpan($parent);
        }
    }

    /**
     * Checks if a Storage items exists using the specified key.
     *
     * @param  string|int $Key the key to retrieve from the KV Config from
     * @return bool       TRUE if it exists, otherwise FALSE
     */
    public static function exists(string|int $Key): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);



        $parent = \Sentry\SentrySdk::getCurrentHub()->getSpan();
        $span = null;

        if ($parent) {
            $context = new \Sentry\Tracing\SpanContext();
            $context->setOp(__METHOD__);
            $context->setDescription('Checking if Config Key Exists');
            $span = $parent->startChild($context);

            \Sentry\SentrySdk::getCurrentHub()->setSpan($span);
        }

        if (self::$Database_Connection === null) {
            self::initDB();
        }
        Logger::getLogger(__METHOD__)->debug("Config::exists: SELECT value FROM Config WHERE key = :ID");
        $statement = self::$Database_Connection->prepare("SELECT value FROM Config WHERE key = :ID");
        $statement->execute([":ID" => $Key]);




        $returnResult = $statement->rowCount() > 0;
        if ($span) {
            $span->finish();
            \Sentry\SentrySdk::getCurrentHub()->setSpan($parent);
        }

        return $returnResult;
    }

    /**
     * Retrieves a value from the KV Storage using the specified key.
     *
     * @param  string $Key         the key to retrieve from the KV Config from
     * @param  array  $UserOptions
     * @return mixed  The value as string, on failure FALSE
     */
    public static function get(string $Key, bool $noCache = false): mixed
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $parent = \Sentry\SentrySdk::getCurrentHub()->getSpan();
        $span = null;

        $returnResult = false;

        if ($parent) {
            $context = new \Sentry\Tracing\SpanContext();
            $context->setOp(__METHOD__);
            $context->setDescription('Getting Config Key');
            $span = $parent->startChild($context);

            \Sentry\SentrySdk::getCurrentHub()->setSpan($span);
        }


        if (self::$Database_Connection === null) {
            self::initDB();
        }
        Logger::getLogger(__METHOD__)->debug("Getting key $Key");

        if (!Cache::isExpired("Config::get::$Key") && !$noCache) {
            Logger::getLogger(__METHOD__)->debug("Cache::Config::get::$Key");

            return self::evaluateRow(json_decode(Cache::get("Config::get::$Key"), true));
        }

        Logger::getLogger(__METHOD__)->debug("Config::get: SELECT value, type FROM Config WHERE key = $Key");

        $statement = self::$Database_Connection->prepare("SELECT value, type FROM Config WHERE key = :ID");
        $statement->execute([":ID" => $Key]);
        if ($statement->rowCount() > 0) {

            $Result = $statement->fetch(\PDO::FETCH_ASSOC);

            Cache::write("Config::get::$Key", json_encode($Result), time() + self::$TTL);

            $returnResult = self::evaluateRow($Result);
        }


        if ($span) {
            $span->finish();
            \Sentry\SentrySdk::getCurrentHub()->setSpan($parent);
        }

        return $returnResult;
    }

    private static function evaluateRow($Result)
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $parent = \Sentry\SentrySdk::getCurrentHub()->getSpan();
        $span = null;

        if ($parent) {
            $context = new \Sentry\Tracing\SpanContext();
            $context->setOp(__METHOD__);
            $context->setDescription('Evaluating Config Key');
            $span = $parent->startChild($context);

            \Sentry\SentrySdk::getCurrentHub()->setSpan($span);
        }


        $returnResult = match ($Result["type"]) {
            'serialized' => unserialize($Result["value"]),
            'boolean' => (bool) $Result["value"],
            'integer' => (int) $Result["value"],
            'double' => (float) $Result["value"],
            default => $Result["value"],
        };


        if ($span) {
            $span->finish();
            \Sentry\SentrySdk::getCurrentHub()->setSpan($parent);
        }

        return $returnResult;
    }

    /**
     * Get the timestamps of a key.
     *
     * @param  string     $Key The KVStorage key
     * @return bool|array Containing last_changed, created_at
     */
    public static function getTimestamp(string $Key): bool|array
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $returnResult = false;


        $parent = \Sentry\SentrySdk::getCurrentHub()->getSpan();
        $span = null;

        if ($parent) {
            $context = new \Sentry\Tracing\SpanContext();
            $context->setOp(__METHOD__);
            $context->setDescription('Getting Config Key Timestamp');
            $span = $parent->startChild($context);

            \Sentry\SentrySdk::getCurrentHub()->setSpan($span);
        }

        if (self::$Database_Connection === null) {
            self::initDB();
        }
        Logger::getLogger(__METHOD__)->debug("Config::getTimestamp: SELECT last_changed, created_at FROM Config WHERE key = $Key");
        $statement = self::$Database_Connection->prepare("SELECT last_changed, created_at FROM Config WHERE key = :ID");
        $statement->execute([":ID" => $Key]);
        if ($statement->rowCount() > 0) {

            $returnResult = $statement->fetch(\PDO::FETCH_ASSOC);
        }

        if ($span) {
            $span->finish();
            \Sentry\SentrySdk::getCurrentHub()->setSpan($parent);
        }


        return $returnResult;
    }

    /**
     * Create a new KV Storage entry using the specified key and value.
     *
     * @param  string $Key   the key to insert
     * @param  mixed  $Value the value to insert
     * @return bool   TRUE on success, on failure FALSE
     */
    public static function create(string $Key, mixed $Value): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $parent = \Sentry\SentrySdk::getCurrentHub()->getSpan();
        $span = null;
        $returnResult = false;

        if ($parent) {
            $context = new \Sentry\Tracing\SpanContext();
            $context->setOp(__METHOD__);
            $context->setDescription('Creating Config Key');
            $span = $parent->startChild($context);

            \Sentry\SentrySdk::getCurrentHub()->setSpan($span);
        }

        if (self::$Database_Connection === null) {
            self::initDB();
        }
        if (self::exists($Key)) {
            $returnResult = self::set($Key, $Value);
        } else {
            Logger::getLogger(__METHOD__)->debug("Config::create: INSERT INTO Config (key) VALUES ($Key)");

            $statement = self::$Database_Connection->prepare("INSERT INTO Config (key) VALUES (:Key)");
            $statement->execute([":Key" => $Key]);

            $returnResult = self::set($Key, $Value);
        }

        if ($span) {
            $span->finish();
            \Sentry\SentrySdk::getCurrentHub()->setSpan($parent);
        }


        self::deleteCache($Key);

        return $returnResult;
    }

    /**
     * Delete a KV Storage entry using the specified key.
     *
     * @param  string $Key the key to insert
     * @return bool   TRUE on success, on failure FALSE
     */
    public static function delete(string $Key): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        

        $parent = \Sentry\SentrySdk::getCurrentHub()->getSpan();
        $span = null;
        $returnResult = false;

        if ($parent) {
            $context = new \Sentry\Tracing\SpanContext();
            $context->setOp(__METHOD__);
            $context->setDescription('Creating Config Key');
            $span = $parent->startChild($context);

            \Sentry\SentrySdk::getCurrentHub()->setSpan($span);
        }

        if (self::$Database_Connection === null) {
            self::initDB();
        }
        Logger::getLogger(__METHOD__)->debug("Config::delete: DELETE FROM Config WHERE key = $Key");
        $statement = self::$Database_Connection->prepare("DELETE FROM Config WHERE key = :Key");
        self::deleteCache($Key);

        $returnResult = $statement->execute([":Key" => $Key]);

        if ($span) {
            $span->finish();
            \Sentry\SentrySdk::getCurrentHub()->setSpan($parent);
        }

        return $returnResult;
    }

    public static function deleteCache(string $Key): bool
    {

        $parent = \Sentry\SentrySdk::getCurrentHub()->getSpan();
        $span = null;
        $returnResult = false;

        if ($parent) {
            $context = new \Sentry\Tracing\SpanContext();
            $context->setOp(__METHOD__);
            $context->setDescription('Deleting Cache for Config Key');
            $span = $parent->startChild($context);

            \Sentry\SentrySdk::getCurrentHub()->setSpan($span);
        }
        $returnResult = Cache::delete("Config::get::$Key");

        if ($span) {
            $span->finish();
            \Sentry\SentrySdk::getCurrentHub()->setSpan($parent);
        }

        return $returnResult;
    }

    /**
     * Updates a value for a key in the KV Storage.
     *
     * @param  string $Key   Existing key to change the value from
     * @param  mixed  $Value The value to set
     * @return bool   TRUE on success, otherwise FALSE
     */
    public static function set(string $Key, mixed $Value): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        

        $parent = \Sentry\SentrySdk::getCurrentHub()->getSpan();
        $span = null;
        $returnResult = false;

        if ($parent) {
            $context = new \Sentry\Tracing\SpanContext();
            $context->setOp(__METHOD__);
            $context->setDescription('Setting Config Key');
            $span = $parent->startChild($context);

            \Sentry\SentrySdk::getCurrentHub()->setSpan($span);
        }


        if (self::$Database_Connection === null) {
            self::initDB();
        }

        if (!self::exists($Key)) {
            self::create($Key, $Value);
        }

        $Type = match (true) {
            is_null($Value) => "NULL",
            Helper::isSerialized($Value), (is_array($Value) || is_object($Value)) => "serialized",
            default => gettype($Value)
        };

        if ($Type == "boolean") {
            $Value = ($Value ? 1 : 0);
        }

        self::deleteCache($Key);
        Logger::getLogger(__METHOD__)->debug("Config::set: UPDATE Config SET value = $Value, type = $Type WHERE key = $Key");
        $statement = self::$Database_Connection->prepare("UPDATE Config SET value = :val, type = :type WHERE key = :key");
        $statement->execute([":val" => $Value, ":key" => $Key, ":type" => $Type]);

        $returnResult = $statement->rowCount() > 0;


        if ($span) {
            $span->finish();
            \Sentry\SentrySdk::getCurrentHub()->setSpan($parent);
        }

        

        return $returnResult;
    }

    /**
     * Returns all keys and values from the KV Storage.
     *
     * @param  bool  $KV List keys as associative array?
     * @return array
     */
    public static function list(bool $KV = false): array
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        
        

        $parent = \Sentry\SentrySdk::getCurrentHub()->getSpan();
        $span = null;
        $returnResult = false;

        if ($parent) {
            $context = new \Sentry\Tracing\SpanContext();
            $context->setOp(__METHOD__);
            $context->setDescription('Setting Config Key');
            $span = $parent->startChild($context);

            \Sentry\SentrySdk::getCurrentHub()->setSpan($span);
        }
        
        if (self::$Database_Connection === null) {
            self::initDB();
        }

        Logger::getLogger(__METHOD__)->debug("Config::list: SELECT key, value FROM Config");
        $statement = self::$Database_Connection->prepare("SELECT key, value FROM Config");
        $statement->execute();

        if (!$KV) {
            $Array = [];

            foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $Item) {
                $Array[$Item["key"]] = self::get($Item["key"]);
            }

            $returnResult = $Array;
        }else{
           $returnResult = $statement->fetchAll(\PDO::FETCH_ASSOC);
        }

        if ($span) {
            $span->finish();
            \Sentry\SentrySdk::getCurrentHub()->setSpan($parent);
        }

        
        return $returnResult;
    }
}
