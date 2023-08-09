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

use Phroute\Phroute\RouteCollector;

/**
 * Used internally, theme loader
 *
 */
class Router
{

    public static function addFun(string $route, mixed $function): void
    {

        $collector = self::get()->any($route, $function);
        $GLOBALS["Crisp_Router"] = $collector;
    }

    public static function add(string $route, mixed $class): void
    {
        $collector = self::get()->any([$route, $class], [$class, "preRender"]);
        $GLOBALS["Crisp_Router"] = $collector;
    }


    public static function get(): RouteCollector
    {
        return $GLOBALS["Crisp_Router"];
    }

    public static function register(): void
    {
        $GLOBALS["Crisp_Router"] = new RouteCollector();
    }

}
