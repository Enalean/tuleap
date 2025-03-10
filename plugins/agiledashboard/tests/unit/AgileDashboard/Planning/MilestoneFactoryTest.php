<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use AgileDashboard_Milestone_MilestoneStatusCounter;
use LogicException;
use PFUser;
use Planning;
use Planning_MilestoneFactory;
use PlanningFactory;
use PlanningPermissionsManager;
use Psr\Log\NullLogger;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\Milestone\MilestoneDao;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Date\DatePeriodWithOpenDays;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\IComputeTimeframes;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Semantic\Timeframe\BuildSemanticTimeframeStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MilestoneFactoryTest extends TestCase
{
    private Planning_MilestoneFactory $milestone;
    private PFUser $user;
    private Planning $planning;
    private Artifact $artifact_closed_passed;
    private Artifact $artifact_open_current_with_start_date;
    private Artifact $artifact_open_current_without_start_date;
    private Artifact $artifact_open_future_without_start_date;
    private Artifact $artifact_open_future_with_start_date;

    protected function setUp(): void
    {
        $project        = ProjectTestBuilder::aProject()->build();
        $this->planning = PlanningBuilder::aPlanning((int) $project->getID())
            ->withMilestoneTracker(TrackerTestBuilder::aTracker()->withId(20)->build())
            ->build();

        $this->user = UserTestBuilder::anActiveUser()->build();

        $planning_factory = $this->createMock(PlanningFactory::class);
        $planning_factory->method('getPlanningByPlanningTracker')->willReturn($this->planning);

        $tracker = TrackerTestBuilder::aTracker()->withId(100)->withProject($project)->build();

        $this->artifact_open_current_with_start_date    = $this->getAnArtifact(1, true, 'open', $tracker);
        $this->artifact_open_current_without_start_date = $this->getAnArtifact(2, true, 'open', $tracker);
        $this->artifact_open_future_with_start_date     = $this->getAnArtifact(3, true, 'open', $tracker);
        $this->artifact_open_future_without_start_date  = $this->getAnArtifact(4, true, 'open', $tracker);
        $this->artifact_closed_passed                   = $this->getAnArtifact(5, false, 'closed', $tracker);

        $artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
        $artifact_factory->method('getArtifactsByTrackerIdUserCanView')
            ->willReturn([
                $this->artifact_open_current_with_start_date,
                $this->artifact_open_current_without_start_date,
                $this->artifact_open_future_without_start_date,
                $this->artifact_closed_passed,
                $this->artifact_open_future_with_start_date,
            ]);

        $formelement_factory = $this->createMock(Tracker_FormElementFactory::class);
        $formelement_factory->method('getFormElementByName')->willReturn(IntFieldBuilder::anIntField(1)->build());

        $status_counter               = $this->createMock(AgileDashboard_Milestone_MilestoneStatusCounter::class);
        $planning_permissions_manager = $this->createMock(PlanningPermissionsManager::class);

        $logger                                      = new NullLogger();
        $date_period_open_current_without_start_date = DatePeriodWithOpenDays::buildFromEndDate(0, 0, $logger);
        $date_period_open_current_with_start_date    = DatePeriodWithOpenDays::buildFromEndDate(strtotime('2015-12-03T14:55:00'), 0, $logger);

        $date_period_open_future_with_start_date = $this->createMock(DatePeriodWithOpenDays::class);
        $date_period_open_future_with_start_date->method('isTodayBeforeDatePeriod')->willReturn(true);
        $date_period_open_future_with_start_date->method('getStartDate')->willReturn(strtotime('2015-12-03T14:55:00'));

        $date_period_open_future_without_start_date = $this->createMock(DatePeriodWithOpenDays::class);
        $date_period_open_future_without_start_date->method('isTodayBeforeDatePeriod')->willReturn(true);
        $date_period_open_future_without_start_date->method('getStartDate')->willReturn(0);

        $date_period_closed_passed = DatePeriodWithOpenDays::buildFromEndDate(0, strtotime('2015-12-03T14:55:00'), $logger);

        $timeframe_calculator       = $this->createMock(IComputeTimeframes::class);
        $semantic_timeframe_builder = BuildSemanticTimeframeStub::withTimeframeCalculator(TrackerTestBuilder::aTracker()->build(), $timeframe_calculator);

        $timeframe_calculator->method('buildDatePeriodWithoutWeekendForChangeset')
            ->with(self::isInstanceOf(Tracker_Artifact_Changeset::class), $this->user, $logger)
            ->willReturnCallback(fn(?Tracker_Artifact_Changeset $changeset) => match ($changeset) {
                $this->artifact_open_current_without_start_date->getLastChangeset() => $date_period_open_current_without_start_date,
                $this->artifact_open_current_with_start_date->getLastChangeset()    => $date_period_open_current_with_start_date,
                $this->artifact_closed_passed->getLastChangeset()                   => $date_period_closed_passed,
                $this->artifact_open_future_with_start_date->getLastChangeset()     => $date_period_open_future_with_start_date,
                $this->artifact_open_future_without_start_date->getLastChangeset()  => $date_period_open_future_without_start_date,
                default                                                             => throw new LogicException("Should not have arg changeset #{$changeset->getId()}"),
            });

        $this->milestone = new Planning_MilestoneFactory(
            $planning_factory,
            $artifact_factory,
            $formelement_factory,
            $status_counter,
            $planning_permissions_manager,
            $this->createMock(MilestoneDao::class),
            $semantic_timeframe_builder,
            $logger,
        );
    }

    public function testGetOnlyCurrentMilestone(): void
    {
        $milestones = $this->milestone->getAllCurrentMilestones($this->user, $this->planning);
        self::assertCount(3, $milestones);
        self::assertEquals($this->artifact_open_current_with_start_date, $milestones[0]->getArtifact());
        self::assertEquals($this->artifact_open_current_without_start_date, $milestones[1]->getArtifact());
        self::assertEquals($this->artifact_open_future_without_start_date, $milestones[2]->getArtifact());
    }

    public function testGetOnlyFutureMilestone(): void
    {
        $milestones = $this->milestone->getAllFutureMilestones($this->user, $this->planning);
        self::assertCount(3, $milestones);
        self::assertEquals($this->artifact_open_current_without_start_date, $milestones[0]->getArtifact());
        self::assertEquals($this->artifact_open_future_without_start_date, $milestones[1]->getArtifact());
        self::assertEquals($this->artifact_open_future_with_start_date, $milestones[2]->getArtifact());
    }

    private function getAnArtifact(int $id, bool $is_open, string $status, Tracker $tracker): Artifact
    {
        return ArtifactTestBuilder::anArtifact($id)
            ->inTracker($tracker)
            ->withChangesets(ChangesetTestBuilder::aChangeset(1)->build())
            ->userCanView($this->user)
            ->withParent(null)
            ->withStatus($status)
            ->isOpen($is_open)
            ->withAncestors([])
            ->build();
    }
}
