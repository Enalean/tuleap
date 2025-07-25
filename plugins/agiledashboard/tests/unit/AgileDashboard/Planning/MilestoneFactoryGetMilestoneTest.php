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

use AgileDashboard_Milestone_MilestoneStatusCounter;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Planning_ArtifactMilestone;
use Planning_MilestoneFactory;
use Planning_NoMilestone;
use PlanningFactory;
use PlanningPermissionsManager;
use Psr\Log\NullLogger;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\Milestone\MilestoneDao;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Date\DatePeriodWithOpenDays;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Semantic\Timeframe\BuildSemanticTimeframeStub;
use Tuleap\Tracker\Test\Stub\Semantic\Timeframe\IComputeTimeframesStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MilestoneFactoryGetMilestoneTest extends TestCase
{
    private Tracker_ArtifactFactory&MockObject $artifact_factory;
    private MilestoneDao&MockObject $dao;
    private PFUser $user;
    private PlanningFactory&MockObject $planning_factory;
    private Planning_MilestoneFactory $milestone_factory;

    protected function setUp(): void
    {
        $this->user             = UserTestBuilder::anActiveUser()->build();
        $this->planning_factory = $this->createMock(PlanningFactory::class);
        $this->artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
        $formelement_factory    = $this->createMock(Tracker_FormElementFactory::class);
        $this->dao              = $this->createMock(MilestoneDao::class);
        $formelement_factory->method('getComputableFieldByNameForUser');

        $this->milestone_factory = new Planning_MilestoneFactory(
            $this->planning_factory,
            $this->artifact_factory,
            $formelement_factory,
            $this->createMock(AgileDashboard_Milestone_MilestoneStatusCounter::class),
            $this->createMock(PlanningPermissionsManager::class),
            $this->dao,
            BuildSemanticTimeframeStub::withTimeframeCalculator(
                TrackerTestBuilder::aTracker()->build(),
                IComputeTimeframesStub::fromStartAndDuration(
                    DatePeriodWithOpenDays::buildFromDuration(1, 1),
                    DateFieldBuilder::aDateField(1)->build(),
                    IntegerFieldBuilder::anIntField(2)->build(),
                ),
            ),
            new NullLogger(),
        );
    }

    public function testItCanRetrieveSubMilestonesOfAGivenMilestone(): void
    {
        $sprints_tracker   = TrackerTestBuilder::aTracker()->build();
        $hackfests_tracker = TrackerTestBuilder::aTracker()->build();

        $sprint_planning   = PlanningBuilder::aPlanning(101)->build();
        $hackfest_planning = PlanningBuilder::aPlanning(101)->build();

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

        $this->dao->method('searchSubMilestones')->willReturn([$row_sprint_1, $row_sprint_2, $row_hackfest_2012]);

        $release_1_0   = ArtifactTestBuilder::anArtifact(1)
            ->withTitle('release_1_0')
            ->withChangesets(ChangesetTestBuilder::aChangeset(1)->build())
            ->userCanView($this->user)
            ->withParent(null)
            ->isOpen(true)
            ->withAncestors([])
            ->build();
        $sprint_1      = ArtifactTestBuilder::anArtifact(101)
            ->withTitle('sprint_1')
            ->withChangesets(ChangesetTestBuilder::aChangeset(2)->build())
            ->inTracker($sprints_tracker)
            ->userCanView($this->user)
            ->withParent(null)
            ->isOpen(true)
            ->withAncestors([])
            ->build();
        $sprint_2      = ArtifactTestBuilder::anArtifact(102)
            ->withTitle('sprint_2')
            ->withChangesets(ChangesetTestBuilder::aChangeset(3)->build())
            ->inTracker($sprints_tracker)
            ->userCanView($this->user)
            ->withParent(null)
            ->isOpen(true)
            ->withAncestors([])
            ->build();
        $hackfest_2012 = ArtifactTestBuilder::anArtifact(102)
            ->withTitle('hackfest_2012')
            ->withChangesets(ChangesetTestBuilder::aChangeset(4)->build())
            ->inTracker($hackfests_tracker)
            ->userCanView($this->user)
            ->withParent(null)
            ->isOpen(true)
            ->withAncestors([])
            ->build();
        $matcher       = $this->exactly(3);

        $this->artifact_factory->expects($matcher)->method('getInstanceFromRow')->willReturnCallback(function (...$parameters) use ($matcher, $row_sprint_1, $row_sprint_2, $row_hackfest_2012, $sprint_1, $sprint_2, $hackfest_2012) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($row_sprint_1, $parameters[0]);
                return $sprint_1;
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($row_sprint_2, $parameters[0]);
                return $sprint_2;
            }
            if ($matcher->numberOfInvocations() === 3) {
                self::assertSame($row_hackfest_2012, $parameters[0]);
                return $hackfest_2012;
            }
        });
        $matcher = $this->exactly(3);


        $this->planning_factory->expects($matcher)->method('getPlanningByPlanningTracker')->willReturnCallback(function (...$parameters) use ($matcher, $sprints_tracker, $hackfests_tracker, $sprint_planning, $hackfest_planning) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($this->user, $parameters[0]);
                self::assertSame($sprints_tracker, $parameters[1]);
                return $sprint_planning;
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($this->user, $parameters[0]);
                self::assertSame($sprints_tracker, $parameters[1]);
                return $sprint_planning;
            }
            if ($matcher->numberOfInvocations() === 3) {
                self::assertSame($this->user, $parameters[0]);
                self::assertSame($hackfests_tracker, $parameters[1]);
                return $hackfest_planning;
            }
        });

        $milestone = new Planning_ArtifactMilestone(
            ProjectTestBuilder::aProject()->build(),
            PlanningBuilder::aPlanning(101)->build(),
            $release_1_0,
        );

        $sub_milestones = $this->milestone_factory->getSubMilestones($this->user, $milestone);

        self::assertCount(3, $sub_milestones);
        self::assertInstanceOf(Planning_ArtifactMilestone::class, $sub_milestones[0]);
        self::assertInstanceOf(Planning_ArtifactMilestone::class, $sub_milestones[1]);
        self::assertInstanceOf(Planning_ArtifactMilestone::class, $sub_milestones[2]);
        self::assertEquals($sprint_1, $sub_milestones[0]->getArtifact());
        self::assertEquals($sprint_2, $sub_milestones[1]->getArtifact());
        self::assertEquals($hackfest_2012, $sub_milestones[2]->getArtifact());
    }

    public function testItBuildsBareMilestoneFromAnArtifact(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $tracker = TrackerTestBuilder::aTracker()
            ->withId(1)
            ->withProject($project)
            ->build();

        $artifact = ArtifactTestBuilder::anArtifact(100)
            ->withTitle('release_1_0')
            ->withChangesets(ChangesetTestBuilder::aChangeset(1)->build())
            ->inTracker($tracker)
            ->userCanView($this->user)
            ->withParent(null)
            ->isOpen(true)
            ->withAncestors([])
            ->build();

        $this->artifact_factory->expects($this->once())->method('getArtifactById')
            ->with($artifact->getId())
            ->willReturn($artifact);

        $this->planning_factory->method('getPlanning')->willReturn(PlanningBuilder::aPlanning(101)->build());

        $milestone = $this->milestone_factory->getBareMilestone(
            $this->user,
            $project,
            1,
            $artifact->getId()
        );

        self::assertEquals($artifact, $milestone->getArtifact());
    }

    public function testItReturnsNoMilestoneWhenThereIsNoArtifact(): void
    {
        $project     = ProjectTestBuilder::aProject()->build();
        $artifact_id = 101;
        $this->artifact_factory->expects($this->once())->method('getArtifactById')
            ->with($artifact_id)
            ->willReturn(null);

        $this->planning_factory->method('getPlanning')->willReturn(PlanningBuilder::aPlanning(101)->build());

        $milestone = $this->milestone_factory->getBareMilestone($this->user, $project, 1, $artifact_id);

        self::assertInstanceOf(Planning_NoMilestone::class, $milestone);
    }

    public function testItCanSetMilestonesWithAHierarchyDepthGreaterThan2(): void
    {
        $depth3_artifact = $this->createMock(Artifact::class);
        $depth3_artifact->method('getId')->willReturn(3);
        $depth3_artifact->method('getUniqueLinkedArtifacts')->willReturn([]);

        $depth2_artifact = $this->createMock(Artifact::class);
        $depth2_artifact->method('getId')->willReturn(2);
        $depth2_artifact->method('getUniqueLinkedArtifacts')->willReturn([$depth3_artifact]);

        $depth1_artifact = $this->createMock(Artifact::class);
        $depth1_artifact->method('getId')->willReturn(1);
        $depth1_artifact->method('getUniqueLinkedArtifacts')->willReturn([$depth2_artifact]);

        $root_artifact = $this->createMock(Artifact::class);
        $root_artifact->method('getId')->willReturn(100);
        $root_artifact->method('getUniqueLinkedArtifacts')->willReturn([$depth1_artifact]);

        $tree_node = $this->milestone_factory->getPlannedArtifacts($this->user, $root_artifact);
        self::assertTrue($tree_node->hasChildren());
        $tree_node1 = $tree_node->getChild(0);
        self::assertTrue($tree_node1->hasChildren());
        $tree_node2 = $tree_node1->getChild(0);
        self::assertTrue($tree_node2->hasChildren());
        $tree_node3 = $tree_node2->getChild(0);
        self::assertEquals(3, $tree_node3->getId());
    }

    public function testItAddsTheArtifactsToTheRootNode(): void
    {
        $root_aid = 100;

        $root_artifact = $this->createMock(Artifact::class);
        $root_artifact->method('getId')->willReturn($root_aid);
        $root_artifact->method('getUniqueLinkedArtifacts')->willReturn([]);

        $root_node = $this->milestone_factory->getPlannedArtifacts($this->user, $root_artifact);

        $root_note_data = $root_node->getObject();
        self::assertEquals($root_aid, $root_node->getId());
        self::assertEquals($root_artifact, $root_note_data);
    }

    public function testItAddsTheArtifactsToTheChildNodes(): void
    {
        $root_aid = 100;

        $depth1_artifact = $this->createMock(Artifact::class);
        $depth1_artifact->method('getId')->willReturn(9999);
        $depth1_artifact->method('getUniqueLinkedArtifacts')->willReturn([]);

        $root_artifact = $this->createMock(Artifact::class);
        $root_artifact->method('getId')->willReturn($root_aid);
        $root_artifact->method('getUniqueLinkedArtifacts')->willReturn([$depth1_artifact]);

        $root_node = $this->milestone_factory->getPlannedArtifacts($this->user, $root_artifact);

        $child_node      = $root_node->getChild(0);
        $child_node_data = $child_node->getObject();
        self::assertEquals(9999, $child_node->getId());
        self::assertEquals($depth1_artifact, $child_node_data);
    }
}
