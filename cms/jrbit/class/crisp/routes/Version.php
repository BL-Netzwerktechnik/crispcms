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


namespace crisp\routes;

use crisp\api\Cache;
use crisp\core;
use crisp\core\Bitmask;
use crisp\core\RESTfulAPI;
use crisp\models\ThemeAPI;
use finfo;
use Twig\Environment;

/**
 * Used internally, theme loader
 *
 */
class Version extends ThemeAPI  {


    public function execute(string $Interface, Environment $TwigTheme): void
    {

        $license = null;

        if(\crisp\api\License::isLicenseAvailable()){
            $licobj = \crisp\api\License::fromDB();
            $license = json_decode($licobj->encode(), true);

            $license["valid"] = $licobj->isValid();
        }

        RESTfulAPI::response(Bitmask::REQUEST_SUCCESS->value, "This site is running CrispCMS!", [
            "version" => [
                "crisp" => core::CRISP_VERSION,
                "api" => core::API_VERSION,
                "release" => core::RELEASE_NAME,
                "theme" => $_ENV["GIT_TAG"] ?? $_ENV["GIT_COMMIT"]
            ],
            "release" => RELEASE,
            "environment" => ENVIRONMENT,
            "build" => BUILD_TYPE,
            "installed_license" => $license
        ]);
        exit;


    }
}
