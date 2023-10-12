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

use Carbon\Carbon;
use crisp\api\lists\Languages;
use crisp\core;
use crisp\core\Crypto;
use crisp\core\Logger;
use crisp\core\Postgres;
use crisp\core\Themes;
use GeoIp2\Database\Reader;
use PDO;
use splitbrain\phpcli\CLI;
use stdClass;


/**
 * GeoIP API
 */
class GeoIP
{

    /**
     * Check if the GeoIP database is available
     *
     * @return boolean
     */
    public static function isAvailable(): bool {
        return count(glob("/usr/share/GeoIP/*.mmdb")) > 0;
    }

    /**
     * Get the GeoIP database City
     *
     * @param string $defaultDB
     * @return Reader
     */
    public static function City(string $defaultDB = "GeoLite2-City.mmdb"): Reader
    {
        return new Reader("/usr/share/GeoIP/$defaultDB");
    }

    /**
     * Get the GeoIP database ASN
     *
     * @param string $defaultDB
     * @return Reader
     */
    public static function ASN(string $defaultDB = "GeoLite2-ASN.mmdb"): Reader
    {
        return new Reader("/usr/share/GeoIP/$defaultDB");
    }

    /**
     * Get the GeoIP database Country
     *
     * @param string $defaultDB
     * @return Reader
     */
    public static function Country(string $defaultDB = "GeoLite2-Country.mmdb"): Reader
    {
        return new Reader("/usr/share/GeoIP/$defaultDB");
    }



}
