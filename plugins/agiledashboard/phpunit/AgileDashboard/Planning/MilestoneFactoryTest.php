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

use AgileDashboard_Milestone_MilestoneDao;
use AgileDashboard_Milestone_MilestoneStatusCounter;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning;
use Planning_MilestoneFactory;
use PlanningFactory;
use PlanningPermissionsManager;
use Project;
use TimePeriodWithoutWeekEnd;
use Tracker;
use Tracker_Artifact;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use TrackerFactory;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;

class MilestoneFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone;
    /**
     * @var Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var Mockery\MockInterface|Planning
     */
    private $planning;
    /**
     * @var Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact_closed_passed;
    /**
     * @var Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact_open_current_with_start_date;
    /**
     * @var Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact_open_current_without_start_date;
    /**
     * @var Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact_open_future_without_start_date;
    /**
     * @var Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact_open_future_with_start_date;

    protected function setUp(): void
    {
        parent::setUp();

        $this->planning = Mockery::mock(Planning::class);
        $this->planning->shouldReceive('getPlanningTrackerId')->andReturn(20);

        $this->user = Mockery::mock(PFUser::class);

        $this->project = Mockery::mock(Project::class);

        $planning_factory = Mockery::mock(PlanningFactory::class);
        $planning_factory->shouldReceive('getPlanningByPlanningTracker')->andReturn($this->planning);

        $this->artifact_open_current_with_start_date    = $this->mockAnArtifact(1, true, 'open');
        $this->artifact_open_current_without_start_date = $this->mockAnArtifact(2, true, 'open');
        $this->artifact_open_future_with_start_date     = $this->mockAnArtifact(3, true, 'open');
        $this->artifact_open_future_without_start_date  = $this->mockAnArtifact(4, true, 'open');
        $this->artifact_closed_passed                   = $this->mockAnArtifact(5, false, 'closed');

        $artifact_factory = Mockery::mock(Tracker_ArtifactFactory::class);
        $artifact_factory
            ->shouldReceive('getArtifactsByTrackerIdUserCanView')
            ->andReturn([
                    $this->artifact_open_current_with_start_date,
                    $this->artifact_open_current_without_start_date,
                    $this->artifact_open_future_without_start_date,
                    $this->artifact_closed_passed,
                    $this->artifact_open_future_with_start_date]);
        $artifact_factory
            ->shouldReceive('getClosedArtifactsByTrackerIdUserCanView')
            ->andReturn([
                $this->artifact_closed_passed
            ]);

        $formelement_factory = Mockery::mock(Tracker_FormElementFactory::class);
        $formelement_factory->shouldReceive('getFormElementByName')->andReturn(Mockery::mock(\Tracker_FormElement_Field::class));

        $tracker_factory              = Mockery::mock(TrackerFactory::class);
        $status_counter               = Mockery::mock(AgileDashboard_Milestone_MilestoneStatusCounter::class);
        $planning_permissions_manager = Mockery::mock(PlanningPermissionsManager::class);
        $milestone_dao                = Mockery::mock(AgileDashboard_Milestone_MilestoneDao::class);
        $scrum_mono_milestone_checker = Mockery::mock(ScrumForMonoMilestoneChecker::class);

        $time_period_open_current_without_start_date = Mockery::mock(TimePeriodWithoutWeekEnd::class);
        $time_period_open_current_without_start_date->shouldReceive('isTodayBeforeTimePeriod')->andReturn(false);
        $time_period_open_current_without_start_date->shouldReceive('getStartDate')->andReturn(0);

        $time_period_open_current_with_start_date = Mockery::mock(TimePeriodWithoutWeekEnd::class);
        $time_period_open_current_with_start_date->shouldReceive('isTodayBeforeTimePeriod')->andReturn(false);
        $time_period_open_current_with_start_date->shouldReceive('getStartDate')->andReturn(strtotime('2015-12-03T14:55:00'));

        $time_period_open_future_with_start_date = Mockery::mock(TimePeriodWithoutWeekEnd::class);
        $time_period_open_future_with_start_date->shouldReceive('isTodayBeforeTimePeriod')->andReturn(true);
        $time_period_open_future_with_start_date->shouldReceive('getStartDate')->andReturn(strtotime('2015-12-03T14:55:00'));

        $time_period_open_future_without_start_date = Mockery::mock(TimePeriodWithoutWeekEnd::class);
        $time_period_open_future_without_start_date->shouldReceive('isTodayBeforeTimePeriod')->andReturn(true);
        $time_period_open_future_without_start_date->shouldReceive('getStartDate')->andReturn(0);

        $time_period_closed_passed = Mockery::mock(TimePeriodWithoutWeekEnd::class);
        $time_period_closed_passed->shouldReceive('getStartDate')->andReturn(0);
        $time_period_closed_passed->shouldReceive('getEndDate')->andReturn(strtotime('2015-12-03T14:55:00'));

        $timeframe_builder = Mockery::mock(TimeframeBuilder::class);
        $timeframe_builder
            ->shouldReceive('buildTimePeriodWithoutWeekendForArtifact')
            ->withArgs([$this->artifact_open_current_without_start_date, $this->user])
            ->andReturn($time_period_open_current_without_start_date);
        $timeframe_builder
            ->shouldReceive('buildTimePeriodWithoutWeekendForArtifact')
            ->withArgs([$this->artifact_open_current_with_start_date, $this->user])
            ->andReturn($time_period_open_current_with_start_date);
        $timeframe_builder
            ->shouldReceive('buildTimePeriodWithoutWeekendForArtifact')
            ->withArgs([$this->artifact_closed_passed, $this->user])
            ->andReturn($time_period_closed_passed);
        $timeframe_builder
            ->shouldReceive('buildTimePeriodWithoutWeekendForArtifact')
            ->withArgs([$this->artifact_open_future_with_start_date, $this->user])
            ->andReturn($time_period_open_future_with_start_date);
        $timeframe_builder
            ->shouldReceive('buildTimePeriodWithoutWeekendForArtifact')
            ->withArgs([$this->artifact_open_future_without_start_date, $this->user])
            ->andReturn($time_period_open_future_without_start_date);

        $milestone_burndown_field_checker = Mockery::mock(MilestoneBurndownFieldChecker::class);
        $milestone_burndown_field_checker->shouldReceive('hasUsableBurndownField')->andReturn(true);

        $this->milestone = new Planning_MilestoneFactory(
            $planning_factory,
            $artifact_factory,
            $formelement_factory,
            $status_counter,
            $planning_permissions_manager,
            $milestone_dao,
            $scrum_mono_milestone_checker,
            $timeframe_builder,
            $milestone_burndown_field_checker
        );
    }

    public function testGetOnlyCurrentMilestone(): void
    {
        $milestones = $this->milestone->getAllCurrentMilestones($this->user, $this->planning);
        $this->assertCount(3, $milestones);
        $this->assertEquals($this->artifact_open_current_with_start_date, $milestones[0]->getArtifact());
        $this->assertEquals($this->artifact_open_current_without_start_date, $milestones[1]->getArtifact());
        $this->assertEquals($this->artifact_open_future_without_start_date, $milestones[2]->getArtifact());
    }

    public function testGetOnlyFutureMilestone(): void
    {
        $milestones = $this->milestone->getAllFutureMilestones($this->user, $this->planning);
        $this->assertCount(3, $milestones);
        $this->assertEquals($this->artifact_open_current_without_start_date, $milestones[0]->getArtifact());
        $this->assertEquals($this->artifact_open_future_without_start_date, $milestones[1]->getArtifact());
        $this->assertEquals($this->artifact_open_future_with_start_date, $milestones[2]->getArtifact());
    }

    public function testGetOnlyPastMilestone(): void
    {
        $milestones = $this->milestone->getPastMilestones($this->user, $this->planning, 1);
        $this->assertCount(1, $milestones);
        $this->assertEquals($this->artifact_closed_passed, $milestones[0]->getArtifact());
    }

    private function mockAnArtifact(int $id, bool $is_open, string $status): Tracker_Artifact
    {
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getProject')->andReturn($this->project);
        $tracker->shouldReceive('getId')->andReturn(100);

        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn($id);
        $artifact->shouldReceive('isOpen')->andReturn($is_open);
        $artifact->shouldReceive('getStatus')->andReturn($status);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        return $artifact;
    }
}
