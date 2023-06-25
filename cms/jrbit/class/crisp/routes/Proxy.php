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
use crisp\core\Bitmask;
use crisp\core\RESTfulAPI;
use crisp\models\ThemeAPI;
use finfo;
use Twig\Environment;

/**
 * Used internally, theme loader
 *
 */
class Proxy extends ThemeAPI  {


    public function execute(string $Interface, Environment $TwigTheme): void
    {
        $ttlIncrease = ($_GET["cache"]) ?? 300;

        if(empty($_GET["url"])){
            RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "URL Cannot be empty");
            exit;
        }

        if(!str_starts_with($_GET["url"], "https://") && !str_starts_with($_GET["url"], "http://")){
            RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "URL must be using the http(s) protocol!", HTTP: 400);
            exit;
        }

        $ttl = time() + $ttlIncrease;
        $Url = urldecode($_GET["url"]);

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

            $data = file_get_contents(urldecode($_GET["url"]));



            $bytes = strlen($data);

            if($bytes > 5 * pow(1024, 2)){
                RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "Proxy cannot serve files larger than 5 megabytes! Received ". number_format($bytes / 1024, 2). "kb", $paramArray);
                exit;
            }
            $headers = implode("\n", $http_response_header);
            if (preg_match_all("/^content-type\s*:\s*(.*)$/mi", $headers, $matches)) {
                $content_type = end($matches[1]);
            }else{

                $finfo = new finfo(FILEINFO_MIME);

                $content_type = $finfo->buffer($data);
            }

            $cacheData = [
                "content" => $data,
                "mimetype" => $content_type
             ];



            Cache::write($Url, serialize($cacheData),  $ttl);

            header("Content-Type: ". $mimetype);
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
