<?php

namespace crisp\commands;

use crisp\api\Helper;
use crisp\core\Logger;
use crisp\core\Themes;
use splitbrain\phpcli\Options;

class Translations
{
    public static function run(\CLI $minimal, Options $options): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        if ($options->getOpt("install")) {

            if (!Themes::isInstalled()) {
                $minimal->error("Theme is not installed");

                return false;
            }
            if (!Themes::isValid()) {
                $minimal->error("Theme is not mounted. Check your Docker Configuration");

                return false;
            }

            $Start = microtime(true);
            if (Themes::installTranslations()) {
                $minimal->success("Translations successfully installed!");
            } else {
                $minimal->error("Failed to install Translations!");
            }
            $End = microtime(true);
            Logger::getLogger(__METHOD__)->debug(sprintf("Operation took %sms to complete!", Helper::truncateText($End - $Start, 6, false)));

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

            $Start = microtime(true);
            if (Themes::uninstallTranslations()) {
                $minimal->success("Translations successfully uninstalled!");
            } else {
                $minimal->error("Failed to uninstall Translations!");
            }
            $End = microtime(true);
            Logger::getLogger(__METHOD__)->debug(sprintf("Operation took %sms to complete!", Helper::truncateText($End - $Start, 6, false)));

            return true;
        }
        echo $options->help();

        return true;
    }
}
