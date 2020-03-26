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

namespace Tuleap\OAuth2Server\RefreshToken;

use DateTimeImmutable;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCodeRevoker;
use Tuleap\OAuth2Server\OAuth2TestScope;
use Tuleap\OAuth2Server\Scope\OAuth2ScopeRetriever;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class OAuth2RefreshTokenVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|OAuth2RefreshTokenDAO
     */
    private $dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|OAuth2ScopeRetriever
     */
    private $scope_retriever;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|OAuth2AuthorizationCodeRevoker
     */
    private $auth_code_revoker;
    /**
     * @var OAuth2RefreshTokenVerifier
     */
    private $verifier;

    protected function setUp(): void
    {
        $this->hasher            = \Mockery::mock(SplitTokenVerificationStringHasher::class);
        $this->dao               = \Mockery::mock(OAuth2RefreshTokenDAO::class);
        $this->scope_retriever   = \Mockery::mock(OAuth2ScopeRetriever::class);
        $this->auth_code_revoker = \Mockery::mock(OAuth2AuthorizationCodeRevoker::class);
        $this->verifier          = new OAuth2RefreshTokenVerifier(
            $this->hasher,
            $this->dao,
            $this->scope_retriever,
            $this->auth_code_revoker,
            new DBTransactionExecutorPassthrough()
        );
    }

    public function testGivingACorrectTokenTheCorrespondingRefreshTokenIsRetrieved(): void
    {
        $refresh_token = new SplitToken(
            1,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $app = $this->buildApp();
        $this->dao->shouldReceive('searchRefreshTokenByID')->with($refresh_token->getID())->andReturn(
            [
                'verifier'              => 'expected_hashed_verification_string',
                'expiration_date'       => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
                'has_already_been_used' => 0,
                'authorization_code_id' => 12,
                'app_id'                => $app->getId()
            ]
        );
        $this->dao->shouldReceive('markRefreshTokenAsUsed')->with($refresh_token->getID())->once();
        $this->hasher->shouldReceive('verifyHash')->andReturn(true);
        $this->scope_retriever->shouldReceive('getScopesBySplitToken')->andReturn([OAuth2TestScope::fromItself()]);

        $verified_refresh_token = $this->verifier->getRefreshToken($app, $refresh_token);

        $this->assertSame(12, $verified_refresh_token->getAssociatedAuthorizationCodeID());
        $this->assertEquals([OAuth2TestScope::fromItself()], $verified_refresh_token->getScopes());
    }

    public function testVerificationFailsWhenRefreshTokenCannotBeFound(): void
    {
        $refresh_token = new SplitToken(
            404,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->shouldReceive('searchRefreshTokenByID')->andReturn(null);

        $this->expectException(OAuth2RefreshTokenNotFoundException::class);
        $this->verifier->getRefreshToken($this->buildApp(), $refresh_token);
    }

    public function testVerificationFailsWhenVerificationStringDoesNotMatch(): void
    {
        $refresh_token = new SplitToken(
            2,
            new SplitTokenVerificationString(new ConcealedString('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'))
        );
        $this->dao->shouldReceive('searchRefreshTokenByID')->with($refresh_token->getID())->andReturn(
            [
                'verifier' => 'wrong_hashed_verification_string',
            ]
        );
        $this->hasher->shouldReceive('verifyHash')->andReturn(false);

        $this->expectException(InvalidOAuth2RefreshTokenException::class);
        $this->verifier->getRefreshToken($this->buildApp(), $refresh_token);
    }

    public function testVerificationFailsWhenRefreshTokenHasAlreadyBeenUsedAndGrantIsRevoked(): void
    {
        $refresh_token = new SplitToken(
            3,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->shouldReceive('searchRefreshTokenByID')->with($refresh_token->getID())->andReturn(
            [
                'verifier'              => 'expected_hashed_verification_string',
                'expiration_date'       => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
                'has_already_been_used' => 1,
                'authorization_code_id' => 12
            ]
        );
        $this->hasher->shouldReceive('verifyHash')->andReturn(true);
        $this->auth_code_revoker->shouldReceive('revokeByAuthCodeId')->with(12)->once();

        $this->expectException(OAuth2RefreshTokenReusedException::class);
        $this->verifier->getRefreshToken($this->buildApp(), $refresh_token);
    }

    public function testVerificationFailsWhenTheRefreshTokenHasExpired(): void
    {
        $refresh_token = new SplitToken(
            4,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->shouldReceive('searchRefreshTokenByID')->with($refresh_token->getID())->andReturn(
            [
                'verifier'              => 'expected_hashed_verification_string',
                'expiration_date'       => (new DateTimeImmutable('yesterday'))->getTimestamp(),
                'has_already_been_used' => 0
            ]
        );
        $this->hasher->shouldReceive('verifyHash')->andReturn(true);

        $this->expectException(OAuth2RefreshTokenExpiredException::class);
        $this->verifier->getRefreshToken($this->buildApp(), $refresh_token);
    }

    public function testVerificationFailsWhenNoValidScopesCanBeFound(): void
    {
        $refresh_token = new SplitToken(
            5,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $app = $this->buildApp();
        $this->dao->shouldReceive('searchRefreshTokenByID')->with($refresh_token->getID())->andReturn(
            [
                'verifier'              => 'expected_hashed_verification_string',
                'expiration_date'       => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
                'has_already_been_used' => 0,
                'app_id'                => $app->getId()
            ]
        );
        $this->dao->shouldReceive('markRefreshTokenAsUsed')->with($refresh_token->getID())->once();
        $this->hasher->shouldReceive('verifyHash')->andReturn(true);
        $this->scope_retriever->shouldReceive('getScopesBySplitToken')->andReturn([]);

        $this->expectException(OAuth2RefreshTokenNoValidScopeFound::class);
        $this->verifier->getRefreshToken($app, $refresh_token);
    }

    public function testVerificationFailsWhenTheRefreshTokenDoesNotMatchTheAuthenticatedApp(): void
    {
        $refresh_token = new SplitToken(
            5,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $app = $this->buildApp();
        $this->dao->shouldReceive('searchRefreshTokenByID')->with($refresh_token->getID())->andReturn(
            [
                'verifier'              => 'expected_hashed_verification_string',
                'expiration_date'       => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
                'has_already_been_used' => 0,
                'app_id'                => $app->getId() + 999
            ]
        );
        $this->dao->shouldReceive('markRefreshTokenAsUsed')->with($refresh_token->getID())->once();
        $this->hasher->shouldReceive('verifyHash')->andReturn(true);
        $this->scope_retriever->shouldReceive('getScopesBySplitToken')->andReturn([]);

        $this->expectException(OAuth2RefreshTokenDoesNotCorrespondToExpectedAppException::class);
        $this->verifier->getRefreshToken($app, $refresh_token);
    }

    private function buildApp(): OAuth2App
    {
        return new OAuth2App(
            3,
            'Verify refresh token',
            'https://example.com',
            true,
            new \Project(['group_id' => 101])
        );
    }
}
