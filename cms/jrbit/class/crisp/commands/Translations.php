<?php

namespace crisp\commands;

use CLI;
use crisp\api\Config;
use crisp\api\Helper;
use crisp\core;
use crisp\core\Migrations;
use crisp\core\Themes;
use Minimal;
use splitbrain\phpcli\Options;

class Translations {
    public static function run(CLI $minimal, Options $options): bool
    {
        if($options->getOpt("install")){

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
            Helper::Log(core\LogTypes::DEBUG, sprintf("Operation took %sms to complete!", Helper::truncateText($End - $Start, 6, false)));

            return true;
        }elseif($options->getOpt("uninstall")){

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
            Helper::Log(core\LogTypes::DEBUG, sprintf("Operation took %sms to complete!", Helper::truncateText($End - $Start, 6, false)));

            return true;
        }
        echo $options->help();

        return true;
    }
}