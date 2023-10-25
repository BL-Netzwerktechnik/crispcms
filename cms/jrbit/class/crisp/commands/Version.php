<?php

namespace crisp\commands;

use CLI;
use crisp\api\Build;
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
        if(Build::getBuildType() !== "Stable"){
            $minimal->warning(sprintf("Build: %s", Build::getBuildType()));
        }else {
            $minimal->success(sprintf("Build: %s", Build::getBuildType()));
        }
        $minimal->info(sprintf("Release: %s", Build::getReleaseString()));
        return true;
    }
}