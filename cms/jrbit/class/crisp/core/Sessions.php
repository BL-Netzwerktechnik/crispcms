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

use League\OAuth2\Client\Token\AccessTokenInterface;
use PDO;

/**
 * Some useful phoenix functions
 */
class Sessions
{

    public static ?PDO $Postgres_Database_Connection = null;


    public static function createSession($ID, $Identifier = "login"): bool|array
    {
        $DB = new Postgres();
        $DBConnection = $DB->getDBConnector();

        if (isset($_SESSION[Config::$Cookie_Prefix . "session_$Identifier"])) {
            unset($_SESSION[Config::$Cookie_Prefix . "session_$Identifier"]);
        }

        $Token = Crypto::UUIDv4();


        $statement = $DBConnection->prepare('INSERT INTO sessions (token, "user", identifier, oidc_token) VALUES (:Token, :User, :Identifier, :oidc)');
        $result = $statement->execute(array(":User" => $ID['sub'], ":Token" => $Token, ":Identifier" => $Identifier, ":oidc" => 'None'));

        if (!$result) {
            return false;
        }

        $Session = array(
            "identifier" => $Identifier,
            "token" => $Token,
            "user" => $ID
        );

        $_SESSION[Config::$Cookie_Prefix . "session_$Identifier"] = $Session;

        return $Session;
    }

    /**
     * Destroy a user's current session and log them out
     * |          Hook Name          |             Parameters            |
     * |:---------------------------:|:---------------------------------:|
     * | beforeDestroyCurrentSession |           array(UserID)           |
     * |  afterDestroyCurrentSession | array(See <b>Returns</b>, UserID) |
     * @return boolean TRUE if the session has been successfully destroyed otherwise FALSE
     */
    public static function destroyCurrentSession($ID, $Identifier = "login")
    {

        if (!isset($_SESSION[Config::$Cookie_Prefix . "session_$Identifier"])) {
            return false;
        }


        $DB = new Postgres();
        $DBConnection = $DB->getDBConnector();

        $statement = $DBConnection->prepare('DELETE FROM sessions WHERE token = :Token AND "user" = :User');
        $Action = $statement->execute(array(":User" => $ID, ":Token" => $_SESSION[Config::$Cookie_Prefix . "session_$Identifier"]["token"]));


        unset($_SESSION[Config::$Cookie_Prefix . "session_$Identifier"]);


        return $Action;
    }

    /**
     * Checks if the session of the current user is valid
     * |       Hook Name      |             Parameters            |
     * |:--------------------:|:---------------------------------:|
     * | beforeIsSessionValid |           array(UserID)           |
     * |  afterIsSessionValid | array(See <b>Returns</b>, UserID) |
     * @return boolean TRUE if session is valid, otherwise FALSE
     */
    public static function isSessionValid($Identifier = "login")
    {

        $DB = new Postgres();

        if (!isset($_SESSION[Config::$Cookie_Prefix . "session_$Identifier"])) {
            return false;
        }
        return true;
    }

}
