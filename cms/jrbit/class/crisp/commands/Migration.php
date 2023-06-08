<?php

namespace crisp\commands;

use CLI;
use crisp\core\Migrations;
use crisp\core\Themes;
use splitbrain\phpcli\Options;

class Migration {
    public static function run(CLI $minimal, Options $options): bool
    {
        if($options->getOpt("theme")){
            Migrations::create($options->getArgs()[0], Themes::getThemeDirectory());
            return true;
        }elseif($options->getOpt("core")){
            Migrations::create($options->getArgs()[0]);
            return true;
        }
        $minimal->error("You must pass either the --theme or --core parameter!");

        return true;
    }
}