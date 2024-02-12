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

use crisp\api\Helper;
use crisp\commands\License;
use crisp\commands\Assets;
use crisp\commands\Crisp;
use crisp\commands\Maintenance;
use crisp\commands\Migration;
use crisp\commands\Storage;
use crisp\commands\Theme;
use crisp\commands\Translations;
use crisp\commands\Version;
use crisp\core;
use crisp\core\CLI as CoreCLI;
use crisp\core\HookFile;
use crisp\core\Logger;
use crisp\core\Themes;
use splitbrain\phpcli\CLI as SplitbrainCLI;
use splitbrain\phpcli\Options;

if (PHP_SAPI !== 'cli') {
    exit;
}


// Check if user is www-data
if (posix_getuid() !== 33) {
    echo "Please run this script as www-data\n";
    exit(1);
}

require_once __DIR__ . "/../jrbit/core.php";

class CLI extends SplitbrainCLI
{
    // register options and arguments
    protected function setup(Options $options)
    {

        $options->setHelp('Interact with CrispCMS');

        $options->registerOption('loglevel', 'Override LogLevel');
        CoreCLI::register($options);
    }

    protected function main(Options $options)
    {


        if ($options->getOpt("loglevel")) {
            Logger::overrideLogLevel($options->getOpt("loglevel"));
        }

        core::init();
        try {
            HookFile::setupCli();
        } catch(Exception $ex){
            $this->warning($ex->__toString());
        }
    
        CoreCLI::runOption($this, $options);
    }
}
$cli = new CLI();
$cli->run();