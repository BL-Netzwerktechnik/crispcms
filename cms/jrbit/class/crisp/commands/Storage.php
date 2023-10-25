<?php

namespace crisp\commands;

use crisp\core\Logger;
use crisp\core\Themes;
use splitbrain\phpcli\Options;

class Storage
{
    public static function run(\CLI $minimal, Options $options): bool
    {

        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]);
        if ($options->getOpt("install")) {

            if (!Themes::isInstalled()) {
                $minimal->error("Theme is not installed");

                return false;
            }
            if (!Themes::isValid()) {
                $minimal->error("Theme is not mounted. Check your Docker Configuration");

                return false;
            }

            Logger::startTiming($Timing);
            if (Themes::installKVStorage((bool) $options->getOpt("force"))) {
                $minimal->success("KVStorage successfully installed!");
            } else {
                $minimal->error("Failed to install KVStorage!");
            }
            Logger::getLogger(__METHOD__)->debug(sprintf("Operation took %sms to complete!", Logger::endTiming($Timing)));

            return true;
        } elseif ($options->getOpt("uninstall")) {

            if (!Themes::isInstalled()) {
                $minimal->error("Theme is not installed");

                return false;
            }
            if (!Themes::isValid()) {
                $minimal->error("Theme is not mounted. Check your Docker Configuration");

                return false;
            }

            Logger::startTiming($Timing);
            if (Themes::uninstallKVStorage()) {
                $minimal->success("KVStorage successfully uninstalled!");
            } else {
                $minimal->error("Failed to uninstall KVStorage!");
            }
            Logger::getLogger(__METHOD__)->debug(sprintf("Operation took %sms to complete!", Logger::endTiming($Timing)));

            return true;
        }
        echo $options->help();

        return true;
    }
}
