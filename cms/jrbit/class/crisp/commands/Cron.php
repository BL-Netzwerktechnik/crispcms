<?php

namespace crisp\commands;

use crisp\api\Build;
use crisp\api\Config;
use crisp\api\Helper;
use crisp\core;
use crisp\core\Cron as CoreCron;
use crisp\core\Logger;
use splitbrain\phpcli\Options;
use crisp\core\Environment;
use crisp\core\Themes;

class Cron
{
    public static function run(\CLI $minimal, Options $options): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        if ($options->getOpt("run")) {
            CoreCron::get()->run();
            $minimal->success("Scheduler run");
            return true;
        }

        $minimal->error("No action");

        return false;
    }
}
