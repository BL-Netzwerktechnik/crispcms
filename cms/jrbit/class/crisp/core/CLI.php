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

use CLI as GlobalCLI;
use crisp\api\Helper;
use crisp\api\License;
use crisp\commands\Assets;
use crisp\commands\Crisp;
use crisp\commands\Cron;
use crisp\commands\License as CommandsLicense;
use crisp\commands\Maintenance;
use crisp\commands\Migration;
use crisp\commands\Storage;
use crisp\commands\Theme;
use crisp\commands\Translations;
use crisp\commands\Version;
use DirectoryIterator;
use PhpCsFixer\Console\Application;

class CLI
{

    public static function get(): Application
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        return $GLOBALS["Crisp_CLI"];
    }

    
    private static function registerCrispCLI(): Application
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);



        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $application = self::get();

        $iterator = new DirectoryIterator(__DIR__. '/../CommandControllers/');
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile()) {
                $fileName = $fileInfo->getFilename();

                $className = pathinfo($fileName, PATHINFO_FILENAME);
                Logger::getLogger(__METHOD__)->debug("Loading command $className");

                $namespace = '\\crisp\\CommandControllers\\';
                $constructedClass = $namespace.$className;
                $application->add(new $constructedClass());

                Logger::getLogger(__METHOD__)->debug("Loaded command $className");
            }
        }



        return $application;

        /** 
        self::registerOption(long: 'version', help: 'print version', short: 'v', needsarg: false, command: '', class: Version::class, callable: "run");

        self::registerOption(long: 'check-permissions', help: 'Check file permissions', short: null, needsarg: false, command: '', class: Crisp::class, callable: "run");
        self::registerOption(long: 'instance-id', help: 'print instance id', short: 'i', needsarg: false, command: '', class: Crisp::class, callable: "run");
        self::registerOption(long: 'migrate', help: 'Run Crisp Core Database Migrations', short: 'm', needsarg: false, command: '', class: Crisp::class, callable: "run");
        self::registerOption(long: 'clear-cache', help: 'Clear Cache of the CMS', short: null, needsarg: false, command: '', class: Crisp::class, callable: "run");
        self::registerOption(long: 'post-install', help: 'Run the Post Install Actions', short: "p", needsarg: false, command: '', class: Crisp::class, callable: "run");

        self::registerOption(long: 'no-formatting', help: 'Remove formatting of getter methods', short: 'n', needsarg: false, command: '', class: null, callable: null);

        self::registerCommand(command: "maintenance", help: "Get or Set the Maintenance Status of CrispCMS");
        self::registerOption(long: 'on', help: 'Turn on the Maintenance Mode', short: null, needsarg: false, command: 'maintenance', class: Maintenance::class, callable: "run");
        self::registerOption(long: 'off', help: 'Turn off the Maintenance Mode', short: null, needsarg: false, command: 'maintenance', class: Maintenance::class, callable: "run");




        self::registerCommand(command: "license", help: "Manage the Licensing System on CrispCMS");
        self::registerOption(long: 'generate-issuer-private', help: 'Generates a new key pair', short: "c", needsarg: false, command: 'license', class: CommandsLicense::class, callable: "run");
        self::registerOption(long: 'info', help: 'Get Info from your current installed License', short: "i", needsarg: false, command: 'license', class: CommandsLicense::class, callable: "run");
        self::registerOption(long: 'generate-development', help: 'Generate a Test License', short: "t", needsarg: false, command: 'license', class: CommandsLicense::class, callable: "run");
        self::registerOption(long: 'expired', help: 'Generate an Expired License', short: "e", needsarg: false, command: 'license', class: CommandsLicense::class, callable: "run");
        self::registerOption(long: 'invalid-instance', help: 'Generate an invalid instance license', short: null, needsarg: false, command: 'license', class: CommandsLicense::class, callable: "run");


        self::registerOption(long: 'delete-data', help: 'Delete the License Data', short: "d", needsarg: false, command: 'license', class: CommandsLicense::class, callable: "run");
        self::registerOption(long: 'delete-key', help: 'Delete the License Key', short: null, needsarg: false, command: 'license', class: CommandsLicense::class, callable: "run");
        self::registerOption(long: 'delete-issuer-public', help: 'Delete the Issuer Key', short: null, needsarg: false, command: 'license', class: CommandsLicense::class, callable: "run");
        self::registerOption(long: 'delete-issuer-private', help: 'Delete the Issuer Private Key', short: null, needsarg: false, command: 'license', class: CommandsLicense::class, callable: "run");
        self::registerOption(long: 'get-issuer-public', help: 'Get the Issuer Public Key', short: null, needsarg: false, command: 'license', class: CommandsLicense::class, callable: "run");
        self::registerOption(long: 'get-issuer-private', help: 'Get the Issuer Private Key', short: null, needsarg: false, command: 'license', class: CommandsLicense::class, callable: "run");
        self::registerOption(long: 'pull', help: 'Pull License From License Server', short: "p", needsarg: false, command: 'license', class: CommandsLicense::class, callable: "run");
        self::registerOption(long: 'pull-key', help: 'Pull License From License Server using the specified <key>', short: null, needsarg: "key", command: 'license', class: CommandsLicense::class, callable: "run");
        self::registerArgument('key', 'Your License Key', false, "license");

        self::registerCommand(command: "assets", help: "Perform various tasks for theme assets");
        self::registerOption(long: 'deploy-to-s3', help: 'Deploy the assets/ folder to s3', short: 'd', needsarg: false, command: 'assets', class: Assets::class, callable: "run");

        self::registerCommand(command: "cron", help: "Perform various tasks for the built in scheduler");
        self::registerOption(long: 'run', help: 'Run the scheduler', short: null, needsarg: false, command: 'cron', class: Cron::class, callable: "run");


        self::registerCommand(command: "theme", help: "Interact with your Theme for CrispCMS");
        self::registerOption(long: 'boot', help: 'Execute Boot files of your theme', short: 'b', needsarg: false, command: 'theme', class: Theme::class, callable: "run");
        self::registerOption(long: 'migrate', help: 'Migrate the Database for your theme', short: 'm', needsarg: false, command: 'theme', class: Theme::class, callable: "run");
        self::registerOption(long: 'install', help: 'Install the Theme mounted to crisptheme', short: 'i', needsarg: false, command: 'theme', class: Theme::class, callable: "run");
        self::registerOption(long: 'uninstall', help: 'Uninstall the Theme mounted to crisptheme', short: 'u', needsarg: false, command: 'theme', class: Theme::class, callable: "run");


        self::registerCommand(command: "migration", help: "Interact with CrispCMS Migrations");
        self::registerOption(long: 'core', help: 'Create a new Core Migration File', short: 'c', needsarg: 'migrationName', command: 'migration', class: Migration::class, callable: "run");
        self::registerOption(long: 'theme', help: 'Create a new Migration File for your Theme', short: 't', needsarg: 'migrationName', command: 'migration', class: Migration::class, callable: "run");
        self::registerArgument(arg: 'migrationName', help: 'The name of your migration', required: true, command: 'migration');

        self::registerCommand(command: "storage", help: "Interact with Crisps KVS");
        self::registerOption(long: 'install', help: 'Initialize the KVS from the theme.json', short: 'i', needsarg: false, command: 'storage', class: Storage::class, callable: "run");
        self::registerOption(long: 'force', help: 'Overwrite the KVS from the theme.json', short: 'f', needsarg: false, command: 'storage', class: Storage::class, callable: "run");
        self::registerOption(long: 'uninstall', help: 'Delete all KVS Items from the database', short: 'u', needsarg: false, command: 'storage', class: Storage::class, callable: "run");

        self::registerCommand(command: "translation", help: "Interact with Crisps KVS");
        self::registerOption(long: 'install', help: 'Initialize the Translations from the theme.json', short: 'i', needsarg: false, command: 'translation', class: Translations::class, callable: "run");
        self::registerOption(long: 'uninstall', help: 'Delete all Translation Items from the database', short: 'u', needsarg: false, command: 'translation', class: Translations::class, callable: "run");

        **/
    }


    public static function register(): Application
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $GLOBALS["Crisp_CLI"] = new Application();
        self::registerCrispCLI();

        return $GLOBALS["Crisp_CLI"];
    }
}
