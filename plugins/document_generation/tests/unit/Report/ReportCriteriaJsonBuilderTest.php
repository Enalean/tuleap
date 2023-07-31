<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\DocumentGeneration\Report;

use ProjectUGroup;
use Tracker;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_List_Bind_Ugroups;
use Tracker_FormElement_Field_List_Bind_UgroupsValue;
use Tracker_FormElement_Field_List_Bind_Users;
use Tracker_FormElement_Field_List_Bind_UsersValue;
use Tracker_FormElement_Field_List_UnsavedValue;
use Tracker_FormElement_Field_OpenList;
use Tracker_FormElement_Field_String;
use Tracker_FormElement_InvalidFieldValueException;
use Tracker_Report;
use Tracker_Report_AdditionalCriterion;
use Tracker_Report_Criteria;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class ReportCriteriaJsonBuilderTest extends TestCase
{
    use GlobalLanguageMock;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\UGroupManager
     */
    private mixed $ugroup_manager;
    private ReportCriteriaJsonBuilder $builder;

    protected function setUp(): void
    {
        $this->ugroup_manager = $this->createStub(\UGroupManager::class);
        $this->builder        = new ReportCriteriaJsonBuilder($this->ugroup_manager);
    }

    public function testItBuildsJsonForReportWithExpertQuery(): void
    {
        $report      = $this->buildReportWithExpertQuery();
        $report_json = $this->builder->buildReportCriteriaJson($report);

        self::assertInstanceOf(ExpertReportCriteriaJson::class, $report_json);
        self::assertNotNull($report_json->is_in_expert_mode);
        self::assertTrue($report_json->is_in_expert_mode);
        self::assertNotNull($report_json->query);
        self::assertSame('field01="value02" AND field02="value02"', $report_json->query);
    }

    public function testItBuildsJsonForReportWithClassicQuery(): void
    {
        $GLOBALS['Language']
            ->method('getText')
            ->with('global', 'none')
            ->willReturn('None');

        $report      = $this->buildReportWithCriteria();
        $report_json = $this->builder->buildReportCriteriaJson($report);

        self::assertInstanceOf(ClassicReportCriteriaJson::class, $report_json);
        self::assertNotNull($report_json->is_in_expert_mode);
        self::assertFalse($report_json->is_in_expert_mode);
        self::assertNotNull($report_json->criteria);
        self::assertCount(8, $report_json->criteria);
        self::assertInstanceOf(ClassicCriterionValueJson::class, $report_json->criteria[0]);
        self::assertSame("Summary", $report_json->criteria[0]->criterion_name);
        self::assertSame("Test", $report_json->criteria[0]->criterion_value);
        self::assertInstanceOf(ClassicCriterionValueJson::class, $report_json->criteria[1]);
        self::assertSame("Users list", $report_json->criteria[1]->criterion_name);
        self::assertSame("User Name 01, User Name 02", $report_json->criteria[1]->criterion_value);
        self::assertInstanceOf(ClassicCriterionValueJson::class, $report_json->criteria[2]);
        self::assertSame("User groups list", $report_json->criteria[2]->criterion_name);
        self::assertSame("Ugroup01, Ugroup02", $report_json->criteria[2]->criterion_value);
        self::assertInstanceOf(ClassicCriterionValueJson::class, $report_json->criteria[3]);
        self::assertSame("Static values list", $report_json->criteria[3]->criterion_name);
        self::assertSame("None, Static value 01, Static value 02", $report_json->criteria[3]->criterion_value);
        self::assertInstanceOf(ClassicCriterionValueJson::class, $report_json->criteria[4]);
        self::assertSame("Open list static values", $report_json->criteria[4]->criterion_name);
        self::assertSame("a, b, abc", $report_json->criteria[4]->criterion_value);
        self::assertInstanceOf(DateCriterionValueJson::class, $report_json->criteria[5]);
        self::assertSame("Submitted On", $report_json->criteria[5]->criterion_name);
        self::assertSame("2021-08-01T00:00:00+02:00", $report_json->criteria[5]->criterion_from_value);
        self::assertSame("2021-08-28T00:00:00+02:00", $report_json->criteria[5]->criterion_to_value);
        self::assertEquals(new ClassicCriterionValueJson('Perms', 'ugroup_name'), $report_json->criteria[6]);
        self::assertEquals(new ClassicCriterionValueJson('Additional01', 'ValueAdd01'), $report_json->criteria[7]);
    }

    private function buildReportWithExpertQuery(): Tracker_Report
    {
        return new Tracker_Report(
            1,
            "Default",
            "Default Report",
            1,
            null,
            101,
            true,
            1,
            true,
            true,
            'field01="value02" AND field02="value02"',
            101,
            (new \DateTimeImmutable())->getTimestamp()
        );
    }

    private function buildReportWithCriteria(): Tracker_Report
    {
        $report                    = $this->createMock(Tracker_Report::class);
        $report->is_in_expert_mode = false;

        $criteria = [
            $this->buildStringCriterion($report),
            $this->buildUserListCriterion($report),
            $this->buildUserGroupListCriterion($report),
            $this->buildStaticListCriterion($report),
            $this->buildStaticOpenListCriterion($report),
            $this->buildAdvancedDateCriterion($report),
            $this->buildNotSetListCriterion($report),
            $this->buildNotSetOpenListCriterion($report),
            $this->buildNotSetDateCriterion($report),
            $this->buildListCriterionWithInvalidValue($report),
            $this->buildPermissionsOnArtifactCriterion($report),
        ];

        $report->method('getCriteria')->willReturn($criteria);

        $additional_criteria = [
            new Tracker_Report_AdditionalCriterion(
                "Additional01",
                "ValueAdd01"
            ),
            new Tracker_Report_AdditionalCriterion(
                "Additional02",
                null
            ),
        ];
        $report->method('getAdditionalCriteria')->willReturn($additional_criteria);

        return $report;
    }

    private function buildStringCriterion(Tracker_Report $report): Tracker_Report_Criteria
    {
        $string_field = $this->createMock(Tracker_FormElement_Field_String::class);

        $criterion_string = new Tracker_Report_Criteria(
            1,
            $report,
            $string_field,
            1,
            0
        );

        $string_field
            ->method('getLabel')
            ->willReturn("Summary");

        $string_field
            ->method('getCriteriaValue')
            ->with($criterion_string)
            ->willReturn("Test");

        return $criterion_string;
    }

    private function buildAdvancedDateCriterion(Tracker_Report $report): Tracker_Report_Criteria
    {
        $date_field = $this->createMock(Tracker_FormElement_Field_Date::class);

        $criterion_date = new Tracker_Report_Criteria(
            2,
            $report,
            $date_field,
            2,
            1
        );

        $date_field
            ->method('getLabel')
            ->willReturn("Submitted On");

        $date_field
            ->method('getCriteriaValue')
            ->with($criterion_date)
            ->willReturn([
                "op" => "=",
                'from_date' => '1627768800',
                'to_date' => '1630101600',
            ]);

        return $criterion_date;
    }

    private function buildUserListCriterion(Tracker_Report $report): Tracker_Report_Criteria
    {
        $list_user_field = $this->createMock(Tracker_FormElement_Field_List::class);

        $criterion_user_list = new Tracker_Report_Criteria(
            3,
            $report,
            $list_user_field,
            3,
            0
        );

        $user_bind = $this->createMock(Tracker_FormElement_Field_List_Bind_Users::class);
        $user_bind
            ->method('getValue')
            ->willReturnMap([
                ['101', new Tracker_FormElement_Field_List_Bind_UsersValue(101, "User01", "User Name 01")],
                ['102', new Tracker_FormElement_Field_List_Bind_UsersValue(102, "User02", "User Name 02")],
            ]);

        $list_user_field
            ->method('getLabel')
            ->willReturn("Users list");

        $list_user_field
            ->method('getCriteriaValue')
            ->with($criterion_user_list)
            ->willReturn(['101', '102']);

        $list_user_field
            ->method('getBind')
            ->willReturn($user_bind);

        return $criterion_user_list;
    }

    private function buildUserGroupListCriterion(Tracker_Report $report): Tracker_Report_Criteria
    {
        $list_groups_field = $this->createMock(Tracker_FormElement_Field_List::class);

        $criterion_groups_list = new Tracker_Report_Criteria(
            4,
            $report,
            $list_groups_field,
            4,
            0
        );

        $ugroup_01 = $this->createMock(ProjectUGroup::class);
        $ugroup_01->method('getTranslatedName')->willReturn("Ugroup01");

        $ugroup_02 = $this->createMock(ProjectUGroup::class);
        $ugroup_02->method('getTranslatedName')->willReturn("Ugroup02");

        $group_bind = $this->createMock(Tracker_FormElement_Field_List_Bind_Ugroups::class);
        $group_bind
            ->method('getValue')
            ->willReturnMap([
                ['115', new Tracker_FormElement_Field_List_Bind_UgroupsValue(115, $ugroup_01, false)],
                ['172', new Tracker_FormElement_Field_List_Bind_UgroupsValue(172, $ugroup_02, false)],
            ]);

        $list_groups_field
            ->method('getLabel')
            ->willReturn("User groups list");

        $list_groups_field
            ->method('getCriteriaValue')
            ->with($criterion_groups_list)
            ->willReturn(['115', '172']);

        $list_groups_field
            ->method('getBind')
            ->willReturn($group_bind);

        return $criterion_groups_list;
    }

    private function buildStaticListCriterion(Tracker_Report $report): Tracker_Report_Criteria
    {
        $list_static_field = $this->createMock(Tracker_FormElement_Field_List::class);

        $criterion_static_list = new Tracker_Report_Criteria(
            5,
            $report,
            $list_static_field,
            5,
            0
        );

        $static_bind = $this->createMock(Tracker_FormElement_Field_List_Bind_Static::class);
        $static_bind
            ->method('getValue')
            ->willReturnMap([
                ['299', new Tracker_FormElement_Field_List_Bind_StaticValue(299, 'Static value 01', '', 1, false)],
                ['300', new Tracker_FormElement_Field_List_Bind_StaticValue(300, 'Static value 02', '', 2, false)],
            ]);

        $list_static_field
            ->method('getLabel')
            ->willReturn("Static values list");

        $list_static_field
            ->method('getCriteriaValue')
            ->with($criterion_static_list)
            ->willReturn(['100', '299', '300']);

        $list_static_field
            ->method('getBind')
            ->willReturn($static_bind);

        return $criterion_static_list;
    }

    private function buildStaticOpenListCriterion(Tracker_Report $report): Tracker_Report_Criteria
    {
        $open_list_static_field = $this->createMock(Tracker_FormElement_Field_OpenList::class);

        $criterion_open_list_static = new Tracker_Report_Criteria(
            6,
            $report,
            $open_list_static_field,
            6,
            0
        );

        $open_list_static_field
            ->method('getLabel')
            ->willReturn("Open list static values");

        $open_list_static_field
            ->method('getCriteriaValue')
            ->with($criterion_open_list_static)
            ->willReturn("b14,b15,!abc");

        $open_list_static_field
            ->method('extractCriteriaValue')
            ->with("b14,b15,!abc")
            ->willReturn([
                new Tracker_FormElement_Field_List_Bind_StaticValue(
                    14,
                    'a',
                    '',
                    1,
                    false
                ),
                new Tracker_FormElement_Field_List_Bind_StaticValue(
                    15,
                    'b',
                    '',
                    2,
                    false
                ),
                new Tracker_FormElement_Field_List_UnsavedValue(
                    'abc'
                ),
            ]);

        return $criterion_open_list_static;
    }

    private function buildNotSetListCriterion(Tracker_Report $report): Tracker_Report_Criteria
    {
        $list_static_field = $this->createMock(Tracker_FormElement_Field_List::class);

        $criterion_static_list = new Tracker_Report_Criteria(
            5,
            $report,
            $list_static_field,
            5,
            0
        );

        $list_static_field
            ->method('getLabel')
            ->willReturn("Static values list");

        $list_static_field
            ->method('getCriteriaValue')
            ->with($criterion_static_list)
            ->willReturn('');

        return $criterion_static_list;
    }

    private function buildNotSetOpenListCriterion(Tracker_Report $report): Tracker_Report_Criteria
    {
        $open_list_static_field = $this->createMock(Tracker_FormElement_Field_OpenList::class);

        $criterion_open_list_static = new Tracker_Report_Criteria(
            8,
            $report,
            $open_list_static_field,
            8,
            0
        );

        $open_list_static_field
            ->method('getLabel')
            ->willReturn("Open list static values");

        $open_list_static_field
            ->method('getCriteriaValue')
            ->with($criterion_open_list_static)
            ->willReturn("");

        return $criterion_open_list_static;
    }

    private function buildListCriterionWithInvalidValue(Tracker_Report $report): Tracker_Report_Criteria
    {
        $field = $this->createMock(Tracker_FormElement_Field_List::class);

        $criterion_with_invalid_value = new Tracker_Report_Criteria(
            5,
            $report,
            $field,
            5,
            0
        );

        $static_bind = $this->createStub(Tracker_FormElement_Field_List_Bind_Static::class);
        $static_bind
            ->method('getValue')
            ->willThrowException(new Tracker_FormElement_InvalidFieldValueException());

        $field
            ->method('getBind')
            ->willReturn($static_bind);

        $field
            ->method('getCriteriaValue')
            ->with($criterion_with_invalid_value)
            ->willReturn(['404']);

        return $criterion_with_invalid_value;
    }

    private function buildPermissionsOnArtifactCriterion(Tracker_Report $report): Tracker_Report_Criteria
    {
        $field = $this->createStub(\Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $field->method('getLabel')->willReturn('Perms');
        $tracker = $this->createStub(Tracker::class);
        $tracker->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());
        $field->method('getTracker')->willReturn($tracker);

        $criterion = new Tracker_Report_Criteria(
            25,
            $report,
            $field,
            5,
            0
        );

        $field
            ->method('getCriteriaValue')
            ->willReturn([123 => ['123']]);

        $ugroup = new ProjectUGroup(['name' => 'ugroup_name']);
        $this->ugroup_manager->method('getUGroup')->willReturn($ugroup);

        return $criterion;
    }

    private function buildNotSetDateCriterion(Tracker_Report $report): Tracker_Report_Criteria
    {
        $date_field = $this->createMock(Tracker_FormElement_Field_Date::class);

        $criterion_date = new Tracker_Report_Criteria(
            9,
            $report,
            $date_field,
            9,
            1
        );

        $date_field
            ->method('getLabel')
            ->willReturn("Submitted On");

        $date_field
            ->method('getCriteriaValue')
            ->with($criterion_date)
            ->willReturn([]);

        return $criterion_date;
    }
}
