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

use crisp\core\Postgres;
use PDO;
use crisp\core\Bitmask;
use crisp\core\RESTfulAPI;

/**
 * Interact with an api key
 */
class APIKey
{

    public string $APIKey;
    private ?PDO $Database_Connection;

    public function __construct($APIKey)
    {
        $DB = new Postgres();
        $this->Database_Connection = $DB->getDBConnector();
        $this->APIKey = $APIKey;
    }

    /**
     * Fetches a Keys details
     * @return array|null
     */
    public function fetch(): ?array
    {
        if ($this->APIKey === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT * FROM APIKeys WHERE `key` = :ID");
        $statement->execute(array(":ID" => $this->APIKey));

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Enables an api key
     * @return bool|null
     * @see disable
     */
    public function enable(): ?bool
    {
        if ($this->APIKey === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("UPDATE APIKeys SET revoked = 0 WHERE `key` = :ID");
        return $statement->execute(array(":ID" => $this->APIKey));
    }

    /**
     * Disables an api key
     * @return bool|null
     * @see enable
     */
    public function disable(): ?bool
    {
        if ($this->APIKey === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("UPDATE APIKeys SET revoked = 0 WHERE `key` = :ID");
        return $statement->execute(array(":ID" => $this->APIKey));
    }

    /**
     * Checks whether a language is enabled or not
     * @return bool|null
     */
    public function isEnabled(): ?bool
    {
        if ($this->APIKey === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT * FROM APIKeys WHERE `key` = :ID");
        $statement->execute(array(":ID" => $this->APIKey));

        return !$statement->fetch(PDO::FETCH_ASSOC)["revoked"];
    }

    /**
     * Check if the language exists in the database
     * @return bool|null
     */
    public function exists(): ?bool
    {
        if ($this->APIKey === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT * FROM APIKeys WHERE `key` = :ID");
        $statement->execute(array(":ID" => $this->APIKey));

        return ($statement->rowCount() !== 0);
    }

}
