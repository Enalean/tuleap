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

require_once(dirname(__FILE__).'/../../include/Tracker/FormElement/Tracker_FormElement_Field_List_Value.class.php');
Mock::generate('Tracker_FormElement_Field_List_Value');


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
        
        $tf = TestHelper::getPartialMock('TransitionFactory', array('addTransition', 'getPostActionFactory', 'duplicatePermissions'));
       
        $values = array(
            2066  => 3066,
            2067  => 3067,
            2068  => 3068
        );
        
        $tf->expectCallCount('addTransition', 3, 'Method addTransition should be called 3 times.');
        $tf->expectAt(0, 'addTransition', array(1, 3066, 3067));
        $tf->expectAt(1, 'addTransition', array(1, 3067, 3068));
        $tf->expectAt(2, 'addTransition', array(1, 3067, 3066));
        $tf->setReturnValueAt(0, 'addTransition', 101);
        $tf->setReturnValueAt(1, 'addTransition', 102);
        $tf->setReturnValueAt(2, 'addTransition', 103);
        
        $tf->expectCallCount('duplicatePermissions', 3, 'Method duplicatePermissions should be called 3 times.');
        $tf->expectAt(0, 'duplicatePermissions', array(1, 101, false, false));
        $tf->expectAt(1, 'duplicatePermissions', array(2, 102, false, false));
        $tf->expectAt(2, 'duplicatePermissions', array(3, 103, false, false));
        
        $tpaf = new MockTransition_PostActionFactory();
        $tpaf->expectCallCount('duplicate', 3, 'Method duplicate should be called 3 times.');
        $tpaf->expectAt(0, 'duplicate', array(1, 101, array(), array()));
        $tpaf->expectAt(1, 'duplicate', array(2, 102, array(), array()));
        $tpaf->expectAt(2, 'duplicate', array(3, 103, array(), array()));
        $tf->setReturnValue('getPostActionFactory', $tpaf);
        
        $tf->duplicate($values, 1, $transitions, array(), false, false);
    }
}

class TransitionFactory_GetInstanceFromXmlTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->field = aMockField()->build();
        $this->from_value  = mock('Tracker_FormElement_Field_List_Value');
        $this->to_value    = mock('Tracker_FormElement_Field_List_Value');
        $this->xml_mapping = array('F1'     => $this->field,
                                   'F32-V1' => $this->from_value,
                                   'F32-V0' => $this->to_value);
        
        $this->factory     = TestHelper::getPartialMock('TransitionFactory', array());
        
    }
    
    public function itReconstitutesDatePostActions() {
        
        $xml = new SimpleXMLElement('
            <transition>
                <from_id REF="F32-V1"/>
                <to_id REF="F32-V0"/>
                <postactions>
                    <postaction_field_date valuetype="1">
                        <field_id REF="F1"/>
                    </postaction_field_date>
                </postactions>
            </transition>
        ');
        
        $transition   = $this->factory->getInstanceFromXML($xml, $this->xml_mapping);
        $post_actions = $transition->getPostActions();
        
        $this->assertCount($post_actions, 1);
        $this->assertIsA($post_actions[0], 'Transition_PostAction_Field_Date');
    }
    
    public function itReconstitutesIntPostActions() {
        
        $xml = new SimpleXMLElement('
            <transition>
                <from_id REF="F32-V1"/>
                <to_id REF="F32-V0"/>
                <postactions>
                    <postaction_field_int value="1">
                        <field_id REF="F1"/>
                    </postaction_field_int>
                </postactions>
            </transition>
        ');
        
        $transition   = $this->factory->getInstanceFromXML($xml, $this->xml_mapping);
        $post_actions = $transition->getPostActions();
        
        $this->assertCount($post_actions, 1);
        $this->assertIsA($post_actions[0], 'Transition_PostAction_Field_Int');
    }
    
    public function itReconstitutesFloatPostActions() {
        
        $xml = new SimpleXMLElement('
            <transition>
                <from_id REF="F32-V1"/>
                <to_id REF="F32-V0"/>
                <postactions>
                    <postaction_field_float value="1.2">
                        <field_id REF="F1"/>
                    </postaction_field_float>
                </postactions>
            </transition>
        ');
        
        $transition   = $this->factory->getInstanceFromXML($xml, $this->xml_mapping);
        $post_actions = $transition->getPostActions();
        
        $this->assertCount($post_actions, 1);
        $this->assertIsA($post_actions[0], 'Transition_PostAction_Field_Float');
    }
}

?>
