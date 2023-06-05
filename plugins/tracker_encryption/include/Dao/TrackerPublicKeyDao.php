<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\TrackerEncryption\Dao;

use Tuleap\DB\DataAccessObject;

final class TrackerPublicKeyDao extends DataAccessObject
{
    public function retrieveKey(int $tracker_id): string
    {
        $sql = "
            SELECT key_content
            FROM plugin_tracker_encryption_key
            WHERE tracker_id = ?
        ";
        return $this->getDB()->single($sql, [$tracker_id]);
    }

    public function insertKey(int $tracker_id, string $key_content): void
    {
        $sql = "
            REPLACE INTO plugin_tracker_encryption_key(key_content, tracker_id)
            VALUES (?, ?)
        ";
        $this->getDB()->run($sql, $key_content, $tracker_id);
    }

    public function deleteKey(int $tracker_id): void
    {
        $this->getDB()->delete('plugin_tracker_encryption_key', ['tracker_id' => $tracker_id]);
    }
}
