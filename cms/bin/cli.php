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
use splitbrain\phpcli\CLI as SplitbrainCLI;
use splitbrain\phpcli\Options;


if (PHP_SAPI !== 'cli') {
    Helper::Log(1, "Not from CLI");
    exit;
}

require_once __DIR__ . "/../jrbit/core.php";


class CLI extends SplitbrainCLI
{
    // register options and arguments
    protected function setup(Options $options)
    {
        $options->setHelp('Interact with CrispCMS');
        /** Global Options */
        $options->registerOption('version', 'print version', 'v');
        /** Global Options */

        /** Maintenance Command */
        $options->registerCommand('maintenance', 'Get or Set the Maintenance Status of CrispCMS');
        $options->registerOption('on', 'Turn on the Maintenance Mode', null, false, 'maintenance');
        $options->registerOption('off', 'Turn off the Maintenance Mode', null, false, 'maintenance');
        /** Maintenance Command */

        /** Maintenance Command */
        $options->registerCommand('license', 'Manage the Licensing System on CrispCMS');
        $options->registerOption('generate-private-key', 'Generates a new key pair and saves it to '. core::PERSISTENT_DATA, "c", false, 'license');
        $options->registerOption('info', 'Get Info from your current '. core::PERSISTENT_DATA. "/license.key", "i", false, 'license');
        $options->registerOption('generate-test', 'Generate a Test License to '. core::PERSISTENT_DATA. "/license.key", "t", false, 'license');
        $options->registerOption('expired', 'Generate an Expired License', "e", false, 'license');
        $options->registerOption('no-expiry', 'Don\'t Expire the Test License', null, false, 'license');
        $options->registerOption('invalid-instance', 'Generate an invalid instance license', null, false, 'license');
        $options->registerOption('delete', 'Delete the License Key', "d", false, 'license');
        $options->registerOption('delete-issuer', 'Delete the License Key', null, false, 'license');
        /** Maintenance Command */

        /** Crisp Command */
        $options->registerCommand('crisp', 'Perform various tasks in the core of crisp');
        $options->registerOption('migrate', 'Run the Database Migrations', "m", false, 'crisp');
        $options->registerOption('post-install', 'Run the Post Install Actions', "p", false, 'crisp');
        $options->registerOption('instance', 'Get your instance ID', "i", false, 'crisp');
        /** Crisp Command */

        /** Crisp Command */
        $options->registerCommand('assets', 'Perform various tasks for theme assets');
        $options->registerOption('deploy-to-s3', 'Deploy the assets/ folder to s3', "d", false, 'assets');
        /** Crisp Command */


        /** Theme Command */
        $options->registerCommand('theme', 'Interact with your Theme for CrispCMS');
        $options->registerOption('boot', 'Execute Boot files of your theme', "b", false, 'theme');
        $options->registerOption('clear-cache', 'Clear Cache of the CMS', "c", false, 'theme');
        $options->registerOption('migrate', 'Migrate the Database for your theme', "m", false, 'theme');
        $options->registerOption('install', 'Install the Theme mounted to crisptheme', "i", false, 'theme');
        $options->registerOption('uninstall', 'Uninstall the Theme mounted to crisptheme', "u", false, 'theme');
        /** Theme Command */


        /** Migration Command */
        $options->registerCommand('migration', 'Interact with CrispCMS Migrations');
        $options->registerOption('core', 'Create a new Core Migration File', "c", "migrationName", 'migration');
        $options->registerOption('theme', 'Create a new Migration File for your Theme', "t", "migrationName", 'migration');
        $options->registerArgument('migrationName', 'The name of your migration', true, 'migration');
        /** Migration Command */

        /** Storage Command */
        $options->registerCommand('storage', 'Interact with Crisps KVS');
        $options->registerOption('install', 'Initialize the KVS from the theme.json', "i", false, 'storage');
        $options->registerOption('force', 'Overwrite the KVS from the theme.json', "f", false, 'storage');
        $options->registerOption('uninstall', 'Delete all KVS Items from the database', "u", false, 'storage');
        /** Storage Command */

        /** Translations Command */
        $options->registerCommand('translation', 'Interact with Crisps KVS');
        $options->registerOption('install', 'Initialize the Translations from the theme.json', "i", false, 'translation');
        $options->registerOption('uninstall', 'Delete all Translation Items from the database', "u", false, 'translation');
        /** Translations Command */

    }

    // implement your code
    protected function main(Options $options)
    {
        if($options->getOpt("version")){
            Version::run($this);
            exit;
        }


        switch ($options->getCmd()) {
            case 'maintenance':
                Maintenance::run($this, $options);
                break;
            case 'theme':
                Theme::run($this, $options);
                break;
            case 'migration':
                Migration::run($this, $options);
                break;
            case 'crisp':
                Crisp::run($this, $options);
                break;
            case 'storage':
                Storage::run($this, $options);
                break;
            case 'translation':
                Translations::run($this, $options);
                break;
            case 'assets':
                Assets::run($this, $options);
                break;
            case 'license':
                License::run($this, $options);
                break;
            default:
                if(strlen($options->getCmd()) > 0){
                    $this->error(sprintf("\"%s\" command is not configured", $options->getCmd()));
                }
                $options->useCompactHelp();
                echo $options->help();
                exit;
        }
    }
}
$cli = new CLI();
$cli->run();