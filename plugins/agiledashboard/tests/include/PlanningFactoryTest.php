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

require_once dirname(__FILE__).'/../bootstrap.php';

Mock::generate('Planning');
Mock::generate('PlanningDao');
Mock::generate('Tracker');

abstract class PlanningFactoryTest extends TuleapTestCase {
    public function setUp() {
        parent::setUp();

        $this->user = aUser()->build();
    }
}

class PlanningFactoryTest_getPlanningTest extends PlanningFactoryTest {

    public function itCanRetrieveBothAPlanningAndItsTrackers() {
        $group_id            = 42;
        $planning_id         = 17;
        $planning_tracker_id = 54;
        $backlog_tracker_id  = 89;

        $planning_dao     = mock('PlanningDao');
        $tracker_factory  = mock('TrackerFactory');
        $planning_tracker = mock('Tracker');
        $backlog_tracker  = mock('Tracker');
        $planning_factory = aPlanningFactory()->withDao($planning_dao)
                                              ->withTrackerFactory($tracker_factory)
                                              ->build();

        $planning_rows = mock('DataAccessResult');
        $planning_row  = array('id'                  => $planning_id,
                               'name'                => 'Foo',
                               'group_id'            => $group_id,
                               'planning_tracker_id' => $planning_tracker_id,
                               'backlog_title'       => 'Release Backlog',
                               'plan_title'          => 'Sprint Plan');

        stub($tracker_factory)->getTrackerById($planning_tracker_id)->returns($planning_tracker);
        stub($tracker_factory)->getTrackerById($backlog_tracker_id)->returns($backlog_tracker);

        stub($planning_dao)->searchById($planning_id)->returns($planning_rows);
        stub($planning_rows)->getRow()->returns($planning_row);

        stub($planning_dao)->searchBacklogTrackersById($planning_id)->returnsDar(array('tracker_id' => $backlog_tracker_id));

        $planning = $planning_factory->getPlanning($planning_id);

        $this->assertIsA($planning, 'Planning');
        $this->assertEqual($planning->getPlanningTracker(), $planning_tracker);
        $this->assertEqual($planning->getBacklogTrackers(), array($backlog_tracker));
    }
}

class PlanningFactory_duplicationTest extends PlanningFactoryTest {

    public function itDuplicatesPlannings() {
        $dao     = new MockPlanningDao();
        $factory = aPlanningFactory()->withDao($dao)->build();

        $group_id = 123;

        $sprint_tracker_id      = 1;
        $story_tracker_id       = 2;
        $bug_tracker_id         = 3;
        $faq_tracker_id         = 4;
        $sprint_tracker_copy_id = 5;
        $story_tracker_copy_id  = 6;
        $bug_tracker_copy_id    = 7;
        $faq_tracker_copy_id    = 8;

        $tracker_mapping = array($sprint_tracker_id => $sprint_tracker_copy_id,
                                 $story_tracker_id  => $story_tracker_copy_id,
                                 $bug_tracker_id    => $bug_tracker_copy_id,
                                 $faq_tracker_id    => $faq_tracker_copy_id);

        $sprint_planning_name = 'Sprint Planning';

        $rows = TestHelper::arrayToDar(
            array('id'                  => 1,
                  'name'                => $sprint_planning_name,
                  'group_id'            => 101,
                  'backlog_title'       => 'Backlog',
                  'plan_title'          => 'Plan',
                  'planning_tracker_id' => $sprint_tracker_id,
                  'backlog_tracker_id'  => $story_tracker_id)
        );

        stub($dao)->searchByPlanningTrackerIds(array_keys($tracker_mapping))->returns($rows);

        $expected_paramters = PlanningParameters::fromArray(array(
            'id'                  => 1,
            'name'                => $sprint_planning_name,
            'group_id'            => 101,
            'backlog_title'       => 'Backlog',
            'plan_title'          => 'Plan',
            'planning_tracker_id' => $sprint_tracker_copy_id,
            'backlog_tracker_id'  => $story_tracker_copy_id
        ));

        $dao->expectOnce('createPlanning', array($group_id,
                                                 $expected_paramters));

        $factory->duplicatePlannings($group_id, $tracker_mapping);
    }

