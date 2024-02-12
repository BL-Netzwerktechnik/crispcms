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
use crisp\commands\License as CommandsLicense;
use crisp\commands\Maintenance;
use crisp\commands\Migration;
use crisp\commands\Storage;
use crisp\commands\Theme;
use crisp\commands\Translations;
use crisp\commands\Version;
use Minimal;
use splitbrain\phpcli\CLI as SplitbrainCLI;
use splitbrain\phpcli\Options;


class CLI
{

    public static function registerCommand(string $command, string $help): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $options = self::get()["options"];
        $options->registerCommand($command, $help);

        $GLOBALS["Crisp_CLI"]["options"] = $options;
    }

    public static function registerArgument($arg, $help, $required = true, $command = ''): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $options = self::get()["options"];
        $options->registerArgument($arg, $help, $required, $command);

        $GLOBALS["Crisp_CLI"]["options"] = $options;
    }

    public static function get()
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        return $GLOBALS["Crisp_CLI"];
    }

    /**
     * Register an option for option parsing and help generation
     *
     * @param string $long multi character option (specified with --)
     * @param string $help help text for this option
     * @param string|null $short one character option (specified with -)
     * @param bool|string $needsarg does this option require an argument? give it a name here
     * @param string $command what command does this option apply to
     * @throws Exception
     */
    public static function registerOption($long, $help, $short = null, $needsarg = false, $command = '', mixed $class = null, string $callable = null)
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $callable = $callable ?? "run";

        $options = self::get()["options"];

        $options->registerOption($long, $help, $short, $needsarg, $command);

        if ($class !== null) {
            $GLOBALS["Crisp_CLI"]["callables"][$command][$long] = [$class, $callable];
        }
        $GLOBALS["Crisp_CLI"]["options"] = $options;
    }

    public static function runOption(SplitbrainCLI $minimal, Options $options)
    {
        $cliglob = self::get();

        if (!$cliglob["callables"][$options->getCmd()]) {
            $minimal->error(sprintf("Command not found! (%s)", $options->getCmd()));
            return false;
        }
        
        foreach ($cliglob["callables"][$options->getCmd()] as $key => $value) {

            if (!$options->getOpt($key)) continue;

            if (class_exists($value[0], true)) {
                $optClass = new $value[0]();

                if ($optClass !== null && !method_exists($optClass, $value[1])) {
                    throw new \Exception("Failed to load $optClass, missing callable!");
                }

                call_user_func([$value[0], $value[1]], $minimal, $options);
                exit;
            }else{
                $minimal->critical("Invalid Class ". $value[0]);
                exit;
            }
        }
        echo $options->help();
        $minimal->error(sprintf("Command combination not found! (%s)", implode(" ", $options->getArgs())));
    }

    public static function registerCrispCLI(): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        self::registerOption(long: 'version', help: 'print version', short: 'v', needsarg: false, command: '', class: Version::class, callable: "run");

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
    }


    public static function register(Options $options): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $GLOBALS["Crisp_CLI"] = [];

        $GLOBALS["Crisp_CLI"]["callables"] = [];
        $GLOBALS["Crisp_CLI"]["options"] = $options;
        self::registerCrispCLI();
    }
}
