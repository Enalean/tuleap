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

use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_String;
use Tracker_Report;
use Tracker_Report_Criteria;
use Tuleap\Test\PHPUnit\TestCase;

final class ReportCriteriaJsonBuilderTest extends TestCase
{
    public function testItBuildsJsonForReportWithExpertQuery(): void
    {
        $report      = $this->buildReportWithExpertQuery();
        $report_json = (new ReportCriteriaJsonBuilder())->buildReportCriteriaJson($report);

        self::assertInstanceOf(ExpertReportCriteriaJson::class, $report_json);
        self::assertNotNull($report_json->is_in_expert_mode);
        self::assertTrue($report_json->is_in_expert_mode);
        self::assertNotNull($report_json->query);
        self::assertSame('field01="value02" AND field02="value02"', $report_json->query);
    }

    public function testItBuildsJsonForReportWithClassicQuery(): void
    {
        $report      = $this->buildReportWithCriteria();
        $report_json = (new ReportCriteriaJsonBuilder())->buildReportCriteriaJson($report);

        self::assertInstanceOf(ClassicReportCriteriaJson::class, $report_json);
        self::assertNotNull($report_json->is_in_expert_mode);
        self::assertFalse($report_json->is_in_expert_mode);
        self::assertNotNull($report_json->criteria);
        self::assertCount(1, $report_json->criteria);
        self::assertSame("Summary", $report_json->criteria[0]->criterion_name);
        self::assertSame("Test", $report_json->criteria[0]->criterion_value);
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

        $string_field = $this->createMock(Tracker_FormElement_Field_String::class);
        $date_field   = $this->createMock(Tracker_FormElement_Field_Date::class);
        $list_field   = $this->createMock(Tracker_FormElement_Field_List::class);

        $criterion_string = new Tracker_Report_Criteria(
            1,
            $report,
            $string_field,
            1,
            0
        );

        $criterion_date = new Tracker_Report_Criteria(
            2,
            $report,
            $date_field,
            2,
            0
        );

        $criterion_list = new Tracker_Report_Criteria(
            3,
            $report,
            $list_field,
            3,
            0
        );

        $string_field
            ->method('getCriteriaValue')
            ->with($criterion_string)
            ->willReturn("Test");

        $string_field
            ->method('getLabel')
            ->willReturn("Summary");

        $criteria = [
            $criterion_string,
            $criterion_date,
            $criterion_list,
        ];

        $report->method('getCriteria')->willReturn($criteria);

        return $report;
    }
}
