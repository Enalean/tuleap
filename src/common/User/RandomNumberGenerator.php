<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
    /**
     * @psalm-param positive-int $token_size
     */
    public function __construct(private int $token_size = 16)
    {
    }

    /**
     * Generate a number that could be used has a session ID or during a password
     * reset procedure
     *
     * @return string Number represented has a hexadecimal string
     * @psalm-return non-empty-string
     */
    public function getNumber(): string
    {
        try {
            $random = bin2hex(random_bytes($this->token_size));
        } catch (Exception $ex) {
            die('Could not generate a random number. Is your OS secure?');
        }
        // This need a PR to Psalm, random_bytes always return a non-empty-string
        if ($random === '') {
            die('Cannot happen, random string is not supposed to be empty');
        }

        return $random;
    }
}
