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

class OAuth2AccessTokenDAO extends DataAccessObject
{
    public function create(int $authorization_code_id, string $hashed_verification_string, int $expiration_date_timestamp): int
    {
        return (int) $this->getDB()->insertReturnId(
            'plugin_oauth2_access_token',
            [
                'authorization_code_id' => $authorization_code_id,
                'verifier'              => $hashed_verification_string,
                'expiration_date'       => $expiration_date_timestamp
            ]
        );
    }

    /**
     * @psalm-return null|array{verifier:string,user_id:int,expiration_date:int}
     */
    public function searchAccessToken(int $access_token_id): ?array
    {
        return $this->getDB()->row(
            'SELECT plugin_oauth2_access_token.verifier, plugin_oauth2_authorization_code.user_id, plugin_oauth2_access_token.expiration_date
                       FROM plugin_oauth2_access_token
                       JOIN plugin_oauth2_authorization_code ON plugin_oauth2_access_token.authorization_code_id = plugin_oauth2_authorization_code.id
                       JOIN plugin_oauth2_server_app ON plugin_oauth2_authorization_code.app_id = plugin_oauth2_server_app.id
                       JOIN `groups` ON plugin_oauth2_server_app.project_id = `groups`.group_id
                       WHERE `groups`.status = "A" AND plugin_oauth2_access_token.id = ?',
            $access_token_id
        );
    }

    /**
     * @psalm-return null|array{authorization_code_id:int,verifier:string}
     */
    public function searchAccessTokenByApp(int $access_token_id, int $app_id): ?array
    {
        return $this->getDB()->row(
            'SELECT token.authorization_code_id, token.verifier
                       FROM plugin_oauth2_access_token AS token
                       JOIN plugin_oauth2_authorization_code AS auth_code ON auth_code.id = token.authorization_code_id
                       JOIN plugin_oauth2_server_app AS app ON app.id = auth_code.app_id
                       JOIN `groups` ON app.project_id = `groups`.group_id
                       WHERE `groups`.status = "A" AND token.id = ? AND app.id = ?',
            $access_token_id,
            $app_id
        );
    }

    public function deleteByExpirationDate(int $current_time): void
    {
        $this->getDB()->run(
            'DELETE plugin_oauth2_access_token.*, plugin_oauth2_access_token_scope.*
            FROM plugin_oauth2_access_token
            LEFT JOIN plugin_oauth2_access_token_scope ON plugin_oauth2_access_token.id = plugin_oauth2_access_token_scope.access_token_id
            WHERE ? > plugin_oauth2_access_token.expiration_date',
            $current_time
        );
    }
}
