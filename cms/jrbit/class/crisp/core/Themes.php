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

use crisp\api\Cache;
use crisp\api\Helper;
use crisp\api\lists\Languages;
use crisp\api\Translation;
use crisp\core;
use Exception;
use FilesystemIterator;
use PDOException;
use Phroute\Phroute\Dispatcher;
use Phroute\Phroute\Route;
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
    public static function loadAPI(string $Interface): void
    {
        try {
            Themes::autoload();
            new RESTfulAPI($Interface);
        } catch (Exception $ex) {
            captureException($ex);
            throw new Exception($ex);
        }
    }

    public static function getRenderer(): Environment
    {
        return $GLOBALS["Crisp_ThemeLoader"];
    }

    public static function render(string $Template): string
    {
        Logger::startTiming($TemplateRender);
        Helper::Log(LogTypes::DEBUG, "START Rendering template $Template");
        $content = $GLOBALS["Crisp_ThemeLoader"]->render($Template, ThemeVariables::getAll());
        Helper::Log(LogTypes::DEBUG, sprintf("DONE Rendering template $Template - Took %s ms", Logger::endTiming($TemplateRender)));
        return $content;
    }

    public static function getThemeDirectory(bool $relative = false): string
    {
        if ($relative) {
            return sprintf("/themes/%s", core::DEFAULT_THEME);
        }

        return realpath(sprintf(__DIR__ . "/../../../../themes/%s", core::DEFAULT_THEME));
    }

    /**
     * @param Environment $TwigTheme
     * @param string $CurrentFile
     * @param string $CurrentPage
     * @throws Exception
     */
    public static function load(): void
    {


        try {

            $_HookFile = self::getThemeMetadata()->hookFile;
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
            $HookClass->preRender($CurrentPage, $CurrentFile);
            Helper::Log(LogTypes::DEBUG, sprintf("DONE executing preRender hooks for HookFile - Took %s ms", Logger::endTiming($HookClassRenderTime)));

            $dispatcher = new Dispatcher(Router::get()->getData());
            echo $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
            if ($HookClass !== null && !method_exists($HookClass, 'postRender')) {
                throw new \Exception("Failed to load HookFile, missing postRender!");
            }
            $HookClass->postRender();
        } catch (Exception $ex) {
            captureException($ex);
            if (PHP_SAPI === 'cli') {
                var_dump($ex);
                exit(1);
            }
            http_response_code(500);


            if (defined('REQUEST_ID')) {
                $refid = REQUEST_ID;
            } else {
                $refid = 'Core';
            }

            if (IS_DEV_ENV) {
                $refid = $ex->getMessage();
            }


            if (IS_API_ENDPOINT) {
                RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, 'Internal Server Error', ['reference_id' => $refid]);
                exit;
            }

            if (Helper::templateExists("errors/servererror.twig")) {
                echo Themes::render("errors/servererror.twig", ['{{ exception }}' => $refid, '{{ sentry_id }}' => SentrySdk::getCurrentHub()->getLastEventId()]);
            } else {
                echo strtr(file_get_contents(__DIR__ . '/../../../../themes/basic/error.html'), ['{{ exception }}' => $refid, '{{ sentry_id }}' => SentrySdk::getCurrentHub()->getLastEventId()]);
            }

            exit;
        }
    }

    public static function includeResource($File, int $cacheTTL = 60 * 60): string
    {
        if (str_starts_with($File, "//") || str_starts_with($File, "http://")  || str_starts_with($File, "https://")) {
            return sprintf("/_proxy/?url=%s", $File, $cacheTTL);
        }

        if (str_starts_with($File, "/")) {
            $File = substr($File, 1);
        }


        $baseDir = self::getThemeDirectory(true);
        $FilePath = self::getThemeDirectory() . "/$File";

        if (isset($_ENV["ASSETS_S3_BUCKET"])) {
            $baseDir = Helper::getS3Url($_ENV["ASSETS_S3_BUCKET"], $_ENV["ASSETS_S3_REGION"], $_ENV["ASSETS_S3_URL"]);
        } elseif (file_exists(self::getThemeDirectory() . "/assets")) {
            $baseDir = "/assets";
            $FilePath = self::getThemeDirectory() . "/assets/$File";
        }



        if (!file_exists($FilePath)) {
            return "$baseDir/$File";
        }


        $hash = hash_file("sha256", $FilePath);

        return "$baseDir/$File?$hash";
    }

    public static function getThemeMetadata(): stdClass|null
    {
        if (!self::isValid()) {
            return null;
        }

        return json_decode(file_get_contents(Themes::getThemeDirectory() . "/theme.json"));
    }

    public static function uninstallTranslations(): bool
    {
        if (!is_object(ThemeMetadata) && !isset(ThemeMetadata->hookFile)) {
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

    public static function uninstallKVStorage(): bool
    {
        if (!is_object(ThemeMetadata) && !isset(ThemeMetadata->hookFile)) {
            return false;
        }

        if (isset(ThemeMetadata->onInstall->createKVStorageItems) && is_object(ThemeMetadata->onInstall->createKVStorageItems)) {
            foreach (ThemeMetadata->onInstall->createKVStorageItems as $Key => $Value) {
                \crisp\api\Config::delete($Key);
            }
        }
        return true;
    }

    /**
     * @return bool
     */
    public static function isValid(): bool
    {
        return file_exists(Themes::getThemeDirectory() . "/theme.json");
    }

    /**
     * @return bool
     */
    public static function uninstall(): bool
    {

        self::clearCache();

        \crisp\api\Config::set("theme", null);


        if (!is_object(ThemeMetadata) && !isset(ThemeMetadata->hookFile)) {
            return false;
        }
        self::performOnUninstall();


        self::broadcastHook("themeUninstall", null);
        return true;
    }

    /**
     * Clear the theme cache
     * @return boolean
     */
    public static function clearCache(string $dir = core::CACHE_DIR): bool
    {
        return Cache::clear($dir);
    }

    /**
     */
    private static function performOnUninstall()
    {
        if (ThemeMetadata->onUninstall->deleteData) {
            self::deleteData();
        }
    }

    /**
     * Deletes all KVStorage Items from the Plugin
     *
     * If the theme is installed, it will get uninstalled first
     * @return boolean TRUE if the data has been successfully deleted
     */
    public static function deleteData(): bool
    {

        if (self::isInstalled()) {
            return self::uninstall();
        }

        self::uninstallKVStorage();
        self::uninstallTranslations();

        return true;
    }

    /**
     * Registers an uninstall hook for your theme.
     * @param string $ThemeName
     * @param mixed $Function Callback function, either anonymous or a string to a function
     * @return bool
     */
    public static function registerUninstallHook(mixed $Function): bool
    {
        if (is_callable($Function) || function_exists($Function)) {
            self::on("themeUninstall", $Function);
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
    public static function registerInstallHook(mixed $Function): bool
    {
        if (is_callable($Function) || function_exists($Function)) {
            self::on("themeInstall", $Function);
            return true;
        }
        return false;
    }

    /**
     * @return bool
     * @throws \JsonException
     */
    private static function performOnInstall(): bool
    {
        if (!isset(ThemeMetadata->onInstall)) {
            return false;
        }

        self::installKVStorage();
        self::installTranslations();

        return true;
    }

    /**
     * @param stdClass ThemeMetadata
     * @param bool $Overwrite
     * @return bool
     */
    public static function installKVStorage(bool $Overwrite = false): bool
    {

        if (!is_object(ThemeMetadata) && !isset(ThemeMetadata->hookFile)) {
            return false;
        }
        if (isset(ThemeMetadata->onInstall->createKVStorageItems) && is_object(ThemeMetadata->onInstall->createKVStorageItems)) {
            Helper::Log(LogTypes::INFO, "Installing KVStorage for Theme " . ThemeMetadata->name);


            foreach (ThemeMetadata->onInstall->createKVStorageItems as $Key => $Value) {
                if (is_array($Value) || is_object($Value)) {
                    $Value = serialize($Value);
                }
                if (!$Overwrite && \crisp\api\Config::exists($Key)) {
                    Helper::Log(LogTypes::WARNING, "Skipping KV key $Key as it already exists and overwrite is false");
                    continue;
                }
                try {
                    Helper::Log(LogTypes::INFO, "Installing KV key $Key");
                    if (\crisp\api\Config::create($Key, $Value)) {
                        Helper::Log(LogTypes::SUCCESS, "Successfully Installed KV key $Key");
                    } else {
                        Helper::Log(LogTypes::ERROR, "Failed to Install  KV key $Key");
                    }
                } catch (PDOException $ex) {
                    continue;
                }
            }
        }
        return true;
    }


    public static function loadBootFiles(): bool
    {

        if (!is_object(ThemeMetadata) && !isset(ThemeMetadata->hookFile)) {
            return false;
        }
        if (isset(ThemeMetadata->onBoot) && is_array(ThemeMetadata->onBoot)) {
            foreach (ThemeMetadata->onBoot as $File) {

                if (!file_exists(Themes::getThemeDirectory() . "/$File")) {
                    throw new Exception("$File does not exist but boot scripts are configured!");
                }

                if (is_dir(Themes::getThemeDirectory() . "/$File")) {
                    throw new Exception("$File boot script is a directory!");
                }

                require_once Themes::getThemeDirectory() . "/$File";
            }
            return true;
        }
        return false;
    }

    public static function autoload(): bool
    {

        if (!is_object(ThemeMetadata) && !isset(ThemeMetadata->hookFile)) {
            return false;
        }
        if (isset(ThemeMetadata->autoload) && is_array(ThemeMetadata->autoload)) {

            foreach (ThemeMetadata->autoload as $Directory) {

                if (file_exists(Themes::getThemeDirectory() . "/$Directory/autoload.php")) {
                    Helper::Log(LogTypes::DEBUG, "Autoloading Composer");
                    require Themes::getThemeDirectory() . "/$Directory/autoload.php";
                    continue;
                }

                if (!file_exists(Themes::getThemeDirectory() . "/$Directory")) {
                    if (isset(ThemeMetadata->strict_autoloading) && !ThemeMetadata->strict_autoloading) {
                        continue;
                    }
                    throw new Exception(Themes::getThemeDirectory() . "/$Directory does not exist but autoloading is configured!");
                }



                spl_autoload_register(static function ($class) use ($Directory) {

                    $file = Themes::getThemeDirectory() . "/$Directory/" . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

                    if (file_exists($file)) {
                        if (isset(ThemeMetadata->strict_autoloading) && ThemeMetadata->strict_autoloading) {
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
     * @param stdClass ThemeMetadata
     * @return bool
     */
    public static function installTranslations(): bool
    {
        if (!is_object(ThemeMetadata) && !isset(ThemeMetadata->hookFile)) {
            return false;
        }

        $_processed = [];
        Helper::Log(LogTypes::INFO, "Installing translations for Theme " . ThemeMetadata->name);

        if (isset(ThemeMetadata->onInstall->createTranslationKeys) && is_string(ThemeMetadata->onInstall->createTranslationKeys)) {
            if (file_exists(Themes::getThemeDirectory() . "/" . ThemeMetadata->onInstall->createTranslationKeys)) {

                $files = glob(Themes::getThemeDirectory() . "/" .  ThemeMetadata->onInstall->createTranslationKeys . "*.{json}", GLOB_BRACE);
                foreach ($files as $File) {

                    Helper::Log(LogTypes::INFO, sprintf("Installing language %s", substr(basename($File), 0, -5)));
                    if (!file_exists($File)) {
                        Helper::Log(LogTypes::ERROR, sprintf("%s not found!", $File));
                        continue;
                    }
                    $Language = Languages::getLanguageByCode(substr(basename($File), 0, -5));

                    if (!$Language) {
                        Helper::Log(LogTypes::ERROR, sprintf("%s not found!", substr(basename($File), 0, -5)));
                        continue;
                    }
                    foreach (json_decode(file_get_contents($File), true, 512, JSON_THROW_ON_ERROR) as $Key => $Value) {
                        try {

                            if ($Language->newTranslation($Key, $Value, substr(basename($File), 0, -5))) {
                                $_processed[] = $Key;
                                Helper::Log(LogTypes::INFO, sprintf("Installed translation key %s", $Key));
                            } else if (defined("CRISP_CLI")) {
                                Helper::Log(LogTypes::WARNING, sprintf("Did not Install translation key %s", $Key));
                            }
                        } catch (PDOException $ex) {
                            if (defined("CRISP_CLI")) {
                                Helper::Log(LogTypes::ERROR, $ex);
                            }
                            continue 2;
                        }
                    }

                    Helper::Log(LogTypes::SUCCESS, sprintf("Successfully Updated %s  translation keys", count($_processed)));
                    $_processed = [];
                }
            }
            return true;
        }
        if (isset(ThemeMetadata->onInstall->createTranslationKeys) && is_object(ThemeMetadata->onInstall->createTranslationKeys)) {
            foreach (ThemeMetadata->onInstall->createTranslationKeys as $Key => $Value) {

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
     * @return boolean TRUE if theme is installed, otherwise FALSE
     */
    public static function isInstalled(): bool
    {
        return (\crisp\api\Config::get("theme") === core::DEFAULT_THEME);
    }

    /**
     * @return bool
     * @throws \JsonException
     */
    public static function install(): bool
    {
        if (\crisp\api\Config::get("theme") !== false && \crisp\api\Config::get("theme") === core::DEFAULT_THEME) {
            return false;
        }

        if (!self::isValid()) {
            Helper::Log(LogTypes::ERROR, "No theme.json found!");
            return false;
        }


        self::performOnInstall();

        if (!is_object(ThemeMetadata) && !isset(ThemeMetadata->hookFile)) {
            Helper::Log(LogTypes::ERROR, "No hookFile Property in theme.json found!");
            return false;
        }


        self::broadcastHook("themeInstall", time());

        return \crisp\api\Config::set("theme", "crisptheme");
    }
}
