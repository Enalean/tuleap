<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
require_once __DIR__.'/../../../bootstrap.php';

class AgileDashboard_Milestone_MilestoneReportCriterionOptionsProviderTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        /*
        Product          Epic
          Release  ----,-- Story
            Sprint ---'      Task
        */

        $this->user                    = aUser()->build();
        $this->release_tracker_id      = 101;
        $this->sprint_tracker_id       = 1001;
        $this->task_tracker_project_id = 200;

        $this->epic_tracker    = aTracker()->withId('epic')->withParent(null)->build();
        $this->story_tracker   = aTracker()->withId('story')->withParent($this->epic_tracker)->build();
        $this->task_tracker    = aTracker()->withId('task')->withProjectId($this->task_tracker_project_id)->withParent($this->story_tracker)->build();
        $this->product_tracker = aTracker()->withId('product')->withParent(null)->build();
        $this->release_tracker = aMockeryTracker()->withId($this->release_tracker_id)->withParent($this->product_tracker)->build();
        $this->sprint_tracker  = aMockeryTracker()->withId($this->sprint_tracker_id)->withParent($this->epic_tracker)->build();

        $this->release_artifact_123 = aMockArtifact()->withId(123)->build();
        $this->release_artifact_124 = aMockArtifact()->withId(124)->build();
        $this->sprint_artifact_1231 = mockery_stub(\Tracker_Artifact::class)->getId()->returns(1231);
        $this->sprint_artifact_1232 = mockery_stub(\Tracker_Artifact::class)->getId()->returns(1232);
        $this->sprint_artifact_1241 = mockery_stub(\Tracker_Artifact::class)->getId()->returns(1241);

        $release_planning = mockery_stub(\Planning::class)->getPlanningTracker()->returns($this->release_tracker);
        $sprint_planning  = mockery_stub(\Planning::class)->getPlanningTracker()->returns($this->sprint_tracker);
        $top_planning     = mockery_stub(\Planning::class)->getBacklogTrackersIds()->returns(
            array(
                $this->release_tracker_id,
                $this->sprint_tracker_id
            )
        );

        $this->planning_factory = \Mockery::spy(\PlanningFactory::class);
        stub($this->planning_factory)->getPlanningByPlanningTracker($this->product_tracker)->returns(null);
        stub($this->planning_factory)->getPlanningByPlanningTracker($this->release_tracker)->returns($release_planning);
        stub($this->planning_factory)->getPlanningByPlanningTracker($this->sprint_tracker)->returns($sprint_planning);
        stub($this->planning_factory)->getVirtualTopPlanning($this->user, $this->task_tracker_project_id)->returns($top_planning);

        $this->hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        stub($this->hierarchy_factory)->getAllParents($this->sprint_tracker)->returns(array($this->release_tracker, $this->product_tracker));

        $this->tracker_factory = \Mockery::spy(\TrackerFactory::class);
        stub($this->tracker_factory)->getTrackerById(101)->returns($this->release_tracker);
        stub($this->tracker_factory)->getTrackerById(1001)->returns($this->sprint_tracker);

        $this->artifact_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);
        stub($this->artifact_factory)->getArtifactById(123)->returns($this->release_artifact_123);
        stub($this->artifact_factory)->getArtifactById(124)->returns($this->release_artifact_124);
        stub($this->artifact_factory)->getArtifactById(1231)->returns($this->sprint_artifact_1231);
        stub($this->artifact_factory)->getArtifactById(1232)->returns($this->sprint_artifact_1232);
        stub($this->artifact_factory)->getArtifactById(1241)->returns($this->sprint_artifact_1241);

        $this->dao = \Mockery::spy(\AgileDashboard_Milestone_MilestoneDao::class);
        $this->dar_01 = array(
            'm101_id'     => '123',
            'm101_title'  => 'Tuleap 6.5',
            'm1001_id'    => '1231',
            'm1001_title' => 'Sprint 31',
        );

        $this->dar_02 = array(
            'm101_id'     => '123',
            'm101_title'  => 'Tuleap 6.5',
            'm1001_id'    => '1232',
            'm1001_title' => 'Sprint 32',
        );

        $this->dar_03 = array(
            'm101_id'     => '124',
            'm101_title'  => 'Tuleap 6.6',
            'm1001_id'    => '1241',
            'm1001_title' => 'Sprint 33',
        );

        $this->nearest_planning_tracker_provider = \Mockery::spy(\AgileDashboard_Planning_NearestPlanningTrackerProvider::class);
        $this->provider = new AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider(
            $this->nearest_planning_tracker_provider,
            $this->dao,
            $this->hierarchy_factory,
            $this->planning_factory,
            $this->tracker_factory,
            $this->artifact_factory
        );
    }

    public function itReturnsEmptyArrayWhenNoNearestPlanningTracker()
    {
        stub($this->nearest_planning_tracker_provider)->getNearestPlanningTracker($this->task_tracker, $this->hierarchy_factory)->returns(null);

        stub($this->dao)->getAllMilestoneByTrackers(array($this->release_tracker_id, $this->sprint_tracker_id))->never();

        $this->assertEqual($this->provider->getSelectboxOptions($this->task_tracker, '*', $this->user), array());
    }

    public function itDoesNotSearchOnProductTrackerSinceThereIsNoPlanning()
    {
        stub($this->nearest_planning_tracker_provider)->getNearestPlanningTracker($this->task_tracker, $this->hierarchy_factory)->returns($this->sprint_tracker);

        stub($this->release_tracker)->userCanView($this->user)->returns(true);
        stub($this->sprint_tracker)->userCanView($this->user)->returns(true);

        stub($this->dao)->getAllMilestoneByTrackers(array($this->release_tracker_id, $this->sprint_tracker_id))->once()->returnsDar(
            $this->dar_01,
            $this->dar_02,
            $this->dar_03
        );

        $this->provider->getSelectboxOptions($this->task_tracker, '*', $this->user);
    }

    public function itDoesNotSearchOnMilestonesUserCantView()
    {
        stub($this->nearest_planning_tracker_provider)->getNearestPlanningTracker($this->task_tracker, $this->hierarchy_factory)->returns($this->sprint_tracker);

        stub($this->release_tracker)->userCanView($this->user)->returns(false);
        stub($this->sprint_tracker)->userCanView($this->user)->returns(true);

        stub($this->release_artifact_123)->userCanView($this->user)->returns(false);
        stub($this->release_artifact_124)->userCanView($this->user)->returns(true);
        stub($this->sprint_artifact_1231)->userCanView($this->user)->returns(false);
        stub($this->sprint_artifact_1232)->userCanView($this->user)->returns(true);
        stub($this->sprint_artifact_1241)->userCanView($this->user)->returns(true);

        stub($this->dao)->getAllMilestoneByTrackers(array($this->release_tracker_id, $this->sprint_tracker_id))->once()->returnsDar(
            $this->dar_01,
            $this->dar_02,
            $this->dar_03
        );

        $options = $this->provider->getSelectboxOptions($this->task_tracker, '*', $this->user);

        $this->assertNoPattern('/Sprint 31/', implode('', $options));
    }
}
