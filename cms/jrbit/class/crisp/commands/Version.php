<?php

namespace crisp\commands;

use CLI;
use crisp\api\Helper;
use crisp\core;
use crisp\core\Logger;
use Minimal;

class Version {
    public static function run(CLI $minimal): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);

        $minimal->info(sprintf("Crisp Version: %s", core::CRISP_VERSION));
        $minimal->info(sprintf("API Version: %s", core::API_VERSION));
        $minimal->info(sprintf("Release Name: %s", core::RELEASE_NAME));
        if($_ENV['BUILD_TYPE'] !== "Stable"){
            $minimal->warning(sprintf("Build: %s", $_ENV['BUILD_TYPE']));
        }else {
            $minimal->success(sprintf("Build: %s", $_ENV['BUILD_TYPE']));
        }
        $minimal->info(sprintf("Release: %s", RELEASE));
        return true;
    }
}