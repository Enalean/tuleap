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
require_once(dirname(__FILE__).'/../../builders/aDateFieldPostAction.php');
require_once(dirname(__FILE__).'/../../builders/anIntFieldPostAction.php');
require_once(dirname(__FILE__).'/../../builders/aFloatFieldPostAction.php');

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
    
    public function setUp() {
        parent::setUp();
        
        $this->factory    = new Transition_PostActionFactory();
        $this->field      = aMockField()->withId(62334)->build();
        $this->mapping    = array('F1' => $this->field->getId());
        $this->transition = aTransition()->fromFieldValueId(2068)
                                         ->toFieldValueId(2069)
                                         ->build();
    }
    
    public function itReconstitutesDateFieldPostActionsFromXML() {
        $xml = new SimpleXMLElement('
            <postaction_field_date valuetype="1">
                <field_id REF="F1"/>
            </postaction_field_date>
        ');
        
        $post_action = $this->factory->getInstanceFromXML($xml, &$this->mapping, $this->transition);
        
        $this->assertIsA($post_action, 'Transition_PostAction_Field_Date');
        $this->assertEqual($post_action->getValueType(), 1);
    }
    
    public function itReconstitutesIntFieldPostActionsFromXML() {
        $xml = new SimpleXMLElement('
            <postaction_field_int value="440">
                <field_id REF="F1"/>
            </postaction_field_int>
        ');
        
        $post_action = $this->factory->getInstanceFromXML($xml, &$this->mapping, $this->transition);
        
        $this->assertIsA($post_action, 'Transition_PostAction_Field_Int');
        $this->assertEqual($post_action->getValue(), 440);
    }
    
    public function itReconstitutesFloatFieldPostActionsFromXML() {
        $xml = new SimpleXMLElement('
            <postaction_field_float value="64.42">
                <field_id REF="F1"/>
            </postaction_field_float>
        ');
        
        $post_action = $this->factory->getInstanceFromXML($xml, &$this->mapping, $this->transition);
        
        $this->assertIsA($post_action, 'Transition_PostAction_Field_Float');
        $this->assertEqual($post_action->getValue(), 64.42);
    }
    
    public function itThrowsAnErrorWhenPostActionIsInvalid() {
        $xml = new SimpleXMLElement('
            <postaction_field_invalid foo="bar">
                <field_id REF="F1"/>
            </postaction_field_invalid>
        ');
        
        $this->expectException('Transition_InvalidPostActionException');
        
        $this->factory->getInstanceFromXML($xml, &$this->mapping, $this->transition);
    }
}

class Transition_PostActionFactory_SaveObjectTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $this->factory = new Transition_PostActionFactoryTestVersion();
        
        $this->date_dao  = mock('Transition_PostAction_Field_DateDao');
        $this->int_dao   = mock('Transition_PostAction_Field_IntDao');
        $this->float_dao = mock('Transition_PostAction_Field_FloatDao');
        
        stub($this->factory)->getDao('field_date')->returns($this->date_dao);
        stub($this->factory)->getDao('field_int')->returns($this->int_dao);
        stub($this->factory)->getDao('field_float')->returns($this->float_dao);        
    }
    
    public function itSavesDateFieldPostActions() {
        $post_action = aDateFieldPostAction()->withTransitionId(123)
                                             ->withFieldId(456)
                                             ->withValueType(1)
                                             ->build();
        $this->date_dao->expectOnce('save', array(123, 456, 1));
        $this->factory->saveObject($post_action);
    }
    
    public function itSavesIntFieldPostActions() {
        $post_action = anIntFieldPostAction()->withTransitionId(123)
                                             ->withFieldId(456)
                                             ->withValue(0)
                                             ->build();
        $this->int_dao->expectOnce('save', array(123, 456, 0));
        $this->factory->saveObject($post_action);
    }
    
    public function itSavesFloatFieldPostActions() {
        $post_action = aFloatFieldPostAction()->withTransitionId(123)
                                               ->withFieldId(456)
                                               ->withValue(0)
                                               ->build();
        $this->float_dao->expectOnce('save', array(123, 456, 0));
        $this->factory->saveObject($post_action);
    }
}

class Transition_PostActionFactory_DeleteWorkflowTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $this->factory = new Transition_PostActionFactoryTestVersion();
        
        $this->date_dao  = mock('Transition_PostAction_Field_DateDao');
        $this->int_dao   = mock('Transition_PostAction_Field_IntDao');
        $this->float_dao = mock('Transition_PostAction_Field_FloatDao');
        
        stub($this->factory)->getDao('field_date')->returns($this->date_dao);
        stub($this->factory)->getDao('field_int')->returns($this->int_dao);
        stub($this->factory)->getDao('field_float')->returns($this->float_dao);  
        
        $this->workflow_id = 1;
    }
    
    public function itDeletesAllFieldsPostActions() {
        $this->date_dao->expectOnce('deletePostActionsByWorkflowId', array($this->workflow_id));
        $this->int_dao->expectOnce('deletePostActionsByWorkflowId', array($this->workflow_id));
        $this->float_dao->expectOnce('deletePostActionsByWorkflowId', array($this->workflow_id));
        
        $this->factory->deleteWorkflow($this->workflow_id);
    }
    
    public function itReturnsTrueWhenAllDeletionsSucceed() {
        stub($this->date_dao)->deletePostActionsByWorkflowId('*')->returns(true);
        stub($this->int_dao)->deletePostActionsByWorkflowId('*')->returns(true);
        stub($this->float_dao)->deletePostActionsByWorkflowId('*')->returns(true);
        
        $this->assertTrue($this->factory->deleteWorkflow($this->workflow_id));
    }
    
    public function itReturnsFalseWhenAnyDeletionFails() {
        stub($this->date_dao)->deletePostActionsByWorkflowId('*')->returns(true);
        stub($this->int_dao)->deletePostActionsByWorkflowId('*')->returns(false);
        stub($this->float_dao)->deletePostActionsByWorkflowId('*')->returns(true);
        
        $this->assertFalse($this->factory->deleteWorkflow($this->workflow_id));
    }
}

