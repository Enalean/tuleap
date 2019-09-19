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

use Tuleap\AgileDashboard\Planning\MilestoneBurndownFieldChecker;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;

require_once __DIR__ . '/../bootstrap.php';

abstract class Planning_MilestoneBaseTest extends TuleapTestCase
{

    public function anArtifactWithId($id)
    {
        $artifact = aMockArtifact()->withId($id)->build();
        stub($artifact)->getAllAncestors()->returns(array());
        return $artifact;
    }

    public function anArtifactWithIdAndUniqueLinkedArtifacts($id, $linked_artifacts)
    {
        $artifact = aMockArtifact()->withId($id)
                              ->withUniqueLinkedArtifacts($linked_artifacts)
                              ->build();
        stub($artifact)->getAllAncestors()->returns(array());
        return $artifact;
    }
}
abstract class Planning_MilestoneFactory_GetMilestoneBaseTest extends Planning_MilestoneBaseTest
{
    protected $project;
    protected $planning_factory;
    protected $artifact_factory;
    protected $formelement_factory;
    protected $milestone_tracker_id;
    protected $milestone_tracker;
    protected $user;
    protected $request;
    protected $status_counter;

    /**
     * @var \Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker
     */
    protected $mono_milestone_checker;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->project    = \Mockery::spy(\Project::class);
        $this->planning_id = 34;
        $this->artifact_id = 56;

        $this->milestone_tracker_id = 112;
        $this->milestone_tracker    = \Mockery::spy(\Tracker::class);
        stub($this->milestone_tracker)->getId()->returns($this->milestone_tracker_id);
        stub($this->milestone_tracker)->getProject()->returns($this->project);

        $this->user                         = \Mockery::spy(\PFUser::class);
        $this->planning                     = aPlanning()->withId($this->planning_id)->build();
        $this->artifact                     = \Mockery::spy(\Tracker_Artifact::class);
        $this->planning_factory             = \Mockery::spy(\PlanningFactory::class);
        $this->artifact_factory             = \Mockery::spy(\Tracker_ArtifactFactory::class);
        $this->formelement_factory          = \Mockery::spy(\Tracker_FormElementFactory::class);
        $this->tracker_factory              = \Mockery::spy(\TrackerFactory::class);
        $this->status_counter               = \Mockery::spy(\AgileDashboard_Milestone_MilestoneStatusCounter::class);
        $this->planning_permissions_manager = \Mockery::spy(\PlanningPermissionsManager::class);
        $this->dao                          = \Mockery::spy(\AgileDashboard_Milestone_MilestoneDao::class);
        $this->mono_milestone_checker       = \Mockery::spy(\Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker::class);
        $this->timeframe_builder            = \Mockery::mock(TimeframeBuilder::class);
        $this->milestone_burndown_cheker    = \Mockery::spy(\Tuleap\AgileDashboard\Planning\MilestoneBurndownFieldChecker::class);

        $this->milestone_factory            = new Planning_MilestoneFactory(
            $this->planning_factory,
            $this->artifact_factory,
            $this->formelement_factory,
            $this->tracker_factory,
            $this->status_counter,
            $this->planning_permissions_manager,
            $this->dao,
            $this->mono_milestone_checker,
            $this->timeframe_builder,
            $this->milestone_burndown_cheker
        );

        stub($this->artifact)->getUniqueLinkedArtifacts($this->user)->returns(array());
        stub($this->artifact)->getTracker()->returns($this->milestone_tracker);
        stub($this->artifact)->userCanView()->returns(true);
        stub($this->artifact)->getAllAncestors()->returns(array());

        stub($this->planning_factory)->getPlanning($this->planning_id)->returns($this->planning);
    }
}

class Planning_MilestoneFactory_getMilestoneTest extends Planning_MilestoneFactory_GetMilestoneBaseTest
{

