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
use Planning;
use Planning_MilestoneFactory;
use PlanningFactory;
use PlanningPermissionsManager;
use Project;
use Psr\Log\NullLogger;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\IComputeTimeframes;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

class MilestoneFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private Project $project;
    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone;
    private PFUser $user;
    /**
     * @var Mockery\MockInterface|Planning
     */
    private $planning;
    /**
     * @var Mockery\MockInterface|Artifact
     */
    private $artifact_closed_passed;
    /**
     * @var Mockery\MockInterface|Artifact
     */
    private $artifact_open_current_with_start_date;
    /**
     * @var Mockery\MockInterface|Artifact
     */
    private $artifact_open_current_without_start_date;
    /**
     * @var Mockery\MockInterface|Artifact
     */
    private $artifact_open_future_without_start_date;
    /**
     * @var Mockery\MockInterface|Artifact
     */
    private $artifact_open_future_with_start_date;

    protected function setUp(): void
    {
        parent::setUp();

        $this->planning = Mockery::mock(Planning::class);
        $this->planning->shouldReceive('getPlanningTrackerId')->andReturn(20);

        $this->user    = UserTestBuilder::anActiveUser()->build();
        $this->project = ProjectTestBuilder::aProject()->build();

        $planning_factory = Mockery::mock(PlanningFactory::class);
        $planning_factory->shouldReceive('getPlanningByPlanningTracker')->andReturn($this->planning);

        $tracker = TrackerTestBuilder::aTracker()->withId(100)->withProject($this->project)->build();

        $this->artifact_open_current_with_start_date    = $this->getAnArtifact(1, true, 'open', $tracker);
        $this->artifact_open_current_without_start_date = $this->getAnArtifact(2, true, 'open', $tracker);
        $this->artifact_open_future_with_start_date     = $this->getAnArtifact(3, true, 'open', $tracker);
        $this->artifact_open_future_without_start_date  = $this->getAnArtifact(4, true, 'open', $tracker);
        $this->artifact_closed_passed                   = $this->getAnArtifact(5, false, 'closed', $tracker);

        $artifact_factory = Mockery::mock(Tracker_ArtifactFactory::class);
        $artifact_factory
            ->shouldReceive('getArtifactsByTrackerIdUserCanView')
            ->andReturn([
                $this->artifact_open_current_with_start_date,
                $this->artifact_open_current_without_start_date,
                $this->artifact_open_future_without_start_date,
                $this->artifact_closed_passed,
                $this->artifact_open_future_with_start_date,
            ]);
        $artifact_factory
            ->shouldReceive('getClosedArtifactsByTrackerIdUserCanView')
            ->andReturn([
                $this->artifact_closed_passed,
            ]);

        $formelement_factory = Mockery::mock(Tracker_FormElementFactory::class);
        $formelement_factory->shouldReceive('getFormElementByName')->andReturn(Mockery::mock(\Tracker_FormElement_Field::class));

        $status_counter               = Mockery::mock(AgileDashboard_Milestone_MilestoneStatusCounter::class);
        $planning_permissions_manager = Mockery::mock(PlanningPermissionsManager::class);
        $milestone_dao                = Mockery::mock(AgileDashboard_Milestone_MilestoneDao::class);

        $date_period_open_current_without_start_date = Mockery::mock(DatePeriodWithoutWeekEnd::class);
        $date_period_open_current_without_start_date->shouldReceive('isTodayBeforeDatePeriod')->andReturn(false);
        $date_period_open_current_without_start_date->shouldReceive('getStartDate')->andReturn(0);

        $date_period_open_current_with_start_date = Mockery::mock(DatePeriodWithoutWeekEnd::class);
        $date_period_open_current_with_start_date->shouldReceive('isTodayBeforeDatePeriod')->andReturn(false);
        $date_period_open_current_with_start_date->shouldReceive('getStartDate')->andReturn(strtotime('2015-12-03T14:55:00'));

        $date_period_open_future_with_start_date = Mockery::mock(DatePeriodWithoutWeekEnd::class);
        $date_period_open_future_with_start_date->shouldReceive('isTodayBeforeDatePeriod')->andReturn(true);
        $date_period_open_future_with_start_date->shouldReceive('getStartDate')->andReturn(strtotime('2015-12-03T14:55:00'));

        $date_period_open_future_without_start_date = Mockery::mock(DatePeriodWithoutWeekEnd::class);
        $date_period_open_future_without_start_date->shouldReceive('isTodayBeforeDatePeriod')->andReturn(true);
        $date_period_open_future_without_start_date->shouldReceive('getStartDate')->andReturn(0);

        $date_period_closed_passed = Mockery::mock(DatePeriodWithoutWeekEnd::class);
        $date_period_closed_passed->shouldReceive('getStartDate')->andReturn(0);
        $date_period_closed_passed->shouldReceive('getEndDate')->andReturn(strtotime('2015-12-03T14:55:00'));

        $logger                     = new NullLogger();
        $timeframe_calculator       = Mockery::mock(IComputeTimeframes::class);
        $semantic_timeframe         = Mockery::mock(SemanticTimeframe::class, ['getTimeframeCalculator' => $timeframe_calculator]);
        $semantic_timeframe_builder = Mockery::mock(SemanticTimeframeBuilder::class);
        $semantic_timeframe_builder->shouldReceive('getSemantic')
            ->with($tracker)
            ->andReturn($semantic_timeframe);

        $timeframe_calculator
            ->shouldReceive('buildDatePeriodWithoutWeekendForChangeset')
            ->withArgs([$this->artifact_open_current_without_start_date->getLastChangeset(), $this->user, $logger])
            ->andReturn($date_period_open_current_without_start_date);
        $timeframe_calculator
            ->shouldReceive('buildDatePeriodWithoutWeekendForChangeset')
            ->withArgs([$this->artifact_open_current_with_start_date->getLastChangeset(), $this->user, $logger])
            ->andReturn($date_period_open_current_with_start_date);
        $timeframe_calculator
            ->shouldReceive('buildDatePeriodWithoutWeekendForChangeset')
            ->withArgs([$this->artifact_closed_passed->getLastChangeset(), $this->user, $logger])
            ->andReturn($date_period_closed_passed);
        $timeframe_calculator
            ->shouldReceive('buildDatePeriodWithoutWeekendForChangeset')
            ->withArgs([$this->artifact_open_future_with_start_date->getLastChangeset(), $this->user, $logger])
            ->andReturn($date_period_open_future_with_start_date);
        $timeframe_calculator
            ->shouldReceive('buildDatePeriodWithoutWeekendForChangeset')
            ->withArgs([$this->artifact_open_future_without_start_date->getLastChangeset(), $this->user, $logger])
            ->andReturn($date_period_open_future_without_start_date);

        $this->milestone = new Planning_MilestoneFactory(
            $planning_factory,
            $artifact_factory,
            $formelement_factory,
            $status_counter,
            $planning_permissions_manager,
            $milestone_dao,
            $semantic_timeframe_builder,
            $logger,
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

    private function getAnArtifact(int $id, bool $is_open, string $status, \Tracker $tracker): Artifact
    {
        return ArtifactTestBuilder::anArtifact($id)
            ->inTracker($tracker)
            ->withChangesets(ChangesetTestBuilder::aChangeset('1')->build())
            ->userCanView($this->user)
            ->withParent(null)
            ->withStatus($status)
            ->isOpen($is_open)
            ->withAncestors([])
            ->build();
    }
}
