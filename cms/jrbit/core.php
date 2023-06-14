<?php

/*
 * Copyright (c) 2021. JRB IT, All Rights Reserved
 *
 *  @author Justin René Back <j.back@jrbit.de>
 *  @link https://vcs.jrbit.de/crispcms/core/
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

namespace crisp;

use CompileError;
use crisp\api\{Config, Flagsmith, Helper, lists\Languages, Translation};
use crisp\core\{Bitmask, Crypto, LogTypes, Redis, RESTfulAPI, Security, Sessions, Themes, License};
use Dotenv\Dotenv;
use Error;
use Exception;
use Flagsmith\Models\Identity;
use Flagsmith\Models\IdentityTrait;
use ParseError;
use RateLimit\{Rate, RedisRateLimiter};
use Sentry\SentrySdk;
use Sentry\State\Scope;
use Throwable;
use Twig\{Environment, Extension\StringLoaderExtension, Loader\FilesystemLoader, TwigFilter, TwigFunction};
use TypeError;
use function Sentry\captureException;
use function Sentry\configureScope;
use function Sentry\init;

/**
 * Core class, nothing else
 *
 * @author Justin Back <j.back@jrbit.de>
 */
class core
{
    /* Some important constants */

    public const CRISP_VERSION = '13.0.1';

    public const API_VERSION = '3.0.0';

    public const RELEASE_NAME = "Bitterlemon";

    public const PERSISTENT_DATA = "/data";

    public const DEFAULT_THEME = "crisptheme";

    public const CACHE_DIR = __DIR__ . '/cache/';

}
require_once __DIR__ . '/../vendor/autoload.php';


