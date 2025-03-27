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

namespace Tuleap\OAuth2ServerCore\Grant\AuthorizationCode;

use DateTimeImmutable;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2ServerCore\OAuth2TestScope;
use Tuleap\OAuth2ServerCore\Scope\OAuth2ScopeRetriever;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OAuth2AuthorizationCodeVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2AuthorizationCodeDAO
     */
    private $dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2ScopeRetriever
     */
    private $scope_retriever;
    /**
     * @var OAuth2AuthorizationCodeVerifier
     */
    private $verifier;

    protected function setUp(): void
    {
        $this->hasher          = $this->createMock(SplitTokenVerificationStringHasher::class);
        $this->user_manager    = $this->createMock(\UserManager::class);
        $this->dao             = $this->createMock(OAuth2AuthorizationCodeDAO::class);
        $this->scope_retriever = $this->createMock(OAuth2ScopeRetriever::class);
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
        $this->user_manager->method('getUserById')->with($expected_user->getId())->willReturn($expected_user);

        $auth_code = new SplitToken(
            1,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->method('searchAuthorizationCode')->with($auth_code->getID())->willReturn(
            [
                'user_id'               => $expected_user->getId(),
                'verifier'              => 'expected_hashed_verification_string',
                'expiration_date'       => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
                'has_already_been_used' => 0,
                'pkce_code_challenge'   => 'code_challenge',
                'oidc_nonce'            => 'nonce',
            ]
        );
        $this->dao->expects($this->once())->method('markAuthorizationCodeAsUsed')->with($auth_code->getID());
        $this->hasher->method('verifyHash')->willReturn(true);
        $this->scope_retriever->method('getScopesBySplitToken')->willReturn([OAuth2TestScope::fromItself()]);

        $verified_authorization = $this->verifier->getAuthorizationCode($auth_code);

        self::assertSame($expected_user, $verified_authorization->getUser());
        $this->assertEquals([OAuth2TestScope::fromItself()], $verified_authorization->getScopes());
    }

    public function testVerificationFailsWhenAuthCodeCannotBeFound(): void
    {
        $auth_code = new SplitToken(
            404,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->method('searchAuthorizationCode')->willReturn(null);

        $this->expectException(OAuth2AuthCodeNotFoundException::class);
        $this->verifier->getAuthorizationCode($auth_code);
    }

    public function testVerificationFailsWhenVerificationStringDoesNotMatch(): void
    {
        $expected_user = new \PFUser(['user_id' => 102, 'language_id' => 'en']);
        $this->user_manager->method('getUserById')->with($expected_user->getId())->willReturn($expected_user);
        $auth_code = new SplitToken(
            2,
            new SplitTokenVerificationString(new ConcealedString('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'))
        );
        $this->dao->method('searchAuthorizationCode')->with($auth_code->getID())->willReturn(
            [
                'user_id'               => $expected_user->getId(),
                'verifier'              => 'wrong_hashed_verification_string',
                'expiration_date'       => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
                'has_already_been_used' => 0,
            ]
        );
        $this->hasher->method('verifyHash')->willReturn(false);

        $this->expectException(InvalidOAuth2AuthCodeException::class);
        $this->verifier->getAuthorizationCode($auth_code);
    }

    public function testVerificationFailsWhenTheAuthCodeHasExpired(): void
    {
        $expected_user = new \PFUser(['user_id' => 102, 'language_id' => 'en']);
        $this->user_manager->method('getUserById')->with($expected_user->getId())->willReturn($expected_user);
        $auth_code = new SplitToken(
            3,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->method('searchAuthorizationCode')->with($auth_code->getID())->willReturn(
            [
                'user_id'               => $expected_user->getId(),
                'verifier'              => 'wrong_hashed_verification_string',
                'expiration_date'       => (new DateTimeImmutable('yesterday'))->getTimestamp(),
                'has_already_been_used' => 0,
            ]
        );
        $this->hasher->method('verifyHash')->willReturn(true);

        $this->expectException(OAuth2AuthCodeExpiredException::class);
        $this->verifier->getAuthorizationCode($auth_code);
    }

    public function testVerificationFailsWhenTheUserAssociatedWithTheAuthCodeCannotBeFound(): void
    {
        $this->user_manager->method('getUserById')->willReturn(null);

        $auth_code = new SplitToken(
            4,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->method('searchAuthorizationCode')->with($auth_code->getID())->willReturn(
            [
                'user_id'               => 404,
                'verifier'              => 'expected_hashed_verification_string',
                'expiration_date'       => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
                'has_already_been_used' => 0,
            ]
        );
        $this->dao->expects($this->once())->method('markAuthorizationCodeAsUsed')->with($auth_code->getID());
        $this->hasher->method('verifyHash')->willReturn(true);

        $this->expectException(OAuth2AuthCodeMatchingUnknownUserException::class);
        $this->verifier->getAuthorizationCode($auth_code);
    }

    public function testVerificationFailsWhenAuthCodeHasAlreadyBeenUsed(): void
    {
        $auth_code = new SplitToken(
            5,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->method('searchAuthorizationCode')->with($auth_code->getID())->willReturn(
            [
                'user_id'               => 102,
                'verifier'              => 'expected_hashed_verification_string',
                'expiration_date'       => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
                'has_already_been_used' => 1,
            ]
        );
        $this->dao->expects($this->once())->method('deleteAuthorizationCodeByID')->with($auth_code->getID());
        $this->hasher->method('verifyHash')->willReturn(true);

        $this->expectException(OAuth2AuthCodeReusedException::class);
        $this->verifier->getAuthorizationCode($auth_code);
    }

    public function testVerificationFailsWhenNoValidScopesCanBeFound(): void
    {
        $expected_user = new \PFUser(['user_id' => 102, 'language_id' => 'en']);
        $this->user_manager->method('getUserById')->with($expected_user->getId())->willReturn($expected_user);

        $auth_code = new SplitToken(
            6,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->method('searchAuthorizationCode')->with($auth_code->getID())->willReturn(
            [
                'user_id'               => $expected_user->getId(),
                'verifier'              => 'expected_hashed_verification_string',
                'expiration_date'       => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
                'has_already_been_used' => 0,
            ]
        );
        $this->dao->expects($this->once())->method('markAuthorizationCodeAsUsed')->with($auth_code->getID());
        $this->hasher->method('verifyHash')->willReturn(true);
        $this->scope_retriever->method('getScopesBySplitToken')->willReturn([]);

        $this->expectException(OAuth2AuthCodeNoValidScopeFound::class);
        $this->verifier->getAuthorizationCode($auth_code);
    }
}
