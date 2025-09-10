<?php

namespace crisp\CommandControllers;

use crisp\Controllers\EventController;
use crisp\core;
use crisp\core\Logger;
use crisp\core\Themes;
use crisp\Events\MigrationEvents;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\EventDispatcher\Event;

class CrispMigrationRunCommand extends Command
{
    protected function configure(): void
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        $this
            ->setName('crisp:migration:run')
            ->setDescription('Run database migrations for crisp')
            ->addOption('core', 'c', InputOption::VALUE_NONE | InputOption::VALUE_NEGATABLE, 'Wether to run core migrations, defaults to yes')
            ->addOption('theme', 't', InputOption::VALUE_NONE | InputOption::VALUE_NEGATABLE, 'Wether to run theme migrations, defaults to yes');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }

        $Migrations = new core\Migrations();

        if (!$input->getOption('no-core')) {
            $output->writeln('Running core migrations...');
            $Migrations->migrate();
            EventController::getEventDispatcher()->dispatch(new Event(), MigrationEvents::CORE_MIGRATIONS_FINISHED);
        }

        if (!$input->getOption('no-theme')) {
            $output->writeln('Running theme migrations...');
            $Migrations->migrate(Themes::getThemeDirectory());
            EventController::getEventDispatcher()->dispatch(new Event(), MigrationEvents::THEME_MIGRATIONS_FINISHED);
        }

        return Command::SUCCESS;
    }
}