try {
    if(!defined('STDIN'))  define('STDIN',  fopen('php://stdin',  'rb'));
    if(!defined('STDOUT')) define('STDOUT', fopen('php://stdout', 'wb'));
    if(!defined('STDERR')) define('STDERR', fopen('php://stderr', 'wb'));
    define('IS_DEV_ENV', (isset($_SERVER['ENVIRONMENT']) && $_SERVER['ENVIRONMENT'] !== 'production'));
    define('ENVIRONMENT', match (strtolower($_SERVER['ENVIRONMENT'] ?? 'production')) {
        'staging' => 'staging',
        'development' => 'development',
        default => 'production'
    });



    define("ThemeMetadata", Themes::getThemeMetadata());

    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    if (IS_DEV_ENV) {
        $dotenv->safeLoad();
    }
    $dotenv->required([
        'POSTGRES_URI',
        'REDIS_HOST',
        'REDIS_PORT',
        'REDIS_INDEX',
        'DEFAULT_LOCALE',
        'CRISP_FLAGSMITH_APP_URL',
        'CRISP_FLAGSMITH_API_KEY',
        'CRISP_THEME',
        'ENVIRONMENT',
        'HOST',
        'PROTO',
        'TZ',
        'DEFAULT_LOCALE',
        'LANG',
        'CRISP_FLAGSMITH_APP_URL',
    ])->notEmpty();
    $dotenv->required(['REDIS_INDEX', 'REDIS_PORT'])->isInteger();
    $dotenv->required('POSTGRES_URI')->allowedRegexValues('/^(?:([^:\/?#\s]+):\/{2})?(?:([^@\/?#\s]+)@)?([^\/?#\s]+)?(?:\/([^?#\s]*))?(?:[?]([^#\s]+))?\S*$/i');



    $GLOBALS['dotenv'] = $dotenv;


        define('CRISP_HOOKED', true);
        /** Core headers, can be accessed anywhere */
        /** After autoloading we include additional headers below */




        $BuildType = $_ENV['BUILD_TYPE'] ?? 0;

        if($BuildType === 0 && (str_contains(strtolower($_ENV['GIT_TAG']), "rc."))){
            $BuildType = 2;
        }elseif($BuildType === 0 && (str_contains(strtolower($_ENV['GIT_TAG']), "pre-release") || str_contains(strtolower($_ENV['GIT_TAG']), "prerelease"))){
            $BuildType = 3;
        }elseif($BuildType === 0 && isset($_ENV['GIT_TAG'])){
            $BuildType = 1;
        }

        $_ENV['BUILD_TYPE'] = match($BuildType){
            1 => "Stable",
            2 => "Release-Candidate",
            3 => "Pre-Release",
            default => "Nightly"
        };

        define('BUILD_TYPE', $_ENV['BUILD_TYPE']);

        define('IS_API_ENDPOINT', (PHP_SAPI !== 'cli' && isset($_SERVER['IS_API_ENDPOINT'])));
        define('IS_NATIVE_API', isset($_SERVER['IS_API_ENDPOINT']));
        define('RELEASE', (IS_API_ENDPOINT ? 'api' : 'crisp')
            . '@' .
            (IS_API_ENDPOINT ? core::API_VERSION : core::CRISP_VERSION)
            . '+' .
            (Helper::getCommitHash() ?? 'nongit')
            . '-' .
            (BUILD_TYPE ?? 'Nightly')
            . '.' .
            ($_ENV['CI_BUILD'] ?? 0)
        );
        define('REQUEST_ID', Crypto::UUIDv4("R"));




        if (PHP_SAPI !== 'cli') {
            $GLOBALS['route'] = api\Helper::processRoute($_GET['route']);
            Helper::Log(LogTypes::INFO, Helper::getRequestLog());
        }

        if(isset($_ENV['SENTRY_DSN'])) {

            init([
                'dsn' => $_ENV['SENTRY_DSN'],
                'traces_sample_rate' => $_ENV['SENTRY_SAMPLE_RATE'] ?? 0.3,
                'environment' => ENVIRONMENT,
                'release' => RELEASE,
            ]);

            configureScope(function (Scope $scope): void {
                $scope->setTag('request_id', REQUEST_ID);
            });

        }

        define('VM_IP', exec('hostname -I'));
        define('RELEASE_ICON', file_get_contents(__DIR__ . '/../themes/basic/releases/' . strtolower(core::RELEASE_NAME) . ".svg"));
        define('CRISP_ICON', file_get_contents(__DIR__ . '/../themes/basic/crisp.svg'));
        define('RELEASE_ART', file_get_contents(__DIR__ . '/../themes/basic/releases/' . strtolower(core::RELEASE_NAME) . ".art"));

        if (!empty($_ENV['FLAGSMITH_API_KEY']) && !empty($_ENV['FLAGSMITH_APP_URL'])) {
            define('USES_FLAGSMITH', true);
            $GLOBALS['Flagsmith'] = Flagsmith::Client();

        } else {
            define('USES_FLAGSMITH', false);
        }

        /** @var \Flagsmith\Flagsmith */
        $GLOBALS['flagsmith_server'] = Flagsmith::Client('CRISP_FLAGSMITH_API_KEY', 'CRISP_FLAGSMITH_APP_URL', 300);


            $ServerID = (new Identity(Helper::getInstanceId()))
                ->withTrait((new IdentityTrait('commit'))
                    ->withValue(Helper::getCommitHash()))
                ->withTrait((new IdentityTrait('hostname'))
                    ->withValue(gethostname()))
                ->withTrait((new IdentityTrait('crisp_version'))
                    ->withValue(core::CRISP_VERSION))
                ->withTrait((new IdentityTrait('api_version'))
                    ->withValue(core::API_VERSION))
                ->withTrait((new IdentityTrait('environment'))
                    ->withValue(ENVIRONMENT));

            $GLOBALS['flagsmith_server_identity'] = $ServerID;
        

        $GLOBALS['flagsmith_server']->setTraitsByIdentity($GLOBALS['flagsmith_server_identity']);


        header('X-Request-ID: ' . REQUEST_ID);

        if(!$_ENV['DONT_EXPOSE_CRISP']){
            header("x-powered-by: CrispCMS/". core::CRISP_VERSION);
        }

    setlocale(LC_TIME, $_ENV["LANG"] ?? 'de_DE.utf8');
    if (PHP_SAPI !== 'cli') {


        $GLOBALS['microtime'] = [];
        $GLOBALS['microtime']['logic'] = [];
        $GLOBALS['microtime']['template'] = [];

        $GLOBALS['microtime']['logic']['start'] = microtime(true);

        $GLOBALS['plugins'] = [];
        $GLOBALS['hook'] = [];
        $GLOBALS['navbar'] = [];
        $GLOBALS['navbar_right'] = [];
        $GLOBALS['render'] = [];

        session_start();


        $CurrentTheme = core::DEFAULT_THEME;
        $CurrentFile = substr(substr($_SERVER['PHP_SELF'], 1), 0, -4);
        $CurrentPage = $GLOBALS['route']->Page;
        $CurrentPage = ($CurrentPage === '' ? 'start' : $CurrentPage);
        $CurrentPage = explode('.', $CurrentPage)[0];


        api\Helper::setLocale();
        $Locale = Helper::getLocale();

        header("X-CMS-CurrentPage: $CurrentPage");
        header("X-CMS-Locale: $Locale");
        header('X-CMS-Version: ' . core::CRISP_VERSION);

        if (!isset($_COOKIE['guid'])) {
            $GLOBALS['guid'] = Crypto::UUIDv4();
            setcookie('guid', $GLOBALS['guid'], time() + (86400 * 30), '/');
        } else {
            $GLOBALS['guid'] = $_COOKIE['guid'];
        }

        $ThemeLoader = new FilesystemLoader([__DIR__ . "/../themes/$CurrentTheme/templates/"]);
        if (ENVIRONMENT === 'production') {
            $TwigTheme = new Environment($ThemeLoader, [
                'cache' => core::CACHE_DIR
            ]);
        } else {
            $TwigTheme = new Environment($ThemeLoader, []);
        }



            $AnonymousID = (new Identity($GLOBALS['guid']))
                ->withTrait((new IdentityTrait('session_id'))
                    ->withValue($GLOBALS['guid'] ?? 'unknown'))
                ->withTrait((new IdentityTrait('route'))
                    ->withValue($GLOBALS['route']->Page ?? 'unknown'))
                ->withTrait((new IdentityTrait('locale'))
                    ->withValue($Locale ?? $_ENV['DEFAULT_LOCALE']))
                ->withTrait((new IdentityTrait('country_code'))
                    ->withValue($_ENV["GEOIP_COUNTRY_CODE"] ?? 'NONE'))
                ->withTrait((new IdentityTrait('country_code3'))
                    ->withValue($_ENV["GEOIP_COUNTRY_CODE3"] ?? 'NONE'))
                ->withTrait((new IdentityTrait('country_name'))
                    ->withValue($_ENV["GEOIP_COUNTRY_NAME"] ?? 'NONE'));

            $GLOBALS['flagsmith_identity'] = $AnonymousID;



        /* Twig Globals */

        $TwigTheme->addGlobal('config', Config::list());
        $TwigTheme->addGlobal('locale', $Locale);
        $TwigTheme->addGlobal('languages', Translation::listLanguages(false));
        $TwigTheme->addGlobal('GET', $_GET);
        $TwigTheme->addGlobal('CurrentPage', $CurrentPage);
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
        $TwigTheme->addGlobal('GLOBAL_IDENTITY', $GLOBALS['flagsmith_identity']);

        $TwigTheme->addFunction(new TwigFunction('prettyDump', [new Helper(), 'prettyDump']));
        $TwigTheme->addExtension(new StringLoaderExtension());

        if ($GLOBALS['flagsmith_server']->isFeatureEnabledByIdentity($GLOBALS['flagsmith_server_identity'], 'version_string_v1')) {
            $TwigTheme->addGlobal('VERSION_STRING', $GLOBALS['flagsmith_server']->getFeatureValueByIdentity($GLOBALS['flagsmith_server_identity'], 'version_string_v1'));
        }

        if (USES_FLAGSMITH) {
            $TwigTheme->addFunction(new TwigFunction('Flagsmith', [new Flagsmith()]));
            $TwigTheme->addFunction(new TwigFunction('fsFeatureEnabledGlobally', [Flagsmith::Client(), 'isFeatureEnabled']));
            $TwigTheme->addFunction(new TwigFunction('fsFeatureEnabled', [new Flagsmith(), 'isFeatureEnabledByIdentity']));
            $TwigTheme->addFunction(new TwigFunction('fsFeatureEnabledByIdentity', [new Flagsmith(), 'isFeatureEnabledByIdentity']));
            $TwigTheme->addFunction(new TwigFunction('fsFeatureGetValue', [new Flagsmith(), 'getFeatureValueByIdentity']));
            $TwigTheme->addFunction(new TwigFunction('fsFeatureGetValueGlobally', [Flagsmith::Client(), 'getFeatureValue']));
            $TwigTheme->addFunction(new TwigFunction('fsFeatureGetValueByIdentity', [new Flagsmith(), 'getFeatureValueByIdentity']));
            Flagsmith::setTraitsByIdentity();
        }
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


        $Translation = new Translation($Locale);


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



        if (IS_API_ENDPOINT) {

            header('Access-Control-Allow-Origin: *');
            header('Cache-Control: max-age=600, public, must-revalidate');

            if (!isset($_SERVER['HTTP_USER_AGENT']) || empty($_SERVER['HTTP_USER_AGENT']) || $_SERVER['HTTP_USER_AGENT'] === 'i am not valid') {
                http_response_code(403);
                echo $TwigTheme->render('errors/nginx/403.twig', ['error_msg' => 'Request forbidden by administrative rules. Please make sure your request has a User-Agent header']);
                exit;
            }

            core\Themes::loadAPI($TwigTheme, $GLOBALS['route']->Page);
        }

        Themes::load($TwigTheme, $CurrentFile, $CurrentPage);
    }
} catch (TypeError | Exception | Error | CompileError | ParseError | Throwable $ex) {
    captureException($ex);
    if (PHP_SAPI === 'cli') {
        var_dump($ex);
        exit(1);
    }
    http_response_code(500);
    $errorraw = file_get_contents(__DIR__ . '/../themes/basic/error.html');

    if (defined('REQUEST_ID')) {
        $refid = REQUEST_ID;
    } else {
        $refid = 'Core';
    }

    if (IS_DEV_ENV) {
        $refid = $ex->__toString();
    }


    if (IS_API_ENDPOINT && $GLOBALS['flagsmith_server']->isFeatureEnabledByIdentity($GLOBALS['flagsmith_server_identity'], 'enable_api')) {
        RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, 'Internal Server Error', ['reference_id' => $refid]);
        exit;
    }

    header("X-Sentry-ID: ". SentrySdk::getCurrentHub()->getLastEventId());

    error_log($ex->__toString());

    echo strtr($errorraw, ['{{ exception }}' => $refid, '{{ sentry_id }}' => SentrySdk::getCurrentHub()->getLastEventId(), "{{ SENTRY_JS_DSN }}" => $_ENV["SENTRY_JS_DSN"]]);
    exit;
}

