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

    private function installLicenseByFile(): void
    {
        if (count($_FILES) === 0) {
            RESTfulAPI::response(Bitmask::MISSING_PARAMETER->value, "Missing Licenses", HTTP: 400);
            exit;
        }

        if (!empty($_FILES["license"])) {


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
    }

    private function installLicenseByKey(): void
    {
        Logger::getLogger(__METHOD__)->info("Installing new License Key...");
        $License = ApiLicense::fromLicenseServer($_POST["key"] ?? null);

        if (!$License) {
            RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "Could not fetch license from server", HTTP: 401);
            exit;
        }

        Cache::delete("license_data");
        Cache::delete("license_key");

        echo "OK";
    }

    private function refreshLicense(): void
    {
        Logger::getLogger(__METHOD__)->info("Refreshing License...");
        $License = ApiLicense::fromLicenseServer();

        if (!$License) {
            RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, "Could not fetch license from server", HTTP: 500);
            exit;
        }

        Cache::delete("license_data");
        Cache::delete("license_key");

        echo "OK";
    }

    private function requireAuthentication(): bool
    {
        return ApiLicense::isLicenseAvailable() && ApiLicense::fromDB()->isValid();
    }

    private function validateAuthentication(?string $instanceId): bool
    {

        if ($this->requireAuthentication() && !isset($instanceId)) {
            Logger::getLogger(__METHOD__)->warning("Missing Instance ID");
            return false;
        } elseif (ApiLicense::isLicenseAvailable() && $instanceId !== Helper::getInstanceId()) {
            Logger::getLogger(__METHOD__)->warning("Invalid Instance ID");
            return false;
        }
        return true;
    }

    public function preRender(array $message = [], ?string $color = "info"): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        if ($_SERVER["REQUEST_METHOD"] === "POST") {

            if (!$this->validateAuthentication($_GET["instance"])) {
                RESTfulAPI::response(Bitmask::INVALID_PARAMETER->value, "Authentication failed, check Logs!", HTTP: 401);
                exit;
            }
            if ($_POST["action"] === "enter_license") {
                $this->installLicenseByFile();
            } elseif ($_POST["action"] === "enter_key") {
                $this->installLicenseByKey();
            } elseif ($_POST["action"] === "refresh") {
                $this->refreshLicense();
            } else {
                RESTfulAPI::response(Bitmask::INVALID_PARAMETER->value, "Unknown Action", HTTP: 404);
            }
            exit;
        }


        ThemeVariables::setMultiple([
            "Authenticated" => $this->validateAuthentication($_GET["instance"]),
            "license" => ApiLicense::fromDB(),
            "IssuerAvailable" => ApiLicense::isIssuerAvailable(),
            "LicenseAvailable" => ApiLicense::isLicenseAvailable(),
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
