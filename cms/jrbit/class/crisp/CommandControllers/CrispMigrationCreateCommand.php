<?php

namespace crisp\CommandControllers;

use crisp\core\Logger;
use crisp\core\Migrations;
use crisp\core\Themes;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CrispMigrationCreateCommand extends Command
{
    protected function configure(): void
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        $this
            ->setName('crisp:migration:create')
            ->setDescription('Create database migrations for crisp')
            ->addOption('core', 'c', InputOption::VALUE_NONE, 'Create migrations for crisp core')
            ->addOption('theme', 't', InputOption::VALUE_NONE, 'Create migrations for crisp themes')
            ->addOption('name', 'i', InputOption::VALUE_REQUIRED, 'Name of the migration');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }

        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('name')) {
            $io->error('Please specify a --name');

            return Command::FAILURE;
        }

        if ($input->getOption('theme')) {
            Migrations::create($input->getOption('name'), Themes::getThemeDirectory());
        } elseif ($input->getOption('core')) {
            Migrations::create($input->getOption('name'));
        } else {
            $io->error('Please specify either --core or --theme');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
