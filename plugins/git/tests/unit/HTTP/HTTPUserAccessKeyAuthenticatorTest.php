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

namespace Tuleap\Git\HTTP;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\InvalidIdentifierFormatException;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\User\AccessKey\AccessKeyException;
use Tuleap\User\AccessKey\AccessKeyVerifier;

final class HTTPUserAccessKeyAuthenticatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|SplitTokenIdentifierTranslator
     */
    private $access_key_identifier_unserializer;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AccessKeyVerifier
     */
    private $access_key_verifier;

    /**
     * @var HTTPUserAccessKeyAuthenticator
     */
    private $authenticator;

    protected function setUp(): void
    {
        $this->access_key_identifier_unserializer = \Mockery::mock(SplitTokenIdentifierTranslator::class);
        $this->access_key_verifier                = \Mockery::mock(AccessKeyVerifier::class);

        $this->authenticator = new  HTTPUserAccessKeyAuthenticator(
            $this->access_key_identifier_unserializer,
            $this->access_key_verifier,
            new \Psr\Log\NullLogger()
        );
    }

    public function testUserCanBeAuthenticatedFromItsAccessKey(): void
    {
        $split_token = \Mockery::mock(SplitToken::class);
        $this->access_key_identifier_unserializer->shouldReceive('getSplitToken')
            ->andReturn($split_token);
        $expected_user = \Mockery::mock(\PFUser::class);
        $expected_user->shouldReceive('getUserName')->andReturn('username');
        $this->access_key_verifier->shouldReceive('getUser')
            ->with($split_token, \Mockery::any(), \Mockery::any())
            ->andReturn($expected_user);

        $user = $this->authenticator->getUser(
            'username',
            new ConcealedString('access_key_identifier'),
            '2001:db8::2'
        );

        $this->assertSame($expected_user, $user);
    }

    public function testDoesNotAuthenticateUserWhenThePasswordStringDoesNotLookLikeAnAccessKey(): void
    {
        $this->access_key_identifier_unserializer->shouldReceive('getSplitToken')
            ->andThrow(InvalidIdentifierFormatException::class);

        $user = $this->authenticator->getUser(
            'username',
            new ConcealedString('wrong_access_key_identifier'),
            '2001:db8::2'
        );

        $this->assertNull($user);
    }

    public function testDoesNotAuthenticateUserWhenTheGivenAccessKeyIsNotValid(): void
    {
        $this->access_key_identifier_unserializer->shouldReceive('getSplitToken')
            ->andReturn(\Mockery::mock(SplitToken::class));
        $this->access_key_verifier->shouldReceive('getUser')
            ->andThrow(
                new class extends AccessKeyException
                {
                }
            );

        $user = $this->authenticator->getUser(
            'username',
            new ConcealedString('invalid_access_key_identifier'),
            '2001:db8::2'
        );

        $this->assertNull($user);
    }

    public function testDoesNotAuthenticateUserWhenTheAccessKeyDoesNotMatchTheGivenUsername(): void
    {
        $split_token = \Mockery::mock(SplitToken::class);
        $this->access_key_identifier_unserializer->shouldReceive('getSplitToken')
            ->andReturn($split_token);
        $found_user_from_access_key = \Mockery::mock(\PFUser::class);
        $found_user_from_access_key->shouldReceive('getUserName')->andReturn('different_user');
        $this->access_key_verifier->shouldReceive('getUser')
            ->andReturn($found_user_from_access_key);

        $this->expectException(HTTPUserAccessKeyMisusageException::class);
        $this->authenticator->getUser(
            'username',
            new ConcealedString('access_key_identifier_for_a_different_username'),
            '2001:db8::2'
        );
    }
}
