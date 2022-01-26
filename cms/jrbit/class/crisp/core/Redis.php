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

use crisp\exceptions\BitmaskException;
use Exception;

/**
 * Interact with the database yourself. Please use this interface only when you REALLY need it for custom tables.
 * We offer a variety of functions to interact with users or the system itself in a safe way :-)
 */
class Redis
{

    private \Redis $Database_Connection;

    /**
     * Constructs the Database_Connectio
     * @throws BitmaskException
     * @see getDBConnector
     */
    public function __construct()
    {
        try {
            $redis = new \Redis();
            $redis->connect($_ENV['REDIS_HOST'], $_ENV['REDIS_PORT']);
            if ($_ENV['REDIS_AUTH']) {
                $redis->auth($_ENV['REDIS_AUTH']);
            }
            $redis->select($_ENV['REDIS_INDEX']);
            $this->Database_Connection = $redis;
        } catch (Exception $ex) {
            throw new BitmaskException($ex, Bitmask::REDIS_CONN_ERROR);
        }
    }

    /**
     * Get the database connector
     * @return \Redis
     */
    public function getDBConnector(): \Redis
    {
        return $this->Database_Connection;
    }

}
