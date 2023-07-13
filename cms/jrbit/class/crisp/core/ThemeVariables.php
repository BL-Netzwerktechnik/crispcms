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
/**
 * Used internally, theme loader
 *
 */
class ThemeVariables
{

    public static function set(string $key, mixed $value): void
    {
        $GLOBALS["Crisp_ThemeVariables"][$key] = $value;
    }

    public static function delete(string $var): void
    {
        unset($GLOBALS["Crisp_ThemeVariables"][$var]);
    }

    public static function get(string $var): mixed
    {
        return $GLOBALS["Crisp_ThemeVariables"][$var];
    }

    public static function getAll(): array
    {
        return $GLOBALS["Crisp_ThemeVariables"];
    }

    public static function register(): void
    {
        $GLOBALS["Crisp_ThemeVariables"] = [];
    }

}
