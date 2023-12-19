<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Log\NullLogger;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusAll;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusOpen;
use Tuleap\AgileDashboard\Milestone\Request\FilteringQuery;
use Tuleap\AgileDashboard\Milestone\Request\SiblingMilestoneRequest;
use Tuleap\AgileDashboard\Milestone\Request\SubMilestoneRequest;
use Tuleap\AgileDashboard\Milestone\Request\TopMilestoneRequest;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
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

final class MilestoneFactoryGetPaginatedMilestonesTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private const PROJECT_ID = 101;

    /**
     * @var \Planning_MilestoneFactory
     */
    private $milestone_factory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\PlanningFactory
     */
    private $planning_factory;
    /**
     * @var \AgileDashboard_Milestone_MilestoneDao|M\LegacyMockInterface|M\MockInterface
     */
    private $milestone_dao;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|IComputeTimeframes
     */
    private $timeframe_calculator;
    private \Planning $top_planning;
    private TopMilestoneRequest $top_milestone_request;
    private SubMilestoneRequest $sub_milestone_request;
    private SiblingMilestoneRequest $sibling_milestone_request;
    private \Tracker $sub_milestone_tracker;
    private \Planning_ArtifactMilestone $reference_milestone;
    private \Project $project;
    private \Planning $sub_planning;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->planning_factory     = M::mock(\PlanningFactory::class);
        $this->artifact_factory     = M::mock(\Tracker_ArtifactFactory::class);
        $this->milestone_dao        = M::mock(\AgileDashboard_Milestone_MilestoneDao::class);
        $this->timeframe_calculator = M::mock(IComputeTimeframes::class);
        $semantic_timeframe         = M::mock(
            SemanticTimeframe::class,
            ['getTimeframeCalculator' => $this->timeframe_calculator]
        );
        $semantic_timeframe_builder = M::mock(SemanticTimeframeBuilder::class, ['getSemantic' => $semantic_timeframe]);

        $this->milestone_factory = new \Planning_MilestoneFactory(
            $this->planning_factory,
            $this->artifact_factory,
            M::spy(\Tracker_FormElementFactory::class),
            M::mock(\AgileDashboard_Milestone_MilestoneStatusCounter::class),
            M::mock(\PlanningPermissionsManager::class),
            $this->milestone_dao,
            $semantic_timeframe_builder,
            new NullLogger(),
            M::mock(MilestoneBurndownFieldChecker::class)
        );

        $this->user         = UserTestBuilder::aUser()->build();
        $this->project      = ProjectTestBuilder::aProject()->build();
        $this->top_planning = PlanningBuilder::aPlanning(self::PROJECT_ID)->withName('Release Planning')->build();

        $this->top_milestone_request = new TopMilestoneRequest(
            $this->user,
            $this->project,
            50,
            0,
            'asc',
            FilteringQuery::fromStatusQuery(new StatusAll())
        );

        $parent_milestone = new \Planning_ArtifactMilestone(
            $this->project,
            $this->top_planning,
            ArtifactTestBuilder::anArtifact(121)->build(),
        );

        $this->sub_milestone_request = new SubMilestoneRequest(
            $this->user,
            $parent_milestone,
            50,
            0,
            'asc',
            new StatusAll()
        );

        $this->sub_milestone_tracker  = TrackerTestBuilder::aTracker()->withId(17)->build();
        $reference_milestone_artifact = ArtifactTestBuilder::anArtifact(121)
            ->inTracker($this->sub_milestone_tracker)
            ->build();

        $this->sub_planning        = PlanningBuilder::aPlanning(self::PROJECT_ID)->withName('Sprint Planning')->build();
        $this->reference_milestone = new \Planning_ArtifactMilestone(
            $this->project,
            $this->sub_planning,
            $reference_milestone_artifact,
        );

        $this->sibling_milestone_request = new SiblingMilestoneRequest(
            $this->user,
            $this->reference_milestone,
            50,
            0,
            new StatusOpen()
        );
    }

    private function getTopMilestones(): \Tuleap\AgileDashboard\Milestone\PaginatedMilestones
    {
        $this->planning_factory->shouldReceive('getVirtualTopPlanning')->andReturn($this->top_planning);

        return $this->milestone_factory->getPaginatedTopMilestones($this->top_milestone_request);
    }

    public function testItReturnsEmptyWhenNoMilestones(): void
    {
        $this->top_planning = PlanningBuilder::aPlanning(self::PROJECT_ID)
            ->withBadConfigurationAndNoMilestoneTracker()
            ->build();

        $milestones = $this->getTopMilestones();

        $this->assertSame(0, $milestones->getTotalSize());
        $this->assertEmpty($milestones->getMilestones());
    }

    public function testItReturnsMilestonesFilteredByStatus(): void
    {
        $milestone_tracker  = TrackerTestBuilder::aTracker()->withId(15)->build();
        $this->top_planning = PlanningBuilder::aPlanning(self::PROJECT_ID)
            ->withMilestoneTracker($milestone_tracker)
            ->build();
        $this->milestone_dao->shouldReceive('searchPaginatedTopMilestones')
            ->once()
            ->with(15, $this->top_milestone_request)
            ->andReturn(
                \TestHelper::arrayToDar(
                    ['id' => 24],
                    ['id' => 25]
                )
            );
        $this->milestone_dao->shouldReceive('foundRows')
            ->once()
            ->andReturn(2);

        $first_artifact  = $this->anArtifact(24, $milestone_tracker);
        $second_artifact = $this->anArtifact(25, $milestone_tracker);

        $this->artifact_factory->shouldReceive('getInstanceFromRow')
            ->andReturn($first_artifact, $second_artifact);
        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')
            ->andReturn($this->top_planning);
        $this->timeframe_calculator->shouldReceive('buildDatePeriodWithoutWeekendForChangeset')
            ->andReturn(DatePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $milestones = $this->getTopMilestones();

        $this->assertSame(2, $milestones->getTotalSize());
        $first_milestone = $milestones->getMilestones()[0];
        $this->assertSame(24, $first_milestone->getArtifactId());
        $second_milestone = $milestones->getMilestones()[1];
        $this->assertSame(25, $second_milestone->getArtifactId());
    }

    private function getSubMilestones(): \Tuleap\AgileDashboard\Milestone\PaginatedMilestones
    {
        $this->milestone_dao->shouldReceive('searchPaginatedSubMilestones')->andReturn(\TestHelper::emptyDar());
        $this->milestone_dao->shouldReceive('foundRows')->andReturn(0);

        return $this->milestone_factory->getPaginatedSubMilestones($this->sub_milestone_request);
    }

    public function testItReturnsEmptyWhenNoSubMilestones(): void
    {
        $sub_milestones = $this->getSubMilestones();

        $this->assertSame(0, $sub_milestones->getTotalSize());
        $this->assertEmpty($sub_milestones->getMilestones());
    }

    public function testItReturnsSubMilestonesFilteredByStatus(): void
    {
        $this->milestone_dao->shouldReceive('searchPaginatedSubMilestones')
            ->once()
            ->with(121, $this->sub_milestone_request)
            ->andReturn(
                \TestHelper::arrayToDar(
                    ['id' => 138],
                    ['id' => 139]
                )
            );
        $this->milestone_dao->shouldReceive('foundRows')
            ->once()
            ->andReturn(2);

        $first_artifact  = $this->anArtifact(138, $this->sub_milestone_tracker);
        $second_artifact = $this->anArtifact(139, $this->sub_milestone_tracker);

        $this->artifact_factory->shouldReceive('getInstanceFromRow')
            ->andReturn($first_artifact, $second_artifact);
        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')
            ->with($this->sub_milestone_tracker)
            ->andReturn($this->sub_planning);
        $this->timeframe_calculator->shouldReceive('buildDatePeriodWithoutWeekendForChangeset')
            ->andReturn(DatePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $sub_milestones = $this->getSubMilestones();

        $this->assertSame(2, $sub_milestones->getTotalSize());
        $first_milestone = $sub_milestones->getMilestones()[0];
        $this->assertSame(138, $first_milestone->getArtifactId());
        $second_milestone = $sub_milestones->getMilestones()[1];
        $this->assertSame(139, $second_milestone->getArtifactId());
    }

    private function getSiblingMilestones(): \Tuleap\AgileDashboard\Milestone\PaginatedMilestones
    {
        return $this->milestone_factory->getPaginatedSiblingMilestones($this->sibling_milestone_request);
    }

    public function testItReturnsEmptyWhenNoSiblingTopMilestones(): void
    {
        $this->milestone_dao->shouldReceive('searchPaginatedSiblingTopMilestones')->andReturn(\TestHelper::emptyDar());
        $this->milestone_dao->shouldReceive('foundRows')->andReturn(0);

        $sibling_milestones = $this->getSiblingMilestones();

        $this->assertSame(0, $sibling_milestones->getTotalSize());
        $this->assertEmpty($sibling_milestones->getMilestones());
    }

    public function testItReturnsSiblingTopMilestonesFilteredByStatus(): void
    {
        $top_milestone_tracker_id        = 69;
        $top_milestone_tracker           = TrackerTestBuilder::aTracker()->withId($top_milestone_tracker_id)->build();
        $reference_milestone_artifact    = ArtifactTestBuilder::anArtifact(93)
            ->inTracker($top_milestone_tracker)
            ->build();
        $this->reference_milestone       = new \Planning_ArtifactMilestone(
            $this->project,
            $this->top_planning,
            $reference_milestone_artifact,
        );
        $this->sibling_milestone_request = new SiblingMilestoneRequest(
            $this->user,
            $this->reference_milestone,
            50,
            0,
            new StatusOpen()
        );

        $this->milestone_dao->shouldReceive('searchPaginatedSiblingTopMilestones')
            ->once()
            ->with(93, $top_milestone_tracker_id, $this->sibling_milestone_request)
            ->andReturn(
                \TestHelper::arrayToDar(
                    ['id' => 138],
                    ['id' => 139]
                )
            );
        $this->milestone_dao->shouldReceive('foundRows')
            ->once()
            ->andReturn(2);

        $first_artifact  = $this->anArtifact(138, $top_milestone_tracker);
        $second_artifact = $this->anArtifact(139, $top_milestone_tracker);

        $this->artifact_factory->shouldReceive('getInstanceFromRow')
            ->andReturn($first_artifact, $second_artifact);
        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')
            ->with($top_milestone_tracker)
            ->andReturn($this->top_planning);
        $this->timeframe_calculator->shouldReceive('buildDatePeriodWithoutWeekendForChangeset')
            ->andReturn(DatePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $sibling_milestones = $this->getSiblingMilestones();

        $this->assertSame(2, $sibling_milestones->getTotalSize());
        $first_milestone = $sibling_milestones->getMilestones()[0];
        $this->assertSame(138, $first_milestone->getArtifactId());
        $second_milestone = $sibling_milestones->getMilestones()[1];
        $this->assertSame(139, $second_milestone->getArtifactId());
    }

    public function testItReturnsEmptyWhenNoSiblingSubMilestones(): void
    {
        $parent_milestone = new \Planning_ArtifactMilestone(
            $this->project,
            $this->top_planning,
            ArtifactTestBuilder::anArtifact(462)->build(),
        );
        $this->reference_milestone->setAncestors([$parent_milestone]);

        $this->milestone_dao->shouldReceive('searchPaginatedSiblingMilestones')->andReturn(\TestHelper::emptyDar());
        $this->milestone_dao->shouldReceive('foundRows')->andReturn(0);

        $sibling_milestones = $this->getSiblingMilestones();

        $this->assertSame(0, $sibling_milestones->getTotalSize());
        $this->assertEmpty($sibling_milestones->getMilestones());
    }

    public function testItReturnsSiblingSubMilestonesFilteredByStatus(): void
    {
        $parent_milestone = new \Planning_ArtifactMilestone(
            $this->project,
            $this->top_planning,
            ArtifactTestBuilder::anArtifact(462)->build(),
        );
        $this->reference_milestone->setAncestors([$parent_milestone]);

        $this->milestone_dao->shouldReceive('searchPaginatedSiblingMilestones')
            ->once()
            ->with(121, $this->sibling_milestone_request)
            ->andReturn(
                \TestHelper::arrayToDar(
                    ['id' => 138],
                    ['id' => 139]
                )
            );
        $this->milestone_dao->shouldReceive('foundRows')
            ->once()
            ->andReturn(2);

        $first_artifact  = $this->anArtifact(138, $this->sub_milestone_tracker);
        $second_artifact = $this->anArtifact(139, $this->sub_milestone_tracker);

        $this->artifact_factory->shouldReceive('getInstanceFromRow')
            ->andReturn($first_artifact, $second_artifact);
        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')
            ->with($this->sub_milestone_tracker)
            ->andReturn($this->sub_planning);
        $this->timeframe_calculator->shouldReceive('buildDatePeriodWithoutWeekendForChangeset')
            ->andReturn(DatePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $sibling_milestones = $this->getSiblingMilestones();

        $this->assertSame(2, $sibling_milestones->getTotalSize());
        $first_milestone = $sibling_milestones->getMilestones()[0];
        $this->assertSame(138, $first_milestone->getArtifactId());
        $second_milestone = $sibling_milestones->getMilestones()[1];
        $this->assertSame(139, $second_milestone->getArtifactId());
    }

    private function anArtifact(int $artifact_id, \Tracker $milestone_tracker): Artifact
    {
        $changeset = ChangesetTestBuilder::aChangeset('1')->build();

        return ArtifactTestBuilder::anArtifact($artifact_id)
            ->withTitle('title')
            ->inTracker($milestone_tracker)
            ->withChangesets($changeset)
            ->userCanView(true)
            ->withParent(null)
            ->isOpen(true)
            ->withAncestors([])
            ->build();
    }
}
