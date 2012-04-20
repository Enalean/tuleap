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

require_once dirname(__FILE__).'/../../include/Planning/PlanningFactory.class.php';
require_once dirname(__FILE__).'/../builders/planning_factory.php';

Mock::generate('Planning');
Mock::generate('PlanningDao');
Mock::generate('Tracker');

class PlanningFactoryTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $this->user = aUser()->build();
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
}

?>
