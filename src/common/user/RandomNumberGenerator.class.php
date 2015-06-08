<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class RandomNumberGenerator {
    private $token_size;

    // 128 bits of entropy is enough most of the time
    // @see https://www.owasp.org/index.php/Insufficient_Session-ID_Length
    public function __construct($token_size = 16) {
        $this->token_size = $token_size;
    }

    /**
     * Generate a number that could be used has a session ID or during a password
     * reset procedure
     *
     * @return string Number represented has a hexadecimal string
     */
    public function getNumber() {
        $token = '';
        if (function_exists('openssl_random_pseudo_bytes')) {
            $token = bin2hex(openssl_random_pseudo_bytes($this->token_size));
        }
        // Before PHP 5.3.7 mcrypt_create_iv() is bugged
        // @see https://bugs.php.net/bug.php?id=55169
        else if (function_exists('mcrypt_create_iv') && version_compare(PHP_VERSION, '5.3.7') >= 0) {
            $token = bin2hex(mcrypt_create_iv($this->token_size));
        }
        else if ($handle = @fopen('/dev/urandom', 'rb')) {
            $token = bin2hex(fread($handle, $this->token_size));
            fclose($handle);
        }

        // In case of a token can not be generated with safe PRNG, we create one
        // using a non cryptographic PRNG
        if (!$token) {
            $token = $this->fallbackRandomGenerator($this->token_size);
        }

        return $token;
    }

    /**
     * This function SHOULD NOT be used unless there is no safe PRNG available
     *
     * We implement a simple PRNG, our main goal is to make it random enough
     * to make it difficult for an attacker to guess the token
     *
     * @return string
     */
    private function fallbackRandomGenerator($size) {
        $token = '';
        $random_state = print_r($_SERVER, true);
        $random_state .= microtime();
        for ($i = 0; $i < ceil($size/32); $i++) {
            $random_state = hash('sha256', $random_state . mt_rand());
            $token .= $random_state;
        }
        return substr($token, 0, $size*2);
    }
}
