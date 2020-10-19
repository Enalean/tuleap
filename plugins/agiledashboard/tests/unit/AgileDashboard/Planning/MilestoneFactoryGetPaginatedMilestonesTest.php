<?php
/*
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
use PHPUnit\Framework\TestCase;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusAll;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusOpen;
use Tuleap\AgileDashboard\Milestone\Request\FilteringQuery;
use Tuleap\AgileDashboard\Milestone\Request\SiblingMilestoneRequest;
use Tuleap\AgileDashboard\Milestone\Request\SubMilestoneRequest;
use Tuleap\AgileDashboard\Milestone\Request\TopMilestoneRequest;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;
use Tuleap\Tracker\TrackerColor;

final class MilestoneFactoryGetPaginatedMilestonesTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Planning_MilestoneFactory
     */
    private $milestone_factory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\PlanningFactory
     */
    private $planning_factory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ScrumForMonoMilestoneChecker
     */
    private $mono_milestone_checker;
    /**
     * @var \AgileDashboard_Milestone_MilestoneDao|M\LegacyMockInterface|M\MockInterface
     */
    private $milestone_dao;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|TimeframeBuilder
     */
    private $timeframe_builder;

    protected function setUp(): void
    {
        $this->planning_factory       = M::mock(\PlanningFactory::class);
        $this->artifact_factory       = M::mock(\Tracker_ArtifactFactory::class);
        $this->timeframe_builder      = M::mock(TimeframeBuilder::class);
        $this->mono_milestone_checker = M::mock(ScrumForMonoMilestoneChecker::class);
        $this->milestone_dao          = M::mock(\AgileDashboard_Milestone_MilestoneDao::class);

        $this->milestone_factory = new \Planning_MilestoneFactory(
            $this->planning_factory,
            $this->artifact_factory,
            M::spy(\Tracker_FormElementFactory::class),
            M::mock(\AgileDashboard_Milestone_MilestoneStatusCounter::class),
            M::mock(\PlanningPermissionsManager::class),
            $this->milestone_dao,
            $this->mono_milestone_checker,
            $this->timeframe_builder,
            M::mock(MilestoneBurndownFieldChecker::class)
        );
    }

    public function testItReturnsEmptyWhenNoMilestones(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $project = \Project::buildForTest();
        $request = new TopMilestoneRequest(
            $user,
            $project,
            50,
            0,
            'asc',
            FilteringQuery::fromStatusQuery(new StatusAll())
        );

        $planning = new \Planning(1, 'Release Planning', 101, 'Irrelevant', 'Irrelevant');
        $this->planning_factory->shouldReceive('getVirtualTopPlanning')->andReturn($planning);

        $milestones = $this->milestone_factory->getPaginatedTopMilestones($request);

        $this->assertSame(0, $milestones->getTotalSize());
        $this->assertEmpty($milestones->getMilestones());
    }

    public function testItReturnsMilestonesFilteredByStatus(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $project = \Project::buildForTest();
        $request = new TopMilestoneRequest(
            $user,
            $project,
            50,
            0,
            'asc',
            FilteringQuery::fromStatusQuery(new StatusAll())
        );

        $planning          = new \Planning(1, 'Release Planning', 101, 'Irrelevant', 'Irrelevant');
        $milestone_tracker = $this->buildTestTracker(15);
        $planning->setPlanningTracker($milestone_tracker);
        $this->planning_factory->shouldReceive('getVirtualTopPlanning')->andReturn($planning);
        $this->mono_milestone_checker->shouldReceive('isMonoMilestoneEnabled')->andReturnFalse();
        $this->milestone_dao->shouldReceive('searchPaginatedTopMilestones')
            ->once()
            ->with(15, $request)
            ->andReturn(
                \TestHelper::arrayToDar(
                    ['id' => 24],
                    ['id' => 25]
                )
            );
        $this->milestone_dao->shouldReceive('foundRows')
            ->once()
            ->andReturn(2);

        $first_artifact  = $this->mockArtifact(24, $milestone_tracker);
        $second_artifact = $this->mockArtifact(25, $milestone_tracker);

        $this->artifact_factory->shouldReceive('getInstanceFromRow')
            ->andReturn($first_artifact, $second_artifact);
        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')
            ->andReturn($planning);
        $this->timeframe_builder->shouldReceive('buildTimePeriodWithoutWeekendForArtifact')
            ->andReturn(\TimePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $milestones = $this->milestone_factory->getPaginatedTopMilestones($request);

        $this->assertSame(2, $milestones->getTotalSize());
        $first_milestone = $milestones->getMilestones()[0];
        $this->assertSame(24, $first_milestone->getArtifactId());
        $second_milestone = $milestones->getMilestones()[1];
        $this->assertSame(25, $second_milestone->getArtifactId());
    }

    public function testItReturnsEmptyWhenNoSubMilestones(): void
    {
        $user        = UserTestBuilder::aUser()->build();
        $top_planning = new \Planning(1, 'Release Planning', 101, 'Irrelevant', 'Irrelevant');
        $parent_milestone_artifact = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $parent_milestone_artifact->shouldReceive('getId')->andReturn(121);
        $parent_milestone = new \Planning_ArtifactMilestone(\Project::buildForTest(), $top_planning, $parent_milestone_artifact, $this->mono_milestone_checker);
        $request =  new SubMilestoneRequest($user, $parent_milestone, 50, 0, 'asc', new StatusAll());

        $this->milestone_dao->shouldReceive('searchPaginatedSubMilestones')->andReturn(\TestHelper::emptyDar());
        $this->milestone_dao->shouldReceive('foundRows')->andReturn(0);

        $sub_milestones = $this->milestone_factory->getPaginatedSubMilestones($request);

        $this->assertSame(0, $sub_milestones->getTotalSize());
        $this->assertEmpty($sub_milestones->getMilestones());
    }

    public function testItReturnsSubMilestonesFilteredByStatus(): void
    {
        $user        = UserTestBuilder::aUser()->build();
        $top_planning = new \Planning(1, 'Release Planning', 101, 'Irrelevant', 'Irrelevant');
        $sub_planning = new \Planning(2, 'Sprint Planning', 101, 'Irrelevant', 'Irrelevant');
        $parent_milestone_artifact = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $parent_milestone_artifact->shouldReceive('getId')->andReturn(121);
        $parent_milestone = new \Planning_ArtifactMilestone(\Project::buildForTest(), $top_planning, $parent_milestone_artifact, $this->mono_milestone_checker);
        $request =  new SubMilestoneRequest($user, $parent_milestone, 50, 0, 'asc', new StatusAll());
        $sub_milestone_tracker = $this->buildTestTracker(17);

        $this->milestone_dao->shouldReceive('searchPaginatedSubMilestones')
            ->once()
            ->with(121, $request)
            ->andReturn(
                \TestHelper::arrayToDar(
                    ['id' => 138],
                    ['id' => 139]
                )
            );
        $this->milestone_dao->shouldReceive('foundRows')
            ->once()
            ->andReturn(2);

        $first_artifact  = $this->mockArtifact(138, $sub_milestone_tracker);
        $second_artifact = $this->mockArtifact(139, $sub_milestone_tracker);

        $this->artifact_factory->shouldReceive('getInstanceFromRow')
            ->andReturn($first_artifact, $second_artifact);
        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')
            ->with($sub_milestone_tracker)
            ->andReturn($sub_planning);
        $this->timeframe_builder->shouldReceive('buildTimePeriodWithoutWeekendForArtifact')
            ->andReturn(\TimePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $sub_milestones = $this->milestone_factory->getPaginatedSubMilestones($request);

        $this->assertSame(2, $sub_milestones->getTotalSize());
        $first_milestone = $sub_milestones->getMilestones()[0];
        $this->assertSame(138, $first_milestone->getArtifactId());
        $second_milestone = $sub_milestones->getMilestones()[1];
        $this->assertSame(139, $second_milestone->getArtifactId());
    }

    public function testItReturnsEmptyWhenNoSiblingTopMilestones(): void
    {
        $user                         = UserTestBuilder::aUser()->build();
        $planning                     = new \Planning(1, 'Release Planning', 101, 'Irrelevant', 'Irrelevant');
        $reference_milestone_artifact = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $reference_milestone_artifact->shouldReceive('getId')->andReturn(121);
        $reference_milestone_artifact->shouldReceive('getTrackerId')->andReturn(17);
        $reference_milestone = new \Planning_ArtifactMilestone(
            \Project::buildForTest(),
            $planning,
            $reference_milestone_artifact,
            $this->mono_milestone_checker
        );
        $request             = new SiblingMilestoneRequest($user, $reference_milestone, 50, 0, new StatusOpen());

        $this->milestone_dao->shouldReceive('searchPaginatedSiblingTopMilestones')->andReturn(\TestHelper::emptyDar());
        $this->milestone_dao->shouldReceive('foundRows')->andReturn(0);

        $sibling_milestones = $this->milestone_factory->getPaginatedSiblingMilestones($request);

        $this->assertSame(0, $sibling_milestones->getTotalSize());
        $this->assertEmpty($sibling_milestones->getMilestones());
    }

    public function testItReturnsSiblingTopMilestonesFilteredByStatus(): void
    {
        $user                         = UserTestBuilder::aUser()->build();
        $planning                     = new \Planning(1, 'Release Planning', 101, 'Irrelevant', 'Irrelevant');
        $reference_milestone_artifact = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $reference_milestone_artifact->shouldReceive('getId')->andReturn(121);
        $sub_milestone_tracker = $this->buildTestTracker(17);
        $reference_milestone_artifact->shouldReceive('getTrackerId')->andReturn(17);
        $reference_milestone = new \Planning_ArtifactMilestone(
            \Project::buildForTest(),
            $planning,
            $reference_milestone_artifact,
            $this->mono_milestone_checker
        );
        $request             = new SiblingMilestoneRequest($user, $reference_milestone, 50, 0, new StatusOpen());

        $this->milestone_dao->shouldReceive('searchPaginatedSiblingTopMilestones')
            ->once()
            ->with(121, 17, $request)
            ->andReturn(
                \TestHelper::arrayToDar(
                    ['id' => 138],
                    ['id' => 139]
                )
            );
        $this->milestone_dao->shouldReceive('foundRows')
            ->once()
            ->andReturn(2);

        $first_artifact  = $this->mockArtifact(138, $sub_milestone_tracker);
        $second_artifact = $this->mockArtifact(139, $sub_milestone_tracker);

        $this->artifact_factory->shouldReceive('getInstanceFromRow')
            ->andReturn($first_artifact, $second_artifact);
        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')
            ->with($sub_milestone_tracker)
            ->andReturn($planning);
        $this->timeframe_builder->shouldReceive('buildTimePeriodWithoutWeekendForArtifact')
            ->andReturn(\TimePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $sibling_milestones = $this->milestone_factory->getPaginatedSiblingMilestones($request);

        $this->assertSame(2, $sibling_milestones->getTotalSize());
        $first_milestone = $sibling_milestones->getMilestones()[0];
        $this->assertSame(138, $first_milestone->getArtifactId());
        $second_milestone = $sibling_milestones->getMilestones()[1];
        $this->assertSame(139, $second_milestone->getArtifactId());
    }

    public function testItReturnsEmptyWhenNoSiblingSubMilestones(): void
    {
        $user                         = UserTestBuilder::aUser()->build();
        $project                      = \Project::buildForTest();
        $top_planning                 = new \Planning(1, 'Release Planning', 101, 'Irrelevant', 'Irrelevant');
        $sub_planning                 = new \Planning(2, 'Sprint Planning', 101, 'Irrelevant', 'Irrelevant');
        $reference_milestone_artifact = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $reference_milestone_artifact->shouldReceive('getId')->andReturn(121);
        $reference_milestone_artifact->shouldReceive('getTrackerId')->andReturn(17);
        $reference_milestone = new \Planning_ArtifactMilestone(
            $project,
            $sub_planning,
            $reference_milestone_artifact,
            $this->mono_milestone_checker
        );
        $parent_milestone    = new \Planning_ArtifactMilestone(
            $project,
            $top_planning,
            M::mock(\Tuleap\Tracker\Artifact\Artifact::class),
            $this->mono_milestone_checker
        );
        $reference_milestone->setAncestors([$parent_milestone]);
        $request = new SiblingMilestoneRequest($user, $reference_milestone, 50, 0, new StatusOpen());

        $this->milestone_dao->shouldReceive('searchPaginatedSiblingMilestones')->andReturn(\TestHelper::emptyDar());
        $this->milestone_dao->shouldReceive('foundRows')->andReturn(0);

        $sibling_milestones = $this->milestone_factory->getPaginatedSiblingMilestones($request);

        $this->assertSame(0, $sibling_milestones->getTotalSize());
        $this->assertEmpty($sibling_milestones->getMilestones());
    }

    public function testItReturnsSiblingSubMilestonesFilteredByStatus(): void
    {
        $user                         = UserTestBuilder::aUser()->build();
        $project                      = \Project::buildForTest();
        $top_planning                 = new \Planning(1, 'Release Planning', 101, 'Irrelevant', 'Irrelevant');
        $sub_planning                 = new \Planning(2, 'Sprint Planning', 101, 'Irrelevant', 'Irrelevant');
        $reference_milestone_artifact = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $reference_milestone_artifact->shouldReceive('getId')->andReturn(121);
        $sub_milestone_tracker = $this->buildTestTracker(17);
        $reference_milestone_artifact->shouldReceive('getTrackerId')->andReturn(17);
        $reference_milestone = new \Planning_ArtifactMilestone(
            $project,
            $sub_planning,
            $reference_milestone_artifact,
            $this->mono_milestone_checker
        );
        $parent_milestone    = new \Planning_ArtifactMilestone(
            $project,
            $top_planning,
            M::mock(\Tuleap\Tracker\Artifact\Artifact::class),
            $this->mono_milestone_checker
        );
        $reference_milestone->setAncestors([$parent_milestone]);
        $request             = new SiblingMilestoneRequest($user, $reference_milestone, 50, 0, new StatusOpen());

        $this->milestone_dao->shouldReceive('searchPaginatedSiblingMilestones')
            ->once()
            ->with(121, $request)
            ->andReturn(
                \TestHelper::arrayToDar(
                    ['id' => 138],
                    ['id' => 139]
                )
            );
        $this->milestone_dao->shouldReceive('foundRows')
            ->once()
            ->andReturn(2);

        $first_artifact  = $this->mockArtifact(138, $sub_milestone_tracker);
        $second_artifact = $this->mockArtifact(139, $sub_milestone_tracker);

        $this->artifact_factory->shouldReceive('getInstanceFromRow')
            ->andReturn($first_artifact, $second_artifact);
        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')
            ->with($sub_milestone_tracker)
            ->andReturn($sub_planning);
        $this->timeframe_builder->shouldReceive('buildTimePeriodWithoutWeekendForArtifact')
            ->andReturn(\TimePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $sibling_milestones = $this->milestone_factory->getPaginatedSiblingMilestones($request);

        $this->assertSame(2, $sibling_milestones->getTotalSize());
        $first_milestone = $sibling_milestones->getMilestones()[0];
        $this->assertSame(138, $first_milestone->getArtifactId());
        $second_milestone = $sibling_milestones->getMilestones()[1];
        $this->assertSame(139, $second_milestone->getArtifactId());
    }

    private function buildTestTracker(int $tracker_id): \Tracker
    {
        return new \Tracker(
            $tracker_id,
            null,
            'Irrelevant',
            'Irrelevant',
            'irrelevant',
            false,
            null,
            null,
            null,
            null,
            true,
            false,
            \Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            TrackerColor::default(),
            false
        );
    }

    /**
     * @return M\LegacyMockInterface|M\MockInterface|\Tuleap\Tracker\Artifact\Artifact
     */
    private function mockArtifact(int $artifact_id, \Tracker $milestone_tracker)
    {
        $first_artifact = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $first_artifact->shouldReceive('getId')->andReturn($artifact_id);
        $first_artifact->shouldReceive('userCanView')->andReturnTrue();
        $first_artifact->shouldReceive('getTracker')->andReturn($milestone_tracker);
        $first_artifact->shouldReceive('getAllAncestors')->andReturn([]);
        return $first_artifact;
    }
}
