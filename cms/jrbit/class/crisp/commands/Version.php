<?php

namespace crisp\commands;

use crisp\api\Helper;
use crisp\core;
use Minimal;

class Version {
    public static function run(Minimal $minimal): bool
    {

        $minimal->warning(Helper::getRequestLog());

        $minimal->info(sprintf("Crisp Version: %s", core::CRISP_VERSION));
        $minimal->info(sprintf("API Version: %s", core::API_VERSION));
        $minimal->info(sprintf("Release: %s", core::RELEASE_NAME));
        if($_ENV['BUILD_TYPE'] !== "Stable"){
            $minimal->warning(sprintf("Build: %s", $_ENV['BUILD_TYPE']));
        }else {
            $minimal->info(sprintf("Build: %s", $_ENV['BUILD_TYPE']));
        }
        return true;
    }
}