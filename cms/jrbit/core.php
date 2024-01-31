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

use crisp\api\Build;
use crisp\api\Helper;
use crisp\core\Bitmask;
use crisp\core\Crypto;
use crisp\core\Environment;
use crisp\core\HookFile;
use crisp\core\RESTfulAPI;
use crisp\core\Sessions;
use crisp\core\Themes;
use crisp\core\Logger;
use crisp\core\Router;
use crisp\core\ThemeVariables;
use Dotenv\Dotenv;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use Sentry\Tracing\TransactionContext;
use Twig\Loader\FilesystemLoader;
use function Sentry\captureException;
use function Sentry\configureScope;
use function Sentry\init;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Core class, nothing else.
 *
 * @author Justin Back <j.back@jrbit.de>
 */
class core
{
    /* Some important constants */

    /**
     * Location of the Persistent Storage.
     */
    public const PERSISTENT_DATA = "/data";

    /**
     * Default Theme.
     */
    public const DEFAULT_THEME = "crisptheme";

    /**
     * Default Cache Location.
     */
    public const CACHE_DIR = '/tmp/crisp-cache';

    /**
     * Default Theme Root Folder.
     */
    public const THEME_BASE_DIR = __DIR__ . '/../themes';

    public const LOG_DIR = '/var/log/crisp';


    public static function init()
    {

        try {
            if (!defined('STDIN')) {
                define('STDIN', fopen('php://stdin', 'rb'));
            }
            if (!defined('STDOUT')) {
                define('STDOUT', fopen('php://stdout', 'wb'));
            }
            if (!defined('STDERR')) {
                define('STDERR', fopen('php://stderr', 'wb'));
            }
            define('IS_DEV_ENV', (Build::getEnvironment() === Environment::DEVELOPMENT));
            define('ENVIRONMENT', Build::getEnvironment()->value);

            if (!file_exists(core::PERSISTENT_DATA)) {
                Helper::createDir(core::PERSISTENT_DATA);
            }

            define("ThemeMetadata", Themes::getThemeMetadata());

            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            if (IS_DEV_ENV) {
                $dotenv->safeLoad();
            }

            $dotenv->required([
                'POSTGRES_URI',
                'DEFAULT_LOCALE',
                'ENVIRONMENT',
                'HOST',
                'PROTO',
                'TZ',
                'LANG',
            ])->notEmpty();
            $dotenv->required('POSTGRES_URI')->allowedRegexValues('/^(?:([^:\/?#\s]+):\/{2})?(?:([^@\/?#\s]+)@)?([^\/?#\s]+)?(?:\/([^?#\s]*))?(?:[?]([^#\s]+))?\S*$/i');

            Helper::getInstanceId();

            $GLOBALS['dotenv'] = $dotenv;

            define('CRISP_HOOKED', true);
            /* Core headers, can be accessed anywhere */
            /* After autoloading we include additional headers below */

            define('IS_API_ENDPOINT', (PHP_SAPI !== 'cli' && isset($_SERVER['IS_API_ENDPOINT'])));
            define('IS_NATIVE_API', isset($_SERVER['IS_API_ENDPOINT']));
            define('REQUEST_ID', Crypto::UUIDv4("R"));

            if (PHP_SAPI !== 'cli') {
                Logger::getLogger(__METHOD__)->info(Helper::getRequestLog());
            }

            if (isset($_ENV['SENTRY_DSN'])) {

                init([
                    'dsn' => $_ENV['SENTRY_DSN'],
                    'traces_sample_rate' => (float)$_ENV['SENTRY_SAMPLE_RATE'] ?? 0.3,
                    'profiles_sample_rate' => (float)$_ENV['SENTRY_PROFILES_SAMPLE_RATE'] ?? 0.3,
                    'environment' => Build::getEnvironment()->value,
                    'release' => Themes::getReleaseString() ?? Build::getReleaseString(),
                ]);

                configureScope(function (Scope $scope): void {
                    $scope->setTag('request_id', REQUEST_ID);
                });
            }

            define('VM_IP', exec('hostname -I'));

            header('X-Request-ID: ' . REQUEST_ID);

            setlocale(LC_TIME, $_ENV["LANG"] ?? 'en_US.utf8');
            if (PHP_SAPI !== 'cli') {

                $transactionContext = (new TransactionContext("HTTP Request"));
                $transactionContext->setOp("http.server");
                $transaction = \Sentry\startTransaction($transactionContext);

                \Sentry\SentrySdk::getCurrentHub()->setSpan($transaction);

                $GLOBALS['plugins'] = [];
                $GLOBALS['hook'] = [];
                $GLOBALS['navbar'] = [];
                $GLOBALS['navbar_right'] = [];
                $GLOBALS['render'] = [];
                /*
        ini_set('session.save_path',core::PERSISTENT_DATA . "/sessions");
        session_save_path(core::PERSISTENT_DATA . "/sessions");
        ini_set('session.gc_probability', 1);
        */
                session_start();

                $CurrentTheme = core::DEFAULT_THEME;
                Themes::autoload();

                api\Helper::setLocale();
                $Locale = Helper::getLocale();

                if (!isset($_COOKIE['guid'])) {
                    $GLOBALS['guid'] = Crypto::UUIDv4();
                    setcookie('guid', $GLOBALS['guid'], time() + (86400 * 30), '/');
                } else {
                    $GLOBALS['guid'] = $_COOKIE['guid'];
                }

                define("IS_SPECIAL_PAGE", str_starts_with($_SERVER['REQUEST_URI'], "/_"));
                Themes::initRenderer();

                ThemeVariables::register();
                Router::register();
                HookFile::setup();

                if (Build::requireLicense() && !IS_SPECIAL_PAGE) {
                    $GLOBALS["license"] = api\License::fromDB();

                    if (!$GLOBALS["license"] || !$GLOBALS["license"]->isValid()) {
                        header("Location: /_/license#renew");
                        \Sentry\SentrySdk::getCurrentHub()->setSpan($transaction);
                        $transaction->finish();
                        exit;
                    }
                }

                /* Twig Globals */

                if (IS_API_ENDPOINT) {

                    header('Access-Control-Allow-Origin: *');
                    header('Cache-Control: max-age=600, public, must-revalidate');

                    new RESTfulAPI();
                } else {
                    Themes::load();
                }
                \Sentry\SentrySdk::getCurrentHub()->setSpan($transaction);
                $transaction->finish();
            }
        } catch (\TypeError | \Exception | \Error | \CompileError | \ParseError | \Throwable $ex) {
            captureException($ex);
            Logger::getLogger(__METHOD__)->critical($ex->__toString(), (array) $ex);
            if (PHP_SAPI === 'cli')  exit(1);

            http_response_code(500);

            if (defined('REQUEST_ID')) {
                $refid = REQUEST_ID;
            } else {
                $refid = 'Core';
            }
            if (IS_API_ENDPOINT) {
                RESTfulAPI::response(Bitmask::GENERIC_ERROR->value, 'Internal Server Error', ['reference_id' => $refid]);
                return;
            }


            Themes::renderErrorPage($ex);

            return;
        }
    }
}