<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\ExplicitBacklog;

use AgileDashboard_Milestone_MilestoneReportCriterionProvider;
use PHPUnit\Framework\TestCase;
use Tracker_Report;
use Tracker_Report_AdditionalCriterion;

final class UnplannedReportCriterionCheckerTest extends TestCase
{
    public function testItReturnsFalseIfAdditionalCriterionIsNotProvided(): void
    {
        $additional_criteria = [
            Tracker_Report::COMMENT_CRITERION_NAME => new Tracker_Report_AdditionalCriterion(
                Tracker_Report::COMMENT_CRITERION_NAME,
                'my comment'
            )
        ];

        $checker = new UnplannedReportCriterionChecker($additional_criteria);

        $this->assertFalse($checker->isUnplannedValueSelected());
    }

    public function testItReturnsFalseIfAdditionalCriterionIsProvidedAndValueIsNotUnplannedValue(): void
    {
        $additional_criteria = [
            Tracker_Report::COMMENT_CRITERION_NAME => new Tracker_Report_AdditionalCriterion(
                Tracker_Report::COMMENT_CRITERION_NAME,
                'my comment'
            ),
            AgileDashboard_Milestone_MilestoneReportCriterionProvider::FIELD_NAME => new Tracker_Report_AdditionalCriterion(
                AgileDashboard_Milestone_MilestoneReportCriterionProvider::FIELD_NAME,
                '104'
            )
        ];

        $checker = new UnplannedReportCriterionChecker($additional_criteria);

        $this->assertFalse($checker->isUnplannedValueSelected());
    }

    public function testItReturnsTrueIfAdditionalCriterionIsProvidedAndValueIsUnplannedValue(): void
    {
        $additional_criteria = [
            Tracker_Report::COMMENT_CRITERION_NAME => new Tracker_Report_AdditionalCriterion(
                Tracker_Report::COMMENT_CRITERION_NAME,
                'my comment'
            ),
            AgileDashboard_Milestone_MilestoneReportCriterionProvider::FIELD_NAME => new Tracker_Report_AdditionalCriterion(
                AgileDashboard_Milestone_MilestoneReportCriterionProvider::FIELD_NAME,
                '-1'
            )
        ];

        $checker = new UnplannedReportCriterionChecker($additional_criteria);

        $this->assertTrue($checker->isUnplannedValueSelected());
    }
}
