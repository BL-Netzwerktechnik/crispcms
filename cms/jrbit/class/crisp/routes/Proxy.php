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


        $Url = urldecode($_GET["url"]);
        if(Cache::isExpired($Url)){

            $data = file_get_contents(urldecode($_GET["url"]));

            $bytes = strlen($data);

            if($bytes > 5 * pow(1024, 2)){
                echo RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "Proxy cannot serve files larger than 5 megabytes! Received ". number_format($bytes / 1024, 2). "kb");
                exit;
            }

            $finfo = new finfo(FILEINFO_MIME);

            $mimetype = $finfo->buffer($data);

            $cacheData = [
                "content" => $data,
                "mimetype" => $mimetype
             ];

            $ttl = time() + ((int)$_GET["cache"]) ?? 300;

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
