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

namespace Tuleap\OAuth2Server\User;

use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;

class AuthorizedScopeFactory
{
    /**
     * @var AuthorizationDao
     */
    private $authorization_dao;
    /**
     * @var AuthorizationScopeDao
     */
    private $scope_dao;
    /**
     * @var AuthenticationScopeBuilder
     */
    private $scope_builder;

    public function __construct(
        AuthorizationDao $authorization_dao,
        AuthorizationScopeDao $scope_dao,
        AuthenticationScopeBuilder $scope_builder
    ) {
        $this->authorization_dao = $authorization_dao;
        $this->scope_dao         = $scope_dao;
        $this->scope_builder     = $scope_builder;
    }

    /**
     * @return AuthenticationScope[]
     */
    public function getAuthorizedScopes(\PFUser $user, OAuth2App $app): array
    {
        $authorization_id = $this->authorization_dao->searchAuthorization($user, $app->getId());
        if ($authorization_id === null) {
            return [];
        }

        $saved_scope_keys = $this->scope_dao->searchScopes($authorization_id);
        $saved_scopes     = [];
        foreach ($saved_scope_keys as $scope_key) {
            $saved_scopes[] = $this->scope_builder->buildAuthenticationScopeFromScopeIdentifier(
                OAuth2ScopeIdentifier::fromIdentifierKey($scope_key)
            );
        }
        return array_values(array_filter($saved_scopes));
    }
}