class Transition_PostActionFactory_IsFieldUsedInPostActionsTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $this->factory = new Transition_PostActionFactoryTestVersion();
        
        $this->date_dao  = mock('Transition_PostAction_Field_DateDao');
        $this->int_dao   = mock('Transition_PostAction_Field_IntDao');
        $this->float_dao = mock('Transition_PostAction_Field_FloatDao');
        
        stub($this->factory)->getDao('field_date')->returns($this->date_dao);
        stub($this->factory)->getDao('field_int')->returns($this->int_dao);
        stub($this->factory)->getDao('field_float')->returns($this->float_dao);        
        
        $this->field_id = 45617;
        $this->field    = aMockField()->withId($this->field_id)->build();
    }
    
    public function itIsTrueWhenFieldIsUsedInADatePostAction() {
        stub($this->date_dao)->countByFieldId($this->field_id)->returns(1);
        stub($this->int_dao)->countByFieldId($this->field_id)->returns(0);
        stub($this->float_dao)->countByFieldId($this->field_id)->returns(0);
        
        $this->assertTrue($this->factory->isFieldUsedInPostActions($this->field));
    }
    
    public function itIsTrueWhenFieldIsUsedInAnIntPostAction() {
        stub($this->date_dao)->countByFieldId($this->field_id)->returns(0);
        stub($this->int_dao)->countByFieldId($this->field_id)->returns(2);
        stub($this->float_dao)->countByFieldId($this->field_id)->returns(0);
        
        $this->assertTrue($this->factory->isFieldUsedInPostActions($this->field));
    }
    
    public function itIsTrueWhenFieldIsUsedInAFloatPostAction() {
        stub($this->date_dao)->countByFieldId($this->field_id)->returns(0);
        stub($this->int_dao)->countByFieldId($this->field_id)->returns(0);
        stub($this->float_dao)->countByFieldId($this->field_id)->returns(3);
        
        $this->assertTrue($this->factory->isFieldUsedInPostActions($this->field));
    }
    
    public function itIsTrueWhenFieldIsUsedInMultiplePostActions() {
        stub($this->date_dao)->countByFieldId($this->field_id)->returns(1);
        stub($this->int_dao)->countByFieldId($this->field_id)->returns(2);
        stub($this->float_dao)->countByFieldId($this->field_id)->returns(3);
        
        $this->assertTrue($this->factory->isFieldUsedInPostActions($this->field));
    }
    public function itIsFalseWhenFieldIsNotUsedInAnyPostAction() {
        stub($this->date_dao)->countByFieldId($this->field_id)->returns(0);
        stub($this->int_dao)->countByFieldId($this->field_id)->returns(0);
        stub($this->float_dao)->countByFieldId($this->field_id)->returns(0);
        
        $this->assertFalse($this->factory->isFieldUsedInPostActions($this->field));
    }
}

?>
