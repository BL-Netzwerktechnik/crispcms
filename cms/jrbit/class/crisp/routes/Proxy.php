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
use crisp\core\RESTfulAPI;
use crisp\models\ThemeAPI;
use finfo;
use Twig\Environment;

/**
 * Used internally, theme loader
 *
 */
class Proxy  {


    public function preRender(): void
    {
        $ttlIncrease = ($_GET["cache"]) ?? 300;
        $Url = urldecode($_GET["url"]);


        if(empty($Url)){
            RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "URL Cannot be empty");
            exit;
        }

        if(!str_starts_with($Url, "https://") && !str_starts_with($Url, "http://")){
            RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "URL must be using the http(s) protocol!", HTTP: 400);
            exit;
        }

        $ttl = time() + $ttlIncrease;

        $paramArray = [
            "cache "=> $ttlIncrease,
            "ttl" => $ttl,
            "url" => $Url
        ];

        if($ttlIncrease <= 1){
            RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "TTL cannot be smaller than or equal 1", $paramArray);
            exit;
        }

        if(Cache::isExpired($Url)){

            $data = file_get_contents($Url);

            $content_type = null;


            $bytes = strlen($data);

            if($bytes > 5 * pow(1024, 2)){
                RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "Proxy cannot serve files larger than 5 megabytes! Received ". number_format($bytes / 1024, 2). "kb", $paramArray);
                exit;
            }
            if(isset($_GET["type"])) {
                $content_type = Helper::generateUpToDateMimeArray()[$_GET["type"]];
            }

            if(!$content_type) {
                if (is_array($http_response_header) && preg_match_all("/^content-type\s*:\s*(.*)$/mi", implode("\n", $http_response_header), $matches)) {
                    $content_type = end($matches[1]);
                } else {
                    $finfo = new finfo(FILEINFO_MIME);
                    $content_type = $finfo->buffer($data);
                    if($content_type === "text/plain"){
                        $content_type = (Helper::detectMimetype($_GET["url"]) ?? $content_type);
                    }
                }
            }

            $cacheData = [
                "content" => $data,
                "mimetype" => $content_type
             ];



            Cache::write($Url, serialize($cacheData),  $ttl);

            header("Content-Type: ". $content_type);
            header("X-Cached: no");
            echo $data;
            exit;
        }

        $cache = unserialize(Cache::get($Url));
        header("Content-Type: ". $cache["mimetype"]);
        header("X-Cached: yes");

        echo $cache["content"];
        exit;




    }
}
