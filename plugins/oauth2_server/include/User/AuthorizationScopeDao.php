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

use Tuleap\Authentication\Scope\AuthenticationScopeIdentifier;
use Tuleap\DB\DataAccessObject;

class AuthorizationScopeDao extends DataAccessObject
{
    public function createMany(int $authorization_id, AuthenticationScopeIdentifier ...$scopes): void
    {
        $inserts = array_map(
            function (AuthenticationScopeIdentifier $scope) use ($authorization_id) {
                return ['authorization_id' => $authorization_id, 'scope_key' => $scope->toString()];
            },
            $scopes
        );
        $this->getDB()->insertMany('plugin_oauth2_authorization_scope', $inserts);
    }

    public function deleteForAuthorization(int $authorization_id): void
    {
        $this->getDB()->delete('plugin_oauth2_authorization_scope', ['authorization_id' => $authorization_id]);
    }

    /**
     * @return string[]
     */
    public function searchScopes(int $authorization_id): array
    {
        $sql = 'SELECT scope_key FROM plugin_oauth2_authorization_scope
                WHERE authorization_id = ?';
        $rows = $this->getDB()->q($sql, $authorization_id);
        $scopes = [];
        foreach ($rows as $row) {
            $scopes[] = $row['scope_key'];
        }
        return $scopes;
    }
}