    public function itCanRetrieveSubMilestonesOfAGivenMilestone()
    {
        $sprints_tracker   = \Mockery::spy(\Tracker::class);
        $hackfests_tracker = \Mockery::spy(\Tracker::class);

        $sprint_planning   = \Mockery::spy(\Planning::class);
        $hackfest_planning = \Mockery::spy(\Planning::class);

        $release_1_0   = \Mockery::spy(\Tracker_Artifact::class);
        $sprint_1      = aMockArtifact()->withTracker($sprints_tracker)->allUsersCanView()->build();
        $sprint_2      = aMockArtifact()->withTracker($sprints_tracker)->allUsersCanView()->build();
        $hackfest_2012 = aMockArtifact()->withTracker($hackfests_tracker)->allUsersCanView()->build();

        $results = \Mockery::mock(DataAccessResult::class);
        stub($this->dao)->searchSubMilestones()->returns($results);
        stub($results)->instanciateWith()->returns(array($sprint_1, $sprint_2, $hackfest_2012));

        stub($release_1_0)->getAllAncestors()->returns(array());
        stub($sprint_1)->getAllAncestors()->returns(array());
        stub($sprint_2)->getAllAncestors()->returns(array());
        stub($hackfest_2012)->getAllAncestors()->returns(array());

        stub($this->planning_factory)->getPlanningByPlanningTracker($sprints_tracker)->returns($sprint_planning);
        stub($this->planning_factory)->getPlanningByPlanningTracker($hackfests_tracker)->returns($hackfest_planning);

        $this->timeframe_builder->shouldReceive('buildTimePeriodWithoutWeekendForArtifact')
            ->with($sprint_1, $this->user)
            ->once()
            ->andReturn(TimePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $this->timeframe_builder->shouldReceive('buildTimePeriodWithoutWeekendForArtifact')
            ->with($sprint_2, $this->user)
            ->once()
            ->andReturn(TimePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $this->timeframe_builder->shouldReceive('buildTimePeriodWithoutWeekendForArtifact')
            ->with($hackfest_2012, $this->user)
            ->once()
            ->andReturn(TimePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $milestone      = aMilestone()->withArtifact($release_1_0)->build();
        $sub_milestones = $this->milestone_factory->getSubMilestones($this->user, $milestone);

        $this->assertEqual(count($sub_milestones), 3);
        $this->assertIsA($sub_milestones[0], 'Planning_ArtifactMilestone');
        $this->assertIsA($sub_milestones[1], 'Planning_ArtifactMilestone');
        $this->assertIsA($sub_milestones[2], 'Planning_ArtifactMilestone');
        $this->assertEqual($sub_milestones[0]->getArtifact(), $sprint_1);
        $this->assertEqual($sub_milestones[1]->getArtifact(), $sprint_2);
        $this->assertEqual($sub_milestones[2]->getArtifact(), $hackfest_2012);
    }

    public function itBuildsBareMilestoneFromAnArtifact()
    {
        stub($this->artifact_factory)->getArtifactById($this->artifact_id)->returns($this->artifact);

        $this->timeframe_builder->shouldReceive('buildTimePeriodWithoutWeekendForArtifact')
            ->with($this->artifact, $this->user)
            ->once()
            ->andReturn(TimePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $milestone = $this->milestone_factory->getBareMilestone(
            $this->user,
            $this->project,
            $this->planning_id,
            $this->artifact_id
        );

        $this->assertEqual($milestone->getArtifact(), $this->artifact);
    }

    public function itReturnsNoMilestoneWhenThereIsNoArtifact()
    {
        stub($this->artifact_factory)->getArtifactById($this->artifact_id)->returns(null);

        $milestone = $this->milestone_factory->getBareMilestone($this->user, $this->project, $this->planning_id, $this->artifact_id);

        $this->assertIsA($milestone, 'Planning_NoMilestone');
    }

    public function itCanSetMilestonesWithaHierarchyDepthGreaterThan2()
    {
        $artifact_id   = 100;

        $depth3_artifact = $this->anArtifactWithId(3);
        $depth2_artifact = $this->anArtifactWithIdAndUniqueLinkedArtifacts(2, array($depth3_artifact));
        $depth1_artifact = $this->anArtifactWithIdAndUniqueLinkedArtifacts(1, array($depth2_artifact));
        $root_artifact   = $this->anArtifactWithIdAndUniqueLinkedArtifacts($artifact_id, array($depth1_artifact));

        $tree_node = $this->milestone_factory->getPlannedArtifacts($this->user, $root_artifact);
        $this->assertTrue($tree_node->hasChildren());
        $tree_node1 = $tree_node->getChild(0);
        $this->assertTrue($tree_node1->hasChildren());
        $tree_node2 = $tree_node1->getChild(0);
        $this->assertTrue($tree_node2->hasChildren());
        $tree_node3 = $tree_node2->getChild(0);
        $this->assertEqual(3, $tree_node3->getId());
    }

    public function itAddsTheArtifactsToTheRootNode()
    {
        $root_aid   = 100;
        $root_artifact = \Mockery::spy(\Tracker_Artifact::class);

        stub($root_artifact)->getId()->returns($root_aid);
        stub($root_artifact)->getTracker()->returns($this->milestone_tracker);
        stub($root_artifact)->getUniqueLinkedArtifacts()->returns(array());

        $root_node = $this->milestone_factory->getPlannedArtifacts($this->user, $root_artifact);

        $root_note_data = $root_node->getObject();
        $this->assertEqual($root_aid, $root_node->getId());
        $this->assertEqual($root_artifact, $root_note_data);
    }

    public function itAddsTheArtifactsToTheChildNodes()
    {
        $root_aid   = 100;
        $root_artifact = \Mockery::spy(\Tracker_Artifact::class);
        stub($root_artifact)->getId()->returns($root_aid);
        stub($root_artifact)->getTracker()->returns($this->milestone_tracker);
        $depth1_artifact = $this->anArtifactWithId(9999);
        stub($root_artifact)->getUniqueLinkedArtifacts()->returns(array($depth1_artifact));

        $root_node = $this->milestone_factory->getPlannedArtifacts($this->user, $root_artifact);

        $child_node      = $root_node->getChild(0);
        $child_node_data = $child_node->getObject();
        $this->assertEqual(9999, $child_node->getId());
        $this->assertEqual($depth1_artifact, $child_node_data);
    }
}

class MilestoneFactory_GetAllMilestonesTest extends TuleapTestCase
{
    private $project;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->user             = \Mockery::spy(\PFUser::class);
        $this->project          = mockery_stub(\Project::class)->getID()->returns(99);
        $this->planning_tracker = mockery_stub(\Tracker::class)->getProject()->returns($this->project);
        $this->planning_id      = 3333;
        $this->planning         = mockery_stub(\Planning::class)->getPlanningTracker()->returns($this->planning_tracker);

        $this->tracker = Mockery::mock(Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(101);

        $this->form_element_factory = Mockery::spy(Tracker_FormElementFactory::class);
    }

    public function itReturnsAnEmptyArrayWhenAllItemsAreClosed()
    {
        $artifact_factory = mockery_stub(\Tracker_ArtifactFactory::class)->getArtifactsByTrackerIdUserCanView()->returns(array());
        $planning_factory = \Mockery::spy(\PlanningFactory::class);
        $factory          = $this->newMileStoneFactory($planning_factory, $artifact_factory);
        $this->assertEqual(array(), $factory->getAllMilestones($this->user, $this->planning));
    }

    public function itReturnsAsManyMilestonesAsThereAreArtifacts()
    {
        $artifacts = array(
            anArtifact()->withChangesets(array(10,11))->withTracker($this->tracker)->withFormElementFactory($this->form_element_factory)->build(),
            anArtifact()->withChangesets(array(12,13))->withTracker($this->tracker)->withFormElementFactory($this->form_element_factory)->build()
        );

        $artifact_factory = mockery_stub(\Tracker_ArtifactFactory::class)->getArtifactsByTrackerIdUserCanView()->returns($artifacts);
        $planning_factory = \Mockery::spy(\PlanningFactory::class);
        $factory          = $this->newMileStoneFactory($planning_factory, $artifact_factory);
        $this->assertEqual(2, count($factory->getAllMilestones($this->user, $this->planning)));
    }

    public function itReturnsMilestones()
    {
        $changeset01 = Mockery::spy(Tracker_Artifact_Changeset::class);
        $artifact    = Mockery::mock(Tracker_Artifact::class);

        $artifact->shouldReceive('getId')->andReturns(101);
        $artifact->shouldReceive('getLinkedArtifacts')->with($this->user)->andReturns([]);
        $artifact->shouldReceive('getUniqueLinkedArtifacts')->with($this->user)->andReturns(null);
        $artifact->shouldReceive('getLastChangeset')->andReturns($changeset01);

        $artifact_factory = mockery_stub(\Tracker_ArtifactFactory::class)->getArtifactsByTrackerIdUserCanView()->returns(array($artifact));
        $planning_factory = \Mockery::spy(\PlanningFactory::class);
        $factory          = $this->newMileStoneFactory($planning_factory, $artifact_factory);

        $all_milestones = $factory->getAllMilestones($this->user, $this->planning);
        $this->assertEqual($artifact->getId(), $all_milestones[0]->getArtifact()->getId());
    }

    public function itReturnsMilestonesWithPlannedArtifacts()
    {
        $artifact         = anArtifact()->withChangesets(array(10,11))->withTracker($this->tracker)->withFormElementFactory($this->form_element_factory)->build();
        $tracker_id       = 7777777;
        stub($this->planning_tracker)->getId()->returns($tracker_id);
        $planning         = aPlanning()->withPlanningTracker($this->planning_tracker)->build();
        $artifact_factory = mockery_stub(\Tracker_ArtifactFactory::class)->getArtifactsByTrackerIdUserCanView($this->user, $tracker_id)->returns(array($artifact));
        $planning_factory = \Mockery::spy(\PlanningFactory::class);

        $planned_artifacts= new ArtifactNode($artifact);
        $factory          = $this->newMileStoneFactory($planning_factory, $artifact_factory);
        stub($factory)->getPlannedArtifacts()->returns($planned_artifacts);

        $milestone  = new Planning_ArtifactMilestone(
            $this->project,
            $planning,
            $artifact,
            \Mockery::spy(\Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker::class),
            $planned_artifacts
        );
        $milestones = $factory->getAllMilestones($this->user, $planning);
        $this->assertEqual($milestone, $milestones[0]);
    }

    public function itReturnsMilestonesWithoutPlannedArtifacts()
    {
        $artifact         = anArtifact()->withChangesets(array(10,11))->withTracker($this->tracker)->withFormElementFactory($this->form_element_factory)->build();
        $tracker_id       = 7777777;
        stub($this->planning_tracker)->getId()->returns($tracker_id);
        $planning         = aPlanning()->withPlanningTracker($this->planning_tracker)->build();
        $artifact_factory = mockery_stub(\Tracker_ArtifactFactory::class)->getArtifactsByTrackerIdUserCanView($this->user, $tracker_id)->returns(array($artifact));
        $planning_factory = \Mockery::spy(\PlanningFactory::class);

        $planned_artifacts = new ArtifactNode($artifact);
        $factory           = $this->newMileStoneFactory($planning_factory, $artifact_factory);
        stub($factory)->getPlannedArtifacts()->returns($planned_artifacts);

        $milestone  = new Planning_ArtifactMilestone(
            $this->project,
            $planning,
            $artifact,
            \Mockery::spy(\Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker::class),
            null
        );
        $milestones = $factory->getAllMilestonesWithoutPlannedElement($this->user, $planning);
        $this->assertEqual($milestone, $milestones[0]);
    }

    public function newMileStoneFactory($planning_factory, $artifact_factory)
    {
        $factory = \Mockery::mock(\Planning_MilestoneFactory::class, [
            $planning_factory,
            $artifact_factory,
            \Mockery::spy(\Tracker_FormElementFactory::class),
            \Mockery::spy(\TrackerFactory::class),
            \Mockery::spy(\AgileDashboard_Milestone_MilestoneStatusCounter::class),
            \Mockery::spy(\PlanningPermissionsManager::class),
            \Mockery::spy(\AgileDashboard_Milestone_MilestoneDao::class),
            \Mockery::spy(\Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker::class),
            \Mockery::mock(TimeframeBuilder::class),
            \Mockery::mock(MilestoneBurndownFieldChecker::class)
        ])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        return $factory;
    }
}

class MilestoneFactory_PlannedArtifactsTest extends Planning_MilestoneBaseTest
{

    public function itReturnsATreeOfPlanningItems()
    {
        $depth3_artifact  = $this->anArtifactWithId(3);
        $depth2_artifact  = $this->anArtifactWithIdAndUniqueLinkedArtifacts(2, array($depth3_artifact));
        $depth1_artifact  = $this->anArtifactWithIdAndUniqueLinkedArtifacts(1, array($depth2_artifact));
        $root_artifact    = $this->anArtifactWithIdAndUniqueLinkedArtifacts(100, array($depth1_artifact));

        $factory = new Planning_MilestoneFactory(
            \Mockery::spy(\PlanningFactory::class),
            \Mockery::spy(\Tracker_ArtifactFactory::class),
            \Mockery::spy(\Tracker_FormElementFactory::class),
            \Mockery::spy(\TrackerFactory::class),
            \Mockery::spy(\AgileDashboard_Milestone_MilestoneStatusCounter::class),
            \Mockery::spy(\PlanningPermissionsManager::class),
            \Mockery::spy(\AgileDashboard_Milestone_MilestoneDao::class),
            \Mockery::spy(\Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker::class),
            \Mockery::mock(TimeframeBuilder::class),
            \Mockery::spy(MilestoneBurndownFieldChecker::class)
        );
        $planning_items_tree = $factory->getPlannedArtifacts(\Mockery::spy(\PFUser::class), $root_artifact);

        $children = $planning_items_tree->flattenChildren();

        $this->assertFalse(empty($children));
        foreach ($children as $tree_node) {
            $this->assertIsA($tree_node->getObject(), 'Tracker_Artifact');
        }
    }
}

class MilestoneFactory_GetMilestoneFromArtifactTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->project          = \Mockery::spy(\Project::class);
        $this->release_planning = \Mockery::spy(\Planning::class);
        $this->release_tracker  = aTracker()->withId(2)->withProject($this->project)->build();
        $this->release_artifact = aMockArtifact()->withTracker($this->release_tracker)->build();

        $this->task_tracker  = aTracker()->withId(21)->withProject($this->project)->build();
        $this->task_artifact = aMockArtifact()->withTracker($this->task_tracker)->build();

        $planning_factory        = mockery_stub(\PlanningFactory::class)->getPlanningByPlanningTracker($this->release_tracker)->returns($this->release_planning);
        $this->milestone_factory = new Planning_MilestoneFactory(
            $planning_factory,
            \Mockery::spy(\Tracker_ArtifactFactory::class),
            \Mockery::spy(\Tracker_FormElementFactory::class),
            \Mockery::spy(\TrackerFactory::class),
            \Mockery::spy(\AgileDashboard_Milestone_MilestoneStatusCounter::class),
            \Mockery::spy(\PlanningPermissionsManager::class),
            \Mockery::spy(\AgileDashboard_Milestone_MilestoneDao::class),
            \Mockery::spy(\Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker::class),
            \Mockery::mock(TimeframeBuilder::class),
            \Mockery::spy(MilestoneBurndownFieldChecker::class)
        );
    }

    public function itCreateMilestoneFromArtifact()
    {
        $release_milestone = $this->milestone_factory->getMilestoneFromArtifact($this->release_artifact);
        $this->assertEqualToReleaseMilestone($release_milestone);
    }

    private function assertEqualToReleaseMilestone($actual_release_milestone)
    {
        $expected_release_milestone = new Planning_ArtifactMilestone(
            $this->project,
            $this->release_planning,
            $this->release_artifact,
            \Mockery::spy(\Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker::class)
        );
        $this->assertEqual($actual_release_milestone, $expected_release_milestone);
    }

    public function itReturnsNullWhenThereIsNoPlanningForTheTracker()
    {
        $task_milestone = $this->milestone_factory->getMilestoneFromArtifact($this->task_artifact);
        $this->assertNull($task_milestone);
    }
}

class MilestoneFactory_getMilestoneFromArtifactWithPlannedArtifactsTest extends TuleapTestCase
{

    public function itCreateMilestoneFromArtifactAndLoadsItsPlannedArtifacts()
    {

        $planning_factory = mockery_stub(\PlanningFactory::class)->getPlanningByPlanningTracker()->returns(\Mockery::spy(\Planning::class));

        $milestone_factory = \Mockery::mock(\Planning_MilestoneFactory::class, [
            $planning_factory,
            \Mockery::spy(\Tracker_ArtifactFactory::class),
            \Mockery::spy(\Tracker_FormElementFactory::class),
            \Mockery::spy(\TrackerFactory::class),
            \Mockery::spy(\AgileDashboard_Milestone_MilestoneStatusCounter::class),
            \Mockery::spy(\PlanningPermissionsManager::class),
            \Mockery::spy(\AgileDashboard_Milestone_MilestoneDao::class),
            \Mockery::spy(\Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker::class),
            \Mockery::mock(TimeframeBuilder::class),
            \Mockery::spy(MilestoneBurndownFieldChecker::class)
        ])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $user      = aUser()->build();
        $project   = Mockery::mock(Project::class);
        $tracker   = \Mockery::spy(\Tracker::class);
        $artifact  = aMockArtifact()->withId(101)->withTracker($tracker)->build();
        $artifact2 = aMockArtifact()->withId(102)->build();
        $artifact3 = aMockArtifact()->withId(103)->build();

        $node = new ArtifactNode($artifact);
        $node->addChild(new ArtifactNode($artifact2));
        $node->addChild(new ArtifactNode($artifact3));

        stub($milestone_factory)->getPlannedArtifacts($user, $artifact)->once()->returns($node);
        stub($milestone_factory)->getMilestoneFromArtifact($artifact, $node)->once();

        $tracker->shouldReceive('getProject')->andReturn($project);

        $milestone_factory->getMilestoneFromArtifactWithPlannedArtifacts($artifact, $user);
    }
}

class MilestoneFactory_GetMilestoneWithAncestorsTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->current_user     = aUser()->build();
        $this->milestone_factory = \Mockery::mock(\Planning_MilestoneFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $this->sprint_artifact  = \Mockery::spy(\Tracker_Artifact::class);
        $this->sprint_milestone = aMilestone()->withArtifact($this->sprint_artifact)->build();
    }

    public function itReturnsEmptyArrayIfThereIsNoArtifactInMilestone()
    {
        $empty_milestone = new Planning_NoMilestone(\Mockery::spy(\Project::class), \Mockery::spy(\Planning::class));

        $milestones = $this->milestone_factory->getMilestoneAncestors($this->current_user, $empty_milestone);
        $this->assertEqual($milestones, array());
    }

    public function itBuildTheMilestonesWhenNoParents()
    {
        stub($this->sprint_artifact)->getAllAncestors($this->current_user)->returns(array());

        $milestones = $this->milestone_factory->getMilestoneAncestors($this->current_user, $this->sprint_milestone);
        $this->assertEqual($milestones, array());
    }

    public function itBuildTheMilestoneForOneParent()
    {
        $release_artifact = aMockArtifact()->build();
        stub($this->sprint_artifact)->getAllAncestors($this->current_user)->returns(array($release_artifact));

        $release_milestone = \Mockery::spy(\Planning_ArtifactMilestone::class);
        stub($this->milestone_factory)->getMilestoneFromArtifact($release_artifact)->returns($release_milestone);

        $milestones = $this->milestone_factory->getMilestoneAncestors($this->current_user, $this->sprint_milestone);
        $this->assertEqual($milestones, array($release_milestone));
    }

    public function itBuildTheMilestoneForSeveralParents()
    {
        $release_artifact = aMockArtifact()->withId(1)->build();
        $product_artifact = aMockArtifact()->withId(2)->build();
        stub($this->sprint_artifact)->getAllAncestors($this->current_user)->returns(array($release_artifact, $product_artifact));

        $product_milestone = aMilestone()->withArtifact($product_artifact)->build();
        $release_milestone = aMilestone()->withArtifact($release_artifact)->build();
        stub($this->milestone_factory)->getMilestoneFromArtifact($product_artifact)->returns($product_milestone);
        stub($this->milestone_factory)->getMilestoneFromArtifact($release_artifact)->returns($release_milestone);

        $milestones = $this->milestone_factory->getMilestoneAncestors($this->current_user, $this->sprint_milestone);
        $this->assertEqual($milestones, array($release_milestone, $product_milestone));
    }

    public function itFiltersOutTheEmptyMilestones()
    {
        $release_artifact = aMockArtifact()->withId(1)->build();
        stub($this->sprint_artifact)->getAllAncestors($this->current_user)->returns(array($release_artifact));

        stub($this->milestone_factory)->getMilestoneFromArtifact($release_artifact)->returns(null);

        $milestones = $this->milestone_factory->getMilestoneAncestors($this->current_user, $this->sprint_milestone);
        $this->assertEqual($milestones, array());
    }
}

class MilestoneFactory_getLastMilestoneCreatedsTest extends TuleapTestCase
{
    private $current_user;
    private $milestone_factory;
    private $sprint_1_artifact;
    private $sprint_1_milestone;
    private $planning_factory;
    private $artifact_factory;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->current_user      = aUser()->build();
        $this->planning_factory  = \Mockery::spy(\PlanningFactory::class);
        $this->artifact_factory  = \Mockery::spy(\Tracker_ArtifactFactory::class);
        $this->milestone_factory = \Mockery::mock(\Planning_MilestoneFactory::class, array(
            $this->planning_factory,
            $this->artifact_factory,
            mock('Tracker_FormElementFactory'),
            mock('TrackerFactory'),
            mock('AgileDashboard_Milestone_MilestoneStatusCounter'),
            mock('PlanningPermissionsManager'),
            mock('AgileDashboard_Milestone_MilestoneDao'),
            mock('\Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker'),
            \Mockery::mock(TimeframeBuilder::class),
            \Mockery::spy(\Tuleap\AgileDashboard\Planning\MilestoneBurndownFieldChecker::class)
        ))
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->sprint_1_artifact   = aMockArtifact()->withId(1)->build();
        $this->sprint_1_milestone  = aMilestone()->withArtifact($this->sprint_1_artifact)->build();

