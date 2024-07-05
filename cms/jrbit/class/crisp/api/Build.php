<?php

/*
 * Copyright (c) 2023. JRB IT, All Rights Reserved
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

namespace crisp\api;

use crisp\core\Environment;
use crisp\core\Logger;
use crisp\core\Tracing;

/**
 * Build related functions.
 */
class Build
{
    public static function getReleaseString(): string
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]);

        $context = new \Sentry\Tracing\SpanContext();
        $context->setOp(__METHOD__);
        $context->setDescription('Generating Release String');

        return Tracing::traceFunction($context, function () {
            return sprintf(
                '%s-%s.%s',
                $_ENV['GIT_TAG'],
                Build::getBuildType(),
                $_ENV['CI_BUILD'] ?? 0
            );

        });
    }

    public static function licenseKeyIsDefined(): bool
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]);
        return (array_key_exists('LICENSE_KEY', $_ENV) && $_ENV['LICENSE_KEY'] !== '' ? true : false) || Config::exists('license_key');
    }

    public static function requireLicenseServer(): bool {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]);
        return array_key_exists('LICENSE_SERVER', $_ENV) && $_ENV['LICENSE_SERVER'] !== '' ? true : false;
    }

    public static function requireLicense(): bool {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]);
        return $_ENV['REQUIRE_LICENSE'] === "true" ? true : false;
    }

    public static function getEnvironment(): Environment {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]);
        return match (strtolower($_SERVER['ENVIRONMENT'] ?? 'production')) {
            'staging' => Environment::STAGING,
            'development' => Environment::DEVELOPMENT,
            default => Environment::PRODUCTION
        };
    }

    public static function getVersion(): string
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]);

        $context = new \Sentry\Tracing\SpanContext();
        $context->setOp(__METHOD__);
        $context->setDescription('Generating Version String');

        return Tracing::traceFunction($context, function () {
            return $_ENV['GIT_TAG'] ?? '0.0.0';
        });
    }

    /**
     * Get current build type.
     *
     * @return string
     */
    public static function getBuildType(): string
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]);

        $context = new \Sentry\Tracing\SpanContext();
        $context->setOp(__METHOD__);
        $context->setDescription('Generating Build Type String');

        return Tracing::traceFunction($context, function () {
            if (str_contains(strtolower(self::getVersion()), 'rc.')) {
                $BuildType = 2;
            } elseif (self::getVersion() !== '0.0.0' && preg_match('/^\d+\.\d+\.\d+$/', self::getVersion())) {
                $BuildType = 1;
            } else {
                $BuildType = 0;
            }

            return match ($BuildType) {
                1 => 'Stable',
                2 => 'Pre-Release',
                default => 'Nightly'
            };

        });
    }
}
