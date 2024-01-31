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

use Carbon\Carbon;
use crisp\api\Build;
use crisp\api\Cache;
use crisp\api\Config;
use crisp\api\Helper;
use crisp\core;
use crisp\core\Bitmask;
use crisp\core\Environment;
use crisp\core\Logger;
use crisp\core\RESTfulAPI;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use splitbrain\phpcli\Exception;

/**
 * Used internally, theme loader.
 */
class License
{
    public function preRender(): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        if ($_SERVER["REQUEST_METHOD"] === "POST") {

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

                    Logger::getLogger(__METHOD__)->info("Installing new License Key...");
                    if (!Config::set("license_key", file_get_contents($_FILES["license"]["tmp_name"]))) {
                        throw new Exception("Failed to save License Key");
                    }

                }
                if (!empty($_FILES["issuer"])) {

                    if (\crisp\api\License::isIssuerAvailable()) {
                        RESTfulAPI::response(Bitmask::MISSING_PARAMETER->value, "Issuer already available!", HTTP: 401);
                        exit;
                    }

                    Logger::getLogger(__METHOD__)->info("Installing new Issuer Key...");
                    if (!Config::set("license_issuer_public_key", file_get_contents($_FILES["issuer"]["tmp_name"]))) {
                        throw new Exception("Failed to save issuer key");
                    }

                }

                Cache::clear();

                echo "OK";

            } elseif ($_POST["action"] === "generate_issuer") {
                if (\crisp\api\License::isIssuerPrivateAvailable()) {
                    RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "Issuer Key already exists!", HTTP: 401);
                    exit;
                }

                if (!\crisp\api\License::generateIssuer()) {
                    throw new Exception("Failed to save Issuer Key");
                }
                echo "OK";

            } elseif ($_POST["action"] === "generate_license") {
                if (!\crisp\api\License::isIssuerPrivateAvailable()) {
                    RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "Issuer Key does not exist!", HTTP: 401);
                    exit;
                }

                if (Build::requireLicense() && Build::getEnvironment() !== Environment::DEVELOPMENT) {
                    RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "Licenses cannot be generated on this instance!", HTTP: 401);
                    exit;
                }

                if (\crisp\api\License::isLicenseAvailable() && !isset($_POST["instance"])) {
                    RESTfulAPI::response(Bitmask::MISSING_PARAMETER->value, "Missing Instance ID", HTTP: 400);
                    exit;
                } elseif (\crisp\api\License::isLicenseAvailable() && $_POST["instance"] !== Helper::getInstanceId()) {
                    RESTfulAPI::response(Bitmask::INVALID_PARAMETER->value, "Invalid Instance ID", HTTP: 401);
                    exit;
                }

                $expiry = null;

                if (isset($_POST["license_has_expiry"])) {
                    $expiry = Carbon::parse($_POST["license_expiry_date"])->unix();
                }

                $license = new \crisp\api\License(
                    version: \crisp\api\License::GEN_VERSION,
                    uuid: core\Crypto::UUIDv4(),
                    whitelabel: empty($_POST["license_whitelabel"]) ? null : $_POST["license_whitelabel"],
                    domains: $_POST["license_domains"] ? array_map('trim', explode(",", $_POST["license_domains"])) : [],
                    name: empty($_POST["license_name"]) ? null : $_POST["license_name"],
                    issuer: empty($_POST["license_issuer"]) ? null : $_POST["license_issuer"],
                    issued_at: time(),
                    expires_at: $expiry,
                    data: empty($_POST["license_data"]) ? null : $_POST["license_data"],
                    instance: empty($_POST["license_instance"]) ? null : $_POST["license_instance"],
                    ocsp: empty($_POST["license_ocsp"]) ? null : $_POST["license_ocsp"],
                );

                if (!$license->sign()) {
                    RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "Could not sign license", HTTP: 500);
                    exit;
                }

                RESTfulAPI::response(Bitmask::REQUEST_SUCCESS->value, "OK", [
                    "license" => $license->exportToString(),
                    "issuerpub" => Config::get("license_issuer_public_key"),
                ]);

                Cache::clear();

            } else {
                RESTfulAPI::response(Bitmask::INVALID_PARAMETER->value, "Unknown Action", HTTP: 404);
            }
            exit;
        }

        Cache::delete("license_key");

        ThemeVariables::setMultiple([
            "license" => \crisp\api\License::fromDB(),
            "IssuerAvailable" => \crisp\api\License::isIssuerAvailable(),
            "LicenseAvailable" => \crisp\api\License::isLicenseAvailable(),
            "IssuerPrivateAvailable" => \crisp\api\License::isIssuerPrivateAvailable(),
            "RequireLicense" => Build::requireLicense()
        ]);

        echo Themes::render("views/license.twig", "themes/basic/templates");
        exit;

    }
}
