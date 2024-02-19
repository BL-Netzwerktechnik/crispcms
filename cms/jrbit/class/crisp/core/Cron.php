<?php

/*
 * Copyright (c) 2021. JRB IT, All Rights Reserved
 *
 *  @author Justin René Back <j.back@jrbit.de>
 *  @link https://github.com/jrbit/crispcms
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace crisp\core;

use CLI as GlobalCLI;
use crisp\api\Helper;
use crisp\api\License;
use crisp\commands\Assets;
use crisp\commands\Crisp;
use crisp\commands\License as CommandsLicense;
use crisp\commands\Maintenance;
use crisp\commands\Migration;
use crisp\commands\Storage;
use crisp\commands\Theme;
use crisp\commands\Translations;
use crisp\commands\Version;
use crisp\cron\License as CronLicense;
use GO\Scheduler;
use Minimal;
use splitbrain\phpcli\CLI as SplitbrainCLI;
use splitbrain\phpcli\Options;


class Cron
{

    public static function get(): Scheduler
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        return $GLOBALS["Crisp_Cron"];
    }

    public static function registerJob(string $tab, mixed $class = null, string $callable = null)
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        self::get()->call([$class, $callable])->at($tab)->output("/var/log/crisp/scheduler.log", true);
    }

    public static function registerJobRaw(string $tab, string $command = null)
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        self::get()->raw($command)->at($tab)->output("/var/log/crisp/scheduler.log", true);
    }

    public static function registerInternals(): void
    {
        self::registerJob("*/15 * * * *", CronLicense::class, "pull");
    }

    public static function register(): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $GLOBALS["Crisp_Cron"] = new Scheduler();


        self::registerInternals();

    }
}
