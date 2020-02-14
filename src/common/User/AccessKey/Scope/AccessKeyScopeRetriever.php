<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\User\AccessKey\Scope;

use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;

class AccessKeyScopeRetriever
{
    /**
     * @var AccessKeyScopeDAO
     */
    private $dao;
    /**
     * @var AuthenticationScopeBuilder
     */
    private $key_scope_builder;

    public function __construct(AccessKeyScopeDAO $dao, AuthenticationScopeBuilder $key_scope_builder)
    {
        $this->dao               = $dao;
        $this->key_scope_builder = $key_scope_builder;
    }

    /**
     * @return AuthenticationScope[]
     */
    public function getScopesByAccessKeyID(int $access_key_id): array
    {
        $scope_key_rows = $this->dao->searchScopeKeysByAccessKeyID($access_key_id);

        $key_scopes = [];

        foreach ($scope_key_rows as $scope_key_row) {
            $key_scope = $this->key_scope_builder->buildAuthenticationScopeFromScopeIdentifier(
                AccessKeyScopeIdentifier::fromIdentifierKey($scope_key_row['scope_key'])
            );
            if ($key_scope !== null) {
                $key_scopes[] = $key_scope;
            }
        }

        return $key_scopes;
    }
}
