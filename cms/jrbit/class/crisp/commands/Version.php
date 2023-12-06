<?php

namespace crisp\commands;

use crisp\api\Build;
use crisp\core\Logger;

class Version
{
    public static function run(\CLI $minimal): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $minimal->info(sprintf("Crisp Version: %s", Build::getReleaseString()));
        if (Build::getBuildType() !== "Stable") {
            $minimal->warning(sprintf("Build: %s", Build::getBuildType()));
        } else {
            $minimal->success(sprintf("Build: %s", Build::getBuildType()));
        }

        return true;
    }
}
