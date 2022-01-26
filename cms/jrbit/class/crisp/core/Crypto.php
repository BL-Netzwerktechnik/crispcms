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

namespace crisp\core;

class Crypto {


    public static function encrypt($plaintext, $pass, $encoding = null) {
        $iv = openssl_random_pseudo_bytes(16, $safe);

        if(!$safe || !$iv){
            return false;
        }
        $ciphertext = openssl_encrypt($plaintext, 'AES-256-CBC', hash('sha256', $pass, true), OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext . $iv, hash('sha256', $pass, true), true);
        return $encoding === 'hex' ? bin2hex($iv . $hmac . $ciphertext) : ($encoding === 'base64' ? base64_encode($iv . $hmac . $ciphertext) : $iv . $hmac . $ciphertext);
    }

    public static function decrypt($ciphertext, $pass, $encoding = null) {
        $ciphertext = $encoding === 'hex' ? hex2bin($ciphertext) : ($encoding === 'base64' ? base64_decode($ciphertext) : $ciphertext);
        if (!hash_equals(hash_hmac('sha256', substr($ciphertext, 48) . substr($ciphertext, 0, 16), hash('sha256', $pass, true), true), substr($ciphertext, 16, 32))) {
            return false;
        }
        return openssl_decrypt(substr($ciphertext, 48), 'AES-256-CBC', hash('sha256', $pass, true), OPENSSL_RAW_DATA, substr($ciphertext, 0, 16));
    }

    public static function UUIDv4($Prefix = null, $Bytes = 16): string
    {

        $data = random_bytes($Bytes);
        return $Prefix . vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }


}
