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

namespace crisp\routes;

use crisp\api\Cache;
use crisp\api\Helper;
use crisp\core\Bitmask;
use crisp\core\Logger;
use crisp\core\RESTfulAPI;

/**
 * Used internally, theme loader.
 */
class Proxy
{

    private const BLACKLISTED_MIMETYPES = [
        "text/html",
        "application/xhtml+xml",
    ];

    public static function isSafePublicUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) {
            return false;
        }

        $host = $parts['host'];

        // Resolve host to IPs
        $ips = gethostbynamel($host);
        if ($ips === false || empty($ips)) {
            return false; // cannot resolve
        }

        foreach ($ips as $ip) {
            if (
                filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
                === false
            ) {
                return false;
            }
        }

        return true; // all resolved IPs are public
    }

    public function preRender(): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $ttlIncrease = ($_GET["cache"]) ?? 300;
        $Url = urldecode($_GET["url"]);

        
        $ttl = time() + $ttlIncrease;

        $paramArray = [
            "cache " => $ttlIncrease,
            "ttl" => $ttl,
            "url" => $Url,
        ];

        if (empty($Url)) {
            RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "URL Cannot be empty");
            exit;
        }

        if (!str_starts_with($Url, "https://") && !str_starts_with($Url, "http://")) {
            RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "URL must be using the http(s) protocol!", HTTP: 400);
            exit;
        }

        if (!self::isSafePublicUrl($Url)) {
            RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "Failed validating Proxy Resource!", $paramArray, HTTP: 400);
            exit;
        }


        if ($ttlIncrease <= 1) {
            RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "TTL cannot be smaller than or equal 1", $paramArray);
            exit;
        }

        if (Cache::isExpired($Url)) {

            $ch = curl_init($Url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,   // return content instead of outputting it
                CURLOPT_FOLLOWLOCATION => false,   // follow redirects (like file_get_contents does)
                CURLOPT_TIMEOUT        => 10,     // safety timeout
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_HEADER         => true,   // <-- include headers in output
            ]);

            $response = curl_exec($ch);

            if ($response === false) {
                $error = curl_error($ch);
                curl_close($ch);
                Logger::getLogger(__METHOD__)->error("cURL error: " . $error, debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
                RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "Failed retrieving remote resource", $paramArray);
                exit;
            }

            // Split headers and body
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headersRaw = substr($response, 0, $headerSize);
            $data       = substr($response, $headerSize);

            curl_close($ch);


            $content_type = null;

            $bytes = strlen($data);

            if ($bytes > 5 * pow(1024, 2)) {
                RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "Proxy cannot serve files larger than 5 megabytes! Received " . number_format($bytes / 1024, 2) . "kb", $paramArray);
                exit;
            }
            if (isset($_GET["type"])) {
                $content_type = Helper::generateUpToDateMimeArray()[$_GET["type"]];
            }

            // Parse Content-Type header
            $content_type = null;
            if (preg_match_all("/^content-type\s*:\s*(.+)$/mi", $headersRaw, $matches)) {
                $content_type = end($matches[1]);
            }

            if (!$content_type) {
                $finfo = new \finfo(FILEINFO_MIME);
                $content_type = $finfo->buffer($data);

                if ($content_type === "text/plain") {
                    $content_type = (Helper::detectMimetype($_GET["url"]) ?? $content_type);
                }
            }



            if (in_array(explode(";", $content_type)[0], self::BLACKLISTED_MIMETYPES, true)) {
                RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "Proxy cannot serve blacklisted mimetypes!", $paramArray);
                exit;
            }



            $cacheData = [
                "content" => $data,
                "mimetype" => $content_type,
            ];

            Cache::write($Url, serialize($cacheData), $ttl);

            header("Content-Type: " . $content_type);
            header("X-Cached: no");
            echo $data;
            exit;
        }

        $cache = unserialize(Cache::get($Url));
        header("Content-Type: " . $cache["mimetype"]);
        header("X-Cached: yes");

        echo $cache["content"];
        exit;
    }
}
