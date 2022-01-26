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
use crisp\core\{Bitmask, Crypto, Redis, RESTfulAPI, Security, Sessions, Themes, License};
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

    public const CRISP_VERSION = '10.2.0';

    public const API_VERSION = '2.2.0';

    public const RELEASE_NAME = "Stroopwafel";

    /**
     * This is my autoloader.
     * There are many like it, but this one is mine.
     * My autoloader is my best friend.
     * It is my life.
     * I must master it as I must master my life.
     * My autoloader, without me, is useless.
     * Without my autoloader, I am useless.
     * I must use my autoloader true.
     * I must code better than my enemy who is trying to be better than me.
     * I must be better than him before he is.
     * And I will be.
     *
     * @throws Exception
     */
    public static function bootstrap(): void
    {
        define('CRISP_HOOKED', true);
        /** Core headers, can be accessed anywhere */
        header('X-Cluster: ' . gethostname());
        /** After autoloading we include additional headers below */


        define('IS_API_ENDPOINT', (PHP_SAPI !== 'cli' && isset($_SERVER['IS_API_ENDPOINT'])));
        define('IS_NATIVE_API', isset($_SERVER['IS_API_ENDPOINT']));
        define('RELEASE', (IS_API_ENDPOINT ? 'api' : 'crisp') . '@' . (IS_API_ENDPOINT ? self::API_VERSION : self::CRISP_VERSION) . '+' . Helper::getCommitHash());
        define('REQUEST_ID', Crypto::UUIDv4());
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

        spl_autoload_register(static function ($class) {
            $file = __DIR__ . '/class/' . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

            if (file_exists($file)) {
                require $file;
                return true;
            }
            return false;
        });

        /** @var \Flagsmith\Flagsmith */
        $GLOBALS['flagsmith_server'] = Flagsmith::Client('CRISP_FLAGSMITH_API_KEY', 'CRISP_FLAGSMITH_APP_URL', 300);


        if (isset($_ENV['COMPILE_EE'])) {
            /** @var License */
            $GLOBALS['License'] = new License($_ENV['LICENSE_FILE']);


            $ServerID = (new Identity($GLOBALS['License']->getMachineID()))
                ->withTrait((new IdentityTrait('commit'))
                    ->withValue(Helper::getCommitHash()))
                ->withTrait((new IdentityTrait('hostname'))
                    ->withValue(gethostname()))
                ->withTrait((new IdentityTrait('crisp_version'))
                    ->withValue(self::CRISP_VERSION))
                ->withTrait((new IdentityTrait('api_version'))
                    ->withValue(self::API_VERSION))
                ->withTrait((new IdentityTrait('environment'))
                    ->withValue(ENVIRONMENT));


            $GLOBALS['flagsmith_server_identity'] = $ServerID;
        } else {
            $ServerID = (new Identity(Helper::getMachineID()))
                ->withTrait((new IdentityTrait('commit'))
                    ->withValue(Helper::getCommitHash()))
                ->withTrait((new IdentityTrait('hostname'))
                    ->withValue(gethostname()))
                ->withTrait((new IdentityTrait('crisp_version'))
                    ->withValue(self::CRISP_VERSION))
                ->withTrait((new IdentityTrait('api_version'))
                    ->withValue(self::API_VERSION))
                ->withTrait((new IdentityTrait('environment'))
                    ->withValue(ENVIRONMENT));

            $GLOBALS['flagsmith_server_identity'] = $ServerID;
        }

        $GLOBALS['flagsmith_server']->setTraitsByIdentity($GLOBALS['flagsmith_server_identity']);


            init([
                'dsn' => $_ENV['SENTRY_DSN'],
                'traces_sample_rate' => 1.0,
                'environment' => ENVIRONMENT,
                'release' => RELEASE,
            ]);

            configureScope(function (Scope $scope): void {
                $scope->setTag('request_id', REQUEST_ID);
            });


        header('X-Request-ID: ' . REQUEST_ID);

    }

}

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    define('IS_DEV_ENV', (isset($_SERVER['ENVIRONMENT']) && $_SERVER['ENVIRONMENT'] !== 'production'));
    define('ENVIRONMENT', match (strtolower($_SERVER['ENVIRONMENT'] ?? 'production')) {
        'staging' => 'staging',
        'development' => 'development',
        default => 'production'
    });


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
        'ENVIRONMENT'
    ])->notEmpty();
    $dotenv->required(['REDIS_INDEX', 'REDIS_PORT'])->isInteger();
    $dotenv->required('POSTGRES_URI')->allowedRegexValues('/^(?:([^:\/?#\s]+):\/{2})?(?:([^@\/?#\s]+)@)?([^\/?#\s]+)?(?:\/([^?#\s]*))?(?:[?]([^#\s]+))?\S*$/i');


    core::bootstrap();

    setlocale(LC_TIME, $_ENV["LANG"] ?? 'de_DE.utf8');
    if (PHP_SAPI !== 'cli') {

        $GLOBALS['route'] = api\Helper::processRoute($_GET['route']);

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


        $CurrentTheme = Config::get('theme');
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
        if (ENVIRONMENT === 'production' && $GLOBALS['flagsmith_server']->isFeatureEnabledByIdentity($GLOBALS['flagsmith_server_identity'], 'cache_twig')) {
            $TwigTheme = new Environment($ThemeLoader, [
                'cache' => __DIR__ . '/cache/'
            ]);
        } else {
            $TwigTheme = new Environment($ThemeLoader, []);
        }


        if (Sessions::isSessionValid()) {
            $GLOBALS["user"] = $_SESSION[core\Config::$Cookie_Prefix . "session_login"]["user"];
            $TwigTheme->addGlobal("user", $GLOBALS["user"]);

            $LoggedInID = (new Identity($GLOBALS['user']['sub']))
                ->withTrait((new IdentityTrait('session_id'))
                    ->withValue($GLOBALS['guid'] ?? 'unknown'))
                ->withTrait((new IdentityTrait('route'))
                    ->withValue($GLOBALS['route']->Page ?? 'unknown'))
                ->withTrait((new IdentityTrait('locale'))
                    ->withValue($Locale ?? $_ENV['DEFAULT_LOCALE']))
                ->withTrait((new IdentityTrait('email'))
                    ->withValue($GLOBALS['user']['email'] ?? 'unknown'))
                ->withTrait((new IdentityTrait('preferred_username'))
                    ->withValue($GLOBALS['user']['preferred_username'] ?? 'unknown'))
                ->withTrait((new IdentityTrait('country_code'))
                    ->withValue($_ENV["GEOIP_COUNTRY_CODE"] ?? 'NONE'))
                ->withTrait((new IdentityTrait('country_code3'))
                    ->withValue($_ENV["GEOIP_COUNTRY_CODE3"] ?? 'NONE'))
                ->withTrait((new IdentityTrait('country_name'))
                    ->withValue($_ENV["GEOIP_COUNTRY_NAME"] ?? 'NONE'));

            $GLOBALS['flagsmith_identity'] = $LoggedInID;

        } else {

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

        }

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

        if ($GLOBALS['flagsmith_server']->isFeatureEnabledByIdentity($GLOBALS['flagsmith_server_identity'], 'prettydump_enabled')) {
            $TwigTheme->addFunction(new TwigFunction('prettyDump', [new Helper(), 'prettyDump']));
        }
        if ($GLOBALS['flagsmith_server']->isFeatureEnabledByIdentity($GLOBALS['flagsmith_server_identity'], 'twig_string_loader_ext')) {
            $TwigTheme->addExtension(new StringLoaderExtension());
        }
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


        $RedisClass = new Redis();
        $rateLimiter = new RedisRateLimiter($RedisClass->getDBConnector());

        if (file_exists(__DIR__ . "/../themes/$CurrentTheme/hook.php") && $GLOBALS['flagsmith_server']->isFeatureEnabledByIdentity($GLOBALS['flagsmith_server_identity'], 'theme_hooks_enabled')) {
            require_once __DIR__ . "/../themes/$CurrentTheme/hook.php";
        }

        if (IS_API_ENDPOINT && $GLOBALS['flagsmith_server']->isFeatureEnabledByIdentity($GLOBALS['flagsmith_server_identity'], 'enable_api',)) {

            header('Access-Control-Allow-Origin: *');
            header('Cache-Control: max-age=600, public, must-revalidate');

            if (!isset($_SERVER['HTTP_USER_AGENT']) || empty($_SERVER['HTTP_USER_AGENT']) || $_SERVER['HTTP_USER_AGENT'] === 'i am not valid') {
                http_response_code(403);
                echo $TwigTheme->render('errors/nginx/403.twig', ['error_msg' => 'Request forbidden by administrative rules. Please make sure your request has a User-Agent header']);
                exit;
            }

            $keyDetails = null;
            if (isset(apache_request_headers()['Authorization'])) {
                $keyDetails = api\Helper::getAPIKeyDetails(apache_request_headers()['Authorization']);

                if ($keyDetails['expires_at'] !== null && strtotime($keyDetails['expires_at']) < time()) {
                    header('X-APIKey: expired');
                } elseif ($keyDetails['revoked']) {
                    header('X-APIKey: revoked');
                } else {
                    header('X-APIKey: ok');
                }
            } else {
                header('X-APIKey: not-given');
            }

            if (api\Helper::getAPIKeyFromHeaders() !== null && !api\Helper::getAPIKey()) {
                http_response_code(401);
                echo $TwigTheme->render('errors/nginx/401.twig', ['error_msg' => 'Request forbidden by administrative rules. Please make sure your request has a valid Authorization header']);
                exit;
            }

            $Benefit = 'Guest';
            $IndicatorSecond = 's_' . Helper::getRealIpAddr();
            $IndicatorHour = 'h_' . Helper::getRealIpAddr();
            $IndicatorDay = 'd_' . Helper::getRealIpAddr();

            $LimitSecond = Rate::perSecond(15);
            $LimitHour = Rate::perHour(1000);
            $LimitDay = Rate::perDay(15000);

            $apikey = api\Helper::getAPIKey();
            if ($apikey) {

                if ($keyDetails['ratelimit_second'] === null) {
                    $LimitSecond = Rate::perSecond(150);
                } else {
                    $LimitSecond = Rate::perSecond($keyDetails['ratelimit_second']);
                }
                if ($keyDetails['ratelimit_hour'] === null) {
                    $LimitHour = Rate::perHour(10000);
                } else {
                    $LimitHour = Rate::perHour($keyDetails['ratelimit_hour']);
                }

                if ($keyDetails['ratelimit_day'] === null) {
                    $LimitDay = Rate::perDay(50000);
                } else {
                    $LimitDay = Rate::perDay($keyDetails['ratelimit_day']);
                }

                $Benefit = $keyDetails['ratelimit_benefit'] ?? 'Partner';
            }

            $statusSecond = $rateLimiter->limitSilently($_ENV['REDIS_PREFIX'] ?? 'crispcms_' . $IndicatorSecond, $LimitSecond);
            $statusHour = $rateLimiter->limitSilently($_ENV['REDIS_PREFIX'] ?? 'crispcms_' . $IndicatorHour, $LimitHour);
            $statusDay = $rateLimiter->limitSilently($_ENV['REDIS_PREFIX'] ?? 'crispcms_' . $IndicatorDay, $LimitDay);

            header('X-RateLimit-Benefit: ' . $Benefit);
            header('X-RateLimit-S: ' . $statusSecond->getRemainingAttempts());
            header('X-RateLimit-H: ' . $statusHour->getRemainingAttempts());
            header('X-RateLimit-D: ' . $statusDay->getRemainingAttempts());
            header('X-RateLimit-Benefit: ' . $Benefit);
            header('X-CMS-API: ' . api\Config::get('api_cdn'));
            header('X-CMS-API-VERSION: ' . core::API_VERSION);

            if ($statusSecond->limitExceeded() || $statusHour->limitExceeded() || $statusDay->limitExceeded()) {
                http_response_code(429);
                echo $TwigTheme->render('errors/nginx/429.twig', ['error_msg' => 'Request forbidden by administrative rules. You are sending too many requests in a certain timeframe.']);
                exit;
            }


            core\Themes::loadAPI($TwigTheme, $GLOBALS['route']->Page);
        }

        if (!$GLOBALS['route']->Language) {
            header("Location: /$Locale/$CurrentPage");
            exit;
        }

        Themes::load($TwigTheme, $CurrentFile, $CurrentPage);
    }
} catch (TypeError | Exception | Error | CompileError | ParseError | Throwable $ex) {
    captureException($ex);
    error_log(var_export($ex, true));
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
        $refid = $ex->getMessage();
    }


    if (IS_API_ENDPOINT && $GLOBALS['flagsmith_server']->isFeatureEnabledByIdentity($GLOBALS['flagsmith_server_identity'], 'enable_api')) {
        RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, 'Internal Server Error', ['reference_id' => $refid]);
        exit;
    }

    echo strtr($errorraw, ['{{ exception }}' => $refid, '{{ sentry_id }}' => SentrySdk::getCurrentHub()->getLastEventId()]);
    exit;
}

