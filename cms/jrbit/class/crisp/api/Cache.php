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
use crisp\core\Logger;

/**
 * Interact with the cache.
 */
class Cache
{
    /**
     * Get the cache directory for a given key.
     *
     * @param  string $Key The key to get the cache directory for
     * @return string The cache directory
     */
    public static function getCrispCacheDir(string $Key): string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        return sprintf("%s/crisp/%s", core::CACHE_DIR, self::calculateCacheDir(self::getHash($Key)));
    }

    /**
     * Get the cache file for a given key.
     *
     * @param  string $Key The key to get the cache file for
     * @return string The cache file
     */
    public static function getCrispCacheFile(string $Key): string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        return sprintf("%s/%s.cache", self::getCrispCacheDir($Key), self::getHash($Key));
    }

    /**
     * Calculate the cache directory for a given level.
     *
     * @param  string $name
     * @param  int    $level
     * @return string The cache directory first letter
     */
    private static function calculateSingleLevel(string $name, int $level): string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        return $name[$level - 1];
    }

    /**
     * Calculate the cache directory for a given key.
     *
     * @param  string $name The key to calculate the cache directory for
     * @return string The cache directory
     */
    private static function calculateCacheDir(string $name): string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        return sprintf(
            "%s/%s/%s/%s",
            self::calculateSingleLevel($name, 1),
            self::calculateSingleLevel($name, 2),
            self::calculateSingleLevel($name, 3),
            self::calculateSingleLevel($name, 4)
        );
    }

    /**
     * Get the expiry date of a given cache key.
     *
     * @param  string    $key The key to get the expiry date for
     * @return int|false The expiry date of the cache
     */
    public static function getExpiryDate(string $key): int|false
    {

        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        if (!self::isCached($key)) {
            return false;
        }

        return json_decode(file_get_contents(self::getCrispCacheFile($key)))->expires;
    }

    /**
     * Check if a given key is cached.
     *
     * @param  string $key The key to check
     * @return bool   True if the key is cached
     */
    private static function isCached(string $key): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug(sprintf("Checking cache %s (%s)", self::getCrispCacheFile($key), $key));

        return file_exists(self::getCrispCacheFile($key));
    }

    /**
     * Create a directory for a given key.
     *
     * @param  string $name The key to create the directory for
     * @return bool   True if the directory was created successfully
     */
    private static function createDir(string $key): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug(sprintf("START Creating cache directories %s (%s)", self::getCrispCacheDir($key), $key));
        $created = mkdir(self::getCrispCacheDir($key), recursive: true);

        if ($created) {
            Logger::getLogger(__METHOD__)->debug(sprintf("DONE Creating cache directories %s (%s)", self::getCrispCacheDir($key), $key));

            return true;
        }
        Logger::getLogger(__METHOD__)->error(sprintf("FAIL Creating cache directories %s (%s)", self::getCrispCacheDir($key), $key));

        return false;
    }

    /**
     * Get the hash of given data.
     *
     * @param  string $data The data to hash
     * @return string The hash of the data
     */
    public static function getHash(string $data): string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        return hash("sha512", $data);
    }

    /**
     * Generate a cache file.
     *
     * @param int    $expires The expiry date of the cache
     * @param string $data    The data to write to the cache
     */
    private static function generateFile(int $expires, string $data)
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        return json_encode(["expires" => $expires, "data" => base64_encode($data)]);
    }

    /**
     * Write data to the cache.
     *
     * @param  string $key     The key to write to
     * @param  string $data    The data to write
     * @param  int    $expires The expiry date of the cache
     * @return bool   True if the cache was written successfully
     */
    public static function write(string $key, string $data, int $expires): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        self::createDir($key);

        Logger::getLogger(__METHOD__)->debug("START Writing Cache " . self::getCrispCacheFile($key));
        $bytes = file_put_contents(self::getCrispCacheFile($key), self::generateFile($expires, $data));

        if ($bytes !== false) {
            Logger::getLogger(__METHOD__)->debug("DONE Writing Cache " . self::getCrispCacheFile($key));

            return true;
        }
        Logger::getLogger(__METHOD__)->error("FAIL Writing Cache ", ["cacheFile" =>self::getCrispCacheFile($key)]);

        return false;
    }

    /**
     * Check if a given cache is expired.
     *
     * @param  string $key The cache to check
     * @return bool   True if the cache is expired
     */
    public static function isExpired(string $key): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $CacheFile = self::getCrispCacheFile($key);
        if (!self::isCached($key)) {
            Logger::getLogger(__METHOD__)->debug("Cache $CacheFile does not exist");

            return true;
        }
        $timestamp = json_decode(file_get_contents($CacheFile))->expires;

        if (time() > $timestamp) {
            Logger::getLogger(__METHOD__)->debug("Cache $CacheFile has expired");

            return true;
        }

        Logger::getLogger(__METHOD__)->debug("Cache $CacheFile did not expire");

        return false;
    }

    /**
     * Clear the cache.
     *
     * @param [type] $dir The directory to clear
     * @return bool True if the cache was cleared successfully
     */
    public static function clear(string $dir = core::CACHE_DIR): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        if (!file_exists($dir)) {
            mkdir($dir);
        }
        chown($dir, 33);
        chgrp($dir, 33);

        $it = new \RecursiveDirectoryIterator(realpath($dir), \FilesystemIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator(
            $it,
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        if ($dir !== "/tmp/symfony-cache") {
            return self::clear("/tmp/symfony-cache");
        }

        return true;
    }

    /**
     * Delete a given key from the cache.
     *
     * @param  string $key The key to delete
     * @return bool   True if the key was deleted successfully
     */
    public static function delete(string $key): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        if (!Cache::isCached($key)) {
            return false;
        }

        return unlink(self::getCrispCacheFile($key));
    }

    /**
     * Get the data of a given key.
     *
     * @param  string       $key The key to get the data for
     * @return string|false The data of the key
     */
    public static function get(string $key): string|false
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        if (!Cache::isCached($key)) {
            return false;
        }

        return base64_decode(json_decode(file_get_contents(self::getCrispCacheFile($key)))->data);
    }
}
