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

Mock::generatePartial(
    'TransitionFactory', 
    'TransitionFactoryTestVersion', 
    array(
        'addTransition'
    )
);
class TransitionFactoryTest extends UnitTestCase {
    
    public function testIsFieldUsedInTransitions() {
        
        $field_start_date = new MockTracker_FormElement_Field_Date($this);
        $field_start_date->setReturnValue('getId', 1002);
        
        $field_close_date = new MockTracker_FormElement_Field_Date($this);
        $field_close_date->setReturnValue('getId', 1003);
        
        $tpaf = new MockTransition_PostActionFactory();
        $tpaf->setReturnValue('isFieldUsedInPostActions', false, array($field_start_date));
        $tpaf->setReturnValue('isFieldUsedInPostActions', true,  array($field_close_date));
        
        $tf = TestHelper::getPartialMock('TransitionFactory', array('getPostActionFactory'));
        $tf->setReturnReference('getPostActionFactory', $tpaf);
        
        $this->assertFalse($tf->isFieldUsedInTransitions($field_start_date));
        $this->assertTrue($tf->isFieldUsedInTransitions($field_close_date));
    }
    
    public function testDuplicate() {
        $field_value_new = new MockTracker_FormElement_Field_List_Value();
        $field_value_new->setReturnValue('getId', 2066);
        $field_value_analyzed = new MockTracker_FormElement_Field_List_Value();
        $field_value_analyzed->setReturnValue('getId', 2067);
        $field_value_accepted = new MockTracker_FormElement_Field_List_Value();
        $field_value_accepted->setReturnValue('getId', 2068);
        
        $t1  = new Transition(1, 1, $field_value_new, $field_value_analyzed);
        $t2  = new Transition(2, 1, $field_value_analyzed, $field_value_accepted);
        $t3  = new Transition(3, 1, $field_value_analyzed, $field_value_new);
        
        $transitions = array($t1, $t2, $t3);
        $tf = new TransitionFactoryTestVersion();
       
        $values = array(
            2066  => 3066,
            2067  => 3067,
            2068  => 3068
        );
        
        $tf->expectCallCount('addTransition', 3, 'Method addTransition should be called 3 times.');
        $tf->expectAt(0, 'addTransition', array(1, 3066, 3067));
        $tf->expectAt(1, 'addTransition', array(1, 3067, 3068));
        $tf->expectAt(2, 'addTransition', array(1, 3067, 3066));
        
        $tf->duplicate($values, 1, $transitions, array(), false, false);
    }
}

?>
