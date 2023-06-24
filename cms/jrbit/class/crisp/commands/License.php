<?php

namespace crisp\commands;

use CLI;
use crisp\api\Config;
use crisp\api\Helper;
use crisp\core;
use crisp\core\Migrations;
use crisp\core\Themes;
use Minimal;
use splitbrain\phpcli\Options;

class License {
    public static function run(CLI $minimal, Options $options): bool
    {
        if($options->getOpt("generate-private-key")){
            $private_key = openssl_pkey_new(array('private_key_bits' => 2048));
            if(file_put_contents(core::PERSISTENT_DATA . "/license.pub", openssl_pkey_get_details($private_key)['key'])){
                $minimal->success("Public Key has been saved to " . core::PERSISTENT_DATA . "/license.pub");
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
            $minimal->notice("Issued At: ". date(DATE_RFC7231, $license->getIssuedAt()));
            $minimal->notice("Expires At: ". ($license->getExpiresAt() ? date(DATE_RFC7231, $license->getExpiresAt()) : "No Expiry Date"));
            $minimal->notice("Data: ". json_encode($license->getData()));

            return true;
        }elseif($options->getOpt("generate-test")){
            $license = new \crisp\api\License(
                version: 1,
                uuid: core\Crypto::UUIDv4(),
                whitelabel: "Acme Inc.",
                domains: ["*.example.com", "example.com"],
                name: "Test License",
                issuer: "Acme Inc.",
                issued_at: time(),
                expires_at: time() + 3600,
                data: null
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
        }
        $minimal->error("No action");

        return true;
    }
}