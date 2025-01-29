<?php

namespace crisp\CommandControllers;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use crisp\core\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CrispLicenseCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crisp:license')
            ->setDescription('Get license info');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $io = new SymfonyStyle($input, $output);

        $license = \crisp\api\License::fromDB();

        if (!$license) {
            $io->error("Could not load license!");

            return false;
        }
        $io->success("Successfully loaded license!");

        $io->title('License Info');
        $io->table(
            [],
            [
                ['Version', $license->getVersion()],
                ['UUID', $license->getUuid()],
                ['Whitelabel', $license->getWhitelabel()],
                ['Domains', implode(", ", $license->getDomains())],
                ['Issued To', $license->getName()],
                ['Issuer', $license->getIssuer()],
                ['Instance', $license->getInstance()],
                ['OCSP', $license->getOcsp()],
                ['Issued At', sprintf(
                    "%s (%s)",
                    date(DATE_RFC7231, $license->getIssuedAt()),
                    Carbon::parse($license->getIssuedAt())->diffForHumans()
                )],
                ['Expires At', sprintf(
                    "%s (%s)",
                    ($license->getExpiresAt() ? date(DATE_RFC7231, $license->getExpiresAt()) : "No Expiry Date"),
                    Carbon::parse($license->getExpiresAt())->diffForHumans()
                )],
                ['Data', json_encode($license->getData())],
            ]
        );

        if (\crisp\api\License::GEN_VERSION > $license->getVersion()) {
            $io->warning(sprintf("The License has been generated with an older Version of CrispCMS! (Installed Version: %s, License Version: %s)", $license->getVersion(), \crisp\api\License::GEN_VERSION));
        } elseif (\crisp\api\License::GEN_VERSION < $license->getVersion()) {
            $io->warning(sprintf("The License has been generated with a newer Version of CrispCMS! (Installed Version: %s, License Version: %s)", $license->getVersion(), \crisp\api\License::GEN_VERSION));
        }

        if ($license->isExpired()) {
            $io->warning("The License expired " . Carbon::parse($license->getExpiresAt())->diffForHumans());
        }

        if (!$license->canExpire()) {
            $io->note("License never Expires!");
        } else {
            $creationDateCarbon = Carbon::parse($license->getIssuedAt());
            $expiryDateCarbon = Carbon::parse($license->getExpiresAt());
            $io->note(sprintf("License is valid for %s", $creationDateCarbon->diffForHumans($expiryDateCarbon, CarbonInterface::DIFF_ABSOLUTE)));
        }

        if (!$license->verifySignature()) {
            $io->error("License Signature is not valid!");
        }
        if (!$license->isValid()) {
            $io->warning("License is not valid!");
        } else {
            $io->note("License is valid!");
        }

        return Command::SUCCESS;
    }
}
