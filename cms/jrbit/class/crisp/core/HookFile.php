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

use crisp\Controllers\EventController;
use crisp\Events\ThemeEvents;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Used internally, theme loader.
 */
class HookFile
{
    public static function loadHookFile()
    {

        if ($GLOBALS['crisp_hookFile']) {
            return $GLOBALS['crisp_hookFile'];
        }

        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $_HookFile = Themes::getThemeMetadata()->hookFile;

        include_once Themes::getThemeDirectory() . "/$_HookFile";

        $_HookClass = substr($_HookFile, 0, -4);
        if (file_exists(Themes::getThemeDirectory() . "/$_HookFile") && class_exists($_HookClass, false)) {
            $GLOBALS['crisp_hookFile'] = new $_HookClass();
            return $GLOBALS['crisp_hookFile'];
        }

        return null;
    }

    public static function preRender(): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $HookClass = self::loadHookFile();
        Logger::getLogger(__METHOD__)->debug(sprintf("START executing preRender hooks for HookFile"));
        Logger::startTiming($HookClassRenderTime);
        if ($HookClass !== null && method_exists($HookClass, 'preRender')) {
            $HookClass->preRender();
        }
        Logger::getLogger(__METHOD__)->debug(sprintf("DONE executing preRender hooks for HookFile - Took %s ms", Logger::endTiming($HookClassRenderTime)));
    }

    public static function postRender(): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $HookClass = self::loadHookFile();
        Logger::getLogger(__METHOD__)->debug(sprintf("START executing postRender hooks for HookFile"));
        Logger::startTiming($HookClassRenderTime);
        if ($HookClass !== null && method_exists($HookClass, 'postRender')) {
            $HookClass->postRender();
        }
        Logger::getLogger(__METHOD__)->debug(sprintf("DONE executing postRender hooks for HookFile - Took %s ms", Logger::endTiming($HookClassRenderTime)));
    }

    public static function postExecute(): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $HookClass = self::loadHookFile();

        Logger::getLogger(__METHOD__)->debug(sprintf("START executing postExecute hooks for HookFile"));
        Logger::startTiming($HookClassRenderTime);
        EventController::getEventDispatcher()->dispatch(new Event(), ThemeEvents::POST_EXECUTE);
        if ($HookClass !== null && method_exists($HookClass, 'postExecute')) {
            $HookClass->postExecute();
        }
        Logger::getLogger(__METHOD__)->debug(sprintf("DONE executing postExecute hooks for HookFile - Took %s ms", Logger::endTiming($HookClassRenderTime)));
    }

    public static function preExecute(): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $HookClass = self::loadHookFile();

        Logger::getLogger(__METHOD__)->debug(sprintf("START executing preExecute hooks for HookFile"));
        Logger::startTiming($HookClassRenderTime);
        EventController::getEventDispatcher()->dispatch(new Event(), ThemeEvents::PRE_EXECUTE);
        if ($HookClass !== null && method_exists($HookClass, 'preExecute')) {
            $HookClass->preExecute();
        }
        Logger::getLogger(__METHOD__)->debug(sprintf("DONE executing preExecute hooks for HookFile - Took %s ms", Logger::endTiming($HookClassRenderTime)));
    }

    public static function setup(): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $HookClass = self::loadHookFile();

        Logger::getLogger(__METHOD__)->debug(sprintf("START executing setup hooks for HookFile"));
        Logger::startTiming($HookClassRenderTime);
        EventController::getEventDispatcher()->dispatch(new Event(), ThemeEvents::SETUP);

        if ($HookClass !== null && method_exists($HookClass, 'setup')) {
            $HookClass->setup();
        }
        Logger::getLogger(__METHOD__)->debug(sprintf("DONE executing setup hooks for HookFile - Took %s ms", Logger::endTiming($HookClassRenderTime)));
    }

    public static function setupCli(): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $HookClass = self::loadHookFile();

        Logger::getLogger(__METHOD__)->debug(sprintf("START executing setupCli hooks for HookFile"));
        Logger::startTiming($HookClassRenderTime);
        EventController::getEventDispatcher()->dispatch(new Event(), ThemeEvents::SETUP_CLI);
        if ($HookClass !== null && method_exists($HookClass, 'setupCli')) {
            $HookClass->setupCli();
        }
        Logger::getLogger(__METHOD__)->debug(sprintf("DONE executing setupCli hooks for HookFile - Took %s ms", Logger::endTiming($HookClassRenderTime)));
    }
}
