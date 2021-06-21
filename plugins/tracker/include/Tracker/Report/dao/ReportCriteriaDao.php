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
    public function duplicate(int $from_report_id, int $to_report_id, array $field_mapping): void
    {
        foreach ($field_mapping as $mapping) {
            $sql = "INSERT INTO tracker_report_criteria (report_id, field_id, rank, is_advanced)
                SELECT ?, field_id, rank, is_advanced
                FROM tracker_report_criteria
                WHERE report_id = ? AND field_id = ?";
            $this->getDB()->run($sql, $to_report_id, $from_report_id, $mapping['from']);

            $report_criteria_id = (int) $this->getDB()->lastInsertId();
            if ($report_criteria_id === 0) {
                continue;
            }

            $sql = "UPDATE tracker_report_criteria SET field_id = ?
                    WHERE report_id = ? AND field_id = ?";
            $this->getDB()->run($sql, $mapping['to'], $to_report_id, $mapping['from']);

            $sql = "SELECT value, 'list' AS field_type
                    FROM tracker_report_criteria AS original_report
                             INNER JOIN tracker_report_criteria_list_value on criteria_id = original_report.id
                    WHERE original_report.field_id = ? AND original_report.report_id = ?
                    UNION SELECT value, 'alphanum' AS field_type
                    FROM tracker_report_criteria AS original_report
                        INNER JOIN tracker_report_criteria_alphanum_value on criteria_id = original_report.id
                    WHERE original_report.field_id = ? AND original_report.report_id = ?";

            $criterias = $this->getDB()->run(
                $sql,
                $mapping['from'],
                $from_report_id,
                $mapping['from'],
                $from_report_id,
            );
            if (! $criterias) {
                continue;
            }

            $data_to_insert = [];
            foreach ($criterias as $row) {
                if ($row['value'] === null) {
                    continue;
                }

                $value = $this->getValueFromRow($row, $mapping);

                $data_to_insert[$row['field_type']][] = ['criteria_id' => $report_criteria_id, 'value' => $value];
            }

            $this->insertListCriteria($data_to_insert);
            $this->insertAlphaNumCriteria($data_to_insert);
        }
    }


    private function insertListCriteria(array $data_to_insert): void
    {
        if (isset($data_to_insert['list']) && count($data_to_insert['list']) > 0) {
            $data_to_insert_without_duplicates = array_map("unserialize", array_unique(array_map("serialize", $data_to_insert['list'])));
            $this->getDB()->insertMany('tracker_report_criteria_list_value', $data_to_insert_without_duplicates);
        }
    }

    private function insertAlphaNumCriteria(array $data_to_insert): void
    {
        if (isset($data_to_insert['alphanum']) && count($data_to_insert['alphanum']) > 0) {
            $data_to_insert_without_duplicates = array_map("unserialize", array_unique(array_map("serialize", $data_to_insert['alphanum'])));
            $this->getDB()->insertMany('tracker_report_criteria_alphanum_value', $data_to_insert_without_duplicates);
        }
    }

    private function hasCriteriaValue(array $mapping, ?string $value): bool
    {
        return $mapping['from'] || ! $value || ! isset($mapping['values'][$value]);
    }

    private function getValueFromRow(array $row, array $mapping): string
    {
        switch ($row['field_type']) {
            case "list":
                if (! $this->hasCriteriaValue($mapping, $row['value'])) {
                    break;
                }

                if ((int) $row['value'] === \Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID) {
                    return $row['value'];
                } else {
                    return $mapping['values'][$row['value']];
                }
            case "alphanum":
                return $row['value'];
        }

        throw new \LogicException($row['field_type'] . " can not return a value");
    }
}
