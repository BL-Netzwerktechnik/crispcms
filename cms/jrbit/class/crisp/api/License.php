<?php

/*
 * Copyright (c) 2021. JRB IT, All Rights Reserved
 *
 *  @author Justin René Back <j.back@jrbit.de>
 *  @link https://github.com/jrbit/crispcms
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


namespace crisp\api;

use crisp\core;
use crisp\core\LogTypes;
use crisp\core\Postgres;
use PDO;
use function serialize;
use function unserialize;
use crisp\core\Bitmask;
use crisp\core\Logger;
use crisp\core\RESTfulAPI;

/**
 * Interact with the key/value storage of the server
 */
class License
{

    public const GEN_VERSION = 3;

    public function __construct(
        private readonly int     $version,
        private readonly ?string $uuid = null,
        private readonly ?string $whitelabel = null,
        private readonly array   $domains = [],
        private readonly ?string $name = null,
        private readonly ?string $issuer = null,
        private readonly ?int    $issued_at = null,
        private readonly ?int    $expires_at = null,
        private readonly ?string $data = null,
        private readonly ?string $instance = null,
        private readonly ?string $ocsp = null,
        private ?string          $signature = null,

    ){

    }

    public function getTimestampNextOCSP(): int|null {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        if(!$this->ocsp) return null;
        return Cache::getExpiryDate("license_ocsp_response");
    }

    public function getHttpCodeOCSP(): int|null {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        if(!$this->ocsp) return null;


        $this->validateOCSP($httpCode);

        return $httpCode;

    }
    public function getGraceOCSP(): int|null {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        if(!$this->ocsp) return null;


        return Config::get("license_ocsp_response_grace");

    }

    public function validateOCSP(&$httpCode = NULL): bool {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);

        if(!$this->ocsp) return true;

        Config::deleteCache("license_ocsp_response_grace");


        if(!Cache::isExpired("license_ocsp_response")){
            $httpCode = Cache::get("license_ocsp_response");
        }else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, strtr($this->ocsp, ["{{uuid}}" => $this->uuid, "{{instance}}" => $this->instance]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            curl_exec($ch);

            if (curl_errno($ch)) {
                return false;
            }

            $httpCode = (string)curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if(str_starts_with($httpCode, "5")){
                Config::deleteCache("license_ocsp_response_grace");
                Config::set("license_ocsp_response_grace", (Config::get("license_ocsp_response_grace") ?? 1 ) + 1);
            }

            Cache::write("license_ocsp_response", $httpCode, time() + 1800);
            curl_close($ch);
        }