    public function itDoesNothingIfThereAreNoTrackerMappings() {
        $dao     = new MockPlanningDao();
        $factory = aPlanningFactory()->withDao($dao)->build();
        $group_id = 123;
        $empty_tracker_mapping = array();

        $dao->expectNever('createPlanning');

        $factory->duplicatePlannings($group_id, $empty_tracker_mapping);
    }

}

class PlanningFactoryTest_getPlanningByPlanningTrackerTest extends PlanningFactoryTest {

    public function setUp() {
        parent::setUp();
        $this->tracker   = aMockTracker()->withId(99)->build();
        $dao             = stub('PlanningDao')->searchByPlanningTrackerId()->returnsDar(
            array(
                'id' => 1,
                'name' => 'Release Planning',
                'group_id' => 102,
                'planning_tracker_id' => 103,
                'backlog_title' => 'Release Backlog',
                'plan_title' => 'Sprint Plan'
            )
        );
        stub($dao)->searchBacklogTrackersById(1)->returnsDar(
            array(
                'planning_id' => 1,
                'tracker_id'  => 104,
            )
        );

        $this->planning_tracker = aMockTracker()->withId(103)->build();
        $this->backlog_tracker  = aMockTracker()->withId(104)->build();
        $tracker_factory  = mock('TrackerFactory');
        stub($tracker_factory)->getTrackerById(103)->returns($this->planning_tracker);
        stub($tracker_factory)->getTrackerById(104)->returns($this->backlog_tracker);

        $this->factory   = aPlanningFactory()->withDao($dao)->withTrackerFactory($tracker_factory)->build();
    }

    public function itReturnsNothingIfThereIsNoAssociatedPlanning() {
        $tracker   = aMockTracker()->withId(99)->build();
        $empty_dar = TestHelper::arrayToDar();
        $dao       = stub('PlanningDao')->searchByPlanningTrackerId()->returns($empty_dar);
        $factory   = aPlanningFactory()->withDao($dao)->build();
        $this->assertNull($factory->getPlanningByPlanningTracker($tracker));
    }

    public function itReturnsAPlanning() {
        $planning  = new Planning(1, 'Release Planning', 102, 'Release Backlog', 'Sprint Plan', array());
        $planning->setPlanningTracker($this->planning_tracker);
        $planning->setBacklogTrackers(array($this->backlog_tracker));

        $this->assertEqual($planning, $this->factory->getPlanningByPlanningTracker($this->tracker));
    }

    public function itAddsThePlanningAndTheBacklogTrackers() {
        $actual_planning = $this->factory->getPlanningByPlanningTracker($this->tracker);
        $this->assertEqual($this->planning_tracker, $actual_planning->getPlanningTracker());
        $this->assertEqual(array($this->backlog_tracker), $actual_planning->getBacklogTrackers());
    }

}

class PlanningFactoryTest_getPlanningsTest extends PlanningFactoryTest {

    private $project_id                  = 123;
    private $project_id_without_planning = 124;

    public function setUp() {
        parent::setUp();
        $tracker_factory   = TrackerFactory::instance();
        $hierarchy_dao     = mock('Tracker_Hierarchy_Dao');
        $hierarchy_factory = new Tracker_HierarchyFactory($hierarchy_dao, $tracker_factory, mock('Tracker_ArtifactFactory'));

        $tracker_factory->setHierarchyFactory($hierarchy_factory);

        $this->setUpTrackers($tracker_factory, $hierarchy_dao);
        $this->setUpPlannings($tracker_factory);
    }

    public function tearDown() {
        TrackerFactory::clearInstance();
        parent::tearDown();
    }

