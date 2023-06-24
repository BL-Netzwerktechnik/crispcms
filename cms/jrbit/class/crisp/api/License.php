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
use crisp\core\RESTfulAPI;

/**
 * Interact with the key/value storage of the server
 */
class License
{

    public const GEN_VERSION = 1;

    public function __construct(
        private readonly int     $version,
        private readonly ?string $uuid = null,
        private readonly ?string $whitelabel = null,
        private readonly array $domains = [],
        private readonly ?string $name = null,
        private readonly ?string $issuer = null,
        private readonly ?int $issued_at = null,
        private readonly ?int $expires_at = null,
        private readonly ?string $data = null,
        private ?string $signature = null,

    ){

    }

    public function sign(): bool {

        $key = License::getPrivateKey();

        if(!$key){
            return false;
        }

        return openssl_sign($this->encode(), $this->signature, $key);
    }

    public static function fromFile(string $file): License|false {
        if(!file_exists($file)){
            return false;
        }
        $data = file_get_contents($file);

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
            $signature
        );
    }

    private function encode(): string {
        return json_encode([
           "version" => $this->version,
           "uuid" => $this->uuid,
           "whitelabel" => $this->whitelabel,
           "domains" => $this->domains,
           "name" => $this->name,
           "issuer" => $this->issuer,
           "issued_at" => $this->issued_at,
           "expires_at" => $this->expires_at,
           "data" => $this->data,
        ]);
    }

    public function exportToString(): string {
        return base64_encode($this->encode()) . "." . base64_encode($this->signature);
    }

    public static function getPublicKey(): \OpenSSLAsymmetricKey|false {
        if(!file_exists(core::PERSISTENT_DATA . "/license.pub")){
            return false;
        }
        return openssl_pkey_get_public(file_get_contents(core::PERSISTENT_DATA . "/license.pub"));
    }


    public static function getPrivateKey(): \OpenSSLAsymmetricKey|false {
        if(!file_exists(core::PERSISTENT_DATA . "/issuer.key")){
            return false;
        }
        return openssl_pkey_get_private(file_get_contents(core::PERSISTENT_DATA . "/issuer.key"));
    }

    public function verifySignature(): bool {
        $publicKey = self::getPublicKey();
        if(!$publicKey){
            return false;
        }

        return openssl_verify($this->serialize(), $this->signature, $publicKey);
    }

    /**
     * @return string|null
     */
    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @return string|null
     */
    public function getWhitelabel(): ?string
    {
        return $this->whitelabel;
    }

    /**
     * @return array
     */
    public function getDomains(): array
    {
        return $this->domains;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getIssuer(): ?string
    {
        return $this->issuer;
    }

    /**
     * @return int|null
     */
    public function getIssuedAt(): ?int
    {
        return $this->issued_at;
    }

    /**
     * @return int|null
     */
    public function getExpiresAt(): ?int
    {
        return $this->expires_at;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
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
        return $this->signature;
    }

}
