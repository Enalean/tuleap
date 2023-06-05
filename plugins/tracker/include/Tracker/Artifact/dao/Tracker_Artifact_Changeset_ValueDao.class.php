<?php
/**
 * Copyright (c) Enalean SAS, 2017 - Present. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

class Tracker_Artifact_Changeset_ValueDao extends \Tuleap\DB\DataAccessObject
{
    /**
     * @psalm-return array{id: int, field_id: int, has_changed:0|1}[]
     */
    public function searchById($id): array
    {
        return $this->getDB()->run(
            'SELECT id, field_id, has_changed FROM tracker_changeset_value WHERE changeset_id = ?',
            $id
        );
    }

    public function getAllChangedValueFromChangesetId(int $id): array
    {
        return $this->getDB()->run(
            'SELECT id, field_id FROM tracker_changeset_value WHERE changeset_id = ? AND has_changed = 1',
            $id
        );
    }

    /**
     * @psalm-return array{id: int, has_changed:0|1}|null
     */
    public function searchByFieldId($changeset_id, $field_id): ?array
    {
        return $this->getDB()->row(
            'SELECT id, has_changed FROM tracker_changeset_value WHERE changeset_id = ? AND field_id = ?',
            $changeset_id,
            $field_id
        );
    }

    /**
     * @psalm-return array<int, list<array{id: int, changeset_id: int, field_id: int, has_changed:0|1}>>
     */
    public function searchByArtifactId($artifact_id): array
    {
        return $this->getDB()->safeQuery(
            'SELECT changeset_value.changeset_id, changeset_value.id, changeset_value.changeset_id, changeset_value.field_id, changeset_value.has_changed
                FROM tracker_changeset_value AS changeset_value
                JOIN tracker_changeset AS changeset ON (changeset.id = changeset_value.changeset_id)
                WHERE changeset.artifact_id = ?',
            [$artifact_id],
            \PDO::FETCH_GROUP | \PDO::FETCH_ASSOC
        );
    }

    public function save($changeset_id, $field_id, $has_changed): int
    {
        $this->getDB()->run(
            'INSERT INTO tracker_changeset_value(changeset_id, field_id, has_changed) VALUES (?,?,?)',
            $changeset_id,
            $field_id,
            $has_changed ? 1 : 0
        );
        return (int) $this->getDB()->lastInsertId();
    }

    /**
     * @psalm-return list<int>
     */
    public function createFromLastChangesetByTrackerId(int $tracker_id, int $field_id): array
    {
        $this->getDB()->run(
            'INSERT INTO tracker_changeset_value(changeset_id, field_id, has_changed)
                SELECT A.last_changeset_id as changeset_id, ?, 1
                FROM tracker_artifact AS A
                WHERE A.tracker_id = ?',
            $field_id,
            $tracker_id
        );

        return $this->getDB()->column(
            'SELECT CV.id as cv
                FROM tracker_changeset_value AS CV
                INNER JOIN tracker_artifact AS A ON (CV.changeset_id = A.last_changeset_id)
                WHERE A.tracker_id = ? AND CV.field_id = ? AND has_changed = 1',
            [$tracker_id, $field_id]
        );
    }

    public function delete($changeset_id): void
    {
        $this->getDB()->run('DELETE FROM tracker_changeset_value WHERE changeset_id = ?', $changeset_id);
    }
}
