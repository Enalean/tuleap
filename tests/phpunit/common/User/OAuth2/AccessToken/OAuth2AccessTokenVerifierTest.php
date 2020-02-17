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

namespace Tuleap\User\OAuth2\AccessToken;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;

final class OAuth2AccessTokenVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|OAuth2AccessTokenDAO
     */
    private $dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var OAuth2AccessTokenVerifier
     */
    private $verifier;

    protected function setUp(): void
    {
        $this->dao          = \Mockery::mock(OAuth2AccessTokenDAO::class);
        $this->user_manager = \Mockery::mock(\UserManager::class);
        $this->hasher       = \Mockery::mock(SplitTokenVerificationStringHasher::class);

        $this->verifier = new OAuth2AccessTokenVerifier($this->dao, $this->user_manager, $this->hasher);
    }

    public function testGivingACorrectTokenTheCorrespondingUserIsRetrieved(): void
    {
        $expected_user = new \PFUser(['user_id' => 102, 'language_id' => 'en']);
        $this->user_manager->shouldReceive('getUserById')->with($expected_user->getId())->andReturn($expected_user);

        $access_token = new SplitToken(
            1,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->shouldReceive('searchAccessToken')->with($access_token->getID())->andReturn(
            ['user_id' => $expected_user->getId(), 'verifier' => 'expected_hashed_verification_string']
        );
        $this->hasher->shouldReceive('verifyHash')->andReturn(true);

        $user = $this->verifier->getUser($access_token);

        $this->assertSame($expected_user, $user);
    }

    public function testVerificationFailsWhenTokenCanNotBeFound(): void
    {
        $access_token = \Mockery::mock(SplitToken::class);
        $access_token->shouldReceive('getID')->andReturn(404);

        $this->dao->shouldReceive('searchAccessToken')->with($access_token->getID())->andReturn(null);

        $this->expectException(OAuth2AccessTokenNotFoundException::class);
        $this->verifier->getUser($access_token);
    }

    public function testVerificationFailsWhenVerificationStringDoesNotMatch(): void
    {
        $access_token = new SplitToken(
            2,
            new SplitTokenVerificationString(new ConcealedString('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'))
        );

        $this->dao->shouldReceive('searchAccessToken')->with($access_token->getID())->andReturn(
            ['user_id' => 102, 'verifier' => 'expected_hashed_verification_string']
        );
        $this->hasher->shouldReceive('verifyHash')->andReturn(false);

        $this->expectException(InvalidOAuth2AccessTokenException::class);
        $this->verifier->getUser($access_token);
    }

    public function testVerificationFailsWhenTheUserCanNotBeFound(): void
    {
        $this->user_manager->shouldReceive('getUserById')->andReturn(null);

        $access_token = new SplitToken(
            3,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->shouldReceive('searchAccessToken')->with($access_token->getID())->andReturn(
            ['user_id' => 404, 'verifier' => 'expected_hashed_verification_string']
        );
        $this->hasher->shouldReceive('verifyHash')->andReturn(true);

        $this->expectException(OAuth2AccessTokenMatchingUnknownUserException::class);
        $this->verifier->getUser($access_token);
    }
}
