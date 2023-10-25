<?php

namespace crisp\commands;

use crisp\api\Config;
use crisp\api\Helper;
use crisp\core;
use crisp\core\Logger;
use splitbrain\phpcli\Options;

class Crisp
{
    public static function run(\CLI $minimal, Options $options): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]);
        if ($options->getOpt("migrate")) {
            $Migrations = new core\Migrations();
            $Migrations->migrate();

            return true;
        } elseif ($options->getOpt("post-install")) {
            $minimal->success("Crisp has been successfully installed!");
            if (ENVIRONMENT !== core\Environment::PRODUCTION->value) {
                $minimal->notice(sprintf("You can access the Debug menu at %s://%s/_debug", $_ENV["PROTO"], $_ENV["HOST"]));
            }
            $minimal->success(sprintf("Your instance id is: %s", Helper::getInstanceId()));

            if ($_ENV['REQUIRE_LICENSE']) {
                if (!\crisp\api\License::isIssuerAvailable() && file_exists("/issuer.pub")) {
                    Config::set("license_issuer_public_key", file_get_contents("/issuer.pub"));
                    $minimal->success("Imported Distributor Public Key!");
                }
            }

            if ($_ENV["REQUIRE_LICENSE"] && !\crisp\api\License::isLicenseAvailable()) {
                $minimal->warning("Your Distributor Requires a valid License but none is installed - You will be prompted to install a License Key");
            }

            return true;
        }
        $minimal->error("No action");

        return true;
    }
}
