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
use crisp\api\Config;
use crisp\api\Helper;
use crisp\api\lists\Languages;
use crisp\api\Translation;
use crisp\core;
use crisp\types\RouteType;
use Exception;
use FilesystemIterator;
use PDOException;
use Phroute\Phroute\Dispatcher;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;
use Phroute\Phroute\Route;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Sentry\Client;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use stdClass;
use Twig\Environment;
use Twig\Extension\StringLoaderExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Carbon\Carbon;

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
    public static function loadAPI(): void
    {
        try {
            new RESTfulAPI();
        } catch (Exception $ex) {
            captureException($ex);
            throw new Exception($ex);
        }
    }

    public static function initRenderer(string $dir = null): void
    {
        if (!$dir) {
            $dir = self::getThemeDirectory() . "/templates";
        }
        $ThemeLoader = new FilesystemLoader([$dir]);

        if (ENVIRONMENT === 'production') {
            $TwigTheme = new Environment($ThemeLoader, [
                'cache' => core::CACHE_DIR
            ]);
        } else {
            $TwigTheme = new Environment($ThemeLoader, []);
        }


        $TwigTheme->addGlobal('config', Config::list());
        $TwigTheme->addGlobal('locale', Helper::getLocale());
        $TwigTheme->addGlobal('languages', Translation::listLanguages(false));
        $TwigTheme->addGlobal('GET', $_GET);
        $TwigTheme->addGlobal('POST', $_POST);
        $TwigTheme->addGlobal('SERVER', $_SERVER);
        $TwigTheme->addGlobal('GLOBALS', $GLOBALS);
        $TwigTheme->addGlobal('COOKIE', $_COOKIE);
        $TwigTheme->addGlobal('ENV', $_ENV);
        $TwigTheme->addGlobal('isMobile', Helper::isMobile());
        $TwigTheme->addGlobal('URL', Helper::currentURL());
        $TwigTheme->addGlobal('CLUSTER', gethostname());
        $TwigTheme->addGlobal('CRISP_VERSION', core::CRISP_VERSION);
        $TwigTheme->addGlobal('API_VERSION', core::API_VERSION);
        $TwigTheme->addGlobal('VM_IP', VM_IP);
        $TwigTheme->addGlobal('REQUEST_ID', REQUEST_ID);
        $TwigTheme->addGlobal('RELEASE_NAME', core::RELEASE_NAME);
        $TwigTheme->addGlobal('RELEASE_ICON', RELEASE_ICON);
        $TwigTheme->addGlobal('RELEASE_ART', RELEASE_ART);
        $TwigTheme->addGlobal('CRISP_ICON', CRISP_ICON);

        $TwigTheme->addFunction(new TwigFunction('prettyDump', [new Helper(), 'prettyDump']));
        $TwigTheme->addExtension(new StringLoaderExtension());



        $TwigTheme->addGlobal('VERSION_STRING', "{{ SERVER.ENVIRONMENT |upper }} | Theme@{{ ENV.THEME_GIT_COMMIT }} | CIP: {{ VM_IP }}@{{ CLUSTER }} | CV: {{ CRISP_VERSION }}@{{ ENV.GIT_COMMIT }} | AV: {{ API_VERSION }}@{{ ENV.GIT_COMMIT }} | RID: {{ REQUEST_ID }}");

        $TwigTheme->addFunction(new TwigFunction('microtime', 'microtime'));
        $TwigTheme->addFunction(new TwigFunction('includeResource', [new Themes(), 'includeResource']));
        $TwigTheme->addFunction(new TwigFunction('generateLink', [new Helper(), 'generateLink']));
        $TwigTheme->addFunction(new TwigFunction('generatePlaceholder', [new Helper(), 'PlaceHolder']));
        $TwigTheme->addFunction(new TwigFunction('generateLoremIpsum', [new Helper(), 'LoremIpsum']));
        $TwigTheme->addFunction(new TwigFunction('date', 'date'));
        $TwigTheme->addFunction(new TwigFunction('in_array_any', [new Helper(), 'in_array_any']));

        /* CSRF Stuff */
        $TwigTheme->addFunction(new TwigFunction('csrf', [new Security(), 'getCSRF']));
        $TwigTheme->addFunction(new TwigFunction('refreshCSRF', [new Security(), 'regenCSRF']));
        $TwigTheme->addFunction(new TwigFunction('validateCSRF', [new Security(), 'matchCSRF']));
        $TwigTheme->addFunction(new TwigFunction('strftime', 'strftime'));
        $TwigTheme->addFunction(new TwigFunction('strtotime', 'strtotime'));
        $TwigTheme->addFunction(new TwigFunction('time', 'time'));
        $TwigTheme->addFunction(new TwigFunction('parseTime', [Carbon::class, 'parse']));
        $TwigTheme->addFunction(new TwigFunction('render', [Themes::class, 'render']));


        $Translation = new Translation(Helper::getLocale());


        $TwigTheme->addFilter(new TwigFilter('bcdiv', 'bcdiv'));
        $TwigTheme->addFilter(new TwigFilter('integer', 'intval'));
        $TwigTheme->addFilter(new TwigFilter('double', 'doubleval'));
        $TwigTheme->addFilter(new TwigFilter('json', 'json_decode'));
        $TwigTheme->addFilter(new TwigFilter('json_encode', 'json_encode'));
        $TwigTheme->addFilter(new TwigFilter('json_decode', 'json_decode'));
        $TwigTheme->addFilter(new TwigFilter('base64_encode', 'base64_encode'));
        $TwigTheme->addFilter(new TwigFilter('unserialize', 'unserialize'));
        $TwigTheme->addFilter(new TwigFilter('md5', 'md5'));
        $TwigTheme->addFilter(new TwigFilter('translate', [$Translation, 'fetch']));
        $TwigTheme->addFilter(new TwigFilter('getlang', [new Languages(), 'getLanguageByCode']));
        $TwigTheme->addFilter(new TwigFilter('truncateText', [new Helper(), 'truncateText']));
        $TwigTheme->addFilter(new TwigFilter('strtotime', 'strtotime'));
        $TwigTheme->addFilter(new TwigFilter('time', 'time'));
        $TwigTheme->addFilter(new TwigFilter('formattime', [new Helper(), 'FormatTime']));

        $GLOBALS["Crisp_ThemeLoader"] = $TwigTheme;
    }

    public static function getRenderer(): Environment
    {
        return $GLOBALS["Crisp_ThemeLoader"];
    }

    public static function render(string $Template): string
    {
        Logger::startTiming($TemplateRender);
        Logger::getLogger(__CLASS__)->debug("START Rendering template $Template");
        $content = $GLOBALS["Crisp_ThemeLoader"]->render($Template, ThemeVariables::getAll());
        Logger::getLogger(__CLASS__)->debug(sprintf("DONE Rendering template $Template - Took %s ms", Logger::endTiming($TemplateRender)));
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


            HookFile::preRender();
            $dispatcher = new Dispatcher(Router::get(RouteType::PUBLIC)->getData());
            echo $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
            HookFile::postRender();
        } catch (HttpRouteNotFoundException $ex) {


            if (Helper::templateExists("errors/notfound.twig")) {
                echo Themes::render("errors/notfound.twig", []);
            } else {
                echo strtr(file_get_contents(__DIR__ . '/../../../../themes/basic/not_found.html'), []);
            }
            exit;
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
            Logger::getLogger(__CLASS__)->info("Installing KVStorage for Theme " . ThemeMetadata->name);

            foreach (ThemeMetadata->onInstall->createKVStorageItems as $Key => $Value) {
                if (is_array($Value) || is_object($Value)) {
                    $Value = serialize($Value);
                }
                if (!$Overwrite && \crisp\api\Config::exists($Key)) {
                    Logger::getLogger(__CLASS__)->warning("Skipping KV key $Key as it already exists and overwrite is false");
                    continue;
                }
                try {
                    Logger::getLogger(__CLASS__)->info("Installing KV key $Key");
                    if (\crisp\api\Config::create($Key, $Value)) {
                        Logger::getLogger(__CLASS__)->notice("Successfully Installed KV key $Key");
                    } else {
                        Logger::getLogger(__CLASS__)->error("Failed to Install  KV key $Key");
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
                    Logger::getLogger(__CLASS__)->debug("Autoloading Composer");
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
        Logger::getLogger(__CLASS__)->info("Installing translations for Theme " . ThemeMetadata->name);

        if (isset(ThemeMetadata->onInstall->createTranslationKeys) && is_string(ThemeMetadata->onInstall->createTranslationKeys)) {
            if (file_exists(Themes::getThemeDirectory() . "/" . ThemeMetadata->onInstall->createTranslationKeys)) {

                $files = glob(Themes::getThemeDirectory() . "/" .  ThemeMetadata->onInstall->createTranslationKeys . "*.{json}", GLOB_BRACE);
                foreach ($files as $File) {

                    Logger::getLogger(__CLASS__)->info(sprintf("Installing language %s", substr(basename($File), 0, -5)));
                    if (!file_exists($File)) {
                        Logger::getLogger(__CLASS__)->error(sprintf("%s not found!", $File));
                        continue;
                    }
                    $Language = Languages::getLanguageByCode(substr(basename($File), 0, -5));

                    if (!$Language) {
                        Logger::getLogger(__CLASS__)->error(sprintf("%s not found!", substr(basename($File), 0, -5)));
                        continue;
                    }
                    foreach (json_decode(file_get_contents($File), true, 512, JSON_THROW_ON_ERROR) as $Key => $Value) {
                        try {

                            if ($Language->newTranslation($Key, $Value, substr(basename($File), 0, -5))) {
                                $_processed[] = $Key;
                                Logger::getLogger(__CLASS__)->info(sprintf("Installed translation key %s", $Key));
                            } else if (defined("CRISP_CLI")) {
                                Logger::getLogger(__CLASS__)->warning(sprintf("Did not Install translation key %s", $Key));
                            }
                        } catch (PDOException $ex) {
                            if (defined("CRISP_CLI")) {
                                Logger::getLogger(__CLASS__)->error($ex);
                            }
                            continue 2;
                        }
                    }

                    Logger::getLogger(__CLASS__)->notice(sprintf("Successfully Updated %s  translation keys", count($_processed)));
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
            Logger::getLogger(__CLASS__)->error("No theme.json found!");
            return false;
        }


        self::performOnInstall();

        if (!is_object(ThemeMetadata) && !isset(ThemeMetadata->hookFile)) {
            Logger::getLogger(__CLASS__)->error("No hookFile Property in theme.json found!");
            return false;
        }


        self::broadcastHook("themeInstall", time());

        return \crisp\api\Config::set("theme", "crisptheme");
    }
}
