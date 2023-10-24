<?php

namespace crisp\commands;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use CLI;
use crisp\api\Config;
use crisp\api\Helper;
use crisp\core;
use crisp\core\Bitmask;
use crisp\core\Logger;
use crisp\core\Migrations;
use crisp\core\RESTfulAPI;
use crisp\core\Themes;
use Minimal;
use PHPUnit\TextUI\Help;
use splitbrain\phpcli\Options;

class License {


    private static function generateIssuer(CLI $minimal, Options $options): bool
    {
        
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);

        if((bool)$_ENV["REQUIRE_LICENSE"]){
            $minimal->fatal("Issuers cannot be generated on this instance!");
            return false;
        }

        if(\crisp\api\License::generateIssuer()){
            $minimal->success("Public Key has been saved");
            $minimal->info("You must ship this public key to your customer");
            $minimal->success("Private Key has been saved");
            $minimal->warning("Keep this key private at all costs!");
            return true;
        }
    }

    public static function run(CLI $minimal, Options $options): bool
    {
        if($options->getOpt("generate-private-key")){
            return self::generateIssuer($minimal, $options);
        }elseif($options->getOpt("info")){
            $license = \crisp\api\License::fromDB();

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
            $minimal->notice("OCSP: ". $license->getOcsp());
            $minimal->notice(sprintf("Issued At: %s (%s)",
                date(DATE_RFC7231, $license->getIssuedAt()),
                Carbon::parse($license->getIssuedAt())->diffForHumans()
            ));
            if($license->canExpire()) {
                $minimal->notice(sprintf("Expires At: %s (%s)",
                    ($license->getExpiresAt() ? date(DATE_RFC7231, $license->getExpiresAt()) : "No Expiry Date"),
                    Carbon::parse($license->getExpiresAt())->diffForHumans()
                ));
            }
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
            }else{
                $minimal->success("License is valid!");
            }

            return true;
        }elseif($options->getOpt("generate-test")){

            if((bool)$_ENV["REQUIRE_LICENSE"]){
                $minimal->fatal("Licenses cannot be generated on this instance!");
                return false;
            }

            $domains = ["*.example.com", "example.com"];

            if($_ENV["HOST"]){
                $domains[] = $_ENV["HOST"];
            }

            $expiry = time() + 3600;
            $instance = Helper::getInstanceId();

            if($options->getOpt("expired")){
                $expiry = time() - 3600;
            }elseif($options->getOpt("no-expiry")){
                $expiry = null;
            }

            if($options->getOpt("invalid-instance")){
                $instance = core\Crypto::UUIDv4("I");
            }


            $license = new \crisp\api\License(
                version: \crisp\api\License::GEN_VERSION,
                uuid: core\Crypto::UUIDv4(),
                whitelabel: "Acme Inc.",
                domains: $domains,
                name: "Test License",
                issuer: "Acme Inc.",
                issued_at: time(),
                expires_at: $expiry,
                data: null,
                instance: $instance,
                ocsp: sprintf("%s://%s/_debug_ocsp",$_ENV["PROTO"], $_ENV["HOST"])
            );


            if(!Config::exists("license_issuer_private_key")){
                $minimal->warning("Issuer Private Key does not exist! Generating one...");
                self::generateIssuer($minimal, $options);
            }

            if(!$license->sign()){
                $minimal->fatal("Could not sign license! Maybe an issuer key is missing!");
                return false;
            }

            if(!Config::set("license_key", $license->exportToString())){
                $minimal->fatal("Could not save license!");
                return false;
            }

            $minimal->success("License has been saved");
            return true;
        }elseif($options->getOpt("delete")){

            if(!Config::delete("license_key")){
                $minimal->fatal("Could not delete license!");
                return false;
            }

            $minimal->success("License has been deleted!");
            return true;
        }elseif($options->getOpt("delete-issuer")){

            if($_ENV["REQUIRE_LICENSE"]){
                $minimal->fatal("Issuers cannot be deleted on this instance!");
                return false;
            }

            if(!Config::delete("license_issuer_public_key")){
                $minimal->fatal("Could not delete issuer!");
                return false;
            }

            $minimal->success("Issuer has been deleted!");
            return true;
        }elseif($options->getOpt("delete-issuer-private")){

            if(!Config::delete("license_issuer_private_key")){
                $minimal->fatal("Could not delete issuer!");
                return false;
            }

            $minimal->success("Issuer has been deleted!");
            return true;
        }elseif($options->getOpt("get-issuer-private")){

            if(!Config::exists("license_issuer_private_key")){
                $minimal->fatal("Issuer Private Key does not exist!");
                return false;
            }

            if($options->getOpt("no-formatting")){
                echo Config::get("license_issuer_private_key");
            }else{
                $minimal->success(sprintf("Issuer Private Key: %s%s", PHP_EOL, Config::get("license_issuer_private_key")));
            }
            return true;
        }elseif($options->getOpt("get-issuer")){

            if(!Config::exists("license_issuer_public_key")){
                $minimal->fatal("Issuer Public Key does not exist!");
                return false;
            }

            if($options->getOpt("no-formatting")){
                echo Config::get("license_issuer_public_key");
            }else{
                $minimal->success(sprintf("Issuer Public Key: %s%s", PHP_EOL, Config::get("license_issuer_public_key")));
            }
            return true;
        }
        $minimal->error("No action");

        return true;
    }
}