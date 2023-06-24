<?php

namespace crisp\commands;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use CLI;
use crisp\api\Config;
use crisp\api\Helper;
use crisp\core;
use crisp\core\Migrations;
use crisp\core\Themes;
use Minimal;
use PHPUnit\TextUI\Help;
use splitbrain\phpcli\Options;

class License {
    public static function run(CLI $minimal, Options $options): bool
    {
        if($options->getOpt("generate-private-key")){
            $private_key = openssl_pkey_new(array('private_key_bits' => 2048));
            if(file_put_contents(core::PERSISTENT_DATA . "/issuer.pub", openssl_pkey_get_details($private_key)['key'])){
                $minimal->success("Public Key has been saved to " . core::PERSISTENT_DATA . "/issuer.pub");
                $minimal->info("You must ship this public key to your customer");
            }
            if(openssl_pkey_export_to_file($private_key, core::PERSISTENT_DATA . "/issuer.key")){
                $minimal->success("Private Key has been saved to " . core::PERSISTENT_DATA . "/issuer.key");
                $minimal->warning("Keep this key private at all costs!");
            }
            return true;
        }elseif($options->getOpt("info")){
            $license = \crisp\api\License::fromFile(core::PERSISTENT_DATA . "/license.key");

            if(!$license){
                $minimal->fatal("Could not load license!");
                return false;
            }

            $minimal->success("Successfully loaded license!");

            $minimal->notice("Version: ". $license->getVersion());
            $minimal->notice("UUID: ". $license->getUuid());
            $minimal->notice("Whitelabel: ". $license->getWhitelabel());
            $minimal->notice("Domains: ". implode(", ", $license->getDomains()));
            $minimal->notice("Issued To: ". $license->getName());
            $minimal->notice("Issuer: ". $license->getIssuer());
            $minimal->notice("Instance: ". $license->getInstance());
            $minimal->notice(sprintf("Issued At: %s (%s)",
                date(DATE_RFC7231, $license->getIssuedAt()),
                Carbon::parse($license->getIssuedAt())->diffForHumans()
            ));
            $minimal->notice(sprintf("Expires At: %s (%s)",
                ($license->getExpiresAt() ? date(DATE_RFC7231, $license->getExpiresAt()) : "No Expiry Date"),
                Carbon::parse($license->getExpiresAt())->diffForHumans()
            ));
            $minimal->notice("Data: ". json_encode($license->getData()));

            if(\crisp\api\License::GEN_VERSION > $license->getVersion()){
                $minimal->warning(sprintf("The License has been generated with an older Version of CrispCMS! (Installed Version: %s, License Version: %s)", $license->getVersion(), \crisp\api\License::GEN_VERSION));
            }elseif(\crisp\api\License::GEN_VERSION < $license->getVersion()){
                $minimal->warning(sprintf("The License has been generated with a newer Version of CrispCMS! (Installed Version: %s, License Version: %s)", $license->getVersion(), \crisp\api\License::GEN_VERSION));
            }

            if($license->isExpired()){
                $minimal->warning("The License expired " . Carbon::parse($license->getExpiresAt())->diffForHumans());
            }

            if(!$license->canExpire()){
                $minimal->warning("License never Expires!");
            }else{
                $creationDateCarbon = Carbon::parse($license->getIssuedAt());
                $expiryDateCarbon = Carbon::parse($license->getExpiresAt());

                $minimal->warning(sprintf("License is valid for %s", $creationDateCarbon->diffForHumans($expiryDateCarbon, CarbonInterface::DIFF_ABSOLUTE)));
            }

            if(!$license->verifySignature()){
                $minimal->alert("License Signature is not valid!");
            }
            if(!$license->isValid()){
                $minimal->alert("License is not valid!");
            }

            return true;
        }elseif($options->getOpt("generate-test")){

            $domains = ["*.example.com", "example.com"];

            if($_ENV["HOST"]){
                $domains[] = $_ENV["HOST"];
            }

            $license = new \crisp\api\License(
                version: \crisp\api\License::GEN_VERSION,
                uuid: core\Crypto::UUIDv4(),
                whitelabel: "Acme Inc.",
                domains: $domains,
                name: "Test License",
                issuer: "Acme Inc.",
                issued_at: time(),
                expires_at: $options->getOpt("expired") ? time() - 3600 : time() + 3600,
                data: null,
                instance: Helper::getInstanceId()
            );

            if(!$license->sign()){
                $minimal->fatal("Could not sign license!");
                return false;
            }

            if(!file_put_contents(core::PERSISTENT_DATA. "/license.key", $license->exportToString())){
                $minimal->fatal("Could not save license!");
                return false;
            }

            $minimal->success("License has been saved to ". core::PERSISTENT_DATA. "/license.key");
            return true;
        }elseif($options->getOpt("delete")){

            if(!unlink(core::PERSISTENT_DATA. "/license.key")){
                $minimal->fatal("Could not delete license!");
                return false;
            }

            $minimal->success("License has been deleted!");
            return true;
        }elseif($options->getOpt("delete-issuer")){

            if(!unlink(core::PERSISTENT_DATA. "/issuer.pub")){
                $minimal->fatal("Could not delete issuer!");
                return false;
            }

            $minimal->success("Issuer has been deleted!");
            return true;
        }
        $minimal->error("No action");

        return true;
    }
}