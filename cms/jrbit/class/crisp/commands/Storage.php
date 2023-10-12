<?php

namespace crisp\commands;

use CLI;
use crisp\api\Config;
use crisp\api\Helper;
use crisp\core;
use crisp\core\Logger;
use crisp\core\Migrations;
use crisp\core\Themes;
use Minimal;
use splitbrain\phpcli\Options;

class Storage {
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


            
            Logger::startTiming($Timing);
            if (Themes::installKVStorage((bool)$options->getOpt("force"))) {
                $minimal->success("KVStorage successfully installed!");
            } else {
                $minimal->error("Failed to install KVStorage!");
            }
            Helper::Log(core\LogTypes::DEBUG, sprintf("Operation took %sms to complete!", Logger::endTiming($Timing)));

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


            Logger::startTiming($Timing);
            if (Themes::uninstallKVStorage()) {
                $minimal->success("KVStorage successfully uninstalled!");
            } else {
                $minimal->error("Failed to uninstall KVStorage!");
            }
            Helper::Log(core\LogTypes::DEBUG, sprintf("Operation took %sms to complete!", Logger::endTiming($Timing)));

            return true;
        }
        echo $options->help();

        return true;
    }
}