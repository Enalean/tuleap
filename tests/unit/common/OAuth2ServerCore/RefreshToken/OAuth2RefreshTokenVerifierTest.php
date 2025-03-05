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

namespace Tuleap\OAuth2ServerCore\RefreshToken;

use DateTimeImmutable;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCodeRevoker;
use Tuleap\OAuth2ServerCore\OAuth2TestScope;
use Tuleap\OAuth2ServerCore\Scope\OAuth2ScopeRetriever;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OAuth2RefreshTokenVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2RefreshTokenDAO
     */
    private $dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2ScopeRetriever
     */
    private $scope_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2AuthorizationCodeRevoker
     */
    private $auth_code_revoker;
    /**
     * @var OAuth2RefreshTokenVerifier
     */
    private $verifier;

    protected function setUp(): void
    {
        $this->hasher            = $this->createMock(SplitTokenVerificationStringHasher::class);
        $this->dao               = $this->createMock(OAuth2RefreshTokenDAO::class);
        $this->scope_retriever   = $this->createMock(OAuth2ScopeRetriever::class);
        $this->auth_code_revoker = $this->createMock(OAuth2AuthorizationCodeRevoker::class);
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
        $app           = $this->buildApp();
        $this->dao->method('searchRefreshTokenByID')->with($refresh_token->getID())->willReturn(
            [
                'verifier'              => 'expected_hashed_verification_string',
                'expiration_date'       => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
                'has_already_been_used' => 0,
                'authorization_code_id' => 12,
                'app_id'                => $app->getId(),
            ]
        );
        $this->dao->expects(self::once())->method('markRefreshTokenAsUsed')->with($refresh_token->getID());
        $this->hasher->method('verifyHash')->willReturn(true);
        $this->scope_retriever->method('getScopesBySplitToken')->willReturn([OAuth2TestScope::fromItself()]);

        $verified_refresh_token = $this->verifier->getRefreshToken($app, $refresh_token);

        self::assertSame(12, $verified_refresh_token->getAssociatedAuthorizationCodeID());
        $this->assertEquals([OAuth2TestScope::fromItself()], $verified_refresh_token->getScopes());
    }

    public function testVerificationFailsWhenRefreshTokenCannotBeFound(): void
    {
        $refresh_token = new SplitToken(
            404,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->method('searchRefreshTokenByID')->willReturn(null);

        $this->expectException(OAuth2RefreshTokenNotFoundException::class);
        $this->verifier->getRefreshToken($this->buildApp(), $refresh_token);
    }

    public function testVerificationFailsWhenVerificationStringDoesNotMatch(): void
    {
        $refresh_token = new SplitToken(
            2,
            new SplitTokenVerificationString(new ConcealedString('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'))
        );
        $this->dao->method('searchRefreshTokenByID')->with($refresh_token->getID())->willReturn(
            [
                'verifier' => 'wrong_hashed_verification_string',
            ]
        );
        $this->hasher->method('verifyHash')->willReturn(false);

        $this->expectException(InvalidOAuth2RefreshTokenException::class);
        $this->verifier->getRefreshToken($this->buildApp(), $refresh_token);
    }

    public function testVerificationFailsWhenRefreshTokenHasAlreadyBeenUsedAndGrantIsRevoked(): void
    {
        $refresh_token = new SplitToken(
            3,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->method('searchRefreshTokenByID')->with($refresh_token->getID())->willReturn(
            [
                'verifier'              => 'expected_hashed_verification_string',
                'expiration_date'       => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
                'has_already_been_used' => 1,
                'authorization_code_id' => 12,
            ]
        );
        $this->hasher->method('verifyHash')->willReturn(true);
        $this->auth_code_revoker->expects(self::once())->method('revokeByAuthCodeId')->with(12);

        $this->expectException(OAuth2RefreshTokenReusedException::class);
        $this->verifier->getRefreshToken($this->buildApp(), $refresh_token);
    }

    public function testVerificationFailsWhenTheRefreshTokenHasExpired(): void
    {
        $refresh_token = new SplitToken(
            4,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->method('searchRefreshTokenByID')->with($refresh_token->getID())->willReturn(
            [
                'verifier'              => 'expected_hashed_verification_string',
                'expiration_date'       => (new DateTimeImmutable('yesterday'))->getTimestamp(),
                'has_already_been_used' => 0,
            ]
        );
        $this->hasher->method('verifyHash')->willReturn(true);

        $this->expectException(OAuth2RefreshTokenExpiredException::class);
        $this->verifier->getRefreshToken($this->buildApp(), $refresh_token);
    }

    public function testVerificationFailsWhenNoValidScopesCanBeFound(): void
    {
        $refresh_token = new SplitToken(
            5,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $app           = $this->buildApp();
        $this->dao->method('searchRefreshTokenByID')->with($refresh_token->getID())->willReturn(
            [
                'verifier'              => 'expected_hashed_verification_string',
                'expiration_date'       => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
                'has_already_been_used' => 0,
                'app_id'                => $app->getId(),
            ]
        );
        $this->dao->expects(self::once())->method('markRefreshTokenAsUsed')->with($refresh_token->getID());
        $this->hasher->method('verifyHash')->willReturn(true);
        $this->scope_retriever->method('getScopesBySplitToken')->willReturn([]);

        $this->expectException(OAuth2RefreshTokenNoValidScopeFound::class);
        $this->verifier->getRefreshToken($app, $refresh_token);
    }

    public function testVerificationFailsWhenTheRefreshTokenDoesNotMatchTheAuthenticatedApp(): void
    {
        $refresh_token = new SplitToken(
            5,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $app           = $this->buildApp();
        $this->dao->method('searchRefreshTokenByID')->with($refresh_token->getID())->willReturn(
            [
                'verifier'              => 'expected_hashed_verification_string',
                'expiration_date'       => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
                'has_already_been_used' => 0,
                'app_id'                => $app->getId() + 999,
            ]
        );
        $this->dao->expects(self::once())->method('markRefreshTokenAsUsed')->with($refresh_token->getID());
        $this->hasher->method('verifyHash')->willReturn(true);
        $this->scope_retriever->method('getScopesBySplitToken')->willReturn([]);

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
