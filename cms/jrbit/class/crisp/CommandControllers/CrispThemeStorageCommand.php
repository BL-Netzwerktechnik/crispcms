<?php

namespace crisp\CommandControllers;

use crisp\api\Config;
use crisp\api\Helper;
use crisp\core;
use crisp\core\Logger;
use crisp\core\Migrations;
use crisp\core\Themes;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CrispThemeStorageCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crisp:theme:storage')
            ->setDescription('Theme KV Storage interactions')
            ->addOption('install', 'i', InputOption::VALUE_NONE, 'Install KV Storage for crisp theme')
            ->addOption('uninstall', 'u', InputOption::VALUE_NONE, 'Uninstall KV Storage for crisp theme')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite KV Storage on install')
            ->addOption('edit', 'e', InputOption::VALUE_NONE, 'Edit the specified --key')
            ->addOption('delete', 'd', InputOption::VALUE_NONE, 'Delete the specified --key')
            ->addOption('key', 'k', InputOption::VALUE_OPTIONAL, '--key to select')
            ->addOption('value', null, InputOption::VALUE_OPTIONAL, 'New value to set');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $io = new SymfonyStyle($input, $output);

        if ($input->getOption("install")) {

            if (!Themes::isInstalled()) {
                $io->error("Theme is not installed");
                return Command::FAILURE;
            }
            if (!Themes::isValid()) {
                $io->error("Theme is not mounted. Check your Docker Configuration");
                return Command::FAILURE;
            }

            Logger::startTiming($Timing);
            if (Themes::installKVStorage((bool) $input->getOption("force"))) {
                $io->success("KVStorage successfully installed!");
            } else {
                $io->error("Failed to install KVStorage!");
            }
            Logger::getLogger(__METHOD__)->debug(sprintf("Operation took %sms to complete!", Logger::endTiming($Timing)));
            return Command::SUCCESS;
        }elseif($input->getOption('uninstall')){

            if (!Themes::isInstalled()) {
                $io->error("Theme is not installed");

                return false;
            }
            if (!Themes::isValid()) {
                $io->error("Theme is not mounted. Check your Docker Configuration");

                return false;
            }

            Logger::startTiming($Timing);
            if (Themes::uninstallKVStorage()) {
                $io->success("KVStorage successfully uninstalled!");
            } else {
                $io->error("Failed to uninstall KVStorage!");
            }
            Logger::getLogger(__METHOD__)->debug(sprintf("Operation took %sms to complete!", Logger::endTiming($Timing)));
            return Command::SUCCESS;
        }elseif($input->getOption('key')){

            if($input->getOption('edit') && !$input->getOption('value')){
                $io->error('You must specify a value to set');
                return Command::FAILURE;
            }

            if($input->getOption('value')){
                Config::set($input->getOption('key'), $input->getOption('value'));
                $io->success(sprintf('%s set to %s', $input->getOption('key'), $input->getOption('value')));
                return Command::SUCCESS;
            }

            if(!Config::exists($input->getOption('key'))){
                $io->error(sprintf('%s key does not exist!', $input->getOption('key')));
                return Command::FAILURE;
            }

            if($input->getOption('delete')){
                if(Config::delete($input->getOption('delete'))){
                    $io->success(sprintf('%s key has been deleted!', $input->getOption('key')));
                    return Command::SUCCESS;
                }
                $io->error(sprintf('%s key does not exist!', $input->getOption('key')));
                return Command::FAILURE;
            }

            $output->writeln(Config::get($input->getOption('key')));
            return Command::SUCCESS;
        }


        $items = [];

        foreach(Config::list(true) as $item){

            $value = $item["value"];

            if(is_array($value)){
                $value = '(array)';
            }

            if(is_string($value)){
                $value = Helper::truncateText($value, 200);
            }

            if(is_bool($value)){
                $value = sprintf("%s (boolean)", $value);
            }

            if(is_null($value)){
                $value = 'NULL';
            }


            $items[] = [$item["key"], $value];
        }
        $io->table(
            ["Key", "Value"],
            $items
        );

        return Command::SUCCESS;
    }
}
