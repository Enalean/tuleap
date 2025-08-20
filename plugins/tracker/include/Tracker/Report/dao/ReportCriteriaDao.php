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

use Tuleap\Tracker\FormElement\Field\List\OpenListField;

final class ReportCriteriaDao extends \Tuleap\DB\DataAccessObject
{
    /**
     * @param int|string $from_report_id
     * @param int|string $to_report_id
     */
    public function duplicate($from_report_id, $to_report_id, array $field_mapping): void
    {
        $this->duplicateComment($to_report_id, $from_report_id);
        $this->duplicatedFields($field_mapping, $to_report_id, $from_report_id);
    }

    private function insertListCriteria(array $data_to_insert): void
    {
        if (isset($data_to_insert['list']) && count($data_to_insert['list']) > 0) {
            $data_to_insert_without_duplicates = array_map('unserialize', array_unique(array_map('serialize', $data_to_insert['list'])));
            $this->getDB()->insertMany('tracker_report_criteria_list_value', $data_to_insert_without_duplicates);
        }
    }

    private function insertAlphaNumCriteria(array $data_to_insert): void
    {
        if (isset($data_to_insert['alphanum']) && count($data_to_insert['alphanum']) > 0) {
            $data_to_insert_without_duplicates = array_map('unserialize', array_unique(array_map('serialize', $data_to_insert['alphanum'])));
            $this->getDB()->insertMany('tracker_report_criteria_alphanum_value', $data_to_insert_without_duplicates);
        }
    }

    private function insertOpenListCriteria(array $data_to_insert): void
    {
        if (isset($data_to_insert['openlist']) && count($data_to_insert['openlist']) > 0) {
            $data_to_insert_without_duplicates = array_map('unserialize', array_unique(array_map('serialize', $data_to_insert['openlist'])));
            $this->getDB()->insertMany('tracker_report_criteria_openlist_value', $data_to_insert_without_duplicates);
        }
    }

    private function insertFileCriteria(array $data_to_insert): void
    {
        if (isset($data_to_insert['file']) && count($data_to_insert['file']) > 0) {
            $data_to_insert_without_duplicates = array_map('unserialize', array_unique(array_map('serialize', $data_to_insert['file'])));
            $this->getDB()->insertMany('tracker_report_criteria_file_value', $data_to_insert_without_duplicates);
        }
    }

    private function insertPermissionsCriteria(array $data_to_insert): void
    {
        if (isset($data_to_insert['permissions']) && count($data_to_insert['permissions']) > 0) {
            $data_to_insert_without_duplicates = array_map('unserialize', array_unique(array_map('serialize', $data_to_insert['permissions'])));
            $this->getDB()->insertMany('tracker_report_criteria_permissionsonartifact_value', $data_to_insert_without_duplicates);
        }
    }

    private function insertDateCriteria(array $data_to_insert): void
    {
        if (isset($data_to_insert['date']) && count($data_to_insert['date']) > 0) {
            $data_to_insert_without_duplicates = array_map('unserialize', array_unique(array_map('serialize', $data_to_insert['date'])));
            $this->getDB()->insertMany('tracker_report_criteria_date_value', $data_to_insert_without_duplicates);
        }
    }

    private function hasCriteriaValue(array $mapping, ?string $value): bool
    {
        return $mapping['from'] || ! $value || ! isset($mapping['values'][$value]);
    }

    private function getValueFromRow(array $row, array $mapping): ?string
    {
        switch ($row['field_type']) {
            case 'list':
                if (! $this->hasCriteriaValue($mapping, $row['value'])) {
                    break;
                }

                if ((int) $row['value'] === \Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID) {
                    return $row['value'];
                }
                return $mapping['values'][$row['value']] ?? null;
            case 'alphanum':
            case 'file':
            case 'permissions':
                return $row['value'];
            case 'openlist':
                return $this->replaceStoredValuesByMapping($row['value'], $mapping);
        }

        throw new \LogicException($row['field_type'] . ' can not return a value');
    }

    private function replaceStoredValuesByMapping(string $db_values, array $mapping): string
    {
        $bind_values      = explode(',', $db_values);
        $converted_values = [];
        foreach ($bind_values as $value) {
            $value = trim($value);
            if ($value) {
                switch ($value[0]) {
                    case OpenListField::BIND_PREFIX:
                        $bindvalue_id       = (int) substr($value, 1);
                        $converted_values[] = OpenListField::BIND_PREFIX . $mapping['values'][$bindvalue_id];
                        break;
                    default:
                        $converted_values[] = $value;
                        break;
                }
            }
        }

        return implode(',', $converted_values);
    }

