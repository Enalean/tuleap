<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\User\Password\Reset;

class Token
{
    const TOKEN_PARTS_SEPARATOR = '.';

    /**
     * @var int
     */
    private $token_id;
    /**
     * @var string
     */
    private $verifier;

    public function __construct($token_id, $verifier)
    {
        $this->token_id = $token_id;
        if (mb_strpos($verifier, self::TOKEN_PARTS_SEPARATOR) !== false) {
            throw new VerifierIncorrectlyFormattedException();
        }
        $this->verifier = $verifier;
    }

    /**
     * @return Token
     * @throws \Tuleap\User\Password\Reset\InvalidIdentifierException
     */
    public static function constructFromIdentifier($identifier)
    {
        $identifier_parts = explode(self::TOKEN_PARTS_SEPARATOR, $identifier);
        if (count($identifier_parts) !== 2) {
            throw new InvalidIdentifierException();
        }

        list($token_id, $verifier_password_hashed) = $identifier_parts;

        return new self($token_id, $verifier_password_hashed);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->token_id;
    }

    /**
     * @return string
     */
    public function getVerifier()
    {
        return $this->verifier;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->token_id . self::TOKEN_PARTS_SEPARATOR . $this->verifier;
    }
}
