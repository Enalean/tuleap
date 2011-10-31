<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once(dirname(__FILE__).'/../../include/workflow/TransitionFactory.class.php');
Mock::generate('Transition_PostActionFactory');

require_once(dirname(__FILE__).'/../../include/Tracker/FormElement/Tracker_FormElement_Field_Date.class.php');
Mock::generate('Tracker_FormElement_Field_Date');

class TransitionFactoryTest extends UnitTestCase {
    
    public function testIsFieldUsedInTransitions() {
        
        $field_start_date = new MockTracker_FormElement_Field_Date($this);
        $field_start_date->setReturnValue('getId', 1002);
        /*
        TODO: Why the hell do we need the above line? 
        
        If both mock objects don't return something for getId or return the 
        same thing (1002 & 1002) then the test becomes red. 
        Whereas we don't use this method. WTF ?
        
        Try to comment the above statement to see by yourself.
        
        To add some fun (it would be too easy) the tests are green if all tracker 
        unit tests are run. the tests are red if this test is run in isolation.
        I'm lovin'it >_<
        
        Fix me that or I will kill some puppies. Thanks.
        */
        
        $field_close_date = new MockTracker_FormElement_Field_Date($this);
        
        $tpaf = new MockTransition_PostActionFactory();
        $tpaf->setReturnValue('isFieldUsedInPostActions', false, array($field_start_date));
        $tpaf->setReturnValue('isFieldUsedInPostActions', true,  array($field_close_date));
        
        $tf = TestHelper::getPartialMock('TransitionFactory', array('getPostActionFactory'));
        $tf->setReturnReference('getPostActionFactory', $tpaf);
        
        $this->assertFalse($tf->isFieldUsedInTransitions($field_start_date));
        $this->assertTrue($tf->isFieldUsedInTransitions($field_close_date));
    }
}

?>
