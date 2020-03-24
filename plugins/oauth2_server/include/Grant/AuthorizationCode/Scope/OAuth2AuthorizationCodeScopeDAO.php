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

namespace Tuleap\OAuth2Server\Grant\AuthorizationCode\Scope;

use Tuleap\DB\DataAccessObject;
use Tuleap\OAuth2Server\Scope\OAuth2ScopeIdentifierSaverDAO;
use Tuleap\OAuth2Server\Scope\OAuth2ScopeIdentifierSearcherDAO;

class OAuth2AuthorizationCodeScopeDAO extends DataAccessObject implements OAuth2ScopeIdentifierSaverDAO, OAuth2ScopeIdentifierSearcherDAO
{
    public function saveScopeKeysByID(int $auth_code_id, string ...$scope_keys): void
    {
        $data_to_insert = [];

        foreach ($scope_keys as $scope_key) {
            $data_to_insert[] = ['auth_code_id' => $auth_code_id, 'scope_key' => $scope_key];
        }

        $this->getDB()->insertMany('plugin_oauth2_authorization_code_scope', $data_to_insert);
    }

    public function searchScopeIdentifiersByOAuth2SplitTokenID(int $auth_code_id): array
    {
        return $this->getDB()->run(
            'SELECT scope_key FROM plugin_oauth2_authorization_code_scope WHERE auth_code_id = ?',
            $auth_code_id
        );
    }
}
