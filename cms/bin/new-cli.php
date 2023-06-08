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


use crisp\api\Config;
use crisp\api\Helper;
use crisp\commands\Version;
use crisp\core;
use crisp\core\Migrations;
use crisp\core\Themes;
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;


if (PHP_SAPI !== 'cli') {
    Helper::Log(1, "Not from CLI");
    exit;
}

require_once __DIR__ . "/../jrbit/core.php";


class Minimal extends CLI
{
    // register options and arguments
    protected function setup(Options $options)
    {
        $options->setHelp('Interact with CrispCMS');
        $options->registerOption('version', 'print version', 'v');
    }

    // implement your code
    protected function main(Options $options)
    {
        if($options->getOpt("version")){
            Version::run($this);
            exit;
        }

        echo $options->help();
    }
}
$cli = new Minimal();
$cli->run();