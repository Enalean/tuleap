<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\User\AccessKey\HTTPBasicAuth;

use Tuleap\Authentication\Scope\AuthenticationTestCoveringScope;
use Tuleap\Authentication\Scope\AuthenticationTestScopeIdentifier;
use Tuleap\Authentication\SplitToken\InvalidIdentifierFormatException;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\User\AccessKey\AccessKeyException;
use Tuleap\User\AccessKey\AccessKeyVerifier;

final class HTTPBasicAuthUserAccessKeyAuthenticatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&SplitTokenIdentifierTranslator
     */
    private $access_key_identifier_unserializer;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AccessKeyVerifier
     */
    private $access_key_verifier;
    private HTTPBasicAuthUserAccessKeyAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->access_key_identifier_unserializer = $this->createMock(SplitTokenIdentifierTranslator::class);
        $this->access_key_verifier                = $this->createMock(AccessKeyVerifier::class);

        $this->authenticator = new HTTPBasicAuthUserAccessKeyAuthenticator(
            $this->access_key_identifier_unserializer,
            $this->access_key_verifier,
            AuthenticationTestCoveringScope::fromIdentifier(AuthenticationTestScopeIdentifier::fromIdentifierKey('test')),
            new \Psr\Log\NullLogger()
        );
    }

    public function testUserCanBeAuthenticatedFromItsAccessKey(): void
    {
        $split_token = $this->createMock(SplitToken::class);
        $this->access_key_identifier_unserializer->method('getSplitToken')
            ->willReturn($split_token);
        $expected_user = UserTestBuilder::aUser()->withUserName('username')->build();
        $this->access_key_verifier->method('getUser')
            ->with($split_token, self::anything(), self::anything())
            ->willReturn($expected_user);

        $user = $this->authenticator->getUser(
            'username',
            new ConcealedString('access_key_identifier'),
            '2001:db8::2'
        );

        self::assertSame($expected_user, $user);
    }

    public function testDoesNotAuthenticateUserWhenThePasswordStringDoesNotLookLikeAnAccessKey(): void
    {
        $this->access_key_identifier_unserializer->method('getSplitToken')
            ->willThrowException(new InvalidIdentifierFormatException());

        $user = $this->authenticator->getUser(
            'username',
            new ConcealedString('wrong_access_key_identifier'),
            '2001:db8::2'
        );

        self::assertNull($user);
    }

    public function testDoesNotAuthenticateUserWhenTheGivenAccessKeyIsNotValid(): void
    {
        $this->access_key_identifier_unserializer->method('getSplitToken')
            ->willReturn($this->createMock(SplitToken::class));
        $this->access_key_verifier->method('getUser')
            ->willThrowException(
                new class extends AccessKeyException
                {
                }
            );

        $user = $this->authenticator->getUser(
            'username',
            new ConcealedString('invalid_access_key_identifier'),
            '2001:db8::2'
        );

        self::assertNull($user);
    }

    public function testDoesNotAuthenticateUserWhenTheAccessKeyDoesNotMatchTheGivenUsername(): void
    {
        $split_token = $this->createMock(SplitToken::class);
        $this->access_key_identifier_unserializer->method('getSplitToken')
            ->willReturn($split_token);
        $found_user_from_access_key = UserTestBuilder::aUser()->withUserName('different_user')->build();
        $this->access_key_verifier->method('getUser')
            ->willReturn($found_user_from_access_key);

        $this->expectException(HTTPBasicAuthUserAccessKeyMisusageException::class);
        $this->authenticator->getUser(
            'username',
            new ConcealedString('access_key_identifier_for_a_different_username'),
            '2001:db8::2'
        );
    }
}