    private function setUpTrackers(TrackerFactory $tracker_factory, Tracker_Hierarchy_Dao $hierarchy_dao) {
        $this->epic_tracker  = aMockTracker()->withId(104)->build();
        $this->story_tracker = aMockTracker()->withId(103)->build();
        $this->release_tracker  = aMockTracker()->withId(107)->build();
        $this->sprint_tracker = aMockTracker()->withId(108)->build();

        $tracker_factory->setCachedInstances(array(
            104 => $this->epic_tracker,
            103 => $this->story_tracker,
            107 => $this->release_tracker,
            108 => $this->sprint_tracker,
        ));

        stub($hierarchy_dao)->searchTrackerHierarchy(array(103, 104))->returnsDar(
            array('parent_id' => '104', 'child_id' => '103')
        );
        stub($hierarchy_dao)->searchTrackerHierarchy(array(108, 107))->returnsDar(
            array('parent_id' => '107', 'child_id' => '108')
        );
        stub($hierarchy_dao)->searchTrackerHierarchy()->returnsEmptyDar();
    }

    private function setUpPlannings(TrackerFactory $tracker_factory) {
        $dao = mock('PlanningDao');
        stub($dao)->searchPlannings($this->project_id)->returnsDar(
            array(
                'id'                  => 1,
                'name'                => 'Sprint Planning',
                'group_id'            => 123,
                'planning_tracker_id' => 108,
                'backlog_title'       => 'Release Backlog',
                'plan_title'          => 'Sprint Plan'
            ),
            array(
                'id'                  => 2,
                'name'                => 'Release Planning',
                'group_id'            => 123,
                'planning_tracker_id' => 107,
                'backlog_title'       => 'Product Backlog',
                'plan_title'          => 'Release Plan'
            )
        );
        stub($dao)->searchBacklogTrackersById(1)->returnsDar(array('tracker_id' => 103));
        stub($dao)->searchBacklogTrackersById(2)->returnsDar(array('tracker_id' => 104));
        stub($dao)->searchPlannings($this->project_id_without_planning)->returnsEmptyDar();

        $this->factory = aPlanningFactory()
            ->withDao($dao)
            ->withTrackerFactory($tracker_factory)
            ->build();

        $this->release_planning = new Planning(2, 'Release Planning', 123, 'Product Backlog', 'Release Plan');
        $this->release_planning->setBacklogTrackers(array($this->epic_tracker));
        $this->release_planning->setPlanningTracker($this->release_tracker);
        $this->sprint_planning  = new Planning(1, 'Sprint Planning', 123, 'Release Backlog', 'Sprint Plan');
        $this->sprint_planning->setBacklogTrackers(array($this->story_tracker));
        $this->sprint_planning->setPlanningTracker($this->sprint_tracker);
    }

    public function itReturnAnEmptyArrayIfThereIsNoPlanningDefinedForAProject() {
        $this->assertEqual(array(), $this->factory->getPlannings($this->user, $this->project_id_without_planning));
    }

    public function itReturnAllDefinedPlanningsForAProjectInTheOrderDefinedByTheHierarchy() {
        stub($this->release_tracker)->userCanView($this->user)->returns(true);
        stub($this->sprint_tracker)->userCanView($this->user)->returns(true);

        $this->assertEqual(
            $this->factory->getPlannings($this->user, $this->project_id),
            array($this->release_planning, $this->sprint_planning)
        );
    }

    public function itReturnOnlyPlanningsWhereTheUserCanViewTrackers() {
        stub($this->release_tracker)->userCanView($this->user)->returns(true);
        stub($this->sprint_tracker)->userCanView($this->user)->returns(false);

        $this->assertEqual(
            $this->factory->getPlannings($this->user, $this->project_id),
            array($this->release_planning)
        );
    }
}

class PlanningFactoryTest_getPlanningTrackerIdsByGroupIdTest extends PlanningFactoryTest {

    public function itDelegatesRetrievalOfPlanningTrackerIdsByGroupIdToDao() {
        $group_id     = 456;
        $expected_ids = array(1, 2, 3);
        $dao          = mock('PlanningDao');
        $factory      = aPlanningFactory()->withDao($dao)->build();

        stub($dao)->searchPlanningTrackerIdsByGroupId($group_id)->returns($expected_ids);

        $actual_ids = $factory->getPlanningTrackerIdsByGroupId($group_id);
        $this->assertEqual($actual_ids, $expected_ids);
    }
}

