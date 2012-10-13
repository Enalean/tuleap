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

require_once dirname(__FILE__).'/../../../tracker/include/constants.php';
require_once dirname(__FILE__).'/../../include/Planning/PlanningFactory.class.php';
require_once dirname(__FILE__).'/../builders/aPlanningFactory.php';
require_once TRACKER_BASE_DIR.'/../tests/builders/aTracker.php';

Mock::generate('Planning');
Mock::generate('PlanningDao');
Mock::generate('Tracker');

abstract class PlanningFactoryTest extends TuleapTestCase {
    public function setUp() {
        parent::setUp();
        
        $this->user = aUser()->build();
    }
}

class PlanningFactoryTest_getPlanningWithTrackersTest extends PlanningFactoryTest {
    
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
        $backlog_row   = array('tracker_id'          => $backlog_tracker_id);
        
        stub($tracker_factory)->getTrackerById($planning_tracker_id)->returns($planning_tracker);
        stub($tracker_factory)->getTrackerById($backlog_tracker_id)->returns($backlog_tracker);
        
        stub($planning_dao)->searchById($planning_id)->returns($planning_rows);
        stub($planning_rows)->getRow()->returns($planning_row);
        
        stub($planning_dao)->searchBacklogTrackerById($planning_id)->returns($backlog_row);
        
        $planning = $planning_factory->getPlanningWithTrackers($planning_id);
        
        $this->assertIsA($planning, 'Planning');
        $this->assertEqual($planning->getPlanningTracker(), $planning_tracker);
        $this->assertEqual($planning->getBacklogTracker(), $backlog_tracker);
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
    public function itReturnsNothingIfThereIsNoAssociatedPlanning() {
        $tracker   = aMockTracker()->withId(99)->build();
        $empty_dar = TestHelper::arrayToDar();
        $dao       = stub('PlanningDao')->searchByPlanningTrackerId()->returns($empty_dar);
        $factory   = aPlanningFactory()->withDao($dao)->build();
        $this->assertNull($factory->getPlanningByPlanningTracker($tracker));
    }
    
    public function itReturnsAPlanning() {
        $tracker   = aMockTracker()->withId(99)->build();
        $dar       = TestHelper::arrayToDar(
                        array('id' => 1, 'name' => 'Release Planning', 'group_id' => 102, 
                              'planning_tracker_id' => 103, 'backlog_title' => 'Release Backlog', 'plan_title' => 'Sprint Plan',
                              'backlog_tracker_id'  => 104));
        $dao       = stub('PlanningDao')->searchByPlanningTrackerId()->returns($dar);

        $planning_tracker = mock('Tracker');
        $backlog_tracker  = mock('Tracker');
        $tracker_factory  = mock('TrackerFactory');
        stub($tracker_factory)->getTrackerById(103)->returns($planning_tracker);
        stub($tracker_factory)->getTrackerById('*')->returns($backlog_tracker);

        $factory   = aPlanningFactory()->withDao($dao)->withTrackerFactory($tracker_factory)->build();
        $planning  = new Planning(1, 'Release Planning', 102, 'Release Backlog', 'Sprint Plan', 104, 103);
        $planning->setPlanningTracker($planning_tracker);
        $planning->setBacklogTracker($backlog_tracker);
        
        $this->assertEqual($planning, $factory->getPlanningByPlanningTracker($tracker));
    }
    