        $this->planning_id = 12;
        $this->planning_tracker_id = 123;
        $this->planning_tracker = aTracker()->withId($this->planning_tracker_id)->withProject(\Mockery::spy(\Project::class))->build();
        $this->planning    = aPlanning()->withId($this->planning_id)->withPlanningTracker($this->planning_tracker)->build();
        stub($this->planning_factory)->getPlanning($this->planning_id)->returns($this->planning);
    }

    public function itReturnsEmptyMilestoneWhenNothingMatches()
    {
        stub($this->artifact_factory)->getOpenArtifactsByTrackerIdUserCanView()->returns(array());
        $milestone = $this->milestone_factory->getLastMilestoneCreated($this->current_user, $this->planning_id);
        $this->assertIsA($milestone, 'Planning_NoMilestone');
    }

    public function itReturnsTheLastOpenArtifactOfPlanningTracker()
    {
        stub($this->artifact_factory)->getOpenArtifactsByTrackerIdUserCanView(
            $this->current_user,
            $this->planning_tracker_id
        )->returns(array('115' => $this->sprint_1_artifact, '104' => aMockArtifact()));

        stub($this->milestone_factory)->getMilestoneFromArtifact($this->sprint_1_artifact)->returns($this->sprint_1_milestone);

        $milestone = $this->milestone_factory->getLastMilestoneCreated($this->current_user, $this->planning_id);
        $this->assertEqual($milestone, $this->sprint_1_milestone);
    }
}

