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

use crisp\api\Helper;
use crisp\exceptions\BitmaskException;
use Exception;
use PDO;

/**
 * Interact with the database yourself. Please use this interface only when you REALLY need it for custom tables.
 * We offer a variety of functions to interact with users or the system itself in a safe way :-)
 */
class Postgres {

    private PDO $Database_Connection;

    /**
     * Constructs the Database_Connection
     * @throws BitmaskException
     * @see getDBConnector
     */
    public function __construct($EnvKey = 'POSTGRES_URI') {


        if($GLOBALS["DBConn_$EnvKey"] !== null){
            $this->Database_Connection = $GLOBALS["DBConn_$EnvKey"];
        }else{


            if (isset($_ENV[$EnvKey]) && !empty($_ENV[$EnvKey])) {
                $db = parse_url($_ENV[$EnvKey]);
            }

            try {
                $pdo = new PDO("pgsql:" . sprintf(
                                "host=%s;port=%s;user=%s;password=%s;dbname=%s",
                                $db["host"],
                                $db["port"],
                                $db["user"],
                                $db["pass"],
                                ltrim($db["path"], "/")
                ));
                Logger::getLogger(__CLASS__)->debug("Created new PDO Session");
                $this->Database_Connection = $pdo;
                $GLOBALS["DBConn_$EnvKey"] = $pdo;
            } catch (Exception $ex) {
                throw new BitmaskException($ex, Bitmask::POSTGRES_CONN_ERROR->value);
            }
        }
    }

    /**
     * Get the database connector
     * @return PDO
     */
    public function getDBConnector(): PDO
    {
        return $this->Database_Connection;
    }

}
