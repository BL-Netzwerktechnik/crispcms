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

namespace crisp\core;

use crisp\types\RouteType;
use Phroute\Phroute\Dispatcher;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;
use Twig\Environment;

/**
 * Used internally, plugin loader.
 */
class RESTfulAPI
{
    /**
     * @param Environment $ThemeLoader
     * @param string      $Interface
     * @param string      $_QUERY
     */
    public function __construct()
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        try {

            HookFile::preExecute();
            $dispatcher = new Dispatcher(Router::get(RouteType::API)->getData());
            echo $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
            HookFile::postExecute();
        } catch (HttpRouteNotFoundException $ex) {

            $_NFFile = Themes::getThemeMetadata()->api->pages->notFound;
            $_NFClass = substr($_NFFile, 0, -4);

            if (!$_NFFile) {
                self::response(Bitmask::GENERIC_ERROR->value, 'API Not Found File has not been configured. Please consult the Docs', []);
                exit;
            }

            if (file_exists(Themes::getThemeDirectory() . "/includes/api/$_NFFile")) {
                require Themes::getThemeDirectory() . "/includes/api/$_NFFile";

                $PageClass = null;

                if (class_exists($_NFClass, false)) {
                    $PageClass = new $_NFClass();
                }

                if ($PageClass !== null && !method_exists($PageClass, 'execute')) {
                    throw new \Exception("execute is missing in api/$_NFFile");
                }

                $PageClass->execute($Interface);
                exit;
            }

            self::response(Bitmask::INTERFACE_NOT_FOUND->value, 'REST Interface not found!', []);
        }
        exit;
    }

    public static function getRequestMethod(): string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        return $_SERVER["REQUEST_METHOD"];
    }

    public static function isRequestContentType(string $contenttype = "application/json"): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        return getallheaders()["Content-Type"] == $contenttype;
    }

    public static function BodyParameterExists(string $key): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $data = json_decode(file_get_contents('php://input'), true);

        return array_key_exists($key, $data);
    }

    public static function getBodyParameter(string $key): mixed
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $data = json_decode(file_get_contents('php://input'), true);

        return $data[$key] ?? null;
    }

    public static function getBody(): mixed
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $data = json_decode(file_get_contents('php://input'), true);

        return $data ?? null;
    }

    /**
     * Send a JSON response.
     *
     * @param  int|null       $Errors     Error array or false
     * @param  string         $message    A message to send
     * @param  array          $Parameters Some response parameters
     * @param  constant|null  $Flags      JSON_ENCODE constants
     * @param  int            $HTTP
     * @throws \JsonException
     */
    public static function response(Bitmask|int $Errors = null, string $message, array $Parameters = [], mixed $Flags = null, int $HTTP = 200)
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        header("Content-Type: application/json");
        http_response_code($HTTP);

        if ($Errors instanceof Bitmask) {
            $Error = $Errors->value;
        } else {
            $Error = $Errors;
        }

        echo json_encode(["error" => $Error ?? Bitmask::NONE->value, "message" => $message, "parameters" => $Parameters], JSON_THROW_ON_ERROR | $Flags);
    }
}
