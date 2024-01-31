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

use crisp\core;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;

/**
 * I like the way phpcli outputs the log, so lets initiate the abstract class.
 */
class Logger
{
    public static function overrideLogLevel(string $logLevel = "INFO"): void
    {
        $_ENV["LOG_LEVEL"] = $logLevel;
    }
    
    public static function getLogLevel(): string
    {
        return $_ENV["LOG_LEVEL"] ?? "INFO";
    }

    public static function startTiming(float &$output = null): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $output = microtime(true);
    }

    public static function getLogger(string $name): MonologLogger
    {
        $logger = new MonologLogger($name);
        $logger->pushHandler(new StreamHandler('php://stdout', Level::fromName(self::getLogLevel())));
        if (Level::fromName(self::getLogLevel()) > Level::Debug) {
            $logger->pushHandler(new RotatingFileHandler(core::LOG_DIR . sprintf("/%s.log", self::getLogLevel()), 7, Level::fromName(self::getLogLevel())));
        }

        return $logger;
    }

    public static function endTiming(float &$start): string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $ms = (float) (microtime(true) - $start);
        unset($start);

        return sprintf("%.3f", $ms * 1000);
    }
}
