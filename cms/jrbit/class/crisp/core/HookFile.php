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

use crisp\api\Helper;
use crisp\types\RouteType;
use Phroute\Phroute\Dispatcher;

/**
 * Used internally, theme loader
 *
 */
class HookFile
{

    public static function preRender(): void
    {
        $_HookFile = Themes::getThemeMetadata()->hookFile;
        $_HookClass = substr($_HookFile, 0, -4);

        require_once Themes::getThemeDirectory() . "/$_HookFile";

        if (class_exists($_HookClass, false)) {
            $HookClass = new $_HookClass();
        }

        if ($HookClass !== null && !method_exists($HookClass, 'preRender')) {
            throw new \Exception("Failed to load $_HookClass, missing preRender!");
        }
        Helper::Log(LogTypes::DEBUG, sprintf("START executing preRender hooks for HookFile"));
        Logger::startTiming($HookClassRenderTime);
        $HookClass->preRender();
        Helper::Log(LogTypes::DEBUG, sprintf("DONE executing preRender hooks for HookFile - Took %s ms", Logger::endTiming($HookClassRenderTime)));
    }
    public static function postRender(): void
    {
        $_HookFile = Themes::getThemeMetadata()->hookFile;
        $_HookClass = substr($_HookFile, 0, -4);

        require_once Themes::getThemeDirectory() . "/$_HookFile";

        if (class_exists($_HookClass, false)) {
            $HookClass = new $_HookClass();
        }

        if ($HookClass !== null && !method_exists($HookClass, 'postRender')) {
            throw new \Exception("Failed to load $_HookClass, missing postRender!");
        }
        Helper::Log(LogTypes::DEBUG, sprintf("START executing postRender hooks for HookFile"));
        Logger::startTiming($HookClassRenderTime);
        $HookClass->postRender();
        Helper::Log(LogTypes::DEBUG, sprintf("DONE executing postRender hooks for HookFile - Took %s ms", Logger::endTiming($HookClassRenderTime)));
    }

    public static function postExecute(): void
    {
        $_HookFile = Themes::getThemeMetadata()->hookFile;
        $_HookClass = substr($_HookFile, 0, -4);

        require_once Themes::getThemeDirectory() . "/$_HookFile";

        if (class_exists($_HookClass, false)) {
            $HookClass = new $_HookClass();
        }

        if ($HookClass !== null && !method_exists($HookClass, 'postExecute')) {
            throw new \Exception("Failed to load $_HookClass, missing postExecute!");
        }
        Helper::Log(LogTypes::DEBUG, sprintf("START executing postExecute hooks for HookFile"));
        Logger::startTiming($HookClassRenderTime);
        $HookClass->postExecute();
        Helper::Log(LogTypes::DEBUG, sprintf("DONE executing postExecute hooks for HookFile - Took %s ms", Logger::endTiming($HookClassRenderTime)));
    }

    public static function preExecute(): void
    {
        $_HookFile = Themes::getThemeMetadata()->hookFile;
        $_HookClass = substr($_HookFile, 0, -4);

        require_once Themes::getThemeDirectory() . "/$_HookFile";

        if (class_exists($_HookClass, false)) {
            $HookClass = new $_HookClass();
        }

        if ($HookClass !== null && !method_exists($HookClass, 'preExecute')) {
            throw new \Exception("Failed to load $_HookClass, missing preExecute!");
        }
        Helper::Log(LogTypes::DEBUG, sprintf("START executing preExecute hooks for HookFile"));
        Logger::startTiming($HookClassRenderTime);
        $HookClass->preExecute();
        Helper::Log(LogTypes::DEBUG, sprintf("DONE executing preExecute hooks for HookFile - Took %s ms", Logger::endTiming($HookClassRenderTime)));
    }

    public static function setup(): void
    {
        $_HookFile = Themes::getThemeMetadata()->hookFile;
        $_HookClass = substr($_HookFile, 0, -4);

        require_once Themes::getThemeDirectory() . "/$_HookFile";

        if (class_exists($_HookClass, false)) {
            $HookClass = new $_HookClass();
        }

        if ($HookClass !== null && !method_exists($HookClass, 'setup')) {
            throw new \Exception("Failed to load $_HookClass, missing setup!");
        }

        Helper::Log(LogTypes::DEBUG, sprintf("START executing setup hooks for HookFile"));
        Logger::startTiming($HookClassRenderTime);
        $HookClass->setup();
        Helper::Log(LogTypes::DEBUG, sprintf("DONE executing setup hooks for HookFile - Took %s ms", Logger::endTiming($HookClassRenderTime)));
    }
}