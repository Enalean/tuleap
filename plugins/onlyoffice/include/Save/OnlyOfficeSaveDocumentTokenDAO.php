<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Save;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DataAccessObject;
use Tuleap\DB\UUID;

class OnlyOfficeSaveDocumentTokenDAO extends DataAccessObject
{
    public function create(
        int $user_id,
        int $document_id,
        string $hashed_verification_string,
        int $expiration_date_timestamp,
        UUID $server_id,
    ): int {
        return (int) $this->getDB()->insertReturnId(
            'plugin_onlyoffice_save_document_token',
            [
                'user_id'         => $user_id,
                'document_id'     => $document_id,
                'verifier'        => $hashed_verification_string,
                'expiration_date' => $expiration_date_timestamp,
                'server_id'       => $server_id->getBytes(),
            ]
        );
    }

    /**
     * @return array{verifier: string, user_id: int, document_id: int, server_id: UUID}|null
     */
    public function searchTokenVerificationAndAssociatedData(int $key_id, int $current_timestamp): ?array
    {
        return $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($current_timestamp, $key_id): ?array {
                $this->deleteExpiredTokens($current_timestamp);
                $row = $db->row('SELECT verifier, user_id, document_id, server_id FROM plugin_onlyoffice_save_document_token WHERE id = ?', $key_id);
                if (isset($row['server_id'])) {
                    $row['server_id'] = $this->uuid_factory->buildUUIDFromBytesData($row['server_id']);
                }
                return $row;
            }
        );
    }

    private function deleteExpiredTokens(int $current_timestamp): void
    {
        $this->getDB()->run('DELETE FROM plugin_onlyoffice_save_document_token WHERE expiration_date <= ?', $current_timestamp);
    }

    public function updateTokensExpirationDate(int $document_id, UUID $server_id, int $current_timestamp, int $expiration_date_timestamp): void
    {
        $this->getDB()->tryFlatTransaction(
            function () use ($document_id, $server_id, $current_timestamp, $expiration_date_timestamp): void {
                $this->getDB()->run(
                    'UPDATE plugin_onlyoffice_save_document_token SET expiration_date = ? WHERE document_id = ? AND server_id = ?',
                    $expiration_date_timestamp,
                    $document_id,
                    $server_id->getBytes(),
                );
                $this->deleteExpiredTokens($current_timestamp);
            }
        );
    }
}
