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

namespace crisp\api;

use crisp\core\Bitmask;
use crisp\exceptions\BitmaskException;
use Flagsmith\Models\Identity;
use stdClass;
use crisp\core\RESTfulAPI;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Unleash\Client\Unleash;
use Unleash\Client\UnleashBuilder;

class Flagsmith
{

    public static function Client(string $EnvApiKey = 'FLAGSMITH_API_KEY', string $EnvAppUrl = 'FLAGSMITH_APP_URL', int $TTL = 15, bool $UseCache = true): \Flagsmith\Flagsmith
    {
        if (empty($_ENV[$EnvApiKey])) {
            throw new \Exception("Missing Environment Variable $EnvApiKey");
        }
        if (empty($_ENV[$EnvAppUrl])) {
            throw new \Exception("Missing Environment Variable $EnvAppUrl");
        }

        Helper::Log(3, "Using $_ENV[$EnvApiKey] to connect to $_ENV[$EnvAppUrl]");

        $Flagsmith = new \Flagsmith\Flagsmith($_ENV[$EnvApiKey], $_ENV[$EnvAppUrl]);

        if ($UseCache) {
            return $Flagsmith->withTimeToLive(15)->withCache(new Psr16Cache(new FilesystemAdapter($_ENV[$EnvApiKey], $TTL))); //15 seconds
        }
        return $Flagsmith;
    }

    public static function isFeatureEnabledByIdentity(string $name, bool $default = false, string $IdentityOverride = 'flagsmith_identity')
    {
        return self::Client()->isFeatureEnabledByIdentity($GLOBALS[$IdentityOverride], $name, $default);
    }

    public static function getFeatureValueByIdentity(string $name, $default = null, string $IdentityOverride = 'flagsmith_identity')
    {
        return self::Client()->getFeatureValueByIdentity($GLOBALS[$IdentityOverride], $name, $default);
    }

    public static function getFlagByIdentity(string $name, string $IdentityOverride = 'flagsmith_identity')
    {
        return self::Client()->getFlagByIdentity($GLOBALS[$IdentityOverride], $name);
    }

    public static function setTraitsByIdentity(string $IdentityOverride = 'flagsmith_identity')
    {
        return self::Client()->setTraitsByIdentity($GLOBALS[$IdentityOverride]);
    }

    public static function getTraitsByIdentity(string $IdentityOverride = 'flagsmith_identity')
    {
        return self::Client()->getTraitsByIdentity($GLOBALS[$IdentityOverride]);
    }

    public static function getFlagsByIdentity(string $IdentityOverride = 'flagsmith_identity')
    {
        return self::Client()->getFlagsByIdentity($GLOBALS[$IdentityOverride]);
    }

    public static function getIdentityByIndentity(string $IdentityOverride = 'flagsmith_identity')
    {
        return self::Client()->getIdentityByIndentity($GLOBALS[$IdentityOverride]);
    }


}
