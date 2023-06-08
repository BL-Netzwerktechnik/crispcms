<?php

namespace crisp\commands;

use CLI;
use crisp\api\Config;
use crisp\api\Helper;
use crisp\core;
use splitbrain\phpcli\Options;

class Maintenance {
    public static function run(CLI $minimal, Options $options): bool
    {


        if($options->getOpt("on")){
            if (Config::set("maintenance_enabled", true)) {
                $minimal->success("Maintenance Mode successfully enabled.");
                return true;
            }else{
                $minimal->error("Maintenance Mode could not be enabled.");
                return false;
            }
        }elseif($options->getOpt("off")){
            if (Config::set("maintenance_enabled", false)) {
                $minimal->success("Maintenance Mode successfully disabled.");
                return true;
            }else{
                $minimal->error("Maintenance Mode could not be disabled.");
                return false;
            }
        }

        if(Config::get("maintenance_enabled")){
            $minimal->alert("Maintenance Mode is currently enabled");
            return true;
        }
        $minimal->success("Maintenance Mode is currently disabled");
        return true;
    }
}