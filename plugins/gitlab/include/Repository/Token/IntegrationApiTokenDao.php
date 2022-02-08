<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

namespace Tuleap\Gitlab\Repository\Token;

use Tuleap\DB\DataAccessObject;

class IntegrationApiTokenDao extends DataAccessObject
{
    public function storeToken(int $integration_id, string $encrypted_token): void
    {
        $this->getDB()->insertOnDuplicateKeyUpdate(
            'plugin_gitlab_repository_integration_token',
            [
                'integration_id'                          => $integration_id,
                'token'                                   => $encrypted_token,
                'is_email_already_send_for_invalid_token' => false,
            ],
            [
                'token',
                'is_email_already_send_for_invalid_token',
            ]
        );
    }

    public function storeTheFactWeAlreadySendEmailForInvalidToken(int $integration_id): void
    {
        $this->getDB()->update(
            'plugin_gitlab_repository_integration_token',
            [
                'is_email_already_send_for_invalid_token' => true,
            ],
            [
                'integration_id' => $integration_id,
            ]
        );
    }

    /**
     * @return array{token: string, is_email_already_send_for_invalid_token: bool}|null
     */
    public function searchIntegrationAPIToken(int $integration_id): ?array
    {
        $sql = 'SELECT *
                FROM plugin_gitlab_repository_integration_token
                WHERE integration_id = ?';

        return $this->getDB()->row($sql, $integration_id);
    }

    public function deleteIntegrationToken(int $integration_id): void
    {
        $this->getDB()->delete(
            'plugin_gitlab_repository_integration_token',
            ['integration_id' => $integration_id]
        );
    }
}
