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

/**
 * Used internally, theme loader
 *
 */
class Router
{

    public static function addFun(string $route, RouteType $routeType, mixed $function): void
    {

        $collector = self::get($routeType)->any($route, $function);
        $GLOBALS["Crisp_Router_" . $routeType->value] = $collector;
    }

    public static function add(string $route, RouteType $routeType, mixed $class): void
    {
        $collector = self::get($routeType)->any([$route, $class], [$class, $routeType == RouteType::PUBLIC ? "preRender" : "execute"]);
        $GLOBALS["Crisp_Router_" . $routeType->value] = $collector;
    }

    public static function registerInteralRoutes(): void
    {
        self::add("_debug_oscp", RouteType::PUBLIC, \crisp\routes\DebugOCSP::class);
        self::add("_debug", RouteType::PUBLIC, \crisp\routes\Debug::class);
        self::add("_license", RouteType::PUBLIC, \crisp\routes\License::class);
        self::add("_proxy", RouteType::PUBLIC, \crisp\routes\Proxy::class);
        self::add("_version", RouteType::PUBLIC, \crisp\routes\Version::class);
    }


    public static function get(RouteType $routeType): RouteCollector
    {
        return $GLOBALS["Crisp_Router_" . $routeType->value];
    }

    public static function register(): void
    {
        foreach (RouteType::cases() as $RouteType) {
            $GLOBALS["Crisp_Router_" . $RouteType->value] = new RouteCollector();
        }

        self::registerInteralRoutes();
    }
}