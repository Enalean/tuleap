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

use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\InvalidIdentifierFormatException;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Cryptography\ConcealedString;

class ResetTokenSerializerTest extends TestCase
{
    public function testCanSerializeAndUnserializeAToken()
    {
        $verification_string = SplitTokenVerificationString::generateNewSplitTokenVerificationString();
        $token               = new SplitToken(100, $verification_string);

        $serializer = new ResetTokenSerializer();

        $identifier = $serializer->getIdentifier($token);

        $unserialized_token = $serializer->getSplitToken($identifier);
        $this->assertSame(100, $unserialized_token->getID());
        $this->assertSame((string) $verification_string->getString(), (string) $unserialized_token->getVerificationString()->getString());
    }

    /**
     * @dataProvider incorrectlyFormattedIdentifierProvider
     */
    public function testIncorrectlyFormattedIdentifierIsRejected(string $incorrectly_formatted_identifier): void
    {
        $serializer = new ResetTokenSerializer();

        $this->expectException(InvalidIdentifierFormatException::class);

        $serializer->getSplitToken(new ConcealedString($incorrectly_formatted_identifier));
    }

    public function incorrectlyFormattedIdentifierProvider(): array
    {
        return [
            ['100' . ResetTokenSerializer::PARTS_SEPARATOR . 'random_string' . ResetTokenSerializer::PARTS_SEPARATOR . 'separated'],
            [''],
            ['100' . ResetTokenSerializer::PARTS_SEPARATOR . 'aaa'],
        ];
    }
}
