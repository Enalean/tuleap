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

namespace Tuleap\OAuth2ServerCore\RefreshToken\Scope;

use Tuleap\DB\DataAccessObject;
use Tuleap\OAuth2ServerCore\Scope\OAuth2ScopeIdentifierSaverDAO;
use Tuleap\OAuth2ServerCore\Scope\OAuth2ScopeIdentifierSearcherDAO;

final class OAuth2RefreshTokenScopeDAO extends DataAccessObject implements OAuth2ScopeIdentifierSearcherDAO, OAuth2ScopeIdentifierSaverDAO
{
    #[\Override]
    public function searchScopeIdentifiersByOAuth2SplitTokenID(int $id): array
    {
        return $this->getDB()->run(
            'SELECT scope_key FROM oauth2_refresh_token_scope WHERE refresh_token_id = ?',
            $id
        );
    }

    #[\Override]
    public function saveScopeKeysByID(int $id, string ...$scope_keys): void
    {
        $data_to_insert = [];

        foreach ($scope_keys as $scope_key) {
            $data_to_insert[] = ['refresh_token_id' => $id, 'scope_key' => $scope_key];
        }

        $this->getDB()->insertMany('oauth2_refresh_token_scope', $data_to_insert);
    }
}
