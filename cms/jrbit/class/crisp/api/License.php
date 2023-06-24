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


namespace crisp\api;

use crisp\core;
use crisp\core\LogTypes;
use crisp\core\Postgres;
use PDO;
use function serialize;
use function unserialize;
use crisp\core\Bitmask;
use crisp\core\RESTfulAPI;

/**
 * Interact with the key/value storage of the server
 */
class License
{


    private static function calculateSingleLevel(string $name, int $level): string
    {
        return $name[$level - 1];
    }

}