    public function itAddsThePlanningAndTheBacklogTrackers() {
        $tracker   = aMockTracker()->withId(99)->build();
        $dar       = TestHelper::arrayToDar(
                        array('id' => 1, 'name' => 'Release Planning', 'group_id' => 102, 
                              'planning_tracker_id' => 103, 'backlog_title' => 'Release Backlog', 'plan_title' => 'Sprint Plan',
                              'backlog_tracker_id'  => 104));
        $backlog_tracker_row = array('tracker_id' => 104);
        
        $dao       = mock('PlanningDao');
        stub($dao)->searchByPlanningTrackerId(99)->returns($dar);
        stub($dao)->searchBacklogTrackerById(1)->returns($backlog_tracker_row);
        
        $planning_tracker = aTracker()->withName('planning tracker')->withId(103)->build();
        $backlog_tracker  = aTracker()->withName('backlog  tracker')->withId(104)->build();
        
        $tracker_factory  = mock('TrackerFactory');
        stub($tracker_factory)->getTrackerById(103)->returns($planning_tracker);
        stub($tracker_factory)->getTrackerById(104)->returns($backlog_tracker);
        $factory   = aPlanningFactory()->withDao($dao)->withTrackerFactory($tracker_factory)->build();

        $actual_planning = $factory->getPlanningByPlanningTracker($tracker);
        $this->assertEqual($planning_tracker, $actual_planning->getPlanningTracker());
        $this->assertEqual($backlog_tracker, $actual_planning->getBacklogTracker());
    }
    
}
class PlanningFactoryTest_getPlanningsTest extends PlanningFactoryTest {
    
    public function itReturnAnEmptyArrayIfThereIsNoPlanningDefinedForAProject() {
        $dao          = new MockPlanningDao();
        $factory      = aPlanningFactory()->withDao($dao)->build();
        $empty_result = TestHelper::arrayToDar();
        $dao->setReturnValue('searchPlannings', $empty_result);
        
        $this->assertEqual(array(), $factory->getPlannings($this->user, 123));
    }
    
    public function itReturnAllDefinedPlanningsForAProject() {
        $tracker        = new MockTracker();
        $tracker->setReturnValue('userCanView', true);
        
        $factoryBuilder = aPlanningFactory();
        $factoryBuilder->tracker_factory->setReturnValue('getTrackerById', $tracker);
        
        $result_set   = TestHelper::arrayToDar(
            array('id' => 1, 'name' => 'Release Backlog', 'group_id' => 102, 'planning_tracker_id' => 103, 'backlog_title' => 'Release Backlog', 'plan_title' => 'Sprint Plan'),
            array('id' => 2, 'name' => 'Product Backlog', 'group_id' => 102, 'planning_tracker_id' => 103, 'backlog_title' => 'Release Backlog', 'plan_title' => 'Sprint Plan')
        );
        
        $factoryBuilder->dao->setReturnValue('searchPlannings', $result_set);
        
        $expected = array(
            new Planning(1, 'Release Backlog', 102, 'Release Backlog', 'Sprint Plan'),
            new Planning(2, 'Product Backlog', 102, 'Release Backlog', 'Sprint Plan'),
        );
        $this->assertEqual($expected, $factoryBuilder->build()->getPlannings($this->user, 123));
    }
    
    public function itReturnOnlyPlanningsWhereTheUserCanViewTrackers() {        
        $tracker1        = new MockTracker();
        stub($tracker1)->userCanView($this->user)->returns(true);
        $tracker2        = new MockTracker();
        stub($tracker2)->userCanView($this->user)->returns(false);
        
        $factoryBuilder = aPlanningFactory();
        $factoryBuilder->tracker_factory->setReturnValue('getTrackerById', $tracker1, array(103));
        $factoryBuilder->tracker_factory->setReturnValue('getTrackerById', $tracker2, array(104));
                
        $result_set   = TestHelper::arrayToDar(
            array('id' => 1, 'name' => 'Release Backlog', 'group_id' => 102, 'planning_tracker_id' => 103, 'backlog_title' => 'Release Backlog', 'plan_title' => 'Sprint Plan'),
            array('id' => 2, 'name' => 'Product Backlog', 'group_id' => 102, 'planning_tracker_id' => 104, 'backlog_title' => 'Release Backlog', 'plan_title' => 'Sprint Plan')
        );
        
        $factoryBuilder->dao->setReturnValue('searchPlannings', $result_set);
        
        $expected = array(
            new Planning(1, 'Release Backlog', 102, 'Release Backlog', 'Sprint Plan'),
        );
        $this->assertEqual($expected, $factoryBuilder->build()->getPlannings($this->user, 123));
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

?>
