<?php

namespace crisp\CommandControllers;

use crisp\api\Translation;
use crisp\core\Logger;
use crisp\core\Themes;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CrispThemeTranslationsCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crisp:theme:translations')
            ->setDescription('Theme translation management')
            ->addOption('install', 'i', InputOption::VALUE_NONE, 'Install crisp theme translations')
            ->addOption('uninstall', 'u', InputOption::VALUE_NONE, 'Uninstall crisp theme translations');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $io = new SymfonyStyle($input, $output);

        if ($input->getOption("install")) {

            if (!Themes::isInstalled()) {
                $io->error("Theme is not already installed");

                return Command::FAILURE;
            }
            if (!Themes::isValid()) {
                $io->error("Theme is not mounted. Check your Docker Configuration");

                return Command::FAILURE;
            }

            Logger::startTiming($Timing);
            try {
                Themes::installTranslations();
            } catch (\Exception $ex) {
                Logger::getLogger(__METHOD__)->error("Error installing translations", [$ex->getMessage()]);
                return Command::FAILURE;
            } finally {
                Logger::getLogger(__METHOD__)->debug(sprintf("Operation took %sms to complete!", Logger::endTiming($Timing)));
            }

            return Command::SUCCESS;
        } elseif ($input->getOption('uninstall')) {

            if (!Themes::isInstalled()) {
                $io->error("Theme is not installed");

                return false;
            }
            if (!Themes::isValid()) {
                $io->error("Theme is not mounted. Check your Docker Configuration");

                return false;
            }

            Logger::startTiming($Timing);
            try {
                if (Translation::uninstallAllTranslations()) {
                    $io->success("Translations successfully uninstalled!");
                } else {
                    $io->error("Failed to uninstall translations!");
                }
            } catch (\Exception $ex) {
                Logger::getLogger(__METHOD__)->error("Error uninstalling translations", (array)$ex);
                return Command::FAILURE;
            }
            Logger::getLogger(__METHOD__)->debug(sprintf("Operation took %sms to complete!", Logger::endTiming($Timing)));

            return Command::SUCCESS;
        }

        $io->error('No action specified, check --help');

        return Command::FAILURE;
    }
}
