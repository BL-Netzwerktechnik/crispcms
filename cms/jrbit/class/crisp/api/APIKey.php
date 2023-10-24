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
use crisp\core\Logger;
use crisp\core\RESTfulAPI;

/**
 * Interact with an api key
 * @deprecated 17.0.0 API Keys is no longer a feature of CrispCMS
 */
class APIKey
{

    public string $APIKey;
    private ?PDO $Database_Connection;

    /**
     * @deprecated 17.0.0 API Keys is no longer a feature of CrispCMS
     *
     * @param [type] $APIKey
     */
    public function __construct($APIKey)
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        Logger::getLogger(__METHOD__)->warning("[DEPRECATED] API Keys is no longer a feature of CrispCMS and will be removed in 17.0.0");
        $DB = new Postgres();
        $this->Database_Connection = $DB->getDBConnector();
        $this->APIKey = $APIKey;
        
    }

    /**
     * Fetches a Keys details
     * @deprecated 17.0.0 API Keys is no longer a feature of CrispCMS
     * @return array|null
     */
    public function fetch(): ?array
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        Logger::getLogger(__METHOD__)->warning("[DEPRECATED] API Keys is no longer a feature of CrispCMS and will be removed in 17.0.0");
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
     * @deprecated 17.0.0 API Keys is no longer a feature of CrispCMS
     * @see disable
     */
    public function enable(): ?bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        Logger::getLogger(__METHOD__)->warning("[DEPRECATED] API Keys is no longer a feature of CrispCMS and will be removed in 17.0.0");
        if ($this->APIKey === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("UPDATE APIKeys SET revoked = 0 WHERE `key` = :ID");
        return $statement->execute(array(":ID" => $this->APIKey));
    }

    /**
     * Disables an api key
     * @return bool|null
     * @deprecated 17.0.0 API Keys is no longer a feature of CrispCMS
     * @see enable
     */
    public function disable(): ?bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        Logger::getLogger(__METHOD__)->warning("[DEPRECATED] API Keys is no longer a feature of CrispCMS and will be removed in 17.0.0");
        if ($this->APIKey === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("UPDATE APIKeys SET revoked = 1 WHERE `key` = :ID");
        return $statement->execute(array(":ID" => $this->APIKey));
    }

    /**
     * Checks whether a language is enabled or not
     * @deprecated 17.0.0 API Keys is no longer a feature of CrispCMS
     * @return bool|null
     */
    public function isEnabled(): ?bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        Logger::getLogger(__METHOD__)->warning("[DEPRECATED] API Keys is no longer a feature of CrispCMS and will be removed in 17.0.0");
        if ($this->APIKey === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT * FROM APIKeys WHERE `key` = :ID");
        $statement->execute(array(":ID" => $this->APIKey));

        return !$statement->fetch(PDO::FETCH_ASSOC)["revoked"];
    }

    /**
     * Check if the language exists in the database
     * @deprecated 17.0.0 API Keys is no longer a feature of CrispCMS
     * @return bool|null
     */
    public function exists(): ?bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        Logger::getLogger(__METHOD__)->warning("[DEPRECATED] API Keys is no longer a feature of CrispCMS and will be removed in 17.0.0");
        if ($this->APIKey === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare("SELECT * FROM APIKeys WHERE `key` = :ID");
        $statement->execute(array(":ID" => $this->APIKey));

        return ($statement->rowCount() !== 0);
    }

}
