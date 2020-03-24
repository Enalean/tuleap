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
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;

class OAuth2ScopeRetriever
{
    /**
     * @var OAuth2ScopeIdentifierSearcherDAO
     */
    private $dao;
    /**
     * @var AuthenticationScopeBuilder
     */
    private $oauth2_scope_builder;

    public function __construct(OAuth2ScopeIdentifierSearcherDAO $dao, AuthenticationScopeBuilder $oauth2_scope_builder)
    {
        $this->dao                  = $dao;
        $this->oauth2_scope_builder = $oauth2_scope_builder;
    }

    /**
     * @return AuthenticationScope[]
     * @psalm-return AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>[]
     */
    public function getScopesBySplitToken(SplitToken $token): array
    {
        $scope_key_rows = $this->dao->searchScopeIdentifiersByOAuth2SplitTokenID($token->getID());

        $scopes = [];

        foreach ($scope_key_rows as $scope_key_row) {
            $scope = $this->oauth2_scope_builder->buildAuthenticationScopeFromScopeIdentifier(
                OAuth2ScopeIdentifier::fromIdentifierKey($scope_key_row['scope_key'])
            );
            if ($scope !== null) {
                $scopes[] = $scope;
            }
        }

        return $scopes;
    }
}
