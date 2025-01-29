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

use crisp\core\Bitmask;
use crisp\core\Logger;
use crisp\core\RESTfulAPI;
use crisp\api\Build;

/**
 * Used internally, theme loader.
 */
class Version
{
    public function preRender(): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $license = null;

        if (\crisp\api\License::isLicenseAvailable()) {
            $licobj = \crisp\api\License::fromDB();
            $license = json_decode($licobj->encode(), true);

            $license["valid"] = $licobj->isValid();
            unset($license["instance"]);
        }

        RESTfulAPI::response(Bitmask::REQUEST_SUCCESS->value, "This site is running CrispCMS!", [
            "release" => Build::getReleaseString(),
            "environment" => Build::getEnvironment(),
            "build" => Build::getBuildType(),
            "installed_license" => $license,
        ]);

        return;

    }
}