    /**
     * @param int|string $to_report_id
     * @param int|string $from_report_id
     */
    private function duplicateComment($to_report_id, $from_report_id): void
    {
        $sql = 'INSERT INTO tracker_report_criteria_comment_value (report_id, comment)
                    SELECT ?, comment
                    FROM tracker_report_criteria_comment_value AS comment
                    WHERE comment.report_id= ?';
        $this->getDB()->run($sql, $to_report_id, $from_report_id);
    }

    /**
     * @param int|string $to_report_id
     * @param int|string $from_report_id
     */
    private function duplicatedFields(array $field_mapping, $to_report_id, $from_report_id): void
    {
        foreach ($field_mapping as $mapping) {
            $sql = 'INSERT INTO tracker_report_criteria (report_id, field_id, `rank`, is_advanced)
                SELECT ?, field_id, `rank`, is_advanced
                FROM tracker_report_criteria
                WHERE report_id = ? AND field_id = ?';
            $this->getDB()->run($sql, $to_report_id, $from_report_id, $mapping['from']);

            $report_criteria_id = (int) $this->getDB()->lastInsertId();
            if ($report_criteria_id === 0) {
                continue;
            }

            $sql = 'UPDATE tracker_report_criteria SET field_id = ?
                    WHERE report_id = ? AND field_id = ?';
            $this->getDB()->run($sql, $mapping['to'], $to_report_id, $mapping['from']);

            $sql = "SELECT value, 'list' AS field_type, null AS op, null AS from_date, null as to_date
                    FROM tracker_report_criteria AS original_report
                             INNER JOIN tracker_report_criteria_list_value on criteria_id = original_report.id
                    WHERE original_report.field_id = ? AND original_report.report_id = ?
                    UNION SELECT value, 'alphanum' AS field_type, null AS op, null AS from_date, null as to_date
                    FROM tracker_report_criteria AS original_report
                        INNER JOIN tracker_report_criteria_alphanum_value on criteria_id = original_report.id
                    WHERE original_report.field_id = ? AND original_report.report_id = ?
                    UNION SELECT value, 'file' AS field_type, null AS op, null AS from_date, null as to_date
                    FROM tracker_report_criteria AS original_report
                        INNER JOIN tracker_report_criteria_file_value on criteria_id = original_report.id
                    WHERE original_report.field_id = ? AND original_report.report_id = ?
                    UNION SELECT value, 'openlist' AS field_type, null AS op, null AS from_date, null as to_date
                    FROM tracker_report_criteria AS original_report
                        INNER JOIN tracker_report_criteria_openlist_value on criteria_id = original_report.id
                    WHERE original_report.field_id = ? AND original_report.report_id = ?
                    UNION SELECT value, 'permissions' AS field_type, null AS op, null AS from_date, null as to_date
                    FROM tracker_report_criteria AS original_report
                        INNER JOIN tracker_report_criteria_permissionsonartifact_value on criteria_id = original_report.id
                    WHERE original_report.field_id = ? AND original_report.report_id = ?
                    UNION SELECT null, 'date' AS field_type, op, from_date, to_date
                    FROM tracker_report_criteria AS original_report
                        INNER JOIN tracker_report_criteria_date_value on criteria_id = original_report.id
                    WHERE original_report.field_id = ? AND original_report.report_id = ?";

            $criterias = $this->getDB()->run(
                $sql,
                $mapping['from'],
                $from_report_id,
                $mapping['from'],
                $from_report_id,
                $mapping['from'],
                $from_report_id,
                $mapping['from'],
                $from_report_id,
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
                if ($row['field_type'] === 'date') {
                    $data_to_insert[$row['field_type']][] = [
                        'criteria_id' => $report_criteria_id,
                        'op' => $row['op'],
                        'from_date' => $row['from_date'],
                        'to_date' => $row['to_date'],
                    ];
                }
                if ($row['field_type'] === 'comment') {
                    $data_to_insert[$row['field_type']][] = [
                        'report_id  ' => $report_criteria_id,
                        'comment' => $row['value'],
                    ];
                } else {
                    if ($row['value'] === null) {
                        continue;
                    }
                    $value = $this->getValueFromRow($row, $mapping);
                    if ($value !== null) {
                        $data_to_insert[$row['field_type']][] = [
                            'criteria_id' => $report_criteria_id,
                            'value' => $value,
                        ];
                    }
                }
            }

            $this->insertListCriteria($data_to_insert);
            $this->insertAlphaNumCriteria($data_to_insert);
            $this->insertFileCriteria($data_to_insert);
            $this->insertOpenListCriteria($data_to_insert);
            $this->insertPermissionsCriteria($data_to_insert);
            $this->insertDateCriteria($data_to_insert);
        }
    }
}
