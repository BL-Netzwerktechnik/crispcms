<?php

namespace crisp\commands;

use crisp\api\Config;
use crisp\core;
use crisp\core\Logger;
use crisp\core\Themes;
use splitbrain\phpcli\Options;

class Theme
{
    public static function run(\CLI $minimal, Options $options): bool
    {

        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        if ($options->getOpt("install")) {

            if (Themes::isInstalled()) {
                $minimal->error("Theme is already installed");

                return false;
            }
            if (!Themes::isValid()) {
                $minimal->error("Theme is not mounted. Check your Docker Configuration");

                return false;
            }
            if (Config::set("maintenance_enabled", true)) {
                $minimal->info("Maintenance Mode successfully enabled.");
            }

            $minimal->info("Installing Theme");

            if (Themes::install()) {
                $minimal->success("Theme successfully installed");
                if (Config::set("maintenance_enabled", false)) {
                    $minimal->info("Maintenance Mode successfully disabled.");
                }

                return true;
            } else {
                $minimal->error("Failed to install theme");
                if (Config::set("maintenance_enabled", false)) {
                    $minimal->info("Maintenance Mode successfully disabled.");
                }

                return false;
            }
        } elseif ($options->getOpt("uninstall")) {

            if (!Themes::isInstalled()) {
                $minimal->error("Theme is not installed");

                return false;
            }
            if (!Themes::isValid()) {
                $minimal->error("Theme is not mounted. Check your Docker Configuration");

                return false;
            }
            if (Themes::uninstall()) {
                $minimal->success("Theme successfully uninstalled");

                return true;
            }

            $minimal->error("Theme failed to uninstall");

            return false;
        } elseif ($options->getOpt("boot")) {

            if (!Themes::isInstalled()) {
                $minimal->error("Theme is not installed");

                return false;
            }
            if (!Themes::isValid()) {
                $minimal->error("Theme is not mounted. Check your Docker Configuration");

                return false;
            }
            if (!Themes::loadBootFiles()) {

                $minimal->error("Failed loading boot files!");

                return false;
            }
            $minimal->success("Theme Boot Files executed!");

            return true;
        } elseif ($options->getOpt("migrate")) {

            if (!Themes::isInstalled()) {
                $minimal->error("Theme is not installed");

                return false;
            }
            if (!Themes::isValid()) {
                $minimal->error("Theme is not mounted. Check your Docker Configuration");

                return false;
            }
            $Migrations = new core\Migrations();

            $Migrations->migrate(Themes::getThemeDirectory());

            return true;
        }

        if (Themes::isInstalled()) {
            $minimal->success("Theme is installed!");

            return true;
        }
        $minimal->alert("Theme is not installed!");

        return true;
    }
}
