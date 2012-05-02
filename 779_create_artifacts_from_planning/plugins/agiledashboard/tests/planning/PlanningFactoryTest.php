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
require_once dirname(__FILE__).'/../builders/planning_factory.php';
require_once TRACKER_BASE_DIR.'/../tests/Test_Tracker_Builder.php';

Mock::generate('Planning');
Mock::generate('PlanningDao');
Mock::generate('Tracker');

class PlanningFactoryTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $this->user = aUser()->build();
    }
    
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
                               'planning_tracker_id' => $planning_tracker_id);
        
        $backlog_rows = array(array('tracker_id' => $backlog_tracker_id));
        
        stub($tracker_factory)->getTrackerById($planning_tracker_id)->returns($planning_tracker);
        stub($tracker_factory)->getTrackerById($backlog_tracker_id)->returns($backlog_tracker);
        
        stub($planning_dao)->searchById($planning_id)->returns($planning_rows);
        stub($planning_rows)->getRow()->returns($planning_row);
        
        stub($planning_dao)->searchBacklogTrackersById($planning_id)->returns($backlog_rows);
        
        $planning = $planning_factory->getPlanningWithTrackers($planning_id);
        
        $this->assertIsA($planning, 'Planning');
        $this->assertEqual($planning->getPlanningTracker(), $planning_tracker);
        $this->assertEqual($planning->getBacklogTrackers(), array($backlog_tracker));
    }
    
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
                  'planning_tracker_id' => $sprint_tracker_id,
                  'backlog_tracker_ids' => "$story_tracker_id,$bug_tracker_id")
        );
        
        stub($dao)->searchByPlanningTrackerIds(array_keys($tracker_mapping))->returns($rows);
        
        $dao->expectOnce('createPlanning', array($sprint_planning_name,
                                                 $group_id,
                                                 array($story_tracker_copy_id, $bug_tracker_copy_id),
                                                 $sprint_tracker_copy_id));
        
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
            array('id' => 1, 'name' => 'Release Backlog', 'group_id' => 102, 'planning_tracker_id'=>103),
            array('id' => 2, 'name' => 'Product Backlog', 'group_id' => 102, 'planning_tracker_id'=>103)
        );
        
        $factoryBuilder->dao->setReturnValue('searchPlannings', $result_set);
        
        $expected = array(
            new Planning(1, 'Release Backlog', 102),
            new Planning(2, 'Product Backlog', 102),
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
            array('id' => 1, 'name' => 'Release Backlog', 'group_id' => 102, 'planning_tracker_id'=>103),
            array('id' => 2, 'name' => 'Product Backlog', 'group_id' => 102, 'planning_tracker_id'=>104)
        );
        
        $factoryBuilder->dao->setReturnValue('searchPlannings', $result_set);
        
        $expected = array(
            new Planning(1, 'Release Backlog', 102),
        );
        $this->assertEqual($expected, $factoryBuilder->build()->getPlannings($this->user, 123));
    }
    
    public function itDelegatesRetrievalOfPlanningTrackerIdsByGroupIdToDao() {
        $group_id     = 456;
        $expected_ids = array(1, 2, 3);
        $dao          = mock('PlanningDao');
        $factory      = aPlanningFactory()->withDao($dao)->build();
        
        stub($dao)->searchPlanningTrackerIdsByGroupId($group_id)->returns($expected_ids);
        
        $actual_ids = $factory->getPlanningTrackerIdsByGroupId($group_id);
        $this->assertEqual($actual_ids, $expected_ids);
    }
    
    public function itRetrievesAvailablePlanningTrackers() {
        $group_id         = 789;
        $planning_dao     = mock('PlanningDao');
        $tracker_factory  = mock('TrackerFactory');
        $planning_factory = aPlanningFactory()->withDao($planning_dao)
                                              ->withTrackerFactory($tracker_factory)
                                              ->build();
        
        $sprints_tracker_row = array('id' => 1, 'name' => 'Sprints');
        $stories_tracker_row = array('id' => 2, 'name' => 'Stories');
        
        $tracker_rows = array($sprints_tracker_row, $stories_tracker_row);
        
        $sprints_tracker = aTracker()->withId(1)->withName('Sprints')->build();
        $stories_tracker = aTracker()->withId(2)->withName('Stories')->build();
        
        stub($tracker_factory)->getInstanceFromRow($sprints_tracker_row)->returns($sprints_tracker);
        stub($tracker_factory)->getInstanceFromRow($stories_tracker_row)->returns($stories_tracker);
        stub($planning_dao)->searchNonPlanningTrackersByGroupId($group_id)->returns($tracker_rows);
        
        $actual_trackers = $planning_factory->getAvailablePlanningTrackers($group_id);
        $this->assertEqual(count($actual_trackers), 2);
        list($sprints_tracker, $stories_tracker) = $actual_trackers;
        $this->assertEqual($sprints_tracker->getId(), 1);
        $this->assertEqual($stories_tracker->getId(), 2);
        $this->assertEqual($sprints_tracker->getName(), 'Sprints');
        $this->assertEqual($stories_tracker->getName(), 'Stories');
    }
}

?>
