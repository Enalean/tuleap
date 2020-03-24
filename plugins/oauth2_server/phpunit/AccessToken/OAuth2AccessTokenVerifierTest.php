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

namespace Tuleap\OAuth2Server\AccessToken;

use DateTimeImmutable;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeDefinition;
use Tuleap\Authentication\Scope\AuthenticationScopeIdentifier;
use Tuleap\Authentication\Scope\AuthenticationScopeThrowOnActualMethodCall;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2Server\Scope\OAuth2ScopeRetriever;
use Tuleap\User\OAuth2\AccessToken\InvalidOAuth2AccessTokenException;
use Tuleap\User\OAuth2\AccessToken\OAuth2AccessTokenDoesNotHaveRequiredScopeException;
use Tuleap\User\OAuth2\AccessToken\OAuth2AccessTokenExpiredException;
use Tuleap\User\OAuth2\AccessToken\OAuth2AccessTokenMatchingUnknownUserException;
use Tuleap\User\OAuth2\AccessToken\OAuth2AccessTokenNotFoundException;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;

final class OAuth2AccessTokenVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|OAuth2AccessTokenDAO
     */
    private $dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|OAuth2ScopeRetriever
     */
    private $scope_retriever;
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
        $this->dao             = \Mockery::mock(OAuth2AccessTokenDAO::class);
        $this->scope_retriever = \Mockery::mock(OAuth2ScopeRetriever::class);
        $this->user_manager    = \Mockery::mock(\UserManager::class);
        $this->hasher          = \Mockery::mock(SplitTokenVerificationStringHasher::class);

        $this->verifier = new OAuth2AccessTokenVerifier($this->dao, $this->scope_retriever, $this->user_manager, $this->hasher);
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
            [
                'user_id'         => $expected_user->getId(),
                'verifier'        => 'expected_hashed_verification_string',
                'expiration_date' => (new DateTimeImmutable('tomorrow'))->getTimestamp()
            ]
        );
        $this->hasher->shouldReceive('verifyHash')->andReturn(true);
        $required_scope = $this->buildRequiredScope();
        $this->scope_retriever->shouldReceive('getScopesBySplitToken')->andReturn([$required_scope]);

        $user = $this->verifier->getUser($access_token, $required_scope);

        $this->assertSame($expected_user, $user);
    }

    public function testVerificationFailsWhenTokenCanNotBeFound(): void
    {
        $access_token = \Mockery::mock(SplitToken::class);
        $access_token->shouldReceive('getID')->andReturn(404);

        $this->dao->shouldReceive('searchAccessToken')->with($access_token->getID())->andReturn(null);

        $this->expectException(OAuth2AccessTokenNotFoundException::class);
        $this->verifier->getUser($access_token, $this->buildRequiredScope());
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
        $this->verifier->getUser($access_token, $this->buildRequiredScope());
    }

    public function testVerificationFailsWhenTheAccessTokenHasExpired(): void
    {
        $access_token = new SplitToken(
            5,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );

        $this->dao->shouldReceive('searchAccessToken')->with($access_token->getID())->andReturn(
            [
                'user_id'         => 102,
                'verifier'        => 'expected_hashed_verification_string',
                'expiration_date' => (new DateTimeImmutable('yesterday'))->getTimestamp()
            ]
        );
        $this->hasher->shouldReceive('verifyHash')->andReturn(true);

        $this->expectException(OAuth2AccessTokenExpiredException::class);
        $this->verifier->getUser($access_token, $this->buildRequiredScope());
    }

    public function testVerificationFailsWhenTheUserCanNotBeFound(): void
    {
        $this->user_manager->shouldReceive('getUserById')->andReturn(null);

        $access_token = new SplitToken(
            3,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->shouldReceive('searchAccessToken')->with($access_token->getID())->andReturn(
            [
                'user_id'         => 404,
                'verifier'        => 'expected_hashed_verification_string',
                'expiration_date' => (new DateTimeImmutable('tomorrow'))->getTimestamp()
            ]
        );
        $this->hasher->shouldReceive('verifyHash')->andReturn(true);
        $required_scope = $this->buildRequiredScope();
        $this->scope_retriever->shouldReceive('getScopesBySplitToken')->andReturn([$required_scope]);

        $this->expectException(OAuth2AccessTokenMatchingUnknownUserException::class);
        $this->verifier->getUser($access_token, $required_scope);
    }

    /**
     * @dataProvider dataProviderScopeFailures
     */
    public function testVerificationFailsWhenTheRequiredScopeCannotBeApproved(AuthenticationScope ...$scopes_matching_access_token): void
    {
        $expected_user = new \PFUser(['user_id' => 103, 'language_id' => 'en']);
        $this->user_manager->shouldReceive('getUserById')->with($expected_user->getId())->andReturn($expected_user);

        $access_token = new SplitToken(
            4,
            new SplitTokenVerificationString(new ConcealedString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))
        );
        $this->dao->shouldReceive('searchAccessToken')->with($access_token->getID())->andReturn(
            [
                'user_id'         => $expected_user->getId(),
                'verifier'        => 'expected_hashed_verification_string',
                'expiration_date' => (new DateTimeImmutable('tomorrow'))->getTimestamp()
            ]
        );
        $this->hasher->shouldReceive('verifyHash')->andReturn(true);
        $this->scope_retriever->shouldReceive('getScopesBySplitToken')->andReturn($scopes_matching_access_token);

        $this->expectException(OAuth2AccessTokenDoesNotHaveRequiredScopeException::class);
        $this->verifier->getUser($access_token, $this->buildRequiredScope());
    }

    public function dataProviderScopeFailures(): array
    {
        return [
            'No scopes associated with the access token' => [],
            'None of the scopes covers the required scope' => [$this->buildScopeCoveringNothing(), $this->buildScopeCoveringNothing()],
        ];
    }

    private function buildRequiredScope(): AuthenticationScope
    {
        return new class /** @psalm-immutable */ implements AuthenticationScope
        {
            public static function fromItself() : AuthenticationScope
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public static function fromIdentifier(AuthenticationScopeIdentifier $identifier) : ?AuthenticationScope
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public function getIdentifier() : AuthenticationScopeIdentifier
            {
                return OAuth2ScopeIdentifier::fromIdentifierKey('required');
            }

            public function getDefinition() : AuthenticationScopeDefinition
            {
                return new class /** @psalm-immutable */ implements AuthenticationScopeDefinition {
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

            public function covers(AuthenticationScope $scope) : bool
            {
                return true;
            }
        };
    }

    public function buildScopeCoveringNothing(): AuthenticationScope
    {
        return new class /** @psalm-immutable */ implements AuthenticationScope
        {
            use AuthenticationScopeThrowOnActualMethodCall;

            public static function fromItself() : AuthenticationScope
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public static function fromIdentifier(AuthenticationScopeIdentifier $identifier) : ?AuthenticationScope
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public function covers(AuthenticationScope $scope) : bool
            {
                return false;
            }
        };
    }
}
