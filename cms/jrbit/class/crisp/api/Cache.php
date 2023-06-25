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

use crisp\core;
use crisp\core\LogTypes;
use crisp\core\Postgres;
use PDO;
use function serialize;
use function unserialize;
use crisp\core\Bitmask;
use crisp\core\RESTfulAPI;

/**
 * Interact with the key/value storage of the server
 */
class Cache
{


    private static function calculateSingleLevel(string $name, int $level): string
    {
        return $name[$level - 1];
    }
    private static function calculateCacheDir(string $name): string
    {
        return sprintf("%s/%s/%s/%s",
            self::calculateSingleLevel($name, 1),
            self::calculateSingleLevel($name, 2),
            self::calculateSingleLevel($name, 3),
            self::calculateSingleLevel($name, 4)
        );
    }

    public static function getExpiryDate(string $key): int|false {

        if(!self::isCached($key)){
            return false;
        }
        $Hash = self::getHash($key);

        $Dir = self::calculateCacheDir($Hash);


        return json_decode(file_get_contents(core::CACHE_DIR . "/crisp/". $Dir . "/" . $Hash . ".cache"))->expires;
    }

    private static function isCached(string $key): bool
    {

        $Hash = self::getHash($key);
        Helper::Log(LogTypes::DEBUG, "Checking cache ". core::CACHE_DIR . "/crisp/". self::calculateCacheDir($Hash). "/$Hash.cache");
        return file_exists(core::CACHE_DIR . "/crisp/". self::calculateCacheDir($Hash). "/$Hash.cache");

    }

    private static function createDir(string $name): bool
    {
        return mkdir(core::CACHE_DIR . "/crisp/$name", recursive: true);
    }

    public static function getHash(string $data): string
    {
        return hash("sha512", $data);
    }

    private static function generateFile(int $expires, string $data){
        return json_encode(["expires" => $expires, "data" => base64_encode($data)]);
    }

    public static function write(string $key, string $data, int $expires): bool
    {
        $Hash = self::getHash($key);

        $Dir = self::calculateCacheDir($Hash);

        self::createDir($Dir);

        Helper::Log(LogTypes::DEBUG, "Writing Cache ". core::CACHE_DIR . "/crisp/". $Dir . "/" . $Hash . ".cache");
        return file_put_contents(core::CACHE_DIR . "/crisp/". $Dir . "/" . $Hash . ".cache", self::generateFile($expires, $data));


    }

    public static function isExpired(string $key): bool
    {

        $Hash = self::getHash($key);

        $Dir = self::calculateCacheDir($Hash);

        if(!self::isCached($key)) return true;

        $timestamp = json_decode(file_get_contents(core::CACHE_DIR . "/crisp/". $Dir . "/" . $Hash . ".cache"))->expires;

        return $timestamp < time();
    }

    public static function delete(string $key): bool {
        $Hash = self::getHash($key);
        $Dir = self::calculateCacheDir($Hash);

        if(!Cache::isCached($key)){
            return false;
        }

        return unlink(core::CACHE_DIR . "/crisp/$Dir/$Hash.cache");
    }

    public static function get(string $key): string|false
    {
        $Hash = self::getHash($key);

        $Dir = self::calculateCacheDir($Hash);

        if(!Cache::isCached($key)){
            return false;
        }

        return base64_decode(json_decode(file_get_contents(core::CACHE_DIR . "/crisp/". $Dir . "/" . $Hash . ".cache"))->data);


    }

}
