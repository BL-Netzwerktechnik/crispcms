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


use crisp\api\Config;
use crisp\api\Helper;
use crisp\api\lists\Languages;
use crisp\core\Migrations;
use crisp\core\Themes;

const CRISP_CLI = true;
const CRISP_API = true;

if (PHP_SAPI !== 'cli') {
    Helper::Log(1, "Not from CLI");
    exit;
}


error_reporting(error_reporting() & ~E_NOTICE);
require_once __DIR__ . "/../jrbit/core.php";

switch ($argv[1]) {

    case "check_translations":

        $ThemeMetadata = Themes::getThemeMetadata();
        $ThemeFolder = Config::get("theme_dir");
        $ThemeName = Config::get("theme");

        if(!file_exists(__DIR__ . "/../../../../$ThemeFolder/$ThemeName/" . $ThemeMetadata->onInstall->createTranslationKeys . "/en.json")){
            if (defined("CRISP_CLI")) {
                echo "ERR: Source Language Not found!" . PHP_EOL;
            }
            exit;
        }

        $masterLang = file_get_contents(__DIR__ . "/../../../../$ThemeFolder/$ThemeName/" . $ThemeMetadata->onInstall->createTranslationKeys . "/en.json");
        $masterLang = json_decode($masterLang, true, 512, JSON_THROW_ON_ERROR);

        if (isset($ThemeMetadata->onInstall->createTranslationKeys) && is_string($ThemeMetadata->onInstall->createTranslationKeys)) {

            if (file_exists(__DIR__ . "/../../../../$ThemeFolder/$ThemeName/" . $ThemeMetadata->onInstall->createTranslationKeys)) {

                $files = glob(__DIR__ . "/../../../../$ThemeFolder/$ThemeName/" . $ThemeMetadata->onInstall->createTranslationKeys . "*.{json}", GLOB_BRACE);
                foreach ($files as $File) {
                    if (!file_exists($File)) {
                        if (defined("CRISP_CLI")) {
                            echo "ERR: $File Not found!" . PHP_EOL;
                        }
                        continue;
                    }


                    $lang = file_get_contents($File);
                    $lang = json_decode($lang, true, 512, JSON_THROW_ON_ERROR);
                    $langCode = substr(basename($File), 0, -5);
                    $result = Helper::array_diff_key_recursive($masterLang, $lang);

                    if(empty($result)) {
                        Helper::Log(2, "Language up to date");
                        continue;
                    }

                    foreach($result as $key => $val) {
                        // check if section key exists in target lang
                        if(array_key_exists($key, $lang)) {
                            // add only missing section keys
                            foreach ($val as $k => $v) {
                                $lang[$key][$k] = $v;
                            }
                            // sort keys
                            ksort($lang[$key]);
                        } else {
                            // add whole section
                            $lang[$key] = $val;
                            ksort($lang);
                        }
                    }

                    $lang = json_encode($lang, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    file_put_contents(__DIR__ . "/../../../../$ThemeFolder/$ThemeName/" . $ThemeMetadata->onInstall->createTranslationKeys . "/$langCode", $lang);

                    Helper::Log(2, $result);
                }
            }
            return true;
        }

        Helper::Log(1, "Array based translation keys are no longer supported. Please switch to directory based ones.");

        break;
    case "release":

        echo RELEASE;
        break;

    case "cache":

        if ($argc < 3) {
            Helper::Log(1, "Missing argument: action");
            exit;
        }

        switch ($argv[2]) {
            case "clear":
                Themes::clearCache();
                Helper::Log(2, "Cleared Cache!");
                break;
        }
        break;

    case "theme":
        if ($argc < 3) {
            Helper::Log(1, "Missing argument: enable/disable/reinstall/translations");
            exit;
        }

        switch ($argv[2]) {

            case "boot":
                if($argc < 4) {
                    Helper::Log(1, "Missing theme name");
                    exit;
                }

                if (!crisp\core\Themes::isInstalled($argv[3])) {
                    Helper::Log(1, "This theme is not installed");
                    exit;
                }
                if (!crisp\core\Themes::isValid($argv[3])) {
                    Helper::Log(1, "This theme does not exist");
                    exit;
                }
                
                if(!crisp\core\Themes::loadBootFiles()){
              
                    Helper::Log(1, "Failed loading boot files!");
                    exit;
                }
                
            break;
                    
                
            case "migrate":
                if ($argc < 4) {
                    Helper::Log(1, "Missing theme name");
                    exit;
                }

                if (!crisp\core\Themes::isInstalled($argv[3])) {
                    Helper::Log(1, "This theme is not installed");
                    exit;
                }
                if (!crisp\core\Themes::isValid($argv[3])) {
                    Helper::Log(1, "This theme does not exist");
                    exit;
                }

                $Migrations = new crisp\core\Migrations();
                $Theme = Themes::getThemeMetadata($argv[3]);

                $Migrations->migrate(__DIR__ . '/../' . Config::get('theme_dir') . '/' . $argv[3], $argv[3]);

                break;

            case "reload":
            case "refresh":
                if ($argc < 4) {
                    Helper::Log(1, "Missing theme name");
                    exit;
                }

                if (!crisp\core\Themes::isInstalled($argv[3])) {
                    Helper::Log(1, "This theme is not installed");
                    exit;
                }
                if (!crisp\core\Themes::isValid($argv[3])) {
                    Helper::Log(1, "This theme does not exist");
                    exit;
                }


                crisp\core\Themes::installKVStorage(Themes::getThemeMetadata($argv[3]), isset($argv[4]) && $argv[4] === "overwrite");
                crisp\core\Themes::installTranslations($argv[3], Themes::getThemeMetadata($argv[3]));

                break;

            case "storage":

                if ($argc < 4) {
                    Helper::Log(1, "Missing argument: reinstall");
                    exit;
                }
                switch ($argv[3]) {
                    case "reinstall":
                    case "refresh":
                        if ($argc < 5) {
                            Helper::Log(1, "Missing theme name");
                            exit;
                        }
                        if (is_array(Helper::isValidPluginName($argv[4]))) {
                            Helper::Log(1, "Invalid Theme Name:\n" . var_export(Helper::isValidPluginName($argv[3]), true));
                            exit;
                        }
                        if (!crisp\core\Themes::isValid($argv[4])) {
                            Helper::Log(1, "This theme does not exist");
                            exit;
                        }
                        if (!crisp\core\Themes::isInstalled($argv[4])) {
                            Helper::Log(1, "This theme is not installed");
                            exit;
                        }
                        if (Config::set("maintenance_enabled", true)) {
                            Helper::Log(2, "Maintenance Mode successfully enabled.");
                        }
                        $Start = microtime(true);
                        if (Themes::installKVStorage(Themes::getThemeMetadata($argv[4]))) {
                            Helper::Log(2, "KV Storage refreshed!");
                        } else {
                            Helper::Log(1, "Failed to refresh KV Storage");
                        }
                        $End = microtime(true);
                        Helper::Log(3, "Took " . Helper::truncateText($End - $Start, 6, false) . "ms");

                        if (Config::set("maintenance_enabled", false)) {
                            Helper::Log(1, "Maintenance Mode successfully disabled.");
                        }
                        break;
                }
                break;
            case "translations":

                if ($argc < 4) {
                    Helper::Log(1, "Missing argument: reinstall");
                    exit;
                }
                switch ($argv[3]) {
                    case "reinstall":
                    case "refresh":
                        if ($argc < 5) {
                            Helper::Log(1, "Missing theme name");
                            exit;
                        }
                        if (is_array(Helper::isValidPluginName($argv[4]))) {
                            Helper::Log(1, "Invalid Theme Name:\n" . var_export(Helper::isValidPluginName($argv[3]), true));
                            exit;
                        }
                        if (!crisp\core\Themes::isValid($argv[4])) {
                            Helper::Log(1, "This theme does not exist");
                            exit;
                        }
                        if (!crisp\core\Themes::isInstalled($argv[4])) {
                            Helper::Log(1, "This theme is not installed");
                            exit;
                        }
                        if (Config::set("maintenance_enabled", true)) {
                            Helper::Log(2, "Maintenance Mode successfully enabled.");
                        }
                        $Start = microtime(true);
                        if (Themes::installTranslations($argv[4], Themes::getThemeMetadata($argv[4]))) {
                            Helper::Log(2, "Translations refreshed!");
                        } else {
                            Helper::Log(1, "Failed to refresh translations");
                        }
                        $End = microtime(true);
                        Helper::Log(3,  "Took " . Helper::truncateText($End - $Start, 6, false) . "ms");
                        if (Config::set("maintenance_enabled", false)) {
                            Helper::Log(2,  "Maintenance Mode successfully disabled.");
                        }
                        break;
                }
                break;
            case "add":
            case "install":
            case "enable":
                if ($argc < 4) {
                    Helper::Log(1, "Missing theme name");
                    exit;
                }
                if (is_array(Helper::isValidPluginName($argv[3]))) {
                    Helper::Log(1, "Invalid Theme Name:\n" . var_export(Helper::isValidPluginName($argv[3]), true));
                    exit;
                }

                if (crisp\core\Themes::isInstalled($argv[3])) {
                    Helper::Log(1, "This theme is already installed");
                    exit;
                }
                if (!crisp\core\Themes::isValid($argv[3])) {
                    Helper::Log(1, "This theme does not exist");
                    exit;
                }
                if (Config::set("maintenance_enabled", true)) {
                    Helper::Log(2, "Maintenance Mode successfully enabled.");
                }

            Helper::Log(2, "Installing Theme ". $argv[3]);

                if (crisp\core\Themes::install($argv[3])) {
                    Helper::Log(2, "Theme successfully installed");
                } else {
                    Helper::Log(1, "Failed to install theme");
                }
                if (Config::set("maintenance_enabled", false)) {
                    Helper::Log(2, "Maintenance Mode successfully disabled.");
                }
                break;
            case "uninstall":
            case "remove":
            case "delete":
            case "disable":
                if ($argc < 4) {
                    echo "Missing theme name" . PHP_EOL;
                    exit;
                }
                if (!crisp\core\Themes::isInstalled($argv[3])) {
                    echo "This theme is not installed" . PHP_EOL;
                    exit;
                }
                if (!crisp\core\Themes::isValid($argv[3])) {
                    echo "This theme does not exist" . PHP_EOL;
                    exit;
                }
                if (crisp\core\Themes::uninstall($argv[3])) {
                    echo "Theme successfully uninstalled" . PHP_EOL;
                    exit;
                }
                echo "Failed to uninstall theme" . PHP_EOL;
                break;
            case "reinstall":
                if ($argc < 4) {
                    echo "Missing theme name" . PHP_EOL;
                    exit;
                }
                if (!crisp\core\Themes::isInstalled($argv[3])) {
                    echo "This theme is not installed" . PHP_EOL;
                    exit;
                }
                if (!crisp\core\Themes::isValid($argv[3])) {
                    echo "This theme does not exist" . PHP_EOL;
                    exit;
                }
                if (Config::set("maintenance_enabled", true)) {
                    echo "Maintenance Mode successfully enabled." . PHP_EOL;
                }
                if (crisp\core\Themes::reinstall($argv[3], Config::get("theme"), __FILE__, "cli")) {
                    echo "Theme successfully reinstalled" . PHP_EOL;
                } else {
                    echo "Failed to reinstall theme" . PHP_EOL;
                }
                if (Config::set("maintenance_enabled", false)) {
                    echo "Maintenance Mode successfully disabled." . PHP_EOL;
                }
                break;
        }
        break;
    case "maintenance":

        if ($argc < 3) {
            echo "Missing argument: enable/disable" . PHP_EOL;
        }

        switch ($argv[2]) {
            case "enable":
            case "on":
            case "true":
                if (Config::set("maintenance_enabled", true)) {
                    echo "Maintenance Mode successfully enabled." . PHP_EOL;
                    exit;
                }
                echo "Failed to enable maintenance mode" . PHP_EOL;
                break;
            case "false":
            case "disable":
            case "off":
                if (Config::set("maintenance_enabled", false)) {
                    echo "Maintenance Mode successfully disabled." . PHP_EOL;
                    exit;
                }
                echo "Failed to disable maintenance mode" . PHP_EOL;
                break;
            default:
                if (Config::get("maintenance_enabled")) {
                    echo "Maintenance Mode is currently enabled!" . PHP_EOL;
                } else {
                    echo "Maintenance Mode is currently disabled." . PHP_EOL;
                }
                break;
        }
        break;
    case "create_migration":


        if ($argc < 3) {
            echo "Missing argument: migration name" . PHP_EOL;
            exit;
        }
        Migrations::create($argv[2]);
        break;
    case "migrate":
        $Migrations = new crisp\core\Migrations();
        $Migrations->migrate();
        break;
    default:
        echo "Crisp CLI" . PHP_EOL;
        echo "---------" . PHP_EOL;
        echo "create_migration - Create a new migration file" . PHP_EOL;
        echo "migrate - Migrate MySQL Tables" . PHP_EOL;
        echo "---------" . PHP_EOL;
        echo "cache - Actions regarding the cache" . PHP_EOL;
        echo "cache clear - Clear twig cache" . PHP_EOL;
        echo "---------" . PHP_EOL;
        echo "export - Export various stuff as json to stdout" . PHP_EOL;
        echo "export translations - Export all translations" . PHP_EOL;
        echo "export translations {LanguageKey} - Export all translations by specific language" . PHP_EOL;
        echo "---------" . PHP_EOL;
        echo "import - Import various stuff from files" . PHP_EOL;
        echo "import translations {File} - Import all translations from file" . PHP_EOL;
        echo "---------" . PHP_EOL;
        echo "theme - Manage themes on Crisp" . PHP_EOL;
        echo "theme enable {ThemeName} - Enable a specific theme" . PHP_EOL;
        echo "theme add {ThemeName} - Enable a specific theme" . PHP_EOL;
        echo "theme install {ThemeName} - Enable a specific theme" . PHP_EOL;
        echo "theme disable {ThemeName} - Disable a specific theme" . PHP_EOL;
        echo "theme delete {ThemeName} - Disable a specific theme" . PHP_EOL;
        echo "theme remove {ThemeName} - Disable a specific theme" . PHP_EOL;
        echo "theme uninstall {ThemeName} - Disable a specific theme" . PHP_EOL;
        echo "theme storage - Interact with the kv storage of a theme" . PHP_EOL;
        echo "theme storage reinstall {ThemeName} - Reinstall the KV Storage of a theme" . PHP_EOL;
        echo "theme storage refresh {ThemeName} - Reinstall the KV Storage of a theme" . PHP_EOL;
        echo "theme translations - Interact with the translations of a theme" . PHP_EOL;
        echo "theme refresh {ThemeName} - Refresh a theme without uninstalling it" . PHP_EOL;
        echo "theme translations reinstall {ThemeName} - Reinstall the translations of a theme" . PHP_EOL;
        echo "theme translations refresh {ThemeName} - Reinstall the translations of a theme" . PHP_EOL;

        echo "---------" . PHP_EOL;
        echo "maintenance - Manage maintenance mode on crisp" . PHP_EOL;
        echo "maintenance enable - Enable the maintenance mode" . PHP_EOL;
        echo "maintenance on - Enable the maintenance mode" . PHP_EOL;
        echo "maintenance disable - Enable the maintenance mode" . PHP_EOL;
        echo "maintenance off - Enable the maintenance mode" . PHP_EOL;
        echo "maintenance status - Get the status of the maintenance mode" . PHP_EOL;

}
