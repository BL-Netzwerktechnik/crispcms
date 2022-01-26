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
use Twig\Environment;

/**
 * Used internally, plugin loader
 *
 */
class RESTfulAPI
{

    use Hook;

    public string $Interface;
    public string $ThemePath;
    public Environment $TwigTheme;

    /**
     *
     * @param Environment $ThemeLoader
     * @param string $Interface
     * @param string $_QUERY
     */
    public function __construct(Environment $ThemeLoader, string $Interface)
    {
        $this->Interface = Helper::filterAlphaNum($Interface);
        $this->TwigTheme = $ThemeLoader;
        $this->ThemePath = realpath(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/");

        if (file_exists($this->ThemePath . "/includes/api/" . $this->Interface . ".php")) {
            require $this->ThemePath . "/includes/api/" . $this->Interface . ".php";
            exit;
        } else {
            if (file_exists($this->ThemePath . "/includes/api/_start.php") && $Interface == "api" ) {
                require $this->ThemePath . "/includes/api/_start.php";
                exit;
            }
            self::response(Bitmask::INTERFACE_NOT_FOUND->value, 'REST Interface not found!', []);
            exit;
        }
    }

    /**
     * Send a JSON response
     * @param array|bool|int $Errors Error array or false
     * @param string $message A message to send
     * @param array $Parameters Some response parameters
     * @param constant|null $Flags JSON_ENCODE constants
     * @throws \JsonException
     */
    public static function response(int $Errors = null, string $message, array $Parameters = [], constant $Flags = null, $HTTP = 200)
    {
        header("Content-Type: application/json");
        http_response_code($HTTP);
        echo json_encode(array("error" => $Errors ?? Bitmask::NONE->value, "message" => $message, "parameters" => $Parameters), JSON_THROW_ON_ERROR | $Flags);
    }

}
