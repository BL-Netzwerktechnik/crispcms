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

use splitbrain\phpcli\CLI;

/**
 * I like the way phpcli outputs the log, so lets initiate the abstract class
 */
class Logger extends CLI
{

    public static function startTiming(float &$output = null): void {
        $output = microtime(true);
    }

    public static function endTiming(float &$start): string {
        $ms = (float)(microtime(true) - $start);
        unset($start);
        return sprintf("%.3f", $ms * 1000);
    }

    protected function setup(\splitbrain\phpcli\Options $options)
    {
        // TODO: Implement setup() method.
    }

    protected function main(\splitbrain\phpcli\Options $options)
    {
        // TODO: Implement main() method.
    }
}