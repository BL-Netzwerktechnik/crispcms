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
use crisp\api\lists\Languages;
use crisp\api\Translation;
use Exception;
use PDOException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Sentry\Client;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use stdClass;
use Twig\Environment;
use function file_exists;
use function file_get_contents;
use function function_exists;
use function is_array;
use function is_callable;
use function is_object;
use function Sentry\captureException;
use function Sentry\configureScope;
use function Sentry\init;
use function serialize;

/**
 * Used internally, theme loader
 *
 */
class Themes
{

    use Hook;

    /**
     * Load API files and check if theme matches it.
     * @param Environment $ThemeLoader
     * @param string $Interface The interface we are listening on
     * @param string $_QUERY The query
     */
    public static function loadAPI(Environment $ThemeLoader, string $Interface): void
    {
        try {

            if (!empty($_ENV['SENTRY_DSN'])) {
                init([
                    'dsn' => $_ENV['SENTRY_DSN'],
                    'traces_sample_rate' => 1.0,
                    'environment' => ENVIRONMENT,
                    'release' => RELEASE,
                ]);

                configureScope(function (Scope $scope): void {
                    $scope->setTag('request_id', REQUEST_ID);
                });
            }


            Themes::autoload();
            new RESTfulAPI($ThemeLoader, $Interface);
        } catch (Exception $ex) {
            captureException($ex);
            throw new Exception($ex);
        }
    }

