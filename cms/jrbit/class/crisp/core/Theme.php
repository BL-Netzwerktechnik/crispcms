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
use crisp\exceptions\BitmaskException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use crisp\routes;
use Twig\Error\SyntaxError;

/**
 * Used internally, plugin loader
 *
 */
class Theme
{

    use Hook;

    public string $CurrentFile;
    public string $CurrentPage;

    /**
     * Load a theme page
     * @param Environment $TwigTheme The twig theme component
     * @param string $CurrentFile The current file, __FILE__
     * @param string $CurrentPage The current page template to render
     * @throws BitmaskException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws \Exception
     */
    public function __construct(string $CurrentFile, string $CurrentPage, bool $Internal = false)
    {
        $this->CurrentFile = $CurrentFile;
        $this->CurrentPage = $CurrentPage;


        $HookClass = null;

        $_HookFile = ThemeMetadata->hookFile;
        $_HookClass = substr($_HookFile, 0, -4);

        if ($Internal) {

            require __DIR__ . "/../routes/$CurrentPage.php";


            $Class = "crisp\\routes\\$CurrentPage";

            if (class_exists($Class, false)) {
                $PageClass = new $Class();

                if (!method_exists($PageClass, 'execute')) {
                    throw new \Exception("Failed to load $Class, missing execute!");
                }
            } else {
                throw new \Exception("Failed to load $Class, missing class!");
            }

            $PageClass->execute($CurrentPage);
        } elseif (Helper::templateExists("/views/$CurrentPage.twig")) {


            require_once Themes::getThemeDirectory() . "/$_HookFile";

            if (class_exists($_HookClass, false)) {
                $HookClass = new $_HookClass();
            }
    
            if ($HookClass !== null && !method_exists($HookClass, 'preRender')) {
                throw new \Exception("Failed to load $_HookClass, missing preRender!");
            }
    
            

            if (file_exists(Themes::getThemeDirectory() . "/includes/$CurrentPage.php")) {
                require Themes::getThemeDirectory() . "/includes/$CurrentPage.php";
            }
            $PageClass = null;

            if (class_exists($CurrentPage, false)) {
                $PageClass = new $CurrentPage();
            }



            if ($PageClass !== null && !method_exists($PageClass, 'preRender')) {
                throw new \Exception("Failed to load $CurrentPage, missing preRender!");
            }


            Logger::getLogger(__METHOD__)->debug(sprintf("START executing preRender hooks for HookFile"));
            Logger::startTiming($HookClassRenderTime);
            $HookClass->preRender($CurrentPage, $CurrentFile);
            Logger::getLogger(__METHOD__)->debug(sprintf("DONE executing preRender hooks for HookFile - Took %s ms", Logger::endTiming($HookClassRenderTime)));


            

            Logger::getLogger(__METHOD__)->debug(sprintf("START executing preRender hooks for %s", $CurrentPage));
            Logger::startTiming($PageClassRenderTime);
            $PageClass->preRender();
            Logger::getLogger(__METHOD__)->debug(sprintf("DONE executing preRender hooks for %s - Took %s ms", $CurrentPage, Logger::endTiming($PageClassRenderTime)));
            echo Themes::render("views/$CurrentPage.twig", ThemeVariables::getAll());

            if ($PageClass !== null && !method_exists($PageClass, 'postRender')) {
                throw new \Exception("Failed to load $CurrentPage, missing postRender!");
            }
            if ($HookClass !== null && !method_exists($HookClass, 'postRender')) {
                throw new \Exception("Failed to load HookFile, missing postRender!");
            }
            $HookClass->postRender($CurrentPage, $CurrentFile);
            $PageClass->postRender();
        } else {
            throw new BitmaskException("Failed to load template " . $this->CurrentPage . ": Missing includes file", Bitmask::THEME_MISSING_INCLUDES);
        }
    }
}
