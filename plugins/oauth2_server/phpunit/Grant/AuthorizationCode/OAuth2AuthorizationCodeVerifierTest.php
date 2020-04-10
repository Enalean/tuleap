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

namespace Tuleap\OAuth2Server\Grant\AuthorizationCode;

use DateTimeImmutable;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2Server\OAuth2TestScope;
use Tuleap\OAuth2Server\Scope\OAuth2ScopeRetriever;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class OAuth2AuthorizationCodeVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|OAuth2AuthorizationCodeDAO
     */
    private $dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|OAuth2ScopeRetriever
     */
    private $scope_retriever;
    /**
     * @var OAuth2AuthorizationCodeVerifier
     */
    private $verifier;

    protected function setUp(): void
    {
        $this->hasher          = \Mockery::mock(SplitTokenVerificationStringHasher::class);
        $this->user_manager    = \Mockery::mock(\UserManager::class);
        $this->dao             = \Mockery::mock(OAuth2AuthorizationCodeDAO::class);
        $this->scope_retriever = \Mockery::mock(OAuth2ScopeRetriever::class);
        $this->verifier        = new OAuth2AuthorizationCodeVerifier(
            $this->hasher,
            $this->user_manager,
            $this->dao,
            $this->scope_retriever,
            new DBTransactionExecutorPassthrough()
        );
    }

    public function testGivingACorrectTokenTheCorrespondingUserIsRetrieved(): void
    {
        $expected_user = new \PFUser(['user_id' => 102, 'language_id' => 'en']);
        $this->user_manager->shouldReceive('getUserById')->with($expected_user->getId())->andReturn($expected_user);

        $auth_code = new SplitToken(
            1,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->shouldReceive('searchAuthorizationCode')->with($auth_code->getID())->andReturn(
            [
                'user_id'               => $expected_user->getId(),
                'verifier'              => 'expected_hashed_verification_string',
                'expiration_date'       => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
                'has_already_been_used' => 0,
                'pkce_code_challenge'   => 'code_challenge',
                'oidc_nonce'            => 'nonce',
            ]
        );
        $this->dao->shouldReceive('markAuthorizationCodeAsUsed')->with($auth_code->getID())->once();
        $this->hasher->shouldReceive('verifyHash')->andReturn(true);
        $this->scope_retriever->shouldReceive('getScopesBySplitToken')->andReturn([OAuth2TestScope::fromItself()]);

        $verified_authorization = $this->verifier->getAuthorizationCode($auth_code);

        $this->assertSame($expected_user, $verified_authorization->getUser());
        $this->assertEquals([OAuth2TestScope::fromItself()], $verified_authorization->getScopes());
    }

    public function testVerificationFailsWhenAuthCodeCannotBeFound(): void
    {
        $auth_code = new SplitToken(
            404,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->shouldReceive('searchAuthorizationCode')->andReturn(null);

        $this->expectException(OAuth2AuthCodeNotFoundException::class);
        $this->verifier->getAuthorizationCode($auth_code);
    }

    public function testVerificationFailsWhenVerificationStringDoesNotMatch(): void
    {
        $expected_user = new \PFUser(['user_id' => 102, 'language_id' => 'en']);
        $this->user_manager->shouldReceive('getUserById')->with($expected_user->getId())->andReturn($expected_user);
        $auth_code = new SplitToken(
            2,
            new SplitTokenVerificationString(new ConcealedString('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'))
        );
        $this->dao->shouldReceive('searchAuthorizationCode')->with($auth_code->getID())->andReturn(
            [
                'user_id'               => $expected_user->getId(),
                'verifier'              => 'wrong_hashed_verification_string',
                'expiration_date'       => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
                'has_already_been_used' => 0
            ]
        );
        $this->hasher->shouldReceive('verifyHash')->andReturn(false);

        $this->expectException(InvalidOAuth2AuthCodeException::class);
        $this->verifier->getAuthorizationCode($auth_code);
    }

    public function testVerificationFailsWhenTheAuthCodeHasExpired(): void
    {
        $expected_user = new \PFUser(['user_id' => 102, 'language_id' => 'en']);
        $this->user_manager->shouldReceive('getUserById')->with($expected_user->getId())->andReturn($expected_user);
        $auth_code = new SplitToken(
            3,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->shouldReceive('searchAuthorizationCode')->with($auth_code->getID())->andReturn(
            [
                'user_id'               => $expected_user->getId(),
                'verifier'              => 'wrong_hashed_verification_string',
                'expiration_date'       => (new DateTimeImmutable('yesterday'))->getTimestamp(),
                'has_already_been_used' => 0
            ]
        );
        $this->hasher->shouldReceive('verifyHash')->andReturn(true);

        $this->expectException(OAuth2AuthCodeExpiredException::class);
        $this->verifier->getAuthorizationCode($auth_code);
    }

    public function testVerificationFailsWhenTheUserAssociatedWithTheAuthCodeCannotBeFound(): void
    {
        $this->user_manager->shouldReceive('getUserById')->andReturn(null);

        $auth_code = new SplitToken(
            4,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->shouldReceive('searchAuthorizationCode')->with($auth_code->getID())->andReturn(
            [
                'user_id'               => 404,
                'verifier'              => 'expected_hashed_verification_string',
                'expiration_date'       => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
                'has_already_been_used' => 0
            ]
        );
        $this->dao->shouldReceive('markAuthorizationCodeAsUsed')->with($auth_code->getID())->once();
        $this->hasher->shouldReceive('verifyHash')->andReturn(true);

        $this->expectException(OAuth2AuthCodeMatchingUnknownUserException::class);
        $this->verifier->getAuthorizationCode($auth_code);
    }

    public function testVerificationFailsWhenAuthCodeHasAlreadyBeenUsed(): void
    {
        $auth_code = new SplitToken(
            5,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->shouldReceive('searchAuthorizationCode')->with($auth_code->getID())->andReturn(
            [
                'user_id'               => 102,
                'verifier'              => 'expected_hashed_verification_string',
                'expiration_date'       => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
                'has_already_been_used' => 1
            ]
        );
        $this->dao->shouldReceive('deleteAuthorizationCodeByID')->with($auth_code->getID())->once();
        $this->hasher->shouldReceive('verifyHash')->andReturn(true);

        $this->expectException(OAuth2AuthCodeReusedException::class);
        $this->verifier->getAuthorizationCode($auth_code);
    }

    public function testVerificationFailsWhenNoValidScopesCanBeFound(): void
    {
        $expected_user = new \PFUser(['user_id' => 102, 'language_id' => 'en']);
        $this->user_manager->shouldReceive('getUserById')->with($expected_user->getId())->andReturn($expected_user);

        $auth_code = new SplitToken(
            6,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->shouldReceive('searchAuthorizationCode')->with($auth_code->getID())->andReturn(
            [
                'user_id'               => $expected_user->getId(),
                'verifier'              => 'expected_hashed_verification_string',
                'expiration_date'       => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
                'has_already_been_used' => 0
            ]
        );
        $this->dao->shouldReceive('markAuthorizationCodeAsUsed')->with($auth_code->getID())->once();
        $this->hasher->shouldReceive('verifyHash')->andReturn(true);
        $this->scope_retriever->shouldReceive('getScopesBySplitToken')->andReturn([]);

        $this->expectException(OAuth2AuthCodeNoValidScopeFound::class);
        $this->verifier->getAuthorizationCode($auth_code);
    }
}