class PlanningFactoryTest_getAvailablePlanningTrackersTest extends PlanningFactoryTest {

    public function itRetrievesAvailablePlanningTrackersIncludingTheCurrentPlanningTracker() {
        $group_id         = 789;
        $planning_dao     = mock('PlanningDao');
        $tracker_factory  = mock('TrackerFactory');
        $planning_factory = aPlanningFactory()->withDao($planning_dao)
                                              ->withTrackerFactory($tracker_factory)
                                              ->build();

        $sprints_tracker_row = array('id' => 1, 'name' => 'Sprints');
        $stories_tracker_row = array('id' => 2, 'name' => 'Stories');

        $tracker_rows = array($sprints_tracker_row, $stories_tracker_row);

        $sprints_tracker  = aTracker()->withId(1)->withName('Sprints')->build();
        $stories_tracker  = aTracker()->withId(2)->withName('Stories')->build();
        $releases_tracker = aTracker()->withId(3)->withName('Releases')->build();

        stub($tracker_factory)->getInstanceFromRow($sprints_tracker_row)->returns($sprints_tracker);
        stub($tracker_factory)->getInstanceFromRow($stories_tracker_row)->returns($stories_tracker);
        stub($planning_dao)->searchNonPlanningTrackersByGroupId($group_id)->returns($tracker_rows);

        $planning = aPlanning()->withGroupId($group_id)
                               ->withPlanningTracker($releases_tracker)
                               ->build();

        $actual_trackers = $planning_factory->getAvailablePlanningTrackers($planning);
        $this->assertEqual(count($actual_trackers), 3);
        list($releases_tracker, $sprints_tracker, $stories_tracker) = $actual_trackers;
        $this->assertEqual($releases_tracker->getId(), 3);
        $this->assertEqual($sprints_tracker->getId(), 1);
        $this->assertEqual($stories_tracker->getId(), 2);
        $this->assertEqual($releases_tracker->getName(), 'Releases');
        $this->assertEqual($sprints_tracker->getName(), 'Sprints');
        $this->assertEqual($stories_tracker->getName(), 'Stories');
    }
}

class PlanningFactoryTest_getVirtualTopPlanningTest extends TuleapTestCase {

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var PlanningDao */
    private $planning_dao;

    public function setUp() {
        parent::setUp();
        $this->user            = mock('PFUser');
        $this->planning_dao    = mock('PlanningDao');
        $this->tracker_factory = mock('TrackerFactory');
        $this->planning_factory = partial_mock('PlanningFactory', array('getRootPlanning'), array($this->planning_dao, $this->tracker_factory));
    }
    public function itThrowsAnExceptionIfNoPlanningsExistForProject() {
        $this->expectException('Planning_NoPlanningsException');

        $this->planning_factory->getVirtualTopPlanning($this->user, 112);
    }

    public function itCreatesNewPlanningWithValidBacklogAndPlanningTrackers() {
        $backlog_tracker  = mock('Tracker');
        $planning_tracker = mock('Tracker');

        stub($backlog_tracker)->getId()->returns(78);
        stub($planning_tracker)->getId()->returns(45);

        $my_planning = new Planning(null, null, null, null, null, array(78), 45);
        $my_planning->setBacklogTrackers(array($backlog_tracker))
                ->setPlanningTracker($planning_tracker);

        stub($this->planning_factory)->getRootPlanning()->returns($my_planning);
        stub($this->tracker_factory)->getTrackerById(45)->returns($backlog_tracker);
        stub($this->tracker_factory)->getTrackerById(78)->returns($planning_tracker);


        $planning = $this->planning_factory->getVirtualTopPlanning($this->user, 56);

        $this->assertIsA($planning, 'Planning');
        $this->assertIsA($planning->getPlanningTracker(), 'Tracker');
        $backlog_trackers = $planning->getBacklogTrackers();
        $this->assertIsA($backlog_trackers[0], 'Tracker');
    }
}

?>
