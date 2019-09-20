<?php
/**
 * Copyright (c) Enalean, 2015-2017. All Rights Reserved.
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

class RandomNumberGenerator
{
    private $token_size;

    // 128 bits of entropy is enough most of the time
    // @see https://www.owasp.org/index.php/Insufficient_Session-ID_Length
    public function __construct($token_size = 16)
    {
        $this->token_size = $token_size;
    }

    /**
     * Generate a number that could be used has a session ID or during a password
     * reset procedure
     *
     * @return string Number represented has a hexadecimal string
     */
    public function getNumber()
    {
        try {
            return bin2hex(random_bytes($this->token_size));
        } catch (Exception $ex) {
            die('Could not generate a random number. Is your OS secure?');
        }
    }
}
