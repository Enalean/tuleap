<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

namespace Tuleap\Tracker\Report\dao;

final class ReportCriteriaDao extends \Tuleap\DB\DataAccessObject
{
    public function duplicate(int $from_report_id, int $to_report_id, array $field_mapping): int
    {
        $sql = "INSERT INTO tracker_report_criteria (report_id, field_id, rank, is_advanced)
                SELECT ?, field_id, rank, is_advanced
                FROM tracker_report_criteria
                WHERE report_id = ?";
        $this->getDB()->run($sql, $to_report_id, $from_report_id);

        $report_criteria_id = (int) $this->getDB()->lastInsertId();

        $this->migrateCriterias($field_mapping, $to_report_id, $report_criteria_id);

        return $report_criteria_id;
    }

    private function migrateCriterias(array $field_mapping, int $to_report_id, int $report_criteria_id): void
    {
        foreach ($field_mapping as $mapping) {
            $sql = "UPDATE tracker_report_criteria SET field_id = ?
                    WHERE report_id = ? AND field_id = ?";
            $this->getDB()->run($sql, $mapping['to'], $to_report_id, $mapping['from']);

            $sql = "SELECT value, 'list' AS field_type
                        FROM tracker_report_criteria AS original_report
                        INNER JOIN tracker_report_criteria_list_value on criteria_id = original_report.id
                    WHERE original_report.field_id = ?";

            $criterias = $this->getDB()->run($sql, $mapping['from']);
            if (! $criterias) {
                continue;
            }

            $data_to_insert = [];
            foreach ($criterias as $row) {
                if (! $this->hasCriteriaValue($mapping, $row['value'])) {
                    continue;
                }
                $data_to_insert[$row['field_type']][] = ['criteria_id' => $report_criteria_id, 'value' => $mapping['values'][$row['value']]];
            }

            $this->insertListCriteria($data_to_insert);
        }
    }


    private function insertListCriteria(array $data_to_insert): void
    {
        if (isset($data_to_insert['list']) && count($data_to_insert['list']) > 0) {
            $data_to_insert_without_duplicates = array_map("unserialize", array_unique(array_map("serialize", $data_to_insert['list'])));
            $this->getDB()->insertMany('tracker_report_criteria_list_value', $data_to_insert_without_duplicates);
        }
    }

    private function hasCriteriaValue(array $mapping, ?string $value): bool
    {
        return $mapping['from'] || ! $value || ! isset($mapping['values'][$value]);
    }
}
