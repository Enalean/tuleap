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

namespace Tuleap\OAuth2Server\RefreshToken;

use Tuleap\DB\DataAccessObject;

class OAuth2RefreshTokenDAO extends DataAccessObject
{
    public function create(int $authorization_code_id, string $hashed_verification_string, int $expiration_date_timestamp): int
    {
        return (int) $this->getDB()->insertReturnId(
            'plugin_oauth2_refresh_token',
            [
                'authorization_code_id' => $authorization_code_id,
                'verifier'              => $hashed_verification_string,
                'expiration_date'       => $expiration_date_timestamp,
                'has_already_been_used' => false,
            ]
        );
    }

    /**
     * @psalm-return null|array{authorization_code_id:int,verifier:string,expiration_date:int,has_already_been_used:0|1,app_id:int}
     */
    public function searchRefreshTokenByID(int $refresh_token_id): ?array
    {
        return $this->getDB()->row(
            'SELECT token.authorization_code_id, token.verifier, token.expiration_date, token.has_already_been_used, auth_code.app_id
                       FROM plugin_oauth2_refresh_token AS token
                       JOIN plugin_oauth2_authorization_code AS auth_code ON auth_code.id = token.authorization_code_id
                       JOIN plugin_oauth2_server_app AS app ON app.id = auth_code.app_id
                       JOIN `groups` ON app.project_id = `groups`.group_id
                       WHERE `groups`.status = "A" AND token.id = ?',
            $refresh_token_id
        );
    }

    /**
     * @psalm-return null|array{authorization_code_id:int,verifier:string}
     */
    public function searchRefreshTokenByApp(int $refresh_token_id, int $app_id): ?array
    {
        return $this->getDB()->row(
            'SELECT token.authorization_code_id, token.verifier
                       FROM plugin_oauth2_refresh_token AS token
                       JOIN plugin_oauth2_authorization_code AS auth_code ON auth_code.id = token.authorization_code_id
                       JOIN plugin_oauth2_server_app AS app ON app.id = auth_code.app_id
                       JOIN `groups` ON app.project_id = `groups`.group_id
                       WHERE `groups`.status = "A" AND token.id = ? AND app.id = ?',
            $refresh_token_id,
            $app_id
        );
    }

    public function markRefreshTokenAsUsed(int $refresh_token_id): void
    {
        $this->getDB()->run(
            'UPDATE plugin_oauth2_refresh_token SET has_already_been_used=TRUE WHERE id=?',
            $refresh_token_id
        );
    }
}
