<?php

namespace crisp\commands;

use crisp\core\Logger;
use crisp\core\Migrations;
use crisp\core\Themes;
use splitbrain\phpcli\Options;

class Migration
{
    public static function run(\CLI $minimal, Options $options): bool
    {

        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        if ($options->getOpt("theme")) {
            Migrations::create($options->getArgs()[0], Themes::getThemeDirectory());

            return true;
        } elseif ($options->getOpt("core")) {
            Migrations::create($options->getArgs()[0]);

            return true;
        }
        $minimal->error("You must pass either the --theme or --core parameter!");

        return true;
    }
}
