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

use PhpCsFixer\Console\Application;

class CLI
{
    public static function get(): Application
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }

        return $GLOBALS['Crisp_CLI'];
    }

    private static function registerCrispCLI(): Application
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        $application = self::get();

        $iterator = new \DirectoryIterator(__DIR__ . '/../CommandControllers/');
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile()) {
                $fileName = $fileInfo->getFilename();

                $className = pathinfo($fileName, PATHINFO_FILENAME);
                Logger::getLogger(__METHOD__)->debug("Loading command $className");

                $namespace = '\\crisp\\CommandControllers\\';
                $constructedClass = $namespace . $className;
                $application->add(new $constructedClass());

                Logger::getLogger(__METHOD__)->debug("Loaded command $className");
            }
        }

        return $application;
    }

    public static function register(): Application
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        $GLOBALS['Crisp_CLI'] = new Application();
        self::registerCrispCLI();

        return $GLOBALS['Crisp_CLI'];
    }
}
