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

namespace Tuleap\OAuth2ServerCore\AccessToken;

use DateTimeImmutable;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeDefinition;
use Tuleap\Authentication\Scope\AuthenticationScopeIdentifier;
use Tuleap\Authentication\Scope\AuthenticationScopeThrowOnActualMethodCall;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2ServerCore\Scope\OAuth2ScopeRetriever;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\User\OAuth2\AccessToken\InvalidOAuth2AccessTokenException;
use Tuleap\User\OAuth2\AccessToken\OAuth2AccessTokenDoesNotHaveRequiredScopeException;
use Tuleap\User\OAuth2\AccessToken\OAuth2AccessTokenExpiredException;
use Tuleap\User\OAuth2\AccessToken\OAuth2AccessTokenMatchingUnknownUserException;
use Tuleap\User\OAuth2\AccessToken\OAuth2AccessTokenNotFoundException;
use Tuleap\User\OAuth2\ResourceServer\GrantedAuthorization;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OAuth2AccessTokenVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2AccessTokenDAO
     */
    private $dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2ScopeRetriever
     */
    private $scope_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var OAuth2AccessTokenVerifier
     */
    private $verifier;

    protected function setUp(): void
    {
        $this->dao             = $this->createMock(OAuth2AccessTokenDAO::class);
        $this->scope_retriever = $this->createMock(OAuth2ScopeRetriever::class);
        $this->user_manager    = $this->createMock(\UserManager::class);
        $this->hasher          = $this->createMock(SplitTokenVerificationStringHasher::class);

        $this->verifier = new OAuth2AccessTokenVerifier($this->dao, $this->scope_retriever, $this->user_manager, $this->hasher);
    }

    public function testGivingACorrectTokenTheGrantedAuthorizationIsRetrieved(): void
    {
        $expected_user          = UserTestBuilder::aUser()->withId(102)->build();
        $required_scope         = $this->buildRequiredScope();
        $expected_authorization = new GrantedAuthorization($expected_user, [$required_scope]);
        $this->user_manager->method('getUserById')->with($expected_user->getId())->willReturn($expected_user);

        $access_token = new SplitToken(
            1,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->method('searchAccessToken')->with($access_token->getID())->willReturn(
            [
                'user_id'         => $expected_user->getId(),
                'verifier'        => 'expected_hashed_verification_string',
                'expiration_date' => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
            ]
        );
        $this->hasher->method('verifyHash')->willReturn(true);
        $this->scope_retriever->method('getScopesBySplitToken')->willReturn([$required_scope]);

        $result = $this->verifier->getGrantedAuthorization($access_token, $required_scope);

        $this->assertEquals($expected_authorization, $result);
    }

    public function testVerificationFailsWhenTokenCanNotBeFound(): void
    {
        $access_token = $this->createMock(SplitToken::class);
        $access_token->method('getID')->willReturn(404);

        $this->dao->method('searchAccessToken')->with($access_token->getID())->willReturn(null);

        $this->expectException(OAuth2AccessTokenNotFoundException::class);
        $this->verifier->getGrantedAuthorization($access_token, $this->buildRequiredScope());
    }

    public function testVerificationFailsWhenVerificationStringDoesNotMatch(): void
    {
        $access_token = new SplitToken(
            2,
            new SplitTokenVerificationString(new ConcealedString('bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'))
        );

        $this->dao->method('searchAccessToken')->with($access_token->getID())->willReturn(
            ['user_id' => 102, 'verifier' => 'expected_hashed_verification_string']
        );
        $this->hasher->method('verifyHash')->willReturn(false);

        $this->expectException(InvalidOAuth2AccessTokenException::class);
        $this->verifier->getGrantedAuthorization($access_token, $this->buildRequiredScope());
    }

    public function testVerificationFailsWhenTheAccessTokenHasExpired(): void
    {
        $access_token = new SplitToken(
            5,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );

        $this->dao->method('searchAccessToken')->with($access_token->getID())->willReturn(
            [
                'user_id'         => 102,
                'verifier'        => 'expected_hashed_verification_string',
                'expiration_date' => (new DateTimeImmutable('yesterday'))->getTimestamp(),
            ]
        );
        $this->hasher->method('verifyHash')->willReturn(true);

        $this->expectException(OAuth2AccessTokenExpiredException::class);
        $this->verifier->getGrantedAuthorization($access_token, $this->buildRequiredScope());
    }

    public function testVerificationFailsWhenTheUserCanNotBeFound(): void
    {
        $this->user_manager->method('getUserById')->willReturn(null);

        $access_token = new SplitToken(
            3,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->method('searchAccessToken')->with($access_token->getID())->willReturn(
            [
                'user_id'         => 404,
                'verifier'        => 'expected_hashed_verification_string',
                'expiration_date' => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
            ]
        );
        $this->hasher->method('verifyHash')->willReturn(true);
        $required_scope = $this->buildRequiredScope();
        $this->scope_retriever->method('getScopesBySplitToken')->willReturn([$required_scope]);

        $this->expectException(OAuth2AccessTokenMatchingUnknownUserException::class);
        $this->verifier->getGrantedAuthorization($access_token, $required_scope);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderScopeFailures')]
    public function testVerificationFailsWhenTheRequiredScopeCannotBeApproved(AuthenticationScope ...$scopes_matching_access_token): void
    {
        $expected_user = new \PFUser(['user_id' => 103, 'language_id' => 'en']);
        $this->user_manager->method('getUserById')->with($expected_user->getId())->willReturn($expected_user);

        $access_token = new SplitToken(
            4,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->method('searchAccessToken')->with($access_token->getID())->willReturn(
            [
                'user_id'         => $expected_user->getId(),
                'verifier'        => 'expected_hashed_verification_string',
                'expiration_date' => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
            ]
        );
        $this->hasher->method('verifyHash')->willReturn(true);
        $this->scope_retriever->method('getScopesBySplitToken')->willReturn($scopes_matching_access_token);

        $this->expectException(OAuth2AccessTokenDoesNotHaveRequiredScopeException::class);
        $this->verifier->getGrantedAuthorization($access_token, $this->buildRequiredScope());
    }

    public static function dataProviderScopeFailures(): array
    {
        return [
            'No scopes associated with the access token' => [],
            'None of the scopes covers the required scope' => [self::buildScopeCoveringNothing(), self::buildScopeCoveringNothing()],
        ];
    }

    /**
     * @psalm-return AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>
     */
    private function buildRequiredScope(): AuthenticationScope
    {
        /**
         * @var AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier> $scope
         */
        $scope = new /** @psalm-immutable */ class implements AuthenticationScope
        {
            /**
             * @psalm-pure
             */
            public static function fromItself(): AuthenticationScope
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            /**
             * @psalm-pure
             */
            public static function fromIdentifier(AuthenticationScopeIdentifier $identifier): ?AuthenticationScope
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public function getIdentifier(): AuthenticationScopeIdentifier
            {
                return OAuth2ScopeIdentifier::fromIdentifierKey('required');
            }

            public function getDefinition(): AuthenticationScopeDefinition
            {
                return new /** @psalm-immutable */ class implements AuthenticationScopeDefinition {
                    public function getName(): string
                    {
                        return 'Name';
                    }

                    public function getDescription(): string
                    {
                        return 'Description';
                    }
                };
            }

            public function covers(AuthenticationScope $scope): bool
            {
                return true;
            }
        };

        return $scope;
    }

    public static function buildScopeCoveringNothing(): AuthenticationScope
    {
        return new /** @psalm-immutable */ class implements AuthenticationScope
        {
            use AuthenticationScopeThrowOnActualMethodCall;

            /**
             * @psalm-pure
             */
            public static function fromItself(): AuthenticationScope
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            /**
             * @psalm-pure
             */
            public static function fromIdentifier(AuthenticationScopeIdentifier $identifier): ?AuthenticationScope
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public function covers(AuthenticationScope $scope): bool
            {
                return false;
            }
        };
    }
}
