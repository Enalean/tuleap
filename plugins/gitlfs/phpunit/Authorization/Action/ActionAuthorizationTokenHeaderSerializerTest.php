<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\GitLFS\Authorization\Action;

use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Cryptography\ConcealedString;

class ActionAuthorizationTokenHeaderSerializerTest extends TestCase
{
    public function testAuthorizationTokenCanBeSerializedToHeaderAndUnserialized()
    {
        $verification_string = SplitTokenVerificationString::generateNewSplitTokenVerificationString();
        $split_token = new SplitToken(1, $verification_string);

        $serializer = new ActionAuthorizationTokenHeaderSerializer();

        $header_identifier        = $serializer->getIdentifier($split_token);
        $split_token_unserialized = $serializer->getSplitToken($header_identifier);

        $this->assertSame($split_token->getID(), $split_token_unserialized->getID());
        $this->assertSame(
            (string) $split_token->getVerificationString()->getString(),
            (string) $split_token_unserialized->getVerificationString()->getString()
        );
    }

    /**
     * @expectedException \Tuleap\Authentication\SplitToken\InvalidIdentifierFormatException
     */
    public function testBuildingFromAnIncorrectlyFormattedIdentifierIsRejected()
    {
        $serializer = new ActionAuthorizationTokenHeaderSerializer();
        $serializer->getSplitToken(new ConcealedString('incorrect_identifier'));
    }
}
