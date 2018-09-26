<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

class TokenTest extends \TuleapTestCase
{
    public function itConstructsTheIdentifier()
    {
        $token = new Token(100, 'random_string');

        $expected_identifier = '100' . Token::TOKEN_PARTS_SEPARATOR . 'random_string';

        $this->assertEqual($expected_identifier, $token->getIdentifier());
    }

    public function itCreatesATokenFromTheIdentifier()
    {
        $identifier = '100' . Token::TOKEN_PARTS_SEPARATOR . 'random_string';

        $token = Token::constructFromIdentifier($identifier);

        $this->assertEqual('100', $token->getId());
        $this->assertEqual('random_string', $token->getVerifier());
    }

    public function itRejectsIncorrectlyFormattedVerifier()
    {
        $this->expectException('Tuleap\\User\\Password\\Reset\\VerifierIncorrectlyFormattedException');

        new Token(100, 'random_string' . Token::TOKEN_PARTS_SEPARATOR . 'separated');
    }

    public function itRejectsIncorrectlyFormattedIdentifier()
    {
        $identifier = '100' . Token::TOKEN_PARTS_SEPARATOR . 'random_string' . Token::TOKEN_PARTS_SEPARATOR . 'separated';

        $this->expectException('Tuleap\\User\\Password\\Reset\\InvalidIdentifierException');

        Token::constructFromIdentifier($identifier);
    }
}
