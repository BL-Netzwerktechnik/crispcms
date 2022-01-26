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

use crisp\core\Bitmask;
use crisp\exceptions\BitmaskException;
use stdClass;
use crisp\core\RESTfulAPI;

class Elastic {

    private string $Elastic_URI;
    private string $Elastic_Index;

    public function __construct() {
        $this->Elastic_URI = $_ENV["ELASTIC_URI"];
        $this->Elastic_Index = $_ENV["ELASTIC_INDEX"];
    }

    /**
     * @param string $Query
     * @return stdClass
     * @throws BitmaskException
     */
    public function search(string $Query): stdClass {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->Elastic_URI . "/" . $this->Elastic_Index . "/_search?q=*" . urlencode($Query) . "*");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        if (str_starts_with(curl_getinfo($ch, CURLINFO_HTTP_CODE), "5")) {
            throw new BitmaskException(curl_getinfo($ch, CURLINFO_HTTP_CODE), Bitmask::ELASTIC_CONN_ERROR);
        }
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
            throw new BitmaskException(curl_getinfo($ch, CURLINFO_HTTP_CODE), Bitmask::ELASTIC_QUERY_MALFORMED);
        }
        if (!$output) {
            throw new BitmaskException(curl_error($ch), Bitmask::ELASTIC_CONN_ERROR);
        }
        curl_close($ch);
        return json_decode($output)->hits;
    }

}
