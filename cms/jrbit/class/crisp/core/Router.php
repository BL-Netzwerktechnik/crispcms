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
use Phroute\Phroute\RouteCollector;
use Phroute\Phroute\Route;

/**
 * Used internally, theme loader.
 */
class Router
{
    public static function addFun(string $route, RouteType $routeType, mixed $function, string $method = Route::ANY): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $collector = self::get($routeType)->addRoute($method, $route, $function);
        $GLOBALS["Crisp_Router_" . $routeType->value] = $collector;
    }

    public static function add(string $route, RouteType $routeType, mixed $class, string $callable = null, string $name = null, string $method = Route::ANY): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $callable = $callable ?? ($routeType == RouteType::PUBLIC ? "preRender" : "execute");


        $collector = self::get($routeType)->addRoute($method, [$route, $name ?? $class], [$class, $callable]);
        $GLOBALS["Crisp_Router_" . $routeType->value] = $collector;
    }

    public static function reverse(string $name, RouteType $routeType, array $params = []): string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        return self::get($routeType)->route($name, $params);
    }

    public static function registerInteralRoutes(): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        self::add("_/debug/oscp", RouteType::PUBLIC, \crisp\routes\DebugOCSP::class, name: "debug.oscp");
        self::add("_/debug/license/server/{status}?/{licenseKey}?", RouteType::PUBLIC, \crisp\routes\DebugLicenseServer::class, name: "debug.license.server");
        self::add("_/debug", RouteType::PUBLIC, \crisp\routes\Debug::class, name: "debug");
        self::add("_/license", RouteType::PUBLIC, \crisp\routes\License::class, name: "license");
        self::add("_/proxy", RouteType::PUBLIC, \crisp\routes\Proxy::class, name: "proxy");
        self::add("_/version", RouteType::PUBLIC, \crisp\routes\Version::class, name: "version");
    }

    public static function get(RouteType $routeType): RouteCollector
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        return $GLOBALS["Crisp_Router_" . $routeType->value];
    }

    public static function register(): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        foreach (RouteType::cases() as $RouteType) {
            $GLOBALS["Crisp_Router_" . $RouteType->value] = new RouteCollector();
        }

        self::registerInteralRoutes();
    }
}
