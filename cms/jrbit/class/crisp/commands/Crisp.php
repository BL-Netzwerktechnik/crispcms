<?php

namespace crisp\commands;

use crisp\api\Build;
use crisp\api\Config;
use crisp\api\Helper;
use crisp\core;
use crisp\core\Logger;
use splitbrain\phpcli\Options;
use crisp\core\Environment;
use crisp\core\Themes;

class Crisp
{
    public static function run(\CLI $minimal, Options $options): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        if ($options->getOpt("migrate")) {
            $Migrations = new core\Migrations();
            $Migrations->migrate();

            return true;
        } elseif ($options->getOpt("post-install")) {
            $minimal->success("Crisp has been successfully installed!");
            if (Build::getEnvironment() !== Environment::PRODUCTION->value) {
                $minimal->notice(sprintf("You can access the Debug menu at %s://%s/_/debug", $_ENV["PROTO"], $_ENV["HOST"]));
            }
            $minimal->success(sprintf("Your instance id is: %s", Helper::getInstanceId()));

            if (Build::requireLicense()) {
                if (!\crisp\api\License::isIssuerAvailable() && file_exists("/issuer.pub")) {
                    Config::set("license_issuer_public_key", file_get_contents("/issuer.pub"));
                    $minimal->success("Imported Distributor Public Key!");
                }
            }

            if (Build::requireLicense() && !\crisp\api\License::isLicenseAvailable()) {
                $minimal->warning("Your Distributor Requires a valid License but none is installed - You will be prompted to install a License Key");
            }

            return true;
        } elseif ($options->getOpt("check-permissions")){

            if (!is_writable(core::PERSISTENT_DATA)) {
                $minimal->error(sprintf("Directory %s is not writable!", core::PERSISTENT_DATA));
            } else {
                $minimal->success(sprintf("Directory %s is writable!", core::PERSISTENT_DATA));
            }

            if (!is_writable(core::PERSISTENT_DATA . "/.instance_id")) {
                $minimal->error(sprintf("File %s is not writable!", core::PERSISTENT_DATA . "/.instance_id"));
            } else {
                $minimal->success(sprintf("File %s is writable!", core::PERSISTENT_DATA . "/.instance_id"));
            }

            if (!is_writable(core::CACHE_DIR)) {
                $minimal->error(sprintf("Directory %s is not writable!", core::CACHE_DIR));
            } else {
                $minimal->success(sprintf("Directory %s is writable!", core::CACHE_DIR));
            }

            return true;

        } elseif ($options->getOpt("clear-cache")) {
            if (Themes::clearCache()) {
                $minimal->success("The cache has been successfully cleared!");

                return true;
            }
            $minimal->error("Failed to clear cache!");

            return false;
        }
        $minimal->error("No action");

        return false;
    }
}
