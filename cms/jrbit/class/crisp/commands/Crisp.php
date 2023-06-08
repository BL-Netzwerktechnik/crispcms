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

class Crisp {
    public static function run(CLI $minimal, Options $options): bool
    {
        if($options->getOpt("migrate")){
            $Migrations = new core\Migrations();
            $Migrations->migrate();
            return true;
        }
        $minimal->error("No action");

        return true;
    }
}