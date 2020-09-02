<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

use ParagonIE\EasyDB\EasyStatement;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Workflow_Dao extends \Tuleap\DB\DataAccessObject
{
    public function create(int $tracker_id, int $field_id): int
    {
        return (int) $this->getDB()->insertReturnId(
            'tracker_workflow',
            [
                'tracker_id' => $tracker_id,
                'field_id' => $field_id,
                'is_advanced' => false,
            ]
        );
    }

    public function searchById(int $workflow_id): ?array
    {
        return $this->getDB()->row('SELECT * FROM tracker_workflow WHERE workflow_id = ?', $workflow_id);
    }

    public function searchByTrackerId(int $tracker_id): ?array
    {
        return $this->getDB()->row('SELECT * FROM tracker_workflow WHERE tracker_id = ?', $tracker_id);
    }

    public function updateActivation(int $workflow_id, bool $is_used): int
    {
        $this->getDB()->run('UPDATE tracker_workflow SET is_used=?, is_legacy=0 WHERE workflow_id = ?', $is_used, $workflow_id);
        return $workflow_id;
    }

    public function delete(int $workflow_id): int
    {
        return $this->getDB()->delete('tracker_workflow', ['workflow_id' => $workflow_id]);
    }

    public function duplicate(int $to_tracker_id, int $to_id, bool $is_used, bool $is_advanced): int
    {
        return (int) $this->getDB()->insertReturnId(
            'tracker_workflow',
            [
                'tracker_id'  => $to_tracker_id,
                'field_id'    => $to_id,
                'is_used'     => $is_used,
                'is_advanced' => $is_advanced,
            ]
        );
    }

    public function save(int $tracker_id, int $field_id, bool $is_used, bool $is_advanced): int
    {
        return (int) $this->getDB()->insertReturnId(
            'tracker_workflow',
            [
                'tracker_id'  => $tracker_id,
                'field_id'    => $field_id,
                'is_used'     => $is_used,
                'is_advanced' => $is_advanced,
            ]
        );
    }

    public function removeWorkflowLegacyState(int $workflow_id): void
    {
        $this->getDB()->run(
            'UPDATE tracker_workflow SET is_legacy=0 WHERE workflow_id=?',
            $workflow_id
        );
    }

    public function switchWorkflowToAdvancedMode(int $workflow_id): void
    {
        $this->getDB()->run(
            'UPDATE tracker_workflow SET is_advanced=1 WHERE workflow_id=?',
            $workflow_id
        );
    }

    public function switchWorkflowToSimpleMode(int $workflow_id): void
    {
        $this->getDB()->run(
            'UPDATE tracker_workflow SET is_advanced=0 WHERE workflow_id=?',
            $workflow_id
        );
    }

    /**
     * @param int[] $tracker_ids
     * @param int[] $field_ids
     * @psalm-return array{tracker_id: int, field_id: int}[]
     */
    public function searchWorkflowsByFieldIDsAndTrackerIDs(array $tracker_ids, array $field_ids): array
    {
        $where_statement = EasyStatement::open()->in('tracker_id IN (?*)', $tracker_ids)->andIn('field_id IN (?*)', $field_ids);

        return $this->getDB()->run(
            "SELECT tracker_id, field_id FROM tracker_workflow WHERE $where_statement",
            ...$where_statement->values()
        );
    }
}
