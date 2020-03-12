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

namespace Tuleap\OAuth2Server\AccessToken;

use Tuleap\DB\DataAccessObject;

class OAuth2AccessTokenAuthorizationGrantAssociationDAO extends DataAccessObject
{
    public function createAssociationBetweenAuthorizationGrantAndAccessToken(
        int $authorization_grant_id,
        int $access_token_id
    ): void {
        $this->getDB()->insert(
            'plugin_oauth2_authorization_code_access_token',
            [
                'authorization_code_id' => $authorization_grant_id,
                'access_token_id'       => $access_token_id
            ]
        );
    }
}
