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
use Twig\Error\SyntaxError;

/**
 * Used internally, plugin loader
 *
 */
class Theme {

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

            usort($GLOBALS["navbar_right"], static function($a, $b) {
                return $a['order'] <=> $b['order'];
            });
            return true;
        }
        $GLOBALS["navbar"][$ID] = array("ID" => $ID, "html" => $Text, "href" => $Link, "target" => $Target, "order" => $Order);

        usort($GLOBALS["navbar"], static function($a, $b) {
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
     */
    public function __construct(Environment $TwigTheme, string $CurrentFile, string $CurrentPage) {
        $this->TwigTheme = $TwigTheme;
        $this->CurrentFile = $CurrentFile;
        $this->CurrentPage = $CurrentPage;


        $HookClass = null;
        $_vars = ($_vars ?? []);

            if ($GLOBALS['flagsmith_server']->isFeatureEnabledByIdentity($GLOBALS['flagsmith_server_identity'], 'theme_hooks_enabled')) {
                $_HookFile = Themes::getThemeMetadata()->hookFile;
                $_HookClass = substr($_HookFile, 0, -4);

                require_once __DIR__ . "/../../../../".\crisp\api\Config::get("theme_dir")."/".\crisp\api\Config::get("theme")."/$_HookFile";

                if(class_exists($_HookClass, false)){
                    $HookClass = new $_HookClass();
                }

                if($HookClass !== null && method_exists($HookClass, 'preRender')){
                    $_vars = array_merge($_vars, $HookClass->preRender($_vars, $TwigTheme, $CurrentPage, $CurrentFile) ?? []);
                }
        }



        if (Helper::templateExists(\crisp\api\Config::get("theme"), "/views/$CurrentPage.twig")) {

                if(file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/includes/$CurrentPage.php")) {
                    require __DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/includes/$CurrentPage.php";
                }
                $PageClass = null;

                if(class_exists($CurrentPage, false)){
                    $PageClass = new $CurrentPage();
                }


                $_vars["template"] = $this;


                if($PageClass !== null && method_exists($PageClass, 'preRender')){
                    $_vars = array_merge($_vars, $PageClass->preRender($_vars, $TwigTheme) ?? []);
                }


                $GLOBALS["microtime"]["logic"]["end"] = microtime(true);
                $GLOBALS["microtime"]["template"]["start"] = microtime(true);
                $TwigTheme->addGlobal("LogicMicroTime", ($GLOBALS["microtime"]["logic"]["end"] - $GLOBALS["microtime"]["logic"]["start"]));
                header("X-CMS-LogicTime: " . ($GLOBALS["microtime"]["logic"]["end"] - $GLOBALS["microtime"]["logic"]["start"]));
                echo $TwigTheme->render("views/$CurrentPage.twig", $_vars);

                if($PageClass !== null && method_exists($PageClass, 'postRender')){
                    $PageClass->postRender($_vars, $TwigTheme);
                }
                if($HookClass !== null && method_exists($HookClass, 'postRender')){
                    $HookClass->postRender($_vars, $TwigTheme, $CurrentPage, $CurrentFile);
                }
        } else {
            throw new BitmaskException("Failed to load template " . $this->CurrentPage . ": Missing includes file", Bitmask::THEME_MISSING_INCLUDES);
        }
    }

}
