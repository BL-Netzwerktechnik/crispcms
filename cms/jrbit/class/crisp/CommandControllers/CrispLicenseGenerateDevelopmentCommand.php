<?php

namespace crisp\CommandControllers;

use crisp\api\Build;
use crisp\api\Config;
use crisp\api\Helper;
use crisp\api\License;
use crisp\core;
use crisp\core\Environment;
use crisp\core\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CrispLicenseGenerateDevelopmentCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crisp:license:generate:development')
            ->setDescription('Generate a development license')
            ->addOption('expiry', null, InputOption::VALUE_OPTIONAL, 'Set expiry date', time() + 3600)
            ->addOption('expired', null, InputOption::VALUE_NONE | InputOption::VALUE_OPTIONAL, 'Set expiry date to past')
            ->addOption('no-expiry', null, InputOption::VALUE_NONE | InputOption::VALUE_OPTIONAL, 'Never expire license')
            ->addOption('invalid-instance', null, InputOption::VALUE_NONE | InputOption::VALUE_OPTIONAL, 'Invalid instance');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $io = new SymfonyStyle($input, $output);

        if (Build::requireLicense() && Build::getEnvironment() !== Environment::DEVELOPMENT) {
            $io->error("Licenses cannot be generated on this instance!");

            return Command::FAILURE;
        }

        $domains = ["*.gitpod.io"];

        if ($_ENV["HOST"]) {
            $domains[] = $_ENV["HOST"];
        }

        $expiry = $input->getOption('expiry');
        $instance = Helper::getInstanceId();

        if ($input->getOption("expired")) {
            $expiry = time() - 3600;
        } elseif ($input->getOption("no-expiry")) {
            $expiry = null;
        }

        if ($input->getOption("invalid-instance")) {
            $instance = core\Crypto::UUIDv4("I");
        }

        $license = new License(
            version: License::GEN_VERSION,
            uuid: core\Crypto::UUIDv4(),
            whitelabel: "Acme Inc.",
            domains: $domains,
            name: "Test License",
            issuer: "Acme Inc.",
            issued_at: time(),
            expires_at: $expiry,
            data: null,
            instance: $instance,
            // ocsp: sprintf("%s://%s/_/debug_ocsp", $_ENV["PROTO"], $_ENV["HOST"])
        );

        if (!Config::exists("license_issuer_private_key")) {
            $io->warning("Issuer Private Key does not exist! Generating one...");
            License::generateIssuer();
        }

        if (!$license->sign()) {
            $io->error("Could not sign license! Maybe an issuer key is missing!");

            return Command::FAILURE;
        }

        if (!$license->install()) {
            $io->error("Could not install license!");

            return Command::FAILURE;
        }

        $io->success("Test License has been saved");

        return Command::SUCCESS;
    }
}
