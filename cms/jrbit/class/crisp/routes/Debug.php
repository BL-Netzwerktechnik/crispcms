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

use crisp\core;
use crisp\core\Logger;
use crisp\core\Themes;

/**
 * Used internally, theme loader.
 */
class Debug
{

    const BASE_COMMAND = "crisp --no-colors %s 2>&1";

    public function preRender(): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        if (ENVIRONMENT !== 'development') {
            echo Themes::render("errors/notfound.twig", "themes/basic/templates");
            exit;
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {

            $output = [];

            switch ($_POST["action"]) {
                case "reload_theme":
                    $output = shell_exec(sprintf(self::BASE_COMMAND, "theme --uninstall"));
                    $output .= shell_exec(sprintf(self::BASE_COMMAND, "theme --install"));
                    break;
                case "reload_kv":
                    $output = shell_exec(sprintf(self::BASE_COMMAND, "storage --install"));
                    shell_exec(sprintf(self::BASE_COMMAND, "theme --clear-cache"));
                    break;
                case "reload_kv_force":
                    $output = shell_exec(sprintf(self::BASE_COMMAND, "storage --install --force"));
                    shell_exec(sprintf(self::BASE_COMMAND, "theme --clear-cache"));
                    break;
                case "execute_boot":
                    $output = shell_exec(sprintf(self::BASE_COMMAND, "theme --boot"));
                    shell_exec(sprintf(self::BASE_COMMAND, "theme --clear-cache"));
                    break;
                case "clear_cache":
                    $output = shell_exec(sprintf(self::BASE_COMMAND, "theme --clear-cache"));
                    break;
                case "postinstall":
                    $output = shell_exec(sprintf(self::BASE_COMMAND, "--post-install"));
                    shell_exec(sprintf(self::BASE_COMMAND, "theme --clear-cache"));
                    break;
                case "migrate_theme":
                    $output = shell_exec(sprintf(self::BASE_COMMAND, "theme --migrate"));
                    shell_exec(sprintf(self::BASE_COMMAND, "theme --clear-cache"));
                    break;

                case "migrate_crisp":
                    $output = shell_exec(sprintf(self::BASE_COMMAND, "--migrate"));
                    $output .= shell_exec(sprintf(self::BASE_COMMAND, "theme --clear-cache"));
                    break;

                case "deletelicense":
                    $output = shell_exec(sprintf(self::BASE_COMMAND, "license --delete"));
                    break;
                case "deleteissuerpublic":
                    $output = shell_exec(sprintf(self::BASE_COMMAND, "license --delete-issuer"));
                    break;

                case "deleteissuerprivate":
                    $output = shell_exec(sprintf(self::BASE_COMMAND, "license --delete-issuer-private"));
                    break;

            }

            echo implode("<br>", array_reverse(explode(PHP_EOL, $output)));
            exit;
        }

        echo Themes::render("views/debug.twig", "themes/basic/templates");
    }
}
