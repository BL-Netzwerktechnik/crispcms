<?php

namespace crisp\CommandControllers;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use crisp\api\Build;
use crisp\api\Config;
use crisp\api\Helper;
use crisp\api\License;
use crisp\core;
use crisp\core\Environment;
use crisp\core\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CrispLicenseDeleteKeyCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crisp:license:delete:key')
            ->setDescription('Delete license key')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force deletion');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $io = new SymfonyStyle($input, $output);


        if(!License::fromDB()){
            $io->error('No license is installed!');
            return Command::FAILURE;
        }

        if(!$input->getOption('force') && !$io->confirm('Are you sure you want to delete license key?', false)){
            return Command::INVALID;
        }

        if(Config::delete('license_key')){
            $io->success("License key has been deleted!");
            return Command::SUCCESS;
        }

        $io->error("Failed to delete license key!");
        return Command::FAILURE;
    }
}
