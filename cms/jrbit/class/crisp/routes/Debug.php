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

use crisp\api\Build;
use crisp\core;
use crisp\core\Bitmask;
use crisp\core\Logger;
use crisp\core\Themes;
use crisp\core\Environment;
use crisp\core\RESTfulAPI;

/**
 * Used internally, theme loader.
 */
class Debug
{

    const BASE_COMMAND = "crisp --no-interaction --no-ansi";
    const COMMANDS = [
        "reload-theme" => ["{{base-command}} crisp:theme --uninstall", "{{base-command}} crisp:theme --install"],
        "reload-kv" => ["{{base-command}} crisp:theme:storage --install"],
        "reload-kv-force" => ["{{base-command}} crisp:theme:storage --install --force"],
        "execute-boot-files" => ["{{base-command}} theme --boot"],
        "clear-cache" => ["{{base-command}} crisp:cache:clear"],
        "post-install" => ["{{base-command}} crisp:post-install"],
        "migrate-theme" => ["{{base-command}} crisp:migration:run --no-core"],
        "migrate-crisp" => ["{{base-command}} crisp:migration:run --no-theme"],
        "delete-license" => ["{{base-command}} crisp:license:delete -f"],
        "delete-key" => ["{{base-command}} crisp:license:delete:key -f"],
        "delete-issuer-public" => ["{{base-command}} crisp:license:issuer:delete:public"],
        "delete-issuer-private" => ["{{base-command}} crisp:license:issuer:delete:private"],
        "generate-development-license" => ["{{base-command}} crisp:license:generate:development"],
        "whoami" => ["whoami"],
        "check-permissions" => ["{{base-command}} crisp:check-permissions"],
        "pull-from-license-server" => ["{{base-command}} crisp:license:pull"],
    ];

    public static function generateCommand(string $command, string $loglevel = null): string {
        
        return strtr(sprintf("%s 2>&1", $command), [
            "{{base-command}}" => sprintf(self::BASE_COMMAND, $loglevel ?? "info")
        ]);
    }

    public function preRender(): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        if (Build::getEnvironment() !== Environment::DEVELOPMENT) {
            echo Themes::render("errors/notfound.twig", "themes/basic/templates");
            exit;
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {

            $output = [];
            $commands = [];

            if (!array_key_exists($_POST["action"], self::COMMANDS)) {
                echo "Command not found";
                exit;
            }

            // Execute command in array
            foreach (self::COMMANDS[$_POST["action"]] as $key => $command) {
                $generatedCommand = self::generateCommand($command, $_POST["loglevel"] ?? null);
                $output = array_merge($output, array_filter(explode(PHP_EOL, shell_exec($generatedCommand))));
                $commands[] = $generatedCommand;
            }

            Themes::clearCache();

            RESTfulAPI::response(Bitmask::REQUEST_SUCCESS, "OK", [
                "output" => array_reverse($output),
                "commands" => $commands,
            ]);
            exit;
        }

        echo Themes::render("views/debug.twig", "themes/basic/templates");
    }
}
