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

    private Environment $TwigTheme;
    public string $CurrentFile;
    public string $CurrentPage;

    /**
     * Add an item to the theme's navigation bar
     * @param string $ID Unique string to identify the item
     * @param string $Text The HTML of the navbar item
     * @param string $Link The Link of the navbar item
     * @param string $Target HTML a=target
     * @param int $Order The order to appear on the navbar
     * @param string $Placement Placed left or right of the navbar if supported by theme
     * @return boolean
     * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/a#target Link Target
     */
    public static function addToNavbar(string $ID, string $Text, string $Link, string $Target = "_self", int $Order = 0, string $Placement = "left"): bool
    {
        if ($Placement === "right") {

            $GLOBALS["navbar_right"][$ID] = array("ID" => $ID, "html" => $Text, "href" => $Link, "target" => $Target, "order" => $Order);

            usort($GLOBALS["navbar_right"], static function ($a, $b) {
                return $a['order'] <=> $b['order'];
            });
            return true;
        }
        $GLOBALS["navbar"][$ID] = array("ID" => $ID, "html" => $Text, "href" => $Link, "target" => $Target, "order" => $Order);

        usort($GLOBALS["navbar"], static function ($a, $b) {
            return $a['order'] <=> $b['order'];
        });
        return true;
    }

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
    public function __construct(Environment $TwigTheme, string $CurrentFile, string $CurrentPage, bool $Internal = false)
    {
        $this->TwigTheme = $TwigTheme;
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


            $HookClass->preRender($CurrentPage, $CurrentFile);
            $PageClass->preRender();


            $GLOBALS["microtime"]["logic"]["end"] = microtime(true);
            $GLOBALS["microtime"]["template"]["start"] = microtime(true);
            $TwigTheme->addGlobal("LogicMicroTime", ($GLOBALS["microtime"]["logic"]["end"] - $GLOBALS["microtime"]["logic"]["start"]));
            header("X-CMS-LogicTime: " . ($GLOBALS["microtime"]["logic"]["end"] - $GLOBALS["microtime"]["logic"]["start"]));
            echo $TwigTheme->render("views/$CurrentPage.twig", ThemeVariables::getAll());

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
