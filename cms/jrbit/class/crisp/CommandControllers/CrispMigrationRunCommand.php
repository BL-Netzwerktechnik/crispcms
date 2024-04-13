<?php

namespace crisp\CommandControllers;

use crisp\core;
use crisp\core\Logger;
use crisp\core\Themes;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CrispMigrationRunCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crisp:migration:run')
            ->setDescription('Run database migrations for crisp')
            ->addOption('core', 'c', InputOption::VALUE_NONE | InputOption::VALUE_NEGATABLE, 'Wether to run core migrations, defaults to yes')
            ->addOption('theme', 't', InputOption::VALUE_NONE | InputOption::VALUE_NEGATABLE, 'Wether to run theme migrations, defaults to yes');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        
        $Migrations = new core\Migrations();

        if(!$input->getOption('no-core')){
            $output->writeln('Running core migrations...');
            $Migrations->migrate();
        }

        if(!$input->getOption('no-theme')){
            $output->writeln('Running theme migrations...');
            $Migrations->migrate(Themes::getThemeDirectory());
        }


        return Command::SUCCESS;
    }
}
