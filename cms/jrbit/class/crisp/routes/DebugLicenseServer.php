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

 /**
  * 400 = Missing License Key
  * 401 = Invalid License Key
  * 403 = Revoked License
  * 2xx = OK
  * Other error codes have a grace period of 10 Pull attempts. If the code is still not 2xx the license will be uninstalled after 10 attempts. 
  */

namespace crisp\routes;

use Carbon\Carbon;
use crisp\api\Build;
use crisp\api\Config;
use crisp\api\Helper;
use crisp\api\License;
use crisp\core\Crypto;
use crisp\core\Logger;

class DebugLicenseServer
{
    public function preRender(string $status = "valid", string $licenseKey = null): void
    {
        #http_response_code(401);
        #exit;

        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        if (Build::getEnvironment() !== \crisp\core\Environment::DEVELOPMENT) {
            Logger::getLogger(__METHOD__)->error("License Server is only available in Development Environment");
            exit;
        }

        $licenseExpiryDate = Carbon::now()->addDays(30)->unix();

        $domains = ["*.gitpod.io"];

        if ($_ENV["HOST"]) {
            $domains[] = $_ENV["HOST"];
        }

        $instance = Helper::getInstanceId();
        
        Logger::getLogger(__METHOD__)->debug("Status: $status");

        switch ($status) {
            case "expired":
                $licenseExpiryDate = Carbon::now()->subDays(30)->unix();
                break;
            case "revoked":
                http_response_code(403);
                exit;
                break;
            case "domain_mismatch":
                $domains = ["invalid.tld"];
                break;
            case "instance_mismatch":
                $instance = Crypto::UUIDv4();
                break;

            case "key":
                if (!$licenseKey) {
                    Logger::getLogger(__METHOD__)->error("Missing License Key");
                    http_response_code(400);
                    exit;
                }
                if($licenseKey !== "testKey"){
                    Logger::getLogger(__METHOD__)->error("Invalid License Key");
                    http_response_code(401);
                    exit;
                }
                break;
        }



        $license = new \crisp\api\License(
            version: \crisp\api\License::GEN_VERSION,
            uuid: Crypto::UUIDv4(),
            whitelabel: "Acme Inc.",
            domains: $domains,
            name: "Test License",
            issuer: "Acme Inc.",
            issued_at: time(),
            expires_at: $licenseExpiryDate,
            data: null,
            instance: $instance
        );

        if (!Config::exists("license_issuer_private_key")) {
            Logger::getLogger(__METHOD__)->debug("Generating new issuer keypair");
            License::generateIssuer();
        }

        if (!$license->sign()) {
            Logger::getLogger(__METHOD__)->error("Failed to sign license");
            exit;
        }

        header("Content-Type: application/json");
        echo $license->serveToLicenseServer();
    }
}
