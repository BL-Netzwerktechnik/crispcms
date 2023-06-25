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
class License extends ThemeAPI  {


    public function execute(string $Interface, Environment $TwigTheme): void
    {

        if($_SERVER["REQUEST_METHOD"] === "POST") {

            if ($_POST["action"] === "enter_license") {
                if (count($_FILES) === 0) {
                    RESTfulAPI::response(Bitmask::MISSING_PARAMETER->value, "Missing Licenses", HTTP: 400);
                    exit;

                }


                if (!empty($_FILES["license"])) {


                    if (\crisp\api\License::isLicenseAvailable() && !isset($_POST["instance"])) {
                        RESTfulAPI::response(Bitmask::MISSING_PARAMETER->value, "Missing Instance ID", HTTP: 400);
                        exit;
                    } elseif (\crisp\api\License::isLicenseAvailable() && $_POST["instance"] !== Helper::getInstanceId()) {
                        RESTfulAPI::response(Bitmask::INVALID_PARAMETER->value, "Invalid Instance ID", HTTP: 401);
                        exit;
                    }

                    Helper::Log(core\LogTypes::INFO, "Installing new License Key...");
                    if (!Config::set("license_key", file_get_contents($_FILES["license"]["tmp_name"]))) {
                        throw new Exception("Failed to save License Key");
                    }

                }
                if (!empty($_FILES["issuer"])) {


                    if (\crisp\api\License::isIssuerAvailable()) {
                        RESTfulAPI::response(Bitmask::MISSING_PARAMETER->value, "Issuer already available!", HTTP: 401);
                        exit;
                    }

                    Helper::Log(core\LogTypes::INFO, "Installing new Issuer Key...");
                    if (!copy($_FILES["issuer"]["tmp_name"], core::PERSISTENT_DATA . "/issuer.pub")) {
                        throw new Exception("Permission denied writing to " . core::PERSISTENT_DATA);
                    }

                }

                echo "OK";

                exit;
            }elseif ($_POST["action"] === "generate_license") {
                if (\crisp\api\License::isIssuerPrivateAvailable()) {
                    RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "Issuer Key already exists!", HTTP: 401);
                    exit;
                }

                if(!\crisp\api\License::generateIssuer()){
                    throw new Exception("Failed to save Issuer Key");
                }
                echo "OK";

                exit;
            }else{
                RESTfulAPI::response(Bitmask::INVALID_PARAMETER->value, "Unknown Action", HTTP: 404);
                exit;
            }
        }

        echo $TwigTheme->render("views/license.twig", [
            "license" => \crisp\api\License::fromDB(),
            "IssuerAvailable" => \crisp\api\License::isIssuerAvailable(),
            "LicenseAvailable" => \crisp\api\License::isLicenseAvailable(),
            "IssuerPrivateAvailable" => \crisp\api\License::isIssuerPrivateAvailable()
        ]);
        exit;

    }
}
