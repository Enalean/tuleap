<?php
/**
  * Copyright (c) Enalean, 2012-Present. All rights reserved
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

use ParagonIE\EasyDB\EasyStatement;

/**
 *  Data Access Object for Tracker_Rule
 */
class Tracker_Rule_Date_Dao extends \Tuleap\DB\DataAccessObject
{
    public function searchById($tracker_id, $id): array
    {
        $sql = "SELECT *
                FROM tracker_rule_date
                JOIN tracker_rule
                ON (id = tracker_rule_id)
                WHERE tracker_rule.id = ?
                  AND tracker_rule.tracker_id = ?";
        return $this->getDB()->row($sql, $id, $tracker_id);
    }

    public function searchByTrackerId($tracker_id): array
    {
        $sql = "SELECT *
                FROM tracker_rule
                JOIN tracker_rule_date
                ON (id = tracker_rule_id)
                WHERE tracker_rule.tracker_id = ?";

        return $this->getDB()->run($sql, $tracker_id);
    }

    /**
     *
     * @param int $tracker_id
     * @param int $source_field_id
     * @param int $target_field_id
     * @param string $comparator
     * @return int The ID of the saved tracker_rule
     * @throws Exception
     */
    public function insert($tracker_id, $source_field_id, $target_field_id, $comparator)
    {
        return $this->getDB()->tryFlatTransaction(
            static function (\ParagonIE\EasyDB\EasyDB $db) use (
                $comparator,
                $target_field_id,
                $source_field_id,
                $tracker_id
            ): int {
                $rule_id = (int) $db->insertReturnId(
                    'tracker_rule',
                    [
                        'tracker_id' => $tracker_id,
                        'rule_type'  => Tracker_Rule::RULETYPE_DATE
                    ]
                );

                $db->insert(
                    'tracker_rule_date',
                    [
                        'tracker_rule_id' => $rule_id,
                        'source_field_id' => $source_field_id,
                        'target_field_id' => $target_field_id,
                        'comparator'      => $comparator
                    ]
                );

                return $rule_id;
            }
        );
    }

    public function deleteById($tracker_id, $rule_id)
    {
        return $this->getDB()->tryFlatTransaction(static function (\ParagonIE\EasyDB\EasyDB $db) use ($tracker_id, $rule_id): int {
            $db->run(
                'DELETE tracker_rule_date.*
                FROM tracker_rule
                INNER JOIN tracker_rule_date ON (id = tracker_rule_id)
                WHERE id = ? AND tracker_id = ?',
                $rule_id,
                $tracker_id
            );
            return $db->delete('tracker_rule', ['id' => $rule_id, 'tracker_id' => $tracker_id]);
        });
    }

    public function save($id, $source_field_id, $target_field_id, $comparator)
    {
        return $this->getDB()->update(
            'tracker_rule_date',
            ['source_field_id' => $source_field_id, 'target_field_id' => $target_field_id, 'comparator' => $comparator],
            ['tracker_rule_id' => $id]
        );
    }

    /**
     * @param int[] $tracker_ids
     * @param int[] $field_ids
     * @psalm-return int[]
     */
    public function searchTrackersWithRulesByFieldIDsAndTrackerIDs(array $tracker_ids, array $field_ids): array
    {
        $where_statement_field_tracker = EasyStatement::open()
            ->in('tracker_id IN (?*)', $tracker_ids)
            ->andGroup()
                ->in('source_field_id IN (?*)', $field_ids)
                ->orIn('target_field_id IN (?*)', $field_ids)
            ->endGroup();

        return $this->getDB()->column(
            "SELECT DISTINCT tracker_rule.tracker_id
                       FROM tracker_rule
                       JOIN tracker_rule_date ON (tracker_rule.id = tracker_rule_date.tracker_rule_id)
                       WHERE tracker_rule.rule_type = ? AND $where_statement_field_tracker",
            array_merge([Tracker_Rule::RULETYPE_DATE], $where_statement_field_tracker->values())
        );
    }
}
