<?php
/*
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 
require_once(dirname(__FILE__).'/../../../include/workflow/PostAction/Transition_PostActionFactory.class.php');
Mock::generatePartial('Transition_PostActionFactory',
                      'Transition_PostActionFactoryTestVersion',
                      array('getDao', 'getFormElementFactory')
);
 
require_once(dirname(__FILE__).'/../../../include/workflow/PostAction/Field/dao/Transition_PostAction_Field_DateDao.class.php');
Mock::generate('Transition_PostAction_Field_DateDao');

require_once(dirname(__FILE__).'/../../../include/Tracker/FormElement/Tracker_FormElement_Field_Date.class.php');
Mock::generate('Tracker_FormElement_Field_Date');

Mock::generate('Tracker_FormElement_Field_List_Value');

require_once(dirname(__FILE__).'/../../builders/aMockField.php');
require_once(dirname(__FILE__).'/../../builders/aTransition.php');

class Transition_PostActionFactoryTest extends TuleapTestCase {
    
    public function itCanAddAPostActionToAnIntField() {
        $postaction = 'field_int';
        $transition = stub('Transition')->getTransitionId()->returns(123);
        $dao        = mock('Transition_PostAction_Field_IntDao');
        $factory    = new Transition_PostActionFactoryTestVersion();
        
        stub($factory)->getDao($postaction)->returns($dao);
        
        $dao->expectOnce('create', array($transition->getTransitionId()));
        $factory->addPostAction($transition, $postaction);
    }
    
    public function itLoadsIntFieldPostActions() {
        //Given
        $transition_id = 123;
        $field_id   = 456;
        $postaction_id = 789;
        $value = 666;
        $transition = stub('Transition')->getTransitionId()->returns($transition_id);
        $int_dao    = mock('Transition_PostAction_Field_IntDao');
        $float_dao  = mock('Transition_PostAction_Field_FloatDao');
        $date_dao   = mock('Transition_PostAction_Field_DateDao');        
        $factory    = new Transition_PostActionFactoryTestVersion();
        $formelement_factory = mock('Tracker_FormElementFactory');
        $field      = mock('Tracker_FormElement_Field_Integer');
        $post_action_rows = array(
            array('id' => $postaction_id, 'field_id' => $field_id, 'value' => $value)
        );
        stub($factory)->getFormElementFactory()->returns($formelement_factory);
        stub($factory)->getDao('field_date')->returns($date_dao);
        stub($factory)->getDao('field_int')->returns($int_dao);
        stub($factory)->getDao('field_float')->returns($float_dao);
        stub($formelement_factory)->getFormElementById($field_id)->returns($field);
        stub($date_dao)->searchByTransitionId($transition_id)->returns(array());
        stub($float_dao)->searchByTransitionId($transition_id)->returns(array());
        stub($int_dao)->searchByTransitionId($transition_id)->returns($post_action_rows);        
        $int_dao->expectOnce('searchByTransitionId', array($transition_id));
        $formelement_factory->expectOnce('getFormElementById', array($field_id));
        
        //aTransition()->withId($transition_id)->withFieldId($field_id)->build();
        $field_value_analyzed = new MockTracker_FormElement_Field_List_Value();
        $field_value_analyzed->setReturnValue('getId', 2068);
        $field_value_accepted = new MockTracker_FormElement_Field_List_Value();
        $field_value_accepted->setReturnValue('getId', 2069);
        $transition  = new Transition($transition_id, $field_id, $field_value_analyzed, $field_value_accepted);
        
        //When
        $factory->loadPostActions($transition);
        
        //Then
        $post_actions = $transition->getPostActions();
        $this->assertEqual(1, count($post_actions));
        $post_action = $post_actions[0];
        $this->assertIsA($post_action, 'Transition_PostAction_Field_Int');
        $this->assertIdentical($post_action->getTransition(), $transition);
    }
    
    public function itCanAddAPostActionToAFloatField() {
        $postaction = 'field_float';
        $transition = stub('Transition')->getTransitionId()->returns(123);
        $dao        = mock('Transition_PostAction_Field_FloatDao');
        $factory    = new Transition_PostActionFactoryTestVersion();
        
        stub($factory)->getDao($postaction)->returns($dao);
        
        $dao->expectOnce('create', array($transition->getTransitionId()));
        $factory->addPostAction($transition, $postaction);
    }
    
    public function itLoadsFloatFieldPostActions() {
        //Given
        $transition_id = 123;
        $field_id   = 456;
        $postaction_id = 789;
        $value = 666;
        $transition = stub('Transition')->getTransitionId()->returns($transition_id);
        $int_dao    = mock('Transition_PostAction_Field_IntDao');
        $float_dao  = mock('Transition_PostAction_Field_FloatDao');
        $date_dao   = mock('Transition_PostAction_Field_DateDao');        
        $factory    = new Transition_PostActionFactoryTestVersion();
        $formelement_factory = mock('Tracker_FormElementFactory');
        $field      = mock('Tracker_FormElement_Field_Float');
        $post_action_rows = array(
            array('id' => $postaction_id, 'field_id' => $field_id, 'value' => $value)
        );
        stub($factory)->getFormElementFactory()->returns($formelement_factory);
        stub($factory)->getDao('field_date')->returns($date_dao);
        stub($factory)->getDao('field_int')->returns($int_dao);
        stub($factory)->getDao('field_float')->returns($float_dao);
        stub($formelement_factory)->getFormElementById($field_id)->returns($field);
        stub($date_dao)->searchByTransitionId($transition_id)->returns(array());
        stub($float_dao)->searchByTransitionId($transition_id)->returns($post_action_rows);
        stub($int_dao)->searchByTransitionId($transition_id)->returns(array());        
        $float_dao->expectOnce('searchByTransitionId', array($transition_id));
        $formelement_factory->expectOnce('getFormElementById', array($field_id));
        
        //aTransition()->withId($transition_id)->withFieldId($field_id)->build();
        $field_value_analyzed = new MockTracker_FormElement_Field_List_Value();
        $field_value_analyzed->setReturnValue('getId', 2068);
        $field_value_accepted = new MockTracker_FormElement_Field_List_Value();
        $field_value_accepted->setReturnValue('getId', 2069);
        $transition  = new Transition($transition_id, $field_id, $field_value_analyzed, $field_value_accepted);
        
        //When
        $factory->loadPostActions($transition);
        
        //Then
        $post_actions = $transition->getPostActions();
        $this->assertEqual(1, count($post_actions));
        $post_action = $post_actions[0];
        $this->assertIsA($post_action, 'Transition_PostAction_Field_Float');
        $this->assertIdentical($post_action->getTransition(), $transition);
    }
    
    public function testDuplicate() {
        
        $tpaf = new Transition_PostActionFactoryTestVersion();
        $dao  = new MockTransition_PostAction_Field_DateDao();
        $dao->setReturnValue('duplicate', true);
        $tpaf->setReturnReference('getDao', $dao);
        
        $field_date1 = new MockTracker_FormElement_Field_Date();
        $field_date1->setReturnValue('getId', 2066);
        $field_date2 = new MockTracker_FormElement_Field_Date();
        $field_date2->setReturnValue('getId', 2067);
        
        $field_value_analyzed = new MockTracker_FormElement_Field_List_Value();
        $field_value_analyzed->setReturnValue('getId', 2068);
        $field_value_accepted = new MockTracker_FormElement_Field_List_Value();
        $field_value_accepted->setReturnValue('getId', 2069);
        
        $t1  = new Transition(1, 1, $field_value_analyzed, $field_value_accepted);       
        
        $tpa1 = new Transition_PostAction_Field_Date($t1, 1, $field_date1, 1);
        $tpa2 = new Transition_PostAction_Field_Date($t1, 2, $field_date2, 1);
        
        $transitions = array($t1);
             
        $field_mapping = array(
            1  => array('from'=>2066, 'to'=>3066),
            2  => array('from'=>2067, 'to'=>3067),
            3  => array('from'=>2068, 'to'=>3068),
            4  => array('from'=>2069, 'to'=>3069)
        );
        
        $postactions = array($tpa1, $tpa2);
        
        $dao->expectCallCount('duplicate', 2, 'Method getDao should be called 2 times.');
        
        $tpaf->duplicate(1, 2, $postactions, $field_mapping);
    }
}

class Transition_PostActionFactory_GetInstanceFromXmlTest extends TuleapTestCase {
    
    public function itReconstitutesDateFieldPostActionsFromXML() {
        $date_field = aMockField()->withId(62334)->build();
        
        $xml = new SimpleXMLElement('
            <postaction_field_date valuetype="1">
                <field_id REF="F1"/>
            </postaction_field_date>
        ');
        $xml_mapping = array('F1' => $date_field->getId());
        
        $transition = aTransition()->fromFieldValueId(2068)
                                   ->toFieldValueId(2069)
                                   ->build();
        
        $factory = new Transition_PostActionFactory();
        $post_action = $factory->getInstanceFromXML($xml, &$xml_mapping, $transition);
        $this->assertIsA($post_action, 'Transition_PostAction_Field_Date');
        $this->assertEqual($post_action->getValueType(), 1);
    }
    
    public function itReconstitutesIntFieldPostActionsFromXML() {
        $int_field = aMockField()->withId(62334)->build();
        
        $xml = new SimpleXMLElement('
            <postaction_field_int value="440">
                <field_id REF="F1"/>
            </postaction_field_int>
        ');
        $xml_mapping = array('F1' => $int_field->getId());
        
        $transition = aTransition()->fromFieldValueId(2068)
                                   ->toFieldValueId(2069)
                                   ->build();
        
        $factory = new Transition_PostActionFactory();
        $post_action = $factory->getInstanceFromXML($xml, &$xml_mapping, $transition);
        $this->assertIsA($post_action, 'Transition_PostAction_Field_Int');
        $this->assertEqual($post_action->getValue(), 440);
    }
    
    public function itReconstitutesFloatFieldPostActionsFromXML() {
        $float_field = aMockField()->withId(62334)->build();
        
        $xml = new SimpleXMLElement('
            <postaction_field_float value="64.42">
                <field_id REF="F1"/>
            </postaction_field_float>
        ');
        $xml_mapping = array('F1' => $float_field->getId());
        
        $transition = aTransition()->fromFieldValueId(2068)
                                   ->toFieldValueId(2069)
                                   ->build();
        
        $factory = new Transition_PostActionFactory();
        $post_action = $factory->getInstanceFromXML($xml, &$xml_mapping, $transition);
        $this->assertIsA($post_action, 'Transition_PostAction_Field_Float');
        $this->assertEqual($post_action->getValue(), 64.42);
    }
    
    public function itThrowsAnErrorWhenPostActionIsInvalid() {
        $xml = new SimpleXMLElement('
            <postaction_field_invalid foo="bar">
                <field_id REF="F1"/>
            </postaction_field_invalid>
        ');
        $xml_mapping = array();
        
        $transition = aTransition()->fromFieldValueId(2068)
                                   ->toFieldValueId(2069)
                                   ->build();
        
        $factory = new Transition_PostActionFactory();
        $this->expectException('Transition_InvalidPostActionException');
        $post_action = $factory->getInstanceFromXML($xml, &$xml_mapping, $transition);
    }
}

?>
