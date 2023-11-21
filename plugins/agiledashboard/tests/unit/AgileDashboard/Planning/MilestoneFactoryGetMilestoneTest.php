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
use Planning_ArtifactMilestone;
use Planning_MilestoneFactory;
use Planning_NoMilestone;
use PlanningFactory;
use PlanningPermissionsManager;
use Project;
use Psr\Log\NullLogger;
use TestHelper;
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

final class MilestoneFactoryGetMilestoneTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \AgileDashboard_Milestone_MilestoneDao|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $dao;
    private PFUser $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;
    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|IComputeTimeframes
     */
    private $timeframe_calculator;
    private NullLogger $logger;

    protected function setUp(): void
    {
        $this->user                   = UserTestBuilder::anActiveUser()->build();
        $this->planning_factory       = Mockery::spy(PlanningFactory::class);
        $this->artifact_factory       = Mockery::spy(Tracker_ArtifactFactory::class);
        $formelement_factory          = Mockery::spy(Tracker_FormElementFactory::class);
        $status_counter               = Mockery::spy(AgileDashboard_Milestone_MilestoneStatusCounter::class);
        $planning_permissions_manager = Mockery::spy(PlanningPermissionsManager::class);
        $this->dao                    = Mockery::spy(AgileDashboard_Milestone_MilestoneDao::class);
        $mono_milestone_checker       = Mockery::spy(ScrumForMonoMilestoneChecker::class);
        $milestone_burndown_cheker    = Mockery::spy(MilestoneBurndownFieldChecker::class);
        $this->timeframe_calculator   = Mockery::mock(IComputeTimeframes::class);
        $semantic_timeframe           = Mockery::mock(SemanticTimeframe::class, ['getTimeframeCalculator' => $this->timeframe_calculator]);
        $semantic_timeframe_builder   = Mockery::mock(SemanticTimeframeBuilder::class, ['getSemantic' => $semantic_timeframe]);
        $this->logger                 = new NullLogger();

        $this->milestone_factory = new Planning_MilestoneFactory(
            $this->planning_factory,
            $this->artifact_factory,
            $formelement_factory,
            $status_counter,
            $planning_permissions_manager,
            $this->dao,
            $mono_milestone_checker,
            $semantic_timeframe_builder,
            $this->logger,
            $milestone_burndown_cheker
        );
    }

    public function testItCanRetrieveSubMilestonesOfAGivenMilestone(): void
    {
        $sprints_tracker   = Mockery::spy(Tracker::class);
        $hackfests_tracker = Mockery::spy(Tracker::class);

        $sprint_planning   = Mockery::mock(Planning::class);
        $hackfest_planning = Mockery::mock(Planning::class);

        $row_sprint_1      = [
            'id'                       => 1,
            'tracker_id'               => 1,
            'submitted_by'             => 102,
            'submitted_on'             => 12345678,
            'use_artifact_permissions' => true,
        ];
        $row_sprint_2      = [
            'id'                       => 2,
            'tracker_id'               => 1,
            'submitted_by'             => 102,
            'submitted_on'             => 12345678,
            'use_artifact_permissions' => true,
        ];
        $row_hackfest_2012 = [
            'id'                       => 3,
            'tracker_id'               => 1,
            'submitted_by'             => 102,
            'submitted_on'             => 12345678,
            'use_artifact_permissions' => true,
        ];

        $this->dao->shouldReceive('searchSubMilestones')->andReturn(
            TestHelper::arrayToDar($row_sprint_1, $row_sprint_2, $row_hackfest_2012)
        );

        $release_1_0   = ArtifactTestBuilder::anArtifact(1)
            ->withTitle('release_1_0')
            ->withChangesets(ChangesetTestBuilder::aChangeset('1')->build())
            ->userCanView(true)
            ->withParent(null)
            ->isOpen(true)
            ->withAncestors([])
            ->build();
        $sprint_1      = ArtifactTestBuilder::anArtifact(101)
            ->withTitle('sprint_1')
            ->withChangesets(ChangesetTestBuilder::aChangeset('2')->build())
            ->inTracker($sprints_tracker)
            ->userCanView(true)
            ->withParent(null)
            ->isOpen(true)
            ->withAncestors([])
            ->build();
        $sprint_2      = ArtifactTestBuilder::anArtifact(102)
            ->withTitle('sprint_2')
            ->withChangesets(ChangesetTestBuilder::aChangeset('3')->build())
            ->inTracker($sprints_tracker)
            ->userCanView(true)
            ->withParent(null)
            ->isOpen(true)
            ->withAncestors([])
            ->build();
        $hackfest_2012 = ArtifactTestBuilder::anArtifact(102)
            ->withTitle('hackfest_2012')
            ->withChangesets(ChangesetTestBuilder::aChangeset('4')->build())
            ->inTracker($hackfests_tracker)
            ->userCanView(true)
            ->withParent(null)
            ->isOpen(true)
            ->withAncestors([])
            ->build();

        $this->artifact_factory->shouldReceive('getInstanceFromRow')->with($row_sprint_1)->andReturn($sprint_1);
        $this->artifact_factory->shouldReceive('getInstanceFromRow')->with($row_sprint_2)->andReturn($sprint_2);
        $this->artifact_factory->shouldReceive('getInstanceFromRow')->with($row_hackfest_2012)->andReturn(
            $hackfest_2012
        );


        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($sprints_tracker)->andReturn(
            $sprint_planning
        );
        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($hackfests_tracker)->andReturn(
            $hackfest_planning
        );

        $this->timeframe_calculator->shouldReceive('buildDatePeriodWithoutWeekendForChangeset')
            ->with($sprint_1->getLastChangeset(), $this->user, $this->logger)
            ->once()
            ->andReturn(DatePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $this->timeframe_calculator->shouldReceive('buildDatePeriodWithoutWeekendForChangeset')
            ->with($sprint_2->getLastChangeset(), $this->user, $this->logger)
            ->once()
            ->andReturn(DatePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $this->timeframe_calculator->shouldReceive('buildDatePeriodWithoutWeekendForChangeset')
            ->with($hackfest_2012->getLastChangeset(), $this->user, $this->logger)
            ->once()
            ->andReturn(DatePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $milestone = Mockery::mock(Planning_ArtifactMilestone::class);
        $milestone->shouldReceive('getArtifact')->andReturn($release_1_0);
        $milestone->shouldReceive('getProject')->andReturn(Mockery::mock(Project::class));

        $sub_milestones = $this->milestone_factory->getSubMilestones($this->user, $milestone);

        $this->assertCount(3, $sub_milestones);
        $this->assertInstanceOf(Planning_ArtifactMilestone::class, $sub_milestones[0]);
        $this->assertInstanceOf(Planning_ArtifactMilestone::class, $sub_milestones[1]);
        $this->assertInstanceOf(Planning_ArtifactMilestone::class, $sub_milestones[2]);
        $this->assertEquals($sprint_1, $sub_milestones[0]->getArtifact());
        $this->assertEquals($sprint_2, $sub_milestones[1]->getArtifact());
        $this->assertEquals($hackfest_2012, $sub_milestones[2]->getArtifact());
    }

    public function testItBuildsBareMilestoneFromAnArtifact(): void
    {
        $project = Mockery::mock(Project::class);
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getProject')->andReturn($project);
        $tracker->shouldReceive('getId')->andReturn(1);

        $artifact = ArtifactTestBuilder::anArtifact(100)
            ->withTitle('release_1_0')
            ->withChangesets(ChangesetTestBuilder::aChangeset('1')->build())
            ->inTracker($tracker)
            ->userCanView(true)
            ->withParent(null)
            ->isOpen(true)
            ->withAncestors([])
            ->build();

        $this->artifact_factory->shouldReceive('getArtifactById')
            ->with($artifact->getId())
            ->andReturn($artifact)
            ->once();

        $this->timeframe_calculator->shouldReceive('buildDatePeriodWithoutWeekendForChangeset')
            ->with($artifact->getLastChangeset(), $this->user, $this->logger)
            ->once()
            ->andReturn(DatePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $this->planning_factory->shouldReceive('getPlanning')->andReturn(Mockery::mock(Planning::class));

        $milestone = $this->milestone_factory->getBareMilestone(
            $this->user,
            $project,
            1,
            $artifact->getId()
        );

        $this->assertEquals($artifact, $milestone->getArtifact());
    }

    public function testItReturnsNoMilestoneWhenThereIsNoArtifact(): void
    {
        $project     = Mockery::mock(Project::class);
        $artifact_id = 101;
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->with($artifact_id)
            ->andReturn(null)
            ->once();

        $this->planning_factory->shouldReceive('getPlanning')->andReturn(Mockery::mock(Planning::class));

        $milestone = $this->milestone_factory->getBareMilestone($this->user, $project, 1, $artifact_id);

        $this->assertInstanceOf(Planning_NoMilestone::class, $milestone);
    }

    public function testItCanSetMilestonesWithAHierarchyDepthGreaterThan2(): void
    {
        $depth3_artifact = Mockery::mock(Artifact::class);
        $depth3_artifact->shouldReceive('getId')->andReturn(3);
        $depth3_artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([]);

        $depth2_artifact = Mockery::mock(Artifact::class);
        $depth2_artifact->shouldReceive('getId')->andReturn(2);
        $depth2_artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([$depth3_artifact]);

        $depth1_artifact = Mockery::mock(Artifact::class);
        $depth1_artifact->shouldReceive('getId')->andReturn(1);
        $depth1_artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([$depth2_artifact]);

        $root_artifact = Mockery::mock(Artifact::class);
        $root_artifact->shouldReceive('getId')->andReturn(100);
        $root_artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([$depth1_artifact]);

        $tree_node = $this->milestone_factory->getPlannedArtifacts($this->user, $root_artifact);
        $this->assertTrue($tree_node->hasChildren());
        $tree_node1 = $tree_node->getChild(0);
        $this->assertTrue($tree_node1->hasChildren());
        $tree_node2 = $tree_node1->getChild(0);
        $this->assertTrue($tree_node2->hasChildren());
        $tree_node3 = $tree_node2->getChild(0);
        $this->assertEquals(3, $tree_node3->getId());
    }

    public function testItAddsTheArtifactsToTheRootNode(): void
    {
        $root_aid = 100;

        $root_artifact = Mockery::mock(Artifact::class);
        $root_artifact->shouldReceive('getId')->andReturn($root_aid);
        $root_artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([]);

        $root_node = $this->milestone_factory->getPlannedArtifacts($this->user, $root_artifact);

        $root_note_data = $root_node->getObject();
        $this->assertEquals($root_aid, $root_node->getId());
        $this->assertEquals($root_artifact, $root_note_data);
    }

    public function testItAddsTheArtifactsToTheChildNodes(): void
    {
        $root_aid = 100;

        $depth1_artifact = Mockery::mock(Artifact::class);
        $depth1_artifact->shouldReceive('getId')->andReturn(9999);
        $depth1_artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([]);

        $root_artifact = Mockery::mock(Artifact::class);
        $root_artifact->shouldReceive('getId')->andReturn($root_aid);
        $root_artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([$depth1_artifact]);

        $root_node = $this->milestone_factory->getPlannedArtifacts($this->user, $root_artifact);

        $child_node      = $root_node->getChild(0);
        $child_node_data = $child_node->getObject();
        $this->assertEquals(9999, $child_node->getId());
        $this->assertEquals($depth1_artifact, $child_node_data);
    }
}
