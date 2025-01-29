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
use crisp\api\License as ApiLicense;
use crisp\core;
use crisp\core\Bitmask;
use crisp\core\Environment;
use crisp\core\Logger;
use crisp\core\RESTfulAPI;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use Exception as GlobalException;

/**
 * Used internally, theme loader.
 */
class License
{
    public function preRender(array $message = [], ?string $color = "info"): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        if ($_SERVER["REQUEST_METHOD"] === "POST") {

            if ($_POST["action"] === "enter_license") {
                if (count($_FILES) === 0) {
                    RESTfulAPI::response(Bitmask::MISSING_PARAMETER->value, "Missing Licenses", HTTP: 400);
                    exit;
                }

                if (!empty($_FILES["license"])) {

                    if (ApiLicense::isLicenseAvailable() && !isset($_POST["instance"])) {
                        RESTfulAPI::response(Bitmask::MISSING_PARAMETER->value, "Missing Instance ID", HTTP: 400);
                        exit;
                    } elseif (ApiLicense::isLicenseAvailable() && $_POST["instance"] !== Helper::getInstanceId()) {
                        RESTfulAPI::response(Bitmask::INVALID_PARAMETER->value, "Invalid Instance ID", HTTP: 401);
                        exit;
                    }

                    Logger::getLogger(__METHOD__)->info("Installing new License Key...");
                    if (!Config::set("license_data", file_get_contents($_FILES["license"]["tmp_name"]))) {
                        throw new GlobalException("Failed to save License Key");
                    }
                }
                if (!empty($_FILES["issuer"])) {

                    if (ApiLicense::isIssuerAvailable()) {
                        RESTfulAPI::response(Bitmask::MISSING_PARAMETER->value, "Issuer already available!", HTTP: 401);
                        exit;
                    }

                    Logger::getLogger(__METHOD__)->info("Installing new Issuer Key...");
                    if (!Config::set("license_issuer_public_key", file_get_contents($_FILES["issuer"]["tmp_name"]))) {
                        throw new GlobalException("Failed to save issuer key");
                    }
                }

                Cache::clear();

                echo "OK";
            } elseif ($_POST["action"] === "generate_issuer") {
                if (ApiLicense::isIssuerPrivateAvailable()) {
                    RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "Issuer Key already exists!", HTTP: 401);
                    exit;
                }

                if (!ApiLicense::generateIssuer()) {
                    throw new GlobalException("Failed to save Issuer Key");
                }
                echo "OK";
            } elseif ($_POST["action"] === "generate_license") {
                if (!ApiLicense::isIssuerPrivateAvailable()) {
                    RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "Issuer Key does not exist!", HTTP: 401);
                    exit;
                }

                if (Build::requireLicense() && Build::getEnvironment() !== Environment::DEVELOPMENT) {
                    RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "Licenses cannot be generated on this instance!", HTTP: 401);
                    exit;
                }

                if (ApiLicense::isLicenseAvailable() && !isset($_POST["instance"])) {
                    RESTfulAPI::response(Bitmask::MISSING_PARAMETER->value, "Missing Instance ID", HTTP: 400);
                    exit;
                } elseif (ApiLicense::isLicenseAvailable() && $_POST["instance"] !== Helper::getInstanceId()) {
                    RESTfulAPI::response(Bitmask::INVALID_PARAMETER->value, "Invalid Instance ID", HTTP: 401);
                    exit;
                }

                $expiry = null;

                if (isset($_POST["license_has_expiry"])) {
                    $expiry = Carbon::parse($_POST["license_expiry_date"])->unix();
                }

                $license = new ApiLicense(
                    version: ApiLicense::GEN_VERSION,
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
            } elseif ($_POST["action"] === "enter_key") {

                Logger::getLogger(__METHOD__)->info("Installing new License Key...");
                $License = ApiLicense::fromLicenseServer($_POST["key"] ?? null);

                if (!$License) {
                    RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "Could not fetch license from server", HTTP: 500);
                    exit;
                }

                Cache::clear();

                echo "OK";
            } elseif ($_POST["action"] === "refresh") {

                Logger::getLogger(__METHOD__)->info("Refreshing License...");
                $License = ApiLicense::fromLicenseServer();

                if (!$License) {
                    RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "Could not fetch license from server", HTTP: 500);
                    exit;
                }

                Cache::clear();

                echo "OK";
            } else {
                RESTfulAPI::response(Bitmask::INVALID_PARAMETER->value, "Unknown Action", HTTP: 404);
            }
            exit;
        }

        Cache::delete("license_data");

        ThemeVariables::setMultiple([
            "license" => ApiLicense::fromDB(),
            "IssuerAvailable" => ApiLicense::isIssuerAvailable(),
            "LicenseAvailable" => ApiLicense::isLicenseAvailable(),
            "IssuerPrivateAvailable" => ApiLicense::isIssuerPrivateAvailable(),
            "RequireLicense" => Build::requireLicense(),
            "RequireLicenseServer" => Build::requireLicenseServer(),
            "LicenseKeyIsDefined" => Build::licenseKeyIsDefined(),
            "HeaderMessage" => $message,
            "HeaderColor" => $color,
        ]);

        echo Themes::render("views/license.twig", "themes/basic/templates");
        exit;
    }
}
