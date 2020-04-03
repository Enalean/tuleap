<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\GitLFS\Authorization;

use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\InvalidIdentifierFormatException;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Cryptography\ConcealedString;

class LFSAuthorizationTokenHeaderSerializerTest extends TestCase
{
    public function testAuthorizationTokenCanBeSerializedToHeaderAndUnserialized()
    {
        $verification_string = SplitTokenVerificationString::generateNewSplitTokenVerificationString();
        $split_token = new SplitToken(1, $verification_string);

        $serializer = new LFSAuthorizationTokenHeaderSerializer();

        $header_identifier        = $serializer->getIdentifier($split_token);
        $split_token_unserialized = $serializer->getSplitToken($header_identifier);

        $this->assertSame($split_token->getID(), $split_token_unserialized->getID());
        $this->assertSame(
            (string) $split_token->getVerificationString()->getString(),
            (string) $split_token_unserialized->getVerificationString()->getString()
        );
    }

    /**
     * @dataProvider incorrectlyFormattedIdentifierProvider
     */
    public function testBuildingFromAnIncorrectlyFormattedIdentifierIsRejected(string $incorrectly_formatted_identifier): void
    {
        $serializer = new LFSAuthorizationTokenHeaderSerializer();

        $this->expectException(InvalidIdentifierFormatException::class);

        $serializer->getSplitToken(new ConcealedString($incorrectly_formatted_identifier));
    }

    public function incorrectlyFormattedIdentifierProvider(): array
    {
        return [
            ['incorrect_identifier'],
            ['RemoteAuth 1.aaa'],
        ];
    }
}
