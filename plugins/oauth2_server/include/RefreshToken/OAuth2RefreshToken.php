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

use Tuleap\Authentication\Scope\AuthenticationScope;

/**
 * @psalm-immutable
 */
final class OAuth2RefreshToken
{
    /**
     * @var int
     */
    private $authorization_code_id;
    /**
     * @var AuthenticationScope[]
     *
     * @psalm-var non-empty-array<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>>
     */
    private $scopes;

    /**
     * @param AuthenticationScope[] $scopes
     *
     * @psalm-param non-empty-array<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>> $scopes
     */
    private function __construct(int $authorization_code_id, array $scopes)
    {
        $this->authorization_code_id = $authorization_code_id;
        $this->scopes                = $scopes;
    }

    /**
     * @param AuthenticationScope[] $scopes
     *
     * @psalm-param non-empty-array<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>> $scopes
     */
    public static function createWithASetOfScopes(int $authorization_code_id, array $scopes): self
    {
        return new self($authorization_code_id, $scopes);
    }

    /**
     * @param AuthenticationScope[] $reduced_scopes
     *
     * @psalm-param non-empty-array<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>> $reduced_scopes
     *
     * @throws OAuth2ScopeNotCoveredByOneOfTheScopeAssociatedWithTheRefreshTokenException
     */
    public static function createWithAReducedSetOfScopes(self $refresh_token, array $reduced_scopes): self
    {
        foreach ($reduced_scopes as $scope) {
            if (! self::isScopeCoveredByTheExistingOnes($refresh_token, $scope)) {
                throw new OAuth2ScopeNotCoveredByOneOfTheScopeAssociatedWithTheRefreshTokenException($scope);
            }
        }

        return new self($refresh_token->getAssociatedAuthorizationCodeID(), $reduced_scopes);
    }

    /**
     * @psalm-param AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier> $scope
     */
    private static function isScopeCoveredByTheExistingOnes(self $refresh_token, AuthenticationScope $scope): bool
    {
        foreach ($refresh_token->getScopes() as $existing_scope) {
            if ($existing_scope->covers($scope)) {
                return true;
            }
        }
        return false;
    }

    public function getAssociatedAuthorizationCodeID(): int
    {
        return $this->authorization_code_id;
    }

    /**
     * @return AuthenticationScope[]
     *
     * @psalm-return non-empty-array<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>>
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }
}
