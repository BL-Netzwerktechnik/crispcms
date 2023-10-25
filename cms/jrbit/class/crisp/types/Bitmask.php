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

namespace crisp\types;

use crisp\core\Logger;

abstract class Bitmask extends Enum
{
    public static function hasBitmask(int $BitwisePermissions, int $PermissionFlag = 0x00000000)
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]);
        if (!is_numeric($BitwisePermissions)) {
            throw new \TypeError("Parameter BitwisePermissions is not a hexadecimal or number.");
        }
        if (!is_numeric($PermissionFlag)) {
            throw new \TypeError("Parameter PermissionFlag is not a hexadecimal or number.");
        }

        if ($PermissionFlag === 0x00000000) {
            return true;
        }

        return $BitwisePermissions & $PermissionFlag ? true : false;
    }

    public static function getConstants()
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]);
        $oClass = new \ReflectionClass(static::class);

        return $oClass->getConstants();
    }

    public static function getBitmask(int $BitwisePermissions, bool $IndexArray = false)
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]);
        if (!is_numeric($BitwisePermissions)) {
            throw new \TypeError("Parameter BitwisePermissions is not a hexadecimal or number.");
        }
        if ($BitwisePermissions === 0x00000000) {
            throw new \TypeError("Parameter BitwisePermissions is zero.");
        }

        $MatchedBits = [];

        foreach (self::getConstants() as $Permission) {

            if (self::hasBitmask($BitwisePermissions, $Permission)) {
                if ($IndexArray) {
                    $MatchedBits[] = array_search($Permission, self::getConstants());
                } else {
                    $MatchedBits[array_search($Permission, self::getConstants())] = $Permission;
                }
            }
        }

        return $MatchedBits;
    }
}
