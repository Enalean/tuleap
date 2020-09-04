<?php
/**
  * Copyright (c) Enalean, 2012-present. All rights reserved
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
class Tracker_Rule_List_Dao extends \Tuleap\DB\DataAccessObject
{
    public function searchById($id): array
    {
        $sql = 'SELECT *
                FROM tracker_rule_list
                    JOIN tracker_rule
                    ON (tracker_rule.id = tracker_rule_list.tracker_rule_id)
                WHERE tracker_rule.id = ?';

        return $this->getDB()->row($sql, $id);
    }

    public function searchByTrackerId($tracker_id): array
    {
        $sql = "SELECT *
                FROM tracker_rule
                    JOIN tracker_rule_list
                    ON (tracker_rule.id = tracker_rule_list.tracker_rule_id)
                WHERE tracker_rule.tracker_id = ?";

        return $this->getDB()->run($sql, $tracker_id);
    }

    public function insert(Tracker_Rule_List $rule): int
    {
        $rule_id = $rule->getTrackerId();

        $source_field_id = $rule->getSourceFieldId();
        if ($rule->getSourceValue() instanceof Tracker_FormElement_Field_List_Value) {
            $source_value_id = $rule->getSourceValue()->getId();
        } else {
            $source_value_id = $rule->getSourceValue();
        }

        $target_field_id = $rule->getTargetFieldId();
        if ($rule->getTargetValue() instanceof Tracker_FormElement_Field_List_Value) {
            $target_value_id = $rule->getTargetValue()->getId();
        } else {
            $target_value_id = $rule->getTargetValue();
        }

        return $this->getDB()->tryFlatTransaction(
            static function (\ParagonIE\EasyDB\EasyDB $db) use (
                $rule_id,
                $source_field_id,
                $source_value_id,
                $target_field_id,
                $target_value_id
            ): int {
                $tracker_rule_id = (int) $db->insertReturnId(
                    'tracker_rule',
                    [
                        'tracker_id' => $rule_id,
                        'rule_type'  => Tracker_Rule::RULETYPE_VALUE
                    ]
                );

                $sql = "INSERT INTO tracker_rule_list (
                        tracker_rule_id,
                        source_field_id,
                        source_value_id,
                        target_field_id,
                        target_value_id
                        ) VALUES (?,?,?,?,?)";
                $db->run($sql, $tracker_rule_id, $source_field_id, $source_value_id, $target_field_id, $target_value_id);

                return $tracker_rule_id;
            }
        );
    }

    public function create($tracker_id, $source_field_id, $source_value_id, $target_field_id, $target_value_id): void
    {
        $this->getDB()->tryFlatTransaction(
            function (\ParagonIE\EasyDB\EasyDB $db) use (
                $tracker_id,
                $source_field_id,
                $source_value_id,
                $target_field_id,
                $target_value_id
            ): void {
                $tracker_rule_id = (int) $db->insertReturnId(
                    'tracker_rule',
                    [
                        'tracker_id' => $tracker_id,
                        'rule_type' => Tracker_Rule::RULETYPE_VALUE
                    ]
                );

                $sql = "INSERT INTO tracker_rule_list (
                        tracker_rule_id,
                        source_field_id,
                        source_value_id,
                        target_field_id,
                        target_value_id)
                    VALUES (?,?,?,?,?)";
                $this->getDB()->run(
                    $sql,
                    $tracker_rule_id,
                    $source_field_id,
                    $source_value_id,
                    $target_field_id,
                    $target_value_id
                );
            }
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
                       JOIN tracker_rule_list ON (tracker_rule.id = tracker_rule_list.tracker_rule_id)
                       WHERE tracker_rule.rule_type = ? AND $where_statement_field_tracker",
            array_merge([Tracker_Rule::RULETYPE_VALUE], $where_statement_field_tracker->values())
        );
    }
}
