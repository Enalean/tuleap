<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone;

use AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider;
use AgileDashboard_Milestone_MilestoneReportCriterionProvider;
use AgileDashboard_Milestone_SelectedMilestoneProvider;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedCriterionOptionsProvider;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedReportCriterionChecker;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MilestoneReportCriterionProviderTest extends TestCase
{
    use GlobalLanguageMock;

    private AgileDashboard_Milestone_MilestoneReportCriterionProvider $provider;
    private AgileDashboard_Milestone_SelectedMilestoneProvider&MockObject $milestone_id_provider;
    private AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider&MockObject $options_provider;
    private UnplannedCriterionOptionsProvider&MockObject $uplanned_criterion_provider;
    private UnplannedReportCriterionChecker&MockObject $uplanned_criterion_checker;
    private PFUser $user;
    private Tracker $task_tracker;

    protected function setUp(): void
    {
        $this->options_provider            = $this->createMock(AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider::class);
        $this->milestone_id_provider       = $this->createMock(AgileDashboard_Milestone_SelectedMilestoneProvider::class);
        $this->uplanned_criterion_provider = $this->createMock(UnplannedCriterionOptionsProvider::class);
        $this->uplanned_criterion_checker  = $this->createMock(UnplannedReportCriterionChecker::class);
        $this->task_tracker                = TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->build())
            ->build();

        $this->user = UserTestBuilder::buildWithDefaults();

        $this->provider = new AgileDashboard_Milestone_MilestoneReportCriterionProvider(
            $this->milestone_id_provider,
            $this->options_provider,
            $this->uplanned_criterion_provider,
            $this->uplanned_criterion_checker
        );
    }

    public function testItReturnsNullWhenNoOptions(): void
    {
        $this->milestone_id_provider->method('getMilestoneId')->willReturn('104');

        $this->options_provider->method('getSelectboxOptions')
            ->with($this->task_tracker, self::anything(), $this->user)
            ->willReturn([]);

        $this->uplanned_criterion_checker->method('isUnplannedValueSelected')->willReturn(false);

        self::assertNull($this->provider->getCriterion($this->task_tracker, $this->user));
    }

    public function testItReturnsASelectBox(): void
    {
        $this->milestone_id_provider->method('getMilestoneId')->willReturn('104');

        $this->options_provider->method('getSelectboxOptions')
            ->with($this->task_tracker, self::anything(), $this->user)
            ->willReturn(['<option>1', '<option>2']);

        $this->uplanned_criterion_checker->method('isUnplannedValueSelected')->willReturn(false);

        $this->uplanned_criterion_provider->expects($this->once())->method('formatUnplannedAsSelectboxOption')->willReturn('');

        self::assertMatchesRegularExpression('/<select name="additional_criteria\[agiledashboard_milestone]"/', $this->provider->getCriterion($this->task_tracker, $this->user));
    }

    public function testItSelectsTheGivenMilestone(): void
    {
        $this->milestone_id_provider->method('getMilestoneId')->willReturn('104');

        $this->uplanned_criterion_checker->method('isUnplannedValueSelected')->willReturn(false);

        $this->options_provider->expects($this->once())->method('getSelectboxOptions')->with($this->task_tracker, '104', $this->user);

        $this->provider->getCriterion($this->task_tracker, $this->user);
    }

    public function testItSelectsUnplannedOption(): void
    {
        $this->milestone_id_provider->expects(self::never())->method('getMilestoneId');

        $this->uplanned_criterion_checker->method('isUnplannedValueSelected')->willReturn(true);

        $this->options_provider->expects($this->once())->method('getSelectboxOptions')->with($this->task_tracker, '-1', $this->user);

        $this->provider->getCriterion($this->task_tracker, $this->user);
    }
}