class MilestoneFactory_GetTopMilestonesTest extends TuleapTestCase
{
    /** @var Planning_MilestoneFactory */
    private $milestone_factory;
    private $planning_factory;
    private $artifact_factory;
    private $top_milestone;
    private $user;
    private $tracker;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->planning_factory  = \Mockery::spy(\PlanningFactory::class);
        $this->artifact_factory  = \Mockery::spy(\Tracker_ArtifactFactory::class);
        $this->timeframe_builder = \Mockery::mock(TimeframeBuilder::class);

        $this->milestone_factory = new Planning_MilestoneFactory(
            $this->planning_factory,
            $this->artifact_factory,
            \Mockery::spy(\Tracker_FormElementFactory::class),
            \Mockery::spy(\TrackerFactory::class),
            \Mockery::spy(\AgileDashboard_Milestone_MilestoneStatusCounter::class),
            \Mockery::spy(\PlanningPermissionsManager::class),
            \Mockery::spy(\AgileDashboard_Milestone_MilestoneDao::class),
            \Mockery::spy(\Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker::class),
            $this->timeframe_builder,
            \Mockery::spy(\Tuleap\AgileDashboard\Planning\MilestoneBurndownFieldChecker::class)
        );

        $planning = \Mockery::spy(\Planning::class);
        stub($planning)->getPlanningTrackerId()->returns(45);

