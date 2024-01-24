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
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class ReportCriteriaDaoTest extends TestIntegrationTestCase
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
                300 => 600,
            ],
            'from' => $from_field_id,
            'to' => $to_field_id,
        ];
        $field_mapping[] = [
            'values' => [
                201 => Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID,
            ],
            'from' => $from_other_field_id,
            'to' => $to_other_field_id,
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

    public function testItDuplicateAlphaNumValues(): void
    {
        $from_report_id      = 10;
        $to_report_id        = 20;
        $from_field_id       = 300;
        $from_other_field_id = 301;
        $to_field_id         = 400;
        $to_other_field_id   = 401;
        $rank                = 1;

        $field_mapping[] = [
            'values' => [],
            'from' => $from_field_id,
            'to' => $to_field_id,
        ];
        $field_mapping[] = [
            'values' => [],
            'from' => $from_other_field_id,
            'to' => $to_other_field_id,
        ];

        $criteria_id = $this->create($from_report_id, $from_field_id, $rank, 0);
        $this->insertAlphanumValue($criteria_id, "Stuff");
        $other_criteria_id = $this->create($from_report_id, $from_other_field_id, $rank, 0);
        $this->insertAlphanumValue($other_criteria_id, "Other stuff");

        $this->dao->duplicate($from_report_id, $to_report_id, $field_mapping);

        $duplicated_data = $this->getNewReportCriteriaAlphanumValues($to_field_id);
        $expected        = [
            ['value' => "Stuff"],
        ];
        $this->assertEquals($expected, $duplicated_data);

        $duplicated_data = $this->getNewReportCriteriaAlphanumValues($to_other_field_id);
        $expected        = [
            ['value' => "Other stuff"],
        ];
        $this->assertEquals($expected, $duplicated_data);
    }

    public function testItDuplicateFileValues(): void
    {
        $from_report_id = 10;
        $to_report_id   = 20;
        $from_field_id  = 111;
        $to_field_id    = 222;
        $rank           = 1;

        $field_mapping[] = [
            'values' => [],
            'from' => $from_field_id,
            'to' => $to_field_id,
        ];

        $criteria_id = $this->create($from_report_id, $from_field_id, $rank, 0);
        $this->insertFileValue($criteria_id, "file stuff");

        $this->dao->duplicate($from_report_id, $to_report_id, $field_mapping);

        $duplicated_data = $this->getNewReportCriteriaFileValues($to_field_id);
        $expected        = [
            ['value' => "file stuff"],
        ];
        $this->assertEquals($expected, $duplicated_data);
    }

    public function testItDuplicateOpenListValues(): void
    {
        $from_report_id = 10;
        $to_report_id   = 20;
        $from_field_id  = 333;
        $to_field_id    = 444;
        $rank           = 1;

        $field_mapping[] = [
            'values' => [
                109 => 901,
            ],
            'from' => $from_field_id,
            'to' => $to_field_id,
        ];

        $criteria_id = $this->create($from_report_id, $from_field_id, $rank, 0);
        $this->insertOpenListValue($criteria_id, "!random value,b109");

        $this->dao->duplicate($from_report_id, $to_report_id, $field_mapping);

        $duplicated_data = $this->getNewReportCriteriaOpenListValues($to_field_id);
        $expected        = [
            ['value' => "!random value,b901"],
        ];
        $this->assertEquals($expected, $duplicated_data);
    }

    public function testItDuplicatePermissionsValues(): void
    {
        $from_report_id = 10;
        $to_report_id   = 20;
        $from_field_id  = 555;
        $to_field_id    = 666;
        $rank           = 1;

        $field_mapping[] = [
            'values' => [],
            'from' => $from_field_id,
            'to' => $to_field_id,
        ];

        $criteria_id = $this->create($from_report_id, $from_field_id, $rank, 0);
        $this->insertPermissionValue($criteria_id, 3);

        $this->dao->duplicate($from_report_id, $to_report_id, $field_mapping);

        $duplicated_data = $this->getNewReportCriteriaPermissionsValues($to_field_id);
        $expected        = [
            ['value' => 3],
        ];
        $this->assertEquals($expected, $duplicated_data);
    }

    public function testItDuplicatesDateValues(): void
    {
        $from_report_id = 10;
        $to_report_id   = 20;
        $from_field_id  = 777;
        $to_field_id    = 888;
        $rank           = 1;

        $field_mapping[] = [
            'values' => [],
            'from' => $from_field_id,
            'to' => $to_field_id,
        ];

        $criteria_id = $this->create($from_report_id, $from_field_id, $rank, 0);
        $this->insertDateValue($criteria_id, 0, 1624202493, ">");

        $this->dao->duplicate($from_report_id, $to_report_id, $field_mapping);

        $duplicated_data = $this->getNewReportCriteriaDateValues($to_field_id);
        $expected        = [
            ['from_date' => 0, 'to_date' => 1624202493, 'op' => '>'],
        ];
        $this->assertEquals($expected, $duplicated_data);
    }

    public function testItDuplicatesCommentValues(): void
    {
        $from_report_id = 10;
        $to_report_id   = 20;
        $from_field_id  = 900;
        $to_field_id    = 999;
        $rank           = 1;

        $field_mapping[] = [
            'values' => [],
            'from' => $from_field_id,
            'to' => $to_field_id,
        ];

        $criteria_id = $this->create($from_report_id, $from_field_id, $rank, 0);
        $this->insertCommentValue($criteria_id, "My custom comment");

        $this->dao->duplicate($from_report_id, $to_report_id, $field_mapping);

        $duplicated_data = $this->getNewReportCommentValues($criteria_id);
        $expected        = [
            ['comment' => "My custom comment"],
        ];
        $this->assertEquals($expected, $duplicated_data);
    }

    private function create(int $report_id, int $field_id, int $rank, int $is_advanced): int
    {
        return $this->db->insertReturnId('tracker_report_criteria', [
            'report_id' => $report_id,
            'field_id' => $field_id,
            'rank' => $rank,
            'is_advanced' => $is_advanced,
        ]);
    }

    private function insertListValue(int $criteria_id, int $value): void
    {
        $this->db->insert('tracker_report_criteria_list_value', [
            'criteria_id' => $criteria_id,
            'value' => $value,
        ]);
    }

    private function insertAlphaNumValue(int $criteria_id, string $value): void
    {
        $this->db->insert('tracker_report_criteria_alphanum_value', [
            'criteria_id' => $criteria_id,
            'value' => $value,
        ]);
    }

    private function insertFileValue(int $criteria_id, string $value): void
    {
        $this->db->insert('tracker_report_criteria_file_value', [
            'criteria_id' => $criteria_id,
            'value' => $value,
        ]);
    }

    private function insertOpenListValue(int $criteria_id, string $value): void
    {
        $this->db->insert('tracker_report_criteria_openlist_value', [
            'criteria_id' => $criteria_id,
            'value' => $value,
        ]);
    }

    private function insertPermissionValue(int $criteria_id, string $value): void
    {
        $this->db->insert('tracker_report_criteria_permissionsonartifact_value', [
            'criteria_id' => $criteria_id,
            'value' => $value,
        ]);
    }

    private function insertDateValue(int $criteria_id, int $from_date, int $to_date, string $op): void
    {
        $this->db->insert(
            'tracker_report_criteria_date_value',
            ['criteria_id' => $criteria_id, 'op' => $op, 'from_date' => $from_date, 'to_date' => $to_date]
        );
    }

    private function insertCommentValue(int $criteria_id, string $comment): void
    {
        $this->db->insert(
            'tracker_report_criteria_comment_value',
            ['report_id' => $criteria_id, 'comment' => $comment]
        );
    }

    private function getNewReportCriteriaListValues(int $field_id): array
    {
        $sql = 'SELECT value FROM tracker_report_criteria AS criteria
                    INNER JOIN tracker_report_criteria_list_value AS list_values ON criteria.id = list_values.criteria_id WHERE field_id = ?';

        return $this->db->run($sql, $field_id);
    }

    private function getNewReportCriteriaAlphanumValues(int $field_id): array
    {
        $sql = 'SELECT value FROM tracker_report_criteria AS criteria
                    INNER JOIN tracker_report_criteria_alphanum_value AS alphanum_values ON criteria.id = alphanum_values.criteria_id WHERE field_id = ?';

        return $this->db->run($sql, $field_id);
    }

    private function getNewReportCriteriaFileValues(int $field_id): array
    {
        $sql = 'SELECT value FROM tracker_report_criteria AS criteria
                    INNER JOIN tracker_report_criteria_file_value AS file_values ON criteria.id = file_values.criteria_id WHERE field_id = ?';

        return $this->db->run($sql, $field_id);
    }

    private function getNewReportCriteriaOpenListValues(int $field_id): array
    {
        $sql = 'SELECT value FROM tracker_report_criteria AS criteria
                    INNER JOIN tracker_report_criteria_openlist_value AS open_values ON criteria.id = open_values.criteria_id WHERE field_id = ?';

        return $this->db->run($sql, $field_id);
    }

    private function getNewReportCriteriaPermissionsValues(int $field_id): array
    {
        $sql = 'SELECT value FROM tracker_report_criteria AS criteria
                    INNER JOIN tracker_report_criteria_permissionsonartifact_value AS permissions_values ON criteria.id = permissions_values.criteria_id WHERE field_id = ?';

        return $this->db->run($sql, $field_id);
    }

    private function getNewReportCriteriaDateValues(int $field_id): array
    {
        $sql = 'SELECT from_date, to_date, op FROM tracker_report_criteria AS criteria
                    INNER JOIN tracker_report_criteria_date_value AS date_values ON criteria.id = date_values.criteria_id WHERE field_id = ?';

        return $this->db->run($sql, $field_id);
    }

    private function getNewReportCommentValues(int $report_id): array
    {
        $sql = 'SELECT comment FROM tracker_report_criteria_comment_value WHERE report_id = ?';

        return $this->db->run($sql, $report_id);
    }
}
