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

namespace Tuleap\OAuth2Server\Scope;

use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\User\OAuth2\Scope\InvalidOAuth2ScopeIdentifierException;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;

class ScopeExtractor
{
    /**
     * @var AuthenticationScopeBuilder
     */
    private $scope_builder;

    public function __construct(AuthenticationScopeBuilder $scope_builder)
    {
        $this->scope_builder = $scope_builder;
    }

    /**
     * @return AuthenticationScope[]
     *
     * @psalm-return non-empty-list<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>>
     *
     * @throws InvalidOAuth2ScopeException
     */
    public function extractScopes(string $scope_list_from_query): array
    {
        $scopes_string = $scope_list_from_query;
        $scope_keys    = explode(' ', $scopes_string);
        $scope_keys    = array_unique($scope_keys);
        $scope_list    = [];
        foreach ($scope_keys as $scope_key) {
            try {
                $scope_identifier = OAuth2ScopeIdentifier::fromIdentifierKey($scope_key);
            } catch (InvalidOAuth2ScopeIdentifierException $e) {
                throw new InvalidOAuth2ScopeException();
            }
            $scope            = $this->scope_builder->buildAuthenticationScopeFromScopeIdentifier(
                $scope_identifier
            );
            if ($scope === null) {
                throw new InvalidOAuth2ScopeException();
            }
            $scope_list[] = $scope;
        }
        return $scope_list;
    }
}