        $project = \Mockery::spy(\Project::class);
        stub($project)->getID()->returns(3233);

        $this->tracker = \Mockery::spy(\Tracker::class);
        stub($this->tracker)->getId()->returns(12);
        stub($this->tracker)->getName()->returns('tracker');

        $this->user = \Mockery::spy(\PFUser::class);

        $this->top_milestone = \Mockery::spy(\Planning_VirtualTopMilestone::class);
        stub($this->top_milestone)->getPlanning()->returns($planning);
        stub($this->top_milestone)->getProject()->returns($project);
    }

    public function itReturnsEmptyArrayWhenNoTopMilestonesExist()
    {
        stub($this->artifact_factory)->getArtifactsByTrackerId()->returns(array());

        $milestones = $this->milestone_factory->getSubMilestones($this->user, $this->top_milestone);

        $this->assertArrayEmpty($milestones);
    }

    public function itReturnsMilestonePerArtifact()
    {
        $artifact_1 = mockery_stub(\Tracker_Artifact::class)->getLastChangeset()->returns(\Mockery::spy(\Tracker_Artifact_Changeset::class));
        stub($artifact_1)->userCanView()->returns(true);
        stub($artifact_1)->getTracker()->returns($this->tracker);
        stub($artifact_1)->getAllAncestors()->returns(array());

        $artifact_2 = mockery_stub(\Tracker_Artifact::class)->getLastChangeset()->returns(\Mockery::spy(\Tracker_Artifact_Changeset::class));
        stub($artifact_2)->userCanView()->returns(true);
        stub($artifact_2)->getTracker()->returns($this->tracker);
        stub($artifact_2)->getAllAncestors()->returns(array());

        $my_artifacts = array(
            $artifact_1,
            $artifact_2
        );

        $this->timeframe_builder->shouldReceive('buildTimePeriodWithoutWeekendForArtifact')
            ->with($artifact_1, $this->user)
            ->once()
            ->andReturn(TimePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $this->timeframe_builder->shouldReceive('buildTimePeriodWithoutWeekendForArtifact')
            ->with($artifact_2, $this->user)
            ->once()
            ->andReturn(TimePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        stub($this->artifact_factory)->getArtifactsByTrackerId()->returns($my_artifacts);
        stub($this->planning_factory)->getRootPlanning()->returns(\Mockery::spy(\Planning::class));

        $milestones = $this->milestone_factory->getSubMilestones($this->user, $this->top_milestone);

        $this->assertCount($milestones, 2);

        $milestone_1 = $milestones[0];
        $milestone_2 = $milestones[1];

        $this->assertEqual($milestone_1->getArtifact(), $artifact_1);
        $this->assertEqual($milestone_2->getArtifact(), $artifact_2);
    }

    public function itSkipsArtifactsWithoutChangeset()
    {
        // Some artifacts have no changeset on Tuleap.net (because of anonymous that can create
        // artifacts but artifact creation fails because they have to write access to fields
        // the artifact creation is stopped half the way hence without changeset
        $artifact_1 = mockery_stub(\Tracker_Artifact::class)->getLastChangeset()->returns(null);
        stub($artifact_1)->getTracker()->returns($this->tracker);

        $artifact_2 = mockery_stub(\Tracker_Artifact::class)->getLastChangeset()->returns(\Mockery::spy(\Tracker_Artifact_Changeset::class));
        stub($artifact_2)->userCanView()->returns(true);
        stub($artifact_2)->getTracker()->returns($this->tracker);
        stub($artifact_2)->getAllAncestors()->returns(array());

        $my_artifacts = array(
            $artifact_1,
            $artifact_2
        );

        $this->timeframe_builder->shouldReceive('buildTimePeriodWithoutWeekendForArtifact')
            ->with($artifact_2, $this->user)
            ->once()
            ->andReturn(TimePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        stub($this->artifact_factory)->getArtifactsByTrackerId()->returns($my_artifacts);
        stub($this->planning_factory)->getRootPlanning()->returns(\Mockery::spy(\Planning::class));

        $milestones = $this->milestone_factory->getSubMilestones($this->user, $this->top_milestone);

        $this->assertCount($milestones, 1);

        $milestone_1 = $milestones[0];

        $this->assertEqual($milestone_1->getArtifact(), $artifact_2);
    }
}

class MilestoneFactory_GetBareMilestoneByArtifactIdTest extends TuleapTestCase
{
    /** @var Planning_MilestoneFactory */
    private $milestone_factory;
    private $planning_factory;
    private $artifact_factory;
    private $user;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->planning_factory  = \Mockery::spy(\PlanningFactory::class);
        $this->artifact_factory  = \Mockery::spy(\Tracker_ArtifactFactory::class);
        $this->timeframe_builder = \Mockery::mock(TimeframeBuilder::class);

        $this->milestone_factory = new Planning_MilestoneFactory(
            $this->planning_factory,
            $this->artifact_factory,
            \Mockery::spy(\Tracker_FormElementFactory::class),
            \Mockery::spy(\TrackerFactory::class),
            \Mockery::spy(\AgileDashboard_Milestone_MilestoneStatusCounter::class),
            \Mockery::spy(\PlanningPermissionsManager::class),
            \Mockery::spy(\AgileDashboard_Milestone_MilestoneDao::class),
            \Mockery::spy(\Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker::class),
            $this->timeframe_builder,
            \Mockery::spy(\Tuleap\AgileDashboard\Planning\MilestoneBurndownFieldChecker::class)
        );
        $this->user = aUser()->build();
        $this->artifact_id = 112;
    }

    public function itReturnsNullIfArtifactDoesntExist()
    {
        $this->assertNull(
            $this->milestone_factory->getBareMilestoneByArtifactId($this->user, $this->artifact_id)
        );
    }

    public function itReturnsAMilestone()
    {
        $planning_tracker = aTracker()->withId(12)->withProject(\Mockery::spy(\Project::class))->build();
        stub($this->planning_factory)->getPlanningByPlanningTracker($planning_tracker)->returns(aPlanning()->withId(4)->build());

        $artifact = Mockery::spy(Tracker_Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($planning_tracker);
        $artifact->shouldReceive('userCanView')->with($this->user)->once()->andReturn($planning_tracker);
        $artifact->shouldReceive('getAllAncestors')->with($this->user)->once()->andReturn([]);
        stub($this->artifact_factory)->getArtifactById($this->artifact_id)->returns($artifact);

        $this->timeframe_builder->shouldReceive('buildTimePeriodWithoutWeekendForArtifact')
            ->with($artifact, $this->user)
            ->once()
            ->andReturn(TimePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $milestone = $this->milestone_factory->getBareMilestoneByArtifactId($this->user, $this->artifact_id);
        $this->assertEqual($milestone->getArtifact(), $artifact);
    }

    public function itReturnsNullWhenArtifactIsNotAMilestone()
    {

        $planning_tracker = aTracker()->withId(12)->withProject(\Mockery::spy(\Project::class))->build();
        stub($this->planning_factory)->getPlanningByPlanningTracker()->returns(false);

        $artifact = Mockery::spy(Tracker_Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($planning_tracker);
        $artifact->shouldReceive('userCanView')->with($this->user)->once()->andReturn($planning_tracker);
        stub($this->artifact_factory)->getArtifactById($this->artifact_id)->returns($artifact);

        $this->assertNull(
            $this->milestone_factory->getBareMilestoneByArtifactId($this->user, $this->artifact_id)
        );
    }

    public function itReturnsNullWhenUserCannotSeeArtifacts()
    {
        stub($this->planning_factory)->getPlanningByPlanningTracker()->returns(aPlanning()->withId(4)->build());

        $artifact = aMockArtifact()->build();
        stub($artifact)->userCanView($this->user)->returns(false);
        stub($this->artifact_factory)->getArtifactById($this->artifact_id)->returns($artifact);

        $this->assertNull(
            $this->milestone_factory->getBareMilestoneByArtifactId($this->user, $this->artifact_id)
        );
    }
}
