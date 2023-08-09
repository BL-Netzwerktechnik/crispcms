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
use crisp\core;
use crisp\core\Bitmask;
use crisp\core\Themes;
use crisp\core\RESTfulAPI;
use crisp\models\ThemeAPI;
use finfo;
use Twig\Environment;

/**
 * Used internally, theme loader
 *
 */
class Debug  {


    public function preRender(): void
    {

        if(ENVIRONMENT !== 'development'){
            echo file_get_contents(core::THEME_BASE_DIR . "/basic/not_found.html");
            exit;
        }

        if($_SERVER["REQUEST_METHOD"] === "POST"){

            $output = [];

            switch($_POST["action"]){
                case "reload_theme":
                    $output = shell_exec("crisp-cli --no-colors theme -u 2>&1 && crisp-cli --no-colors theme -i 2>&1");
                    break;
                case "reload_kv":
                    $output = shell_exec("crisp-cli --no-colors storage -i 2>&1");
                    break;
                case "reload_kv_force":
                    $output = shell_exec("crisp-cli --no-colors storage -i -f 2>&1");
                    break;
                case "execute_boot":
                    $output = shell_exec("crisp-cli --no-colors theme -b 2>&1");
                    break;
                case "clear_cache":
                    $output = shell_exec("crisp-cli --no-colors theme -c 2>&1");
                    break;
                case "postinstall":
                    $output = shell_exec("crisp-cli --no-colors crisp -p 2>&1");
                    break;

                case "migrate_theme":
                    $output = shell_exec("crisp-cli --no-colors theme -m 2>&1");
                    break;

                case "migrate_crisp":
                    $output = shell_exec("crisp-cli --no-colors crisp -m 2>&1");
                    break;

                case "deletelicense":
                    $output = shell_exec("crisp-cli --no-colors license -d 2>&1");
                    break;
                case "deleteissuerpublic":
                    $output = shell_exec("crisp-cli --no-colors license --delete-issuer 2>&1");
                    break;

                case "deleteissuerprivate":
                    $output = shell_exec("crisp-cli --no-colors license --delete-issuer-private 2>&1");
                    break;


            }

            echo implode("<br>", array_reverse(explode(PHP_EOL, $output)));
            exit;
        }

        echo Themes::render("views/debug.twig");
    }
}
