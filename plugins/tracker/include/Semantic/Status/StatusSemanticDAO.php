<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic\Status;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;
use Tuleap\Option\Option;

class StatusSemanticDAO extends DataAccessObject implements SearchStatusField, SearchStatusOpenValues
{
    #[\Override]
    public function searchFieldByTrackerId(int $tracker_id): Option
    {
        $sql      = 'SELECT field_id FROM tracker_semantic_status WHERE tracker_id = ?';
        $field_id = $this->getDB()->cell($sql, $tracker_id);
        return $field_id !== false
            ? Option::fromValue($field_id)
            : Option::nothing(\Psl\Type\int());
    }

    #[\Override]
    public function searchOpenValuesByFieldId(int $field_id): array
    {
        $sql = 'SELECT open_value_id FROM tracker_semantic_status WHERE field_id = ?';
        return $this->getDB()->column($sql, [$field_id]);
    }

    /**
     * @param list<int> $open_value_ids
     */
    public function save(int $tracker_id, int $field_id, array $open_value_ids): true
    {
        // Start to delete all previous entries
        $this->delete($tracker_id);

        if ($open_value_ids === []) {
            return true;
        }

        // Now save the new values
        $this->getDB()->insertMany('tracker_semantic_status', array_map(
            static fn(int $open_value_id) => [
                'tracker_id'    => $tracker_id,
                'field_id'      => $field_id,
                'open_value_id' => $open_value_id,
            ],
            $open_value_ids,
        ));

        return true;
    }

    public function delete(int $tracker_id): void
    {
        $this->getDB()->delete('tracker_semantic_status', ['tracker_id' => $tracker_id]);
    }

    /**
     * @param list<int> $trackers_id
     */
    public function getNbOfTrackerWithoutSemanticStatusDefined(array $trackers_id): int
    {
        return count($this->getTrackerIdsWithoutSemanticStatusDefined($trackers_id));
    }

    /**
     * @param list<int> $trackers_id
     * @return list<int>
     */
    public function getTrackerIdsWithoutSemanticStatusDefined(array $trackers_id): array
    {
        if ($trackers_id === []) {
            return [];
        }
        $trackers_id_statement = EasyStatement::open()->in('tracker.id IN (?*)', $trackers_id);

        $sql = <<<SQL
        SELECT tracker.id
        FROM tracker
        LEFT JOIN tracker_semantic_status AS status ON tracker.id = status.tracker_id
        WHERE $trackers_id_statement AND status.tracker_id IS NULL
        SQL;

        return $this->getDB()->column($sql, $trackers_id);
    }
}
