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
use FilesystemIterator;
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function serialize;
use function unserialize;
use crisp\core\Bitmask;
use crisp\core\RESTfulAPI;

/**
 * Interact with the key/value storage of the server
 */
class Cache
{


    public static function getCrispCacheDir(string $Key): string {
        return sprintf("%s/crisp/%s", core::CACHE_DIR, self::calculateCacheDir(self::getHash($Key)));
    }
    public static function getCrispCacheFile(string $Key): string {
        return sprintf("%s/%s.cache", self::getCrispCacheDir($Key), self::getHash($Key));
    }

    /**
     * Calculate the cache directory for a given level
     * @param string $name
     * @param integer $level
     * @return string
     */
    private static function calculateSingleLevel(string $name, int $level): string
    {
        return $name[$level - 1];
    }

    /**
     * Calculate the cache directory for a given key
     *
     * @param string $name
     * @return string
     */
    private static function calculateCacheDir(string $name): string
    {
        return sprintf("%s/%s/%s/%s",
            self::calculateSingleLevel($name, 1),
            self::calculateSingleLevel($name, 2),
            self::calculateSingleLevel($name, 3),
            self::calculateSingleLevel($name, 4)
        );
    }

    /**
     * Get the expiry date of a given cache key
     *
     * @param string $key
     * @return integer|false
     */
    public static function getExpiryDate(string $key): int|false {

        if(!self::isCached($key)){
            return false;
        }


        return json_decode(file_get_contents(self::getCrispCacheFile($key)))->expires;
    }

    /**
     * Check if a given key is cached
     *
     * @param string $key
     * @return boolean
     */
    private static function isCached(string $key): bool
    {
        Helper::Log(LogTypes::DEBUG, sprintf("Checking cache %s (%s)", self::getCrispCacheFile($key), $key));
        return file_exists(self::getCrispCacheFile($key));
    }

    /**
     * Create a directory for a given key
     *
     * @param string $name
     * @return boolean
     */
    private static function createDir(string $key): bool
    {
        Helper::Log(LogTypes::DEBUG, sprintf("Creating cache directories %s (%s)", self::getCrispCacheDir($key), $key));
        return mkdir(self::getCrispCacheDir($key), recursive: true);
    }

    /**
     * Get the hash of given data
     *
     * @param string $data
     * @return string
     */
    public static function getHash(string $data): string
    {
        return hash("sha512", $data);
    }

    /**
     * Generate a cache file
     *
     * @param integer $expires
     * @param string $data
     * @return void
     */
    private static function generateFile(int $expires, string $data){
        return json_encode(["expires" => $expires, "data" => base64_encode($data)]);
    }

    /**
     * Write data to the cache
     *
     * @param string $key The key to write to
     * @param string $data The data to write
     * @param integer $expires The expiry date of the cache
     * @return boolean True if the cache was written successfully
     */
    public static function write(string $key, string $data, int $expires): bool
    {
        self::createDir($key);

        Helper::Log(LogTypes::DEBUG, "Writing Cache ". self::getCrispCacheFile($key));
        return file_put_contents(self::getCrispCacheFile($key), self::generateFile($expires, $data));


    }

    /**
     * Check if a given cache is expired
     *
     * @param string $key The cache to check
     * @return boolean True if the cache is expired
     */
    public static function isExpired(string $key): bool
    {

        $CacheFile = self::getCrispCacheFile($key);
        if(!self::isCached($key)) {
            Helper::Log(LogTypes::DEBUG, "Cache $CacheFile does not exist");
            return true;
        }
        $timestamp = json_decode(file_get_contents($CacheFile))->expires;

        if(time() > $timestamp){
            Helper::Log(LogTypes::DEBUG, "Cache $CacheFile has expired");
            return true;
        }

        Helper::Log(LogTypes::DEBUG, "Cache $CacheFile did not expire");
        return false;
    }

    /**
     * Clear the cache
     *
     * @param [type] $dir
     * @return boolean
     */
    public static function clear(string $dir = core::CACHE_DIR): bool {

        if(!file_exists($dir)){
            mkdir($dir);
        }
        chown($dir, 33);
        chgrp($dir, 33);


        $it = new RecursiveDirectoryIterator(realpath($dir), FilesystemIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it,
            RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        if($dir !== "/tmp/symfony-cache"){
            return self::clear("/tmp/symfony-cache");
        }
        return true;
    }

    /**
     * Delete a given cache
     *
     * @param string $key
     * @return boolean
     */
    public static function delete(string $key): bool {
        if(!Cache::isCached($key)){
            return false;
        }

        return unlink(self::getCrispCacheFile($key));
    }

    /**
     * Get the data of a given cache
     *
     * @param string $key
     * @return string|false
     */
    public static function get(string $key): string|false
    {
        if(!Cache::isCached($key)){
            return false;
        }

        return base64_decode(json_decode(file_get_contents(self::getCrispCacheFile($key)))->data);


    }

}
