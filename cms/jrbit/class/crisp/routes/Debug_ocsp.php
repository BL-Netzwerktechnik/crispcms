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


namespace crisp\routes;

use crisp\api\Cache;
use crisp\api\Config;
use crisp\api\Helper;
use crisp\core;
use crisp\core\Bitmask;
use crisp\core\RESTfulAPI;
use crisp\models\ThemeAPI;
use finfo;
use splitbrain\phpcli\Exception;
use Twig\Environment;

/**
 * Used internally, theme loader
 *
 */
class Debug_ocsp extends ThemeAPI  {


    public function execute(string $Interface, Environment $TwigTheme): void
    {

        /**
         * The OCSP Check expects a 2xx Status Code for a valid License Key
         * HTTP codes other than 2xx will revoke the certificate.
         *
         * With a HTTP Code of 5xx, Crisp will attempt to retry the OCSP request 3 times before revoking the license.
         * The OCSP Property exposes the following two variables to validate against your endpoint with: {{uuid}} for the license id and {{instance}} with the instance id
         */

        #http_response_code(500);
    }
}