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

require_once(dirname(__FILE__).'/../../include/Planning/PlanningFactory.class.php');

Mock::generate('Planning');
Mock::generate('PlanningDao');

class PlanningFactoryTest extends TuleapTestCase {
    
    function itReturnAnEmptyArrayIfThereIsNoPlanningDefinedForAProject() {
        $dao          = new MockPlanningDao();
        $factory      = new PlanningFactory($dao);
        $empty_result = TestHelper::arrayToDar();
        $dao->setReturnValue('searchPlannings', $empty_result);
        
        $this->assertEqual(array(), $factory->getPlannings(123));
    }
    
    function itReturnAllDefinedPlanningsForAProject() {
        $dao          = new MockPlanningDao();
        $factory      = new PlanningFactory($dao);
        $empty_result = TestHelper::arrayToDar(
            array('id' => 1, 'name' => 'Release Backlog', 'group_id' => 102),
            array('id' => 2, 'name' => 'Product Backlog', 'group_id' => 102)
        );
        $dao->setReturnValue('searchPlannings', $empty_result);
        
        $expected = array(
            new Planning(1, 'Release Backlog', 102),
            new Planning(2, 'Product Backlog', 102),
        );
        $this->assertEqual($expected, $factory->getPlannings(123));
    }
}

?>
