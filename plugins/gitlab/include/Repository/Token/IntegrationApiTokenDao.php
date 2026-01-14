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

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Symmetric\EncryptionAdditionalData;
use Tuleap\DB\DataAccessObject;

class IntegrationApiTokenDao extends DataAccessObject
{
    public function storeToken(int $integration_id, ConcealedString $token): void
    {
        $this->getDB()->insertOnDuplicateKeyUpdate(
            'plugin_gitlab_repository_integration_token',
            [
                'integration_id'                          => $integration_id,
                'token'                                   => $this->encryptDataToStoreInATableRow($token, $this->getTokenEncryptionAdditionalData($integration_id)),
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
     * @return array{token: ConcealedString, is_email_already_send_for_invalid_token: bool}|null
     */
    public function searchIntegrationAPIToken(int $integration_id): ?array
    {
        $sql = 'SELECT token, is_email_already_send_for_invalid_token
                FROM plugin_gitlab_repository_integration_token
                WHERE integration_id = ?';

        $row = $this->getDB()->row($sql, $integration_id);
        if ($row === null) {
            return null;
        }
        $row['token'] = $this->decryptDataStoredInATableRow($row['token'], $this->getTokenEncryptionAdditionalData($integration_id));

        return $row;
    }

    private function getTokenEncryptionAdditionalData(int $integration_id): EncryptionAdditionalData
    {
        return new EncryptionAdditionalData('plugin_gitlab_repository_integration_token', 'token', (string) $integration_id);
    }

    public function deleteIntegrationToken(int $integration_id): void
    {
        $this->getDB()->delete(
            'plugin_gitlab_repository_integration_token',
            ['integration_id' => $integration_id]
        );
    }
}