    /**
     * @param Environment $TwigTheme
     * @param string $CurrentFile
     * @param string $CurrentPage
     * @throws Exception
     */
    public static function load(Environment $TwigTheme, string $CurrentFile, string $CurrentPage): void
    {


        try {


            if (!empty($_ENV['SENTRY_DSN'])) {
                init([
                    'dsn' => $_ENV['SENTRY_DSN'],
                    'traces_sample_rate' => 1.0,
                    'environment' => ENVIRONMENT,
                    'release' => RELEASE,
                ]);

                configureScope(function (Scope $scope): void {
                    $scope->setTag('request_id', REQUEST_ID);
                });
            }


            Themes::autoload();
            if (count($GLOBALS["render"]) === 0) {
                if (file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/includes/$CurrentPage.php") && Helper::templateExists(\crisp\api\Config::get("theme"), "/views/$CurrentPage.twig")) {
                    new Theme($TwigTheme, $CurrentFile, $CurrentPage);
                } else {
                    $GLOBALS["microtime"]["logic"]["end"] = microtime(true);
                    $GLOBALS["microtime"]["template"]["start"] = microtime(true);
                    $TwigTheme->addGlobal("LogicMicroTime", ($GLOBALS["microtime"]["logic"]["end"] - $GLOBALS["microtime"]["logic"]["start"]));
                    http_response_code(404);
                    echo $TwigTheme->render("errors/notfound.twig", []);
                }
            }
        } catch (Exception $ex) {
            captureException($ex);
            error_log(var_export($ex, true));
            if (PHP_SAPI === 'cli') {
                var_dump($ex);
                exit(1);
            }
            http_response_code(500);
            $errorraw = file_get_contents(__DIR__ . '/../../../../themes/basic/error.html');

            if (defined('REQUEST_ID')) {
                $refid = REQUEST_ID;
            } else {
                $refid = 'Core';
            }

            if (IS_DEV_ENV) {
                $refid = $ex->getMessage();
            }


            if (IS_API_ENDPOINT && $GLOBALS['flagsmith_server']->isFeatureEnabledByIdentity($GLOBALS['flagsmith_server_identity'], 'enable_api')) {
                RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, 'Internal Server Error', ['reference_id' => $refid]);
                exit;
            }

            echo strtr($errorraw, ['{{ exception }}' => $refid, '{{ sentry_id }}' => SentrySdk::getCurrentHub()->getLastEventId()]);
            exit;
        }
    }

    public static function includeResource($File, bool $Prefix = true, $Theme = null): string
    {
        if (str_starts_with($File, "/")) {
            $File = substr($File, 1);
        }

        if ($Theme === null) {
            $Theme = \crisp\api\Config::get("theme");
        }

        if (!file_exists(__DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . \crisp\api\Config::get("theme") . "/$File")) {
            return ($Prefix ? "/" . \crisp\api\Config::get("theme_dir") . "/" . $Theme : "") . "/$File";
        }

        return "/" . ($Prefix ? \crisp\api\Config::get("theme_dir") . "/" . $Theme : "") . "/$File?" . hash_file("sha256", __DIR__ . "/../../../../" . \crisp\api\Config::get("theme_dir") . "/" . $Theme . "/$File");
    }

    public static function getThemeMetadata(string $ThemeName = null): stdClass|null
    {
        $ThemeFolder = \crisp\api\Config::get("theme_dir");

        if ($ThemeName == null) {
            $ThemeName = \crisp\api\Config::get('theme');
        }

        if (!self::isValid($ThemeName)) {
            return null;
        }

        return json_decode(file_get_contents(__DIR__ . "/../../../../$ThemeFolder/$ThemeName/theme.json"));

    }

    /**
     * @param string $ThemeName
     * @param stdClass $ThemeMetadata
     * @return bool
     */
    public static function refreshTranslations(string $ThemeName, stdClass $ThemeMetadata): bool
    {
        self::uninstallTranslations($ThemeMetadata);
        return self::installTranslations($ThemeName, $ThemeMetadata);
    }

    public static function uninstallTranslations($ThemeMetadata): bool
    {
        if (!is_object($ThemeMetadata) && !isset($ThemeMetadata->hookFile)) {
            return false;
        }
        if (defined("CRISP_CLI")) {
            echo "----------" . PHP_EOL;
            echo "Uninstalling translations" . PHP_EOL;
            echo "----------" . PHP_EOL;
        }
        try {
            $Configs = Translation::listTranslations();


            $Language = Languages::getLanguageByCode("de");

            foreach ($Configs as $Key => $Translation) {
                if (str_contains($Translation["key"], "plugin_")) {
                    continue;
                }

                if (defined("CRISP_CLI")) {
                    echo "Deleting key " . $Translation["key"] . PHP_EOL;
                }
                if ($Language->deleteTranslation($Translation["key"])) {
                    if (defined("CRISP_CLI")) {
                        echo "Deleted Key " . $Translation["key"] . PHP_EOL;
                    }
                }
            }
        } catch (PDOException) {

        }
        return true;
    }

    public static function refreshKVStorage(stdClass $ThemeMetadata): bool
    {
        self::uninstallKVStorage($ThemeMetadata);
        return self::installKVStorage($ThemeMetadata);
    }

    public static function uninstallKVStorage($ThemeMetadata): bool
    {
        if (!is_object($ThemeMetadata) && !isset($ThemeMetadata->hookFile)) {
            return false;
        }

        if (isset($ThemeMetadata->onInstall->createKVStorageItems) && is_object($ThemeMetadata->onInstall->createKVStorageItems)) {
            foreach ($ThemeMetadata->onInstall->createKVStorageItems as $Key => $Value) {
                \crisp\api\Config::delete($Key);
            }
        }
        return true;
    }

    /**
     * @param string $ThemeName
     * @return bool
     */
    public static function isValid(string $ThemeName): bool
    {
        $ThemeFolder = \crisp\api\Config::get("theme_dir");

        Helper::Log(3, "ThemeName: $ThemeName");
        Helper::Log(3, "ThemeFolder: $ThemeFolder");

        return file_exists(__DIR__ . "/../../../../$ThemeFolder/$ThemeName/theme.json");
    }

    /**
     * @param string $ThemeName
     * @return bool
     */
    public static function reinstall(string $ThemeName): bool
    {
        if (!self::uninstall($ThemeName)) {
            return false;
        }
        return self::install($ThemeName);
    }

    /**
     * @param string $ThemeName
     * @return bool
     */
    public static function uninstall(string $ThemeName): bool
    {

        $ThemeFolder = \crisp\api\Config::get("theme_dir");

        if (!self::isValid($ThemeName)) {
            return false;
        }

        self::clearCache();

        \crisp\api\Config::set("theme", null);

        $ThemeMetadata = self::getThemeMetadata($ThemeName);


        if (!is_object($ThemeMetadata) && !isset($ThemeMetadata->hookFile)) {
            return false;
        }
        self::performOnUninstall($ThemeName, $ThemeMetadata);


        self::broadcastHook("themeUninstall_$ThemeName", null);
        self::broadcastHook("themeUninstall", $ThemeName);
        return true;
    }

    /**
     * Clear the theme cache
     * @return boolean
     */
    public static function clearCache(): bool
    {
        if(!file_exists(__DIR__ . "/../../../cache/")){
            return false;
        }
        $it = new RecursiveDirectoryIterator(realpath(__DIR__ . "/../../../cache/"), RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it,
            RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        return true;
    }

    /**
     * @param string $ThemeName
     * @param stdClass $ThemeMetadata
     */
    private static function performOnUninstall(string $ThemeName, stdClass $ThemeMetadata)
    {
        if ($ThemeMetadata->onUninstall->deleteData) {
            self::deleteData($ThemeName);
        }
    }

    /**
     * Deletes all KVStorage Items from the Plugin
     *
     * If the theme is installed, it will get uninstalled first
     * @param string $ThemeName The folder name of the theme
     * @return boolean TRUE if the data has been successfully deleted
     */
    public static function deleteData(string $ThemeName): bool
    {

        if (self::isInstalled($ThemeName)) {
            return self::uninstall($ThemeName);
        }

        $ThemeFolder = \crisp\api\Config::get("theme_dir");

        if (!self::isValid($ThemeName)) {
            return false;
        }

        $ThemeMetadata = self::getThemeMetadata($ThemeName);

        self::uninstallKVStorage($ThemeMetadata);
        self::uninstallTranslations($ThemeMetadata);
    }

    /**
     * Registers an uninstall hook for your theme.
     * @param string $ThemeName
     * @param mixed $Function Callback function, either anonymous or a string to a function
     * @return bool
     */
    public static function registerUninstallHook(string $ThemeName, mixed $Function): bool
    {
        if (is_callable($Function) || function_exists($ThemeName)($Function)) {
            self::on("themeUninstall_$ThemeName", $Function);
            return true;
        }
        return false;
    }

    /**
     * Registers an install hook for your theme.
     * @param string $ThemeName
     * @param mixed $Function Callback function, either anonymous or a string to a function
     * @return bool
     */
    public static function registerInstallHook(string $ThemeName, mixed $Function): bool
    {
        if (is_callable($Function) || function_exists($ThemeName)($Function)) {
            self::on("themeInstall_$ThemeName", $Function);
            return true;
        }
        return false;
    }

    /**
     * @param string $ThemeName
     * @param stdClass $ThemeMetadata
     * @return bool
     */
    private static function performOnInstall(string $ThemeName, stdClass $ThemeMetadata): bool
    {
        if (!isset($ThemeMetadata->onInstall)) {
            return false;
        }

        self::installKVStorage($ThemeMetadata);
        self::installTranslations($ThemeName, $ThemeMetadata);

        return true;
    }

    /**
     * @param stdClass $ThemeMetadata
     * @param bool $Overwrite
     * @return bool
     */
    public static function installKVStorage(stdClass $ThemeMetadata, bool $Overwrite = false): bool
    {

        if (!is_object($ThemeMetadata) && !isset($ThemeMetadata->hookFile)) {
            return false;
        }
        if (isset($ThemeMetadata->onInstall->createKVStorageItems) && is_object($ThemeMetadata->onInstall->createKVStorageItems)) {

            if (defined("CRISP_CLI")) {
                echo "----------" . PHP_EOL;
                echo "Installing KVStorage for theme " . $ThemeMetadata->name . PHP_EOL;
                echo "----------" . PHP_EOL;
            }

            foreach ($ThemeMetadata->onInstall->createKVStorageItems as $Key => $Value) {
                if (is_array($Value) || is_object($Value)) {
                    $Value = serialize($Value);
                }
                if (!$Overwrite && \crisp\api\Config::exists($Key)) {
                    if (defined("CRISP_CLI")) {
                        echo "Skipping KV key $Key as it already exists and overwrite is false" . PHP_EOL;
                    }
                    continue;
                }
                try {
                    if (defined("CRISP_CLI")) {
                        echo "Installing KV key $Key" . PHP_EOL;
                    }
                    \crisp\api\Config::create($Key, $Value);
                    if (defined("CRISP_CLI")) {
                        echo "Installed KV key $Key" . PHP_EOL;
                    }
                } catch (PDOException $ex) {
                    continue;
                }
            }
        }
        return true;
    }

    
    public static function loadBootFiles(string $ThemeName = null, stdClass $ThemeMetadata = null): bool
    {
        $ThemeFolder = \crisp\api\Config::get("theme_dir");
        if ($ThemeName == null) {
            $ThemeName = \crisp\api\Config::get('theme');
        }
        $FullThemeFolder = __DIR__ . "/../../../../$ThemeFolder/$ThemeName";

        if ($ThemeMetadata == null) {
            $ThemeMetadata = self::getThemeMetadata($ThemeName);
        }


        $GLOBALS['Crisp_FullThemeFolder'] = $FullThemeFolder;
        $GLOBALS['Crisp_ThemeMetadata'] = $ThemeMetadata;

        if (!is_object($ThemeMetadata) && !isset($ThemeMetadata->hookFile)) {
            return false;
        }
        if (isset($ThemeMetadata->onBoot) && is_array($ThemeMetadata->onBoot)) {

            foreach ($ThemeMetadata->onBoot as $File) {

                if (!file_exists("$FullThemeFolder/$File")) {
                    throw new Exception("$FullThemeFolder/$File does not exist but boot scripts are configured!");
                }
                
                if (is_dir("$FullThemeFolder/$File")) {
                    throw new Exception("$FullThemeFolder/$File boot script is a directory!");
                }
                
                require_once "$FullThemeFolder/$File";
               
            }
            return true;
        }
        return false;
    }
    
    public static function autoload(string $ThemeName = null, stdClass $ThemeMetadata = null): bool
    {
        $ThemeFolder = \crisp\api\Config::get("theme_dir");
        if ($ThemeName == null) {
            $ThemeName = \crisp\api\Config::get('theme');
        }
        $FullThemeFolder = __DIR__ . "/../../../../$ThemeFolder/$ThemeName";

        if ($ThemeMetadata == null) {
            $ThemeMetadata = self::getThemeMetadata($ThemeName);
        }


        $GLOBALS['Crisp_FullThemeFolder'] = $FullThemeFolder;
        $GLOBALS['Crisp_ThemeMetadata'] = $ThemeMetadata;

        if (!is_object($ThemeMetadata) && !isset($ThemeMetadata->hookFile)) {
            return false;
        }
        if (isset($ThemeMetadata->autoload) && is_array($ThemeMetadata->autoload)) {

            foreach ($ThemeMetadata->autoload as $Directory) {

                if (file_exists("$FullThemeFolder/$Directory/autoload.php")) {
                    error_log('Autoloading composer...');
                    require "$FullThemeFolder/$Directory/autoload.php";
                    continue;
                }

                if (!file_exists("$FullThemeFolder/$Directory")) {
                    if (isset($ThemeMetadata->strict_autoloading) && !$ThemeMetadata->strict_autoloading) {
                        continue;
                    }
                    throw new Exception("$FullThemeFolder/$Directory does not exist but autoloading is configured!");
                }

                $GLOBALS['Crisp_CurAutoloadDir'] = $Directory;


                spl_autoload_register(static function ($class) {

                    $file = $GLOBALS['Crisp_FullThemeFolder'] . "/" . $GLOBALS['Crisp_CurAutoloadDir'] . "/" . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

                    if (file_exists($file)) {
                        if (isset($GLOBALS['Crisp_ThemeMetadata']->strict_autoloading) && $GLOBALS['Crisp_ThemeMetadata']->strict_autoloading) {
                            require $file;
                            return true;
                        }
                        include $file;
                        return true;
                    }
                    return false;
                });
            }
            return true;
        }
        return false;
    }

    /**
     * @param string $ThemeName
     * @param stdClass $ThemeMetadata
     * @return bool
     */
    public static function installTranslations(string $ThemeName, stdClass $ThemeMetadata): bool
    {
        if (!is_object($ThemeMetadata) && !isset($ThemeMetadata->hookFile)) {
            return false;
        }

        $_processed = [];
        if (defined("CRISP_CLI")) {
            echo "----------" . PHP_EOL;
            echo "Installing translations for theme $ThemeName" . PHP_EOL;
            echo "----------" . PHP_EOL;
        }

        if (isset($ThemeMetadata->onInstall->createTranslationKeys) && is_string($ThemeMetadata->onInstall->createTranslationKeys)) {

            $ThemeFolder = \crisp\api\Config::get("theme_dir");
            if (file_exists(__DIR__ . "/../../../../$ThemeFolder/$ThemeName/" . $ThemeMetadata->onInstall->createTranslationKeys)) {

                $files = glob(__DIR__ . "/../../../../$ThemeFolder/$ThemeName/" . $ThemeMetadata->onInstall->createTranslationKeys . "*.{json}", GLOB_BRACE);
                foreach ($files as $File) {

                    if (defined("CRISP_CLI")) {
                        echo "----------" . PHP_EOL;
                        echo "Installing language " . substr(basename($File), 0, -5) . PHP_EOL;
                        echo "----------" . PHP_EOL;
                    }
                    if (!file_exists($File)) {
                        if (defined("CRISP_CLI")) {
                            echo "ERR: $File Not found!" . PHP_EOL;
                        }
                        continue;
                    }
                    $Language = Languages::getLanguageByCode(substr(basename($File), 0, -5));

                    if (!$Language) {
                        if (defined("CRISP_CLI")) {
                            echo "ERR: " . substr(basename($File), 0, -5) . " Not found!" . PHP_EOL;
                        }
                        continue;
                    }

                    if (defined("CRISP_CLI")) {
                        echo "Reading $File" . PHP_EOL;
                    }
                    foreach (json_decode(file_get_contents($File), true, 512, JSON_THROW_ON_ERROR) as $Key => $Value) {
                        try {

                            if ($Language->newTranslation($Key, $Value, substr(basename($File), 0, -5))) {
                                $_processed[] = $Key;
                                if (defined("CRISP_CLI")) {
                                    echo "Installing translation key $Key" . PHP_EOL;
                                }
                            } else if (defined("CRISP_CLI")) {
                                echo "Not installing translation key $Key" . PHP_EOL;
                            }
                        } catch (PDOException $ex) {
                            if (defined("CRISP_CLI")) {
                                echo $ex . PHP_EOL;
                            }
                            continue 2;
                        }
                    }

                    if (defined("CRISP_CLI")) {
                        echo "Installed/Updated " . count($_processed) . " translation keys" . PHP_EOL;
                        $_processed = [];
                    }
                }
            }
            return true;
        }
        if (isset($ThemeMetadata->onInstall->createTranslationKeys) && is_object($ThemeMetadata->onInstall->createTranslationKeys)) {
            foreach ($ThemeMetadata->onInstall->createTranslationKeys as $Key => $Value) {

                try {
                    $Language = Languages::getLanguageByCode($Key);

                    if (!$Language) {
                        continue;
                    }

                    foreach ($Value as $KeyTranslation => $ValueTranslation) {
                        $Language->newTranslation($KeyTranslation, $ValueTranslation, $Key);
                    }
                } catch (PDOException $ex) {
                    continue;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Checks if the specified theme is installed
     * @param string $ThemeName The folder name of the theme
     * @return boolean TRUE if theme is installed, otherwise FALSE
     */
    public static function isInstalled(string $ThemeName): bool
    {
        return (\crisp\api\Config::get("theme") === $ThemeName);
    }

    /**
     * @param string $ThemeName
     * @return bool
     */
    public static function install(string $ThemeName): bool
    {

        var_dump($ThemeName);

        if (\crisp\api\Config::get("theme") !== false && \crisp\api\Config::get("theme") === $ThemeName) {
            return false;
        }

        echo $ThemeName . PHP_EOL;

        $ThemeFolder = \crisp\api\Config::get("theme_dir");

        if (!self::isValid($ThemeName)) {
            echo "No theme.json found!" . PHP_EOL;
            return false;
        }

        $ThemeMetadata = self::getThemeMetadata($ThemeName);


        self::performOnInstall($ThemeName, $ThemeMetadata);

        if (!is_object($ThemeMetadata) && !isset($ThemeMetadata->hookFile)) {
            var_dump($ThemeMetadata);
            echo "No hookfile in theme.json found!" . PHP_EOL;
            return false;
        }


        self::broadcastHook("themeInstall_$ThemeName", time());
        self::broadcastHook("themeInstall", $ThemeName);

        return \crisp\api\Config::set("theme", $ThemeName);
    }

}
