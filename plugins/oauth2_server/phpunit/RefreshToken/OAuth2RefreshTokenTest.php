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

use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeDefinition;
use Tuleap\Authentication\Scope\AuthenticationScopeIdentifier;
use Tuleap\OAuth2Server\OAuth2TestScope;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;

final class OAuth2RefreshTokenTest extends TestCase
{
    public function testBuildsValidRefreshToken(): void
    {
        $scope         = OAuth2TestScope::fromItself();
        $refresh_token = OAuth2RefreshToken::createWithASetOfScopes(
            12,
            [$scope]
        );

        $this->assertSame(12, $refresh_token->getAssociatedAuthorizationCodeID());
        $this->assertEquals([$scope], $refresh_token->getScopes());
    }

    public function testBuildsRefreshTokenWithAReducedSetOfScopes(): void
    {
        $scope              = OAuth2TestScope::fromItself();
        $scope_test_refresh = $this->buildTestScopeRefreshToken();
        $initial_refresh_token = OAuth2RefreshToken::createWithASetOfScopes(
            13,
            [$scope, $scope_test_refresh]
        );

        $this->assertEquals([$scope, $scope_test_refresh], $initial_refresh_token->getScopes());

        $refresh_token_with_reduced_set_of_scopes = OAuth2RefreshToken::createWithAReducedSetOfScopes(
            $initial_refresh_token,
            [$scope]
        );
        $this->assertEquals([$scope], $refresh_token_with_reduced_set_of_scopes->getScopes());
    }

    public function testBuildingARefreshTokenWithAReducedSetOfScopesWithAScopeNotAlreadyCoveredIsRejected(): void
    {
        $refresh_token = OAuth2RefreshToken::createWithASetOfScopes(
            14,
            [OAuth2TestScope::fromItself()]
        );

        $this->expectException(OAuth2ScopeNotCoveredByOneOfTheScopeAssociatedWithTheRefreshTokenException::class);
        OAuth2RefreshToken::createWithAReducedSetOfScopes(
            $refresh_token,
            [$this->buildTestScopeRefreshToken()]
        );
    }

    private function buildTestScopeRefreshToken(): AuthenticationScope
    {
        return new class implements AuthenticationScope {
            /**
             * @var OAuth2ScopeIdentifier
             */
            private $identifier;

            public function __construct()
            {
                $this->identifier = OAuth2ScopeIdentifier::fromIdentifierKey('refresh_token_test');
            }

            public static function fromItself(): AuthenticationScope
            {
                throw new \LogicException('Not supposed to be called during the tests');
            }

            public static function fromIdentifier(AuthenticationScopeIdentifier $identifier): ?AuthenticationScope
            {
                throw new \LogicException('Not supposed to be called during the tests');
            }

            public function getIdentifier(): AuthenticationScopeIdentifier
            {
                return $this->identifier;
            }

            public function getDefinition(): AuthenticationScopeDefinition
            {
                throw new \LogicException('Not supposed to be called during the tests');
            }


            public function covers(AuthenticationScope $scope): bool
            {
                return $this->identifier->toString() === $scope->getIdentifier()->toString();
            }
        };
    }
}
