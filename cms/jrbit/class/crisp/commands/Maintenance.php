<?php

namespace crisp\commands;

use crisp\api\Config;
use crisp\core\Logger;
use splitbrain\phpcli\Options;

class Maintenance
{
    public static function run(\CLI $minimal, Options $options): bool
    {

        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]);
        if ($options->getOpt("on")) {
            if (Config::set("maintenance_enabled", true)) {
                $minimal->success("Maintenance Mode successfully enabled.");

                return true;
            } else {
                $minimal->error("Maintenance Mode could not be enabled.");

                return false;
            }
        } elseif ($options->getOpt("off")) {
            if (Config::set("maintenance_enabled", false)) {
                $minimal->success("Maintenance Mode successfully disabled.");

                return true;
            } else {
                $minimal->error("Maintenance Mode could not be disabled.");

                return false;
            }
        }

        if (Config::get("maintenance_enabled")) {
            $minimal->alert("Maintenance Mode is currently enabled");

            return true;
        }
        $minimal->success("Maintenance Mode is currently disabled");

        return true;
    }
}
