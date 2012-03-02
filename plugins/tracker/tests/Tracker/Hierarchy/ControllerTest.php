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

require_once(dirname(__FILE__).'/../../../include/Tracker/Hierarchy/Presenter.class.php');
require_once(dirname(__FILE__).'/../../../include/Tracker/Hierarchy/Controller.class.php');
require_once(dirname(__FILE__).'/../../Test_Tracker_Builder.php');
Mock::generate('Tracker');

class Tracker_Hierarchy_ControllerTest extends UnitTestCase {
    function testRendersChildrenNames() {
        $tracker = aTracker()->withName('Stories')->build();

        $possible_child_1 = new MockTracker();
        $possible_child_1->setReturnValue('getName', 'Bugs');
        
        $possible_child_2 = new MockTracker();
        $possible_child_2->setReturnValue('getName', 'Tasks');
        
        $possible_children = array($possible_child_1, $possible_child_2);
        
        ob_start();
        $controller = new Tracker_Hierarchy_Controller($tracker);
        $controller->edit($possible_children);

        $content = ob_get_clean();
        
        $this->assertContainsAll(array('Bugs', 'Tasks'), $content);
    }
    
    
    private function assertContainsAll($expected_strings, $actual_text) {
        foreach($expected_strings as $string) {
            $this->assertPattern('/'.$string.'/', $actual_text);
        }
    }
}

?>
