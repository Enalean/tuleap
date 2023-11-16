<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Planning;

use AgileDashboard_Milestone_MilestoneDao;
use AgileDashboard_Milestone_MilestoneStatusCounter;
use Mockery;
use PFUser;
use Planning;
use Planning_MilestoneFactory;
use PlanningFactory;
use PlanningPermissionsManager;
use Project;
use Psr\Log\NullLogger;
use Tracker;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\IComputeTimeframes;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;

final class MilestoneFactoryGetBareMilestoneByArtifactIdTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var int
     */
    private $artifact_id;
    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;
    private PFUser $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|IComputeTimeframes
     */
    private $timeframe_calculator;
    private NullLogger $logger;

    protected function setUp(): void
    {
        $this->planning_factory     = Mockery::spy(PlanningFactory::class);
        $this->artifact_factory     = Mockery::spy(Tracker_ArtifactFactory::class);
        $this->timeframe_calculator = Mockery::mock(IComputeTimeframes::class);
        $semantic_timeframe         = Mockery::mock(SemanticTimeframe::class, ['getTimeframeCalculator' => $this->timeframe_calculator]);
        $semantic_timeframe_builder = Mockery::mock(SemanticTimeframeBuilder::class, ['getSemantic' => $semantic_timeframe]);
        $this->logger               = new NullLogger();

        $this->milestone_factory = new Planning_MilestoneFactory(
            $this->planning_factory,
            $this->artifact_factory,
            Mockery::spy(Tracker_FormElementFactory::class),
            Mockery::spy(AgileDashboard_Milestone_MilestoneStatusCounter::class),
            Mockery::spy(PlanningPermissionsManager::class),
            Mockery::spy(AgileDashboard_Milestone_MilestoneDao::class),
            Mockery::spy(ScrumForMonoMilestoneChecker::class),
            $semantic_timeframe_builder,
            $this->logger,
            Mockery::spy(MilestoneBurndownFieldChecker::class)
        );
        $this->user              = UserTestBuilder::anActiveUser()->build();
        $this->artifact_id       = 112;
    }

    public function testItReturnsNullIfArtifactDoesntExist(): void
    {
        $this->assertNull(
            $this->milestone_factory->getBareMilestoneByArtifactId($this->user, $this->artifact_id)
        );
    }

    public function testItReturnsAMilestone(): void
    {
        $planning_tracker = Mockery::mock(Tracker::class);
        $planning_tracker->shouldReceive('getProject')->andReturn(Mockery::spy(Project::class));
        $planning_tracker->shouldReceive('getId')->andReturn(12);

        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($planning_tracker)->andReturn(Mockery::mock(Planning::class));


        $changeset = ChangesetTestBuilder::aChangeset('1')->build();

        $artifact = ArtifactTestBuilder::anArtifact($this->artifact_id)
            ->withTitle('title')
            ->inTracker($planning_tracker)
            ->withChangesets($changeset)
            ->userCanView(true)
            ->withParent(null)
            ->isOpen(true)
            ->withAncestors([])
            ->build();

        $this->artifact_factory->shouldReceive('getArtifactById')->with($this->artifact_id)->andReturn($artifact);

        $this->timeframe_calculator->shouldReceive('buildDatePeriodWithoutWeekendForChangeset')
            ->with($artifact->getLastChangeset(), $this->user, $this->logger)
            ->once()
            ->andReturn(DatePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $milestone = $this->milestone_factory->getBareMilestoneByArtifactId($this->user, $this->artifact_id);
        $this->assertEquals($artifact, $milestone->getArtifact());
    }

    public function testItReturnsNullWhenArtifactIsNotAMilestone(): void
    {
        $planning_tracker = Mockery::mock(Tracker::class);
        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')->with()->andReturn(false);

        $artifact = Mockery::spy(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($planning_tracker);
        $artifact->shouldReceive('userCanView')->with($this->user)->once()->andReturn($planning_tracker);
        $this->artifact_factory->shouldReceive('getArtifactById')->with($this->artifact_id)->andReturn($artifact);

        $this->assertNull(
            $this->milestone_factory->getBareMilestoneByArtifactId($this->user, $this->artifact_id)
        );
    }

    public function testItReturnsNullWhenUserCannotSeeArtifacts(): void
    {
        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')->andReturn(Mockery::mock(Planning::class));

        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('userCanView')->with($this->user)->andReturn(false);
        $this->artifact_factory->shouldReceive('getArtifactById')->with($this->artifact_id)->andReturn($artifact);

        $this->assertNull(
            $this->milestone_factory->getBareMilestoneByArtifactId($this->user, $this->artifact_id)
        );
    }
}
