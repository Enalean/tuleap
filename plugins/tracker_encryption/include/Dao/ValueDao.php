<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use Tracker_Artifact_Changeset_ValueDao;
use Tracker_FormElement_Field_Encrypted;
use Tuleap\DB\DataAccessObject;
use Tuleap\Tracker\FormElement\Field\DeleteFieldValue;
use Tuleap\Tracker\FormElement\Field\SearchFieldValue;

final class ValueDao extends DataAccessObject implements SearchFieldValue, DeleteFieldValue
{
    public function __construct(private readonly Tracker_Artifact_Changeset_ValueDao $changeset_value_dao)
    {
        parent::__construct();
    }

    public function create(int $changeset_value_id, string $encrypted_value): bool
    {
        return $this->getDB()->run(
            "REPLACE INTO tracker_changeset_value_encrypted(changeset_value_id, value) VALUES (?, ?)",
            $changeset_value_id,
            $encrypted_value
        ) !== null;
    }

    public function createNoneValue(int $tracker_id, int $field_id): void
    {
        $changeset_value_ids = $this->changeset_value_dao->createFromLastChangesetByTrackerId($tracker_id, $field_id);
        if (empty($changeset_value_ids)) {
            return;
        }
        $this->getDB()->insert(
            'tracker_changeset_value_encrypted',
            [
                'changeset_value_id' => $changeset_value_ids,
                'value' => '',
            ],
        );
    }

    public function resetEncryptedFieldValues(int $tracker_id): void
    {
        $sql = "
            UPDATE tracker_changeset_value_encrypted
            SET value = ''
            WHERE changeset_value_id IN (
                SELECT tracker_changeset_value.id
                FROM tuleap.tracker_changeset_value JOIN tracker_field
                ON (tracker_changeset_value.field_id=tracker_field.id)
                WHERE formElement_type = ? AND tracker_id = ?
            )";
        $this->getDB()->run($sql, Tracker_FormElement_Field_Encrypted::TYPE, $tracker_id);
    }

    public function keep(int $previous_changeset_value_id, int $changeset_value_id): bool
    {
        $sql = "
            INSERT INTO tracker_changeset_value_encrypted(changeset_value_id, value)
            SELECT ?, value
            FROM tracker_changeset_value_encrypted
            WHERE changeset_value_id = ?
        ";
        return $this->getDB()->run($sql, $changeset_value_id, $previous_changeset_value_id) !== null;
    }

    /**
     * @psalm-return array{"changeset_value_id": int, "value": string} | null
     */
    public function searchById(int $changeset_value_id): ?array
    {
        $sql = "
            SELECT *
            FROM tracker_changeset_value_encrypted
            WHERE changeset_value_id = ?
        ";
        return $this->getDB()->row($sql, $changeset_value_id) ?: null;
    }

    public function delete(int $changeset_value_id): void
    {
        $this->getDB()->delete('tracker_changeset_value_encrypted', ['changeset_value_id' => $changeset_value_id]);
    }
}