        if(Config::get("license_ocsp_response_grace") >= 3 && str_starts_with($httpCode, "5")){
            $httpCode = Cache::get("license_ocsp_response");
            return false;
        }elseif(str_starts_with($httpCode, "2")) {
            Config::delete("license_ocsp_response_grace");
            return true;
        }elseif(str_starts_with($httpCode, "5")){
            return true;
        }
        return false;

    }

    public static function isLicenseAvailable(): bool {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        return Config::exists("license_key");
    }

    public static function generateIssuer(): bool {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        Logger::getLogger(__METHOD__)->info( "Generating Isser Keys...");
        $private_key = openssl_pkey_new(array('private_key_bits' => 2048));
        if(Config::set("license_issuer_public_key", openssl_pkey_get_details($private_key)['key']) && openssl_pkey_export($private_key, $pkey) && Config::set("license_issuer_private_key", $pkey)){
            return true;
        }

        Config::delete("license_issuer_public_key");
        Config::delete("license_issuer_private_key");
        return false;
    }

    public static function isIssuerAvailable(): bool {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        return Config::exists("license_issuer_public_key");
    }
    public static function isIssuerPrivateAvailable(): bool {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        return Config::exists("license_issuer_private_key");
    }

    public function isValid(): bool {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        return !$this->isExpired()
            && $this->isDomainAllowed($_SERVER["HTTP_HOST"] ?? $_ENV["HOST"])
            && $this->isInstanceAllowed()
            && $this->validateOCSP()
            && $this->verifySignature();
    }

    public function canExpire(): bool {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        if($this->expires_at === null || $this->expires_at === 0) return false;

        return true;
    }

    public function isInstanceAllowed(): bool {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        if($this->instance === null) return true;

        return Helper::getInstanceId() === $this->instance;
    }

    public function isExpired(): bool {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        if(!$this->canExpire()) return false;

        return $this->expires_at < time();
    }

    public function isDomainAllowed(string $currentDomain): bool {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        if(count($this->domains) === 0) return true;

        foreach($this->domains as $allowedDomain){
            if(fnmatch($allowedDomain, $currentDomain)) return true;
        }
        return false;
    }

    public function sign(): bool {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);

        $key = License::getPrivateKey();

        if(!$key){
            return false;
        }

        return openssl_sign($this->encode(), $this->signature, $key);
    }

    public static function fromDB(): License|false {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        if(!Config::exists("license_key")){
            return false;
        }
        $data = Config::get("license_key");

        $exploded = explode(".", $data);

        if(count($exploded) !== 2){
            return false;
        }

        $license = json_decode(base64_decode($exploded[0]), true);
        $signature = base64_decode($exploded[1]);

        return new License(
            $license["version"],
            $license["uuid"],
            $license["whitelabel"],
            $license["domains"],
            $license["name"],
            $license["issuer"],
            $license["issued_at"],
            $license["expires_at"],
            $license["data"],
            $license["instance"],
            $license["ocsp"],
            $signature
        );
    }

    public function encode(): string {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);

        $fields = [
            "version" => $this->version,
            "uuid" => $this->uuid,
            "whitelabel" => $this->whitelabel,
            "domains" => $this->domains,
            "name" => $this->name,
            "issuer" => $this->issuer,
            "issued_at" => $this->issued_at,
            "expires_at" => $this->expires_at,
            "data" => $this->data,
        ];

        if($this->version >= 2){
            $fields["instance"] = $this->instance;
        }
        if($this->version >= 3){
            $fields["ocsp"] = $this->ocsp;
        }
        return json_encode($fields);
    }

    public function exportToString(): string {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        return base64_encode($this->encode()) . "." . base64_encode($this->signature);
    }

    public static function getPublicKey(): \OpenSSLAsymmetricKey|false {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        if(!self::isIssuerAvailable()){
            return false;
        }
        return openssl_pkey_get_public(Config::get("license_issuer_public_key"));
    }


    public static function getPrivateKey(): \OpenSSLAsymmetricKey|false {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        if(!self::isIssuerPrivateAvailable()){
            return false;
        }
        return openssl_pkey_get_private(Config::get("license_issuer_private_key"));
    }

    public function verifySignature(): bool {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        $publicKey = self::getPublicKey();
        if(!$publicKey){
            return false;
        }

        return openssl_verify($this->encode(), $this->signature, $publicKey);
    }

    /**
     * @return string|null
     */
    public function getUuid(): ?string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        return $this->uuid;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        return $this->version;
    }

    /**
     * @return string|null
     */
    public function getWhitelabel(): ?string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        return $this->whitelabel;
    }

    /**
     * @return array
     */
    public function getDomains(): array
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        return $this->domains;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getIssuer(): ?string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        return $this->issuer;
    }

    /**
     * @return int|null
     */
    public function getIssuedAt(): ?int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        return $this->issued_at;
    }

    /**
     * @return int|null
     */
    public function getExpiresAt(): ?int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        return $this->expires_at;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        if($this->data === null){
            return [];
        }
        return json_decode($this->data, true);
    }

    /**
     * @return string|null
     */
    public function getSignature(): ?string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        return $this->signature;
    }

    /**
     * @return string|null
     */
    public function getInstance(): ?string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        return $this->instance;
    }

    /**
     * @return string|null
     */
    public function getOcsp(): ?string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        return $this->ocsp;
    }

}
