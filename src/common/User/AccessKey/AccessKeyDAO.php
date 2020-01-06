<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\User\AccessKey;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;

class AccessKeyDAO extends DataAccessObject
{
    public function create(int $user_id, string $hashed_verification_string, int $current_time, string $description, ?int $expiration_date_timestamp): int
    {
        return (int) $this->getDB()->insertReturnId(
            'user_access_key',
            [
                'user_id'         => $user_id,
                'verifier'        => $hashed_verification_string,
                'creation_date'   => $current_time,
                'description'     => $description,
                'expiration_date' => $expiration_date_timestamp
            ]
        );
    }

    public function searchAccessKeyVerificationAndTraceabilityDataByID(int $key_id): ?array
    {
        return $this->getDB()->row('SELECT verifier, user_id, last_usage, last_ip, expiration_date FROM user_access_key WHERE id = ?', $key_id);
    }

    public function searchMetadataByUserIDAtCurrentTime(int $user_id, int $current_timestamp): array
    {
        return $this->getDB()->run(
            'SELECT id, creation_date, expiration_date, description, last_usage, last_ip
             FROM user_access_key
             WHERE user_id = ?
             AND (expiration_date IS NULL OR expiration_date > ?)',
            $user_id,
            $current_timestamp
        );
    }

    public function deleteByUserIDAndKeyIDs(int $user_id, array $key_ids): void
    {
        if (empty($key_ids)) {
            return;
        }

        $this->deleteByCondition(
            EasyStatement::open()->with('user_access_key.user_id = ?', $user_id)->andIn('user_access_key.id IN (?*)', $key_ids)
        );
    }

    public function deleteByExpirationDate(int $timestamp): void
    {
        $this->deleteByCondition(
            EasyStatement::open()->with('expiration_date <= ?', $timestamp)->andWith('expiration_date IS NOT NULL')
        );
    }

    private function deleteByCondition(EasyStatement $condition): void
    {
        $this->getDB()->run(
            "DELETE user_access_key, user_access_key_scope
            FROM user_access_key
            LEFT JOIN user_access_key_scope ON (user_access_key_scope.access_key_id = user_access_key.id)
            WHERE $condition",
            ...$condition->values()
        );
    }

    public function updateAccessKeyUsageByID(int $id, $current_time, $ip_address): void
    {
        $sql = 'UPDATE user_access_key
                JOIN user_access ON (user_access_key.user_id = user_access.user_id)
                SET user_access_key.last_usage = ?, user_access_key.last_ip = ?, user_access.last_access_date = ?
                WHERE user_access_key.id = ?';

        $this->getDB()->run($sql, $current_time, $ip_address, $current_time, $id);
    }

    public function deleteKeysWithNoScopes(): void
    {
        $this->getDB()->run(
            'DELETE user_access_key.*
                    FROM user_access_key
                    LEFT JOIN user_access_key_scope ON user_access_key.id = user_access_key_scope.access_key_id
                    WHERE user_access_key_scope.access_key_id IS NULL'
        );
    }
}
