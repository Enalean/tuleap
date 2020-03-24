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

class OAuth2ScopeSaver
{
    /**
     * @var OAuth2ScopeIdentifierSaverDAO
     */
    private $dao;

    public function __construct(OAuth2ScopeIdentifierSaverDAO $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param AuthenticationScope[] $scopes
     *
     * @psalm-param non-empty-array<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>> $scopes
     */
    public function saveScopes(int $id, array $scopes): void
    {
        $scope_identifier_keys = [];
        foreach ($scopes as $scope) {
            $scope_identifier_keys[] = $scope->getIdentifier()->toString();
        }

        $this->dao->saveScopeKeysByID($id, ...$scope_identifier_keys);
    }
}
