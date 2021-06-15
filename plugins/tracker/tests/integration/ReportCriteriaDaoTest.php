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

use ParagonIE\EasyDB\EasyDB;
use Tracker_FormElement_Field_List_Bind_StaticValue_None;
use Tuleap\DB\DBFactory;

final class ReportCriteriaDaoTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ReportCriteriaDao $dao;
    private EasyDB $db;

    public function setUp(): void
    {
        $this->dao = new ReportCriteriaDao();
        $this->db  = DBFactory::getMainTuleapDBConnection()->getDB();
    }

    public function testItDuplicateListFieldValues(): void
    {
        $from_report_id      = 10;
        $to_report_id        = 20;
        $from_field_id       = 100;
        $from_other_field_id = 101;
        $to_field_id         = 200;
        $to_other_field_id   = 201;
        $rank                = 1;

        $field_mapping[] = [
            'values' => [
                200 => 400,
                300 => 600
            ],
            'from' => $from_field_id,
            'to' => $to_field_id
        ];
        $field_mapping[] = [
            'values' => [
                201 => Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID
            ],
            'from' => $from_other_field_id,
            'to' => $to_other_field_id
        ];

        $criteria_id = $this->create($from_report_id, $from_field_id, $rank, 0);
        $this->insertListValue($criteria_id, 200);
        $this->insertListValue($criteria_id, 300);
        $other_criteria_id = $this->create($from_report_id, $from_other_field_id, $rank, 0);
        $this->insertListValue($other_criteria_id, 201);

        $this->dao->duplicate($from_report_id, $to_report_id, $field_mapping);

        $duplicated_data = $this->getNewReportCriteriaListValues($to_field_id);
        $expected        = [
            ['value' => 400],
            ['value' => 600],
        ];
        $this->assertEquals($expected, $duplicated_data);

        $duplicated_data = $this->getNewReportCriteriaListValues($to_other_field_id);
        $expected        = [
            ['value' => Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID],
        ];
        $this->assertEquals($expected, $duplicated_data);
    }

    private function create(int $report_id, int $field_id, int $rank, int $is_advanced): int
    {
        return $this->db->insertReturnId('tracker_report_criteria', [
            'report_id' => $report_id,
            'field_id' => $field_id,
            'rank' => $rank,
            'is_advanced' => $is_advanced
        ]);
    }

    private function insertListValue(int $criteria_id, int $value): void
    {
        $this->db->insert('tracker_report_criteria_list_value', [
            'criteria_id' => $criteria_id,
            'value' => $value,
        ]);
    }

    private function getNewReportCriteriaListValues(int $field_id): array
    {
        $sql = 'SELECT value FROM tracker_report_criteria AS criteria
                    INNER JOIN tracker_report_criteria_list_value AS list_values ON criteria.id = list_values.criteria_id WHERE field_id = ?';

        return $this->db->run($sql, $field_id);
    }
}
