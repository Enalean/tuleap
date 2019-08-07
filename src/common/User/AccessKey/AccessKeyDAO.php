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

namespace Tuleap\User\AccessKey;

use DateTimeImmutable;
use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;

class AccessKeyDAO extends DataAccessObject
{
    /**
     * @return int
     */
    public function create($user_id, $hashed_verification_string, $current_time, $description, $expiration_date_timestamp)
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

    /**
     * @return null|array
     */
    public function searchAccessKeyVerificationAndTraceabilityDataByID($key_id)
    {
        return $this->getDB()->row('SELECT verifier, user_id, last_usage, last_ip, expiration_date FROM user_access_key WHERE id = ?', $key_id);
    }

    public function searchMetadataByUserIDAtCurrentTime(int $user_id, int $current_timestamp)
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

    public function deleteByUserIDAndKeyIDs($user_id, array $key_ids)
    {
        if (empty($key_ids)) {
            return;
        }

        $this->getDB()->delete(
            'user_access_key',
            EasyStatement::open()->with('user_id = ?', $user_id)->andIn('id IN (?*)', $key_ids)
        );
    }

    public function deleteByExpirationDate(int $timestamp): void
    {
        $this->getDB()->delete(
            'user_access_key',
            EasyStatement::open()->with('expiration_date <= ?', $timestamp)->andWith('expiration_date IS NOT NULL')
        );
    }

    public function updateAccessKeyUsageByID($id, $current_time, $ip_address)
    {
        $sql = 'UPDATE user_access_key
                JOIN user_access ON (user_access_key.user_id = user_access.user_id)
                SET user_access_key.last_usage = ?, user_access_key.last_ip = ?, user_access.last_access_date = ?
                WHERE user_access_key.id = ?';

        $this->getDB()->run($sql, $current_time, $ip_address, $current_time, $id);
    }
}
