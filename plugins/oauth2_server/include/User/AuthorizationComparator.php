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
use Tuleap\OAuth2Server\App\OAuth2App;

class AuthorizationComparator
{
    /**
     * @var AuthorizedScopeFactory
     */
    private $scope_factory;

    public function __construct(AuthorizedScopeFactory $scope_factory)
    {
        $this->scope_factory = $scope_factory;
    }

    /**
     * @param AuthenticationScope[] $requested_scopes
     *
     * @psalm-param non-empty-list<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>> $requested_scopes
     */
    public function areRequestedScopesAlreadyGranted(
        \PFUser $user,
        OAuth2App $app,
        array $requested_scopes
    ): bool {
        $saved_scopes = $this->scope_factory->getAuthorizedScopes($user, $app);
        return array_reduce(
            $requested_scopes,
            function (bool $accumulator, AuthenticationScope $request_scope) use ($saved_scopes) {
                return $accumulator && $this->doesSomeSavedScopeCoverRequestedScope($request_scope, ...$saved_scopes);
            },
            true
        );
    }

    private function doesSomeSavedScopeCoverRequestedScope(
        AuthenticationScope $requested_scope,
        AuthenticationScope ...$saved_scopes
    ): bool {
        return array_reduce(
            $saved_scopes,
            function (bool $accumulator, AuthenticationScope $saved_scope) use ($requested_scope) {
                return $accumulator || $saved_scope->covers($requested_scope);
            },
            false
        );
    }
}
