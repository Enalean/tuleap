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

require_once dirname(__FILE__).'/../../../include/workflow/PostAction/Transition_PostActionFactory.class.php';
require_once dirname(__FILE__).'/../../../include/workflow/PostAction/Field/dao/Transition_PostAction_Field_DateDao.class.php';
require_once dirname(__FILE__).'/../../../include/workflow/PostAction/CIBuild/Transition_PostAction_CIBuildDao.class.php';
require_once dirname(__FILE__).'/../../../include/Tracker/FormElement/Tracker_FormElement_Field_Date.class.php';
require_once dirname(__FILE__).'/../../builders/aMockField.php';
require_once dirname(__FILE__).'/../../builders/aTransition.php';
require_once dirname(__FILE__).'/../../builders/aDateFieldPostAction.php';
require_once dirname(__FILE__).'/../../builders/anIntFieldPostAction.php';
require_once dirname(__FILE__).'/../../builders/aFloatFieldPostAction.php';
require_once dirname(__FILE__).'/../../builders/aPostActionFactory.php';
require_once dirname(__FILE__).'/../../builders/aCIBuildPostAction.php';

Mock::generatePartial('Transition_PostActionFactory',
                      'Transition_PostActionFactoryTestVersion',
                      array('getDao', 'getFormElementFactory'));

class Transition_PostActionFactory_AddPostActionTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $this->transition_id = 123;
        $this->transition    = stub('Transition')->getTransitionId()->returns($this->transition_id);
    }
    
    public function itCanAddAPostActionToAnIntField() {
        $dao     = mock('Transition_PostAction_Field_IntDao');
        $factory = aPostActionFactory()->withFieldIntDao($dao)->build();
        
        $dao->expectOnce('create', array($this->transition_id));
        $factory->addPostAction($this->transition, 'field_int');
    }
    
    public function itCanAddAPostActionToAFloatField() {
        $dao     = mock('Transition_PostAction_Field_FloatDao');
        $factory = aPostActionFactory()->withFieldFloatDao($dao)->build();
        
        $dao->expectOnce('create', array($this->transition_id));
        $factory->addPostAction($this->transition, 'field_float');
    }

}

class Transition_PostActionFactory_DuplicateTest extends TuleapTestCase {
    
    public function itDelegatesDuplicationToTheCorrespondingDao() {
        $dao     = stub('Transition_PostAction_Field_DateDao')->duplicate()->returns(true);
        $factory = aPostActionFactory()->withFieldDateDao($dao)->build();
        
        $post_actions = array(aDateFieldPostAction()->withFieldId(2066)->build(),
                              aDateFieldPostAction()->withFieldId(2067)->build());
        
        $field_mapping = array(1 => array('from'=>2066, 'to'=>3066),
                               2 => array('from'=>2067, 'to'=>3067));
        
        $dao->expectCallCount('duplicate', 2, 'Method getDao should be called 2 times.');
        $factory->duplicate(1, 2, $post_actions, $field_mapping);
    }
}

class Transition_PostActionFactory_GetInstanceFromXmlTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $this->factory    = new Transition_PostActionFactory();
        $this->mapping    = array('F1' => 62334);
        $this->transition = aTransition()->build();
    }
    
    public function itReconstitutesDateFieldPostActionsFromXML() {
        $xml = new SimpleXMLElement('
            <postaction_field_date valuetype="1">
                <field_id REF="F1"/>
            </postaction_field_date>
        ');
        
        $post_action = $this->factory->getInstanceFromXML($xml, $this->mapping, $this->transition);
        
        $this->assertIsA($post_action, 'Transition_PostAction_Field_Date');
        $this->assertEqual($post_action->getValueType(), 1);
    }
    
    public function itReconstitutesIntFieldPostActionsFromXML() {
        $xml = new SimpleXMLElement('
            <postaction_field_int value="440">
                <field_id REF="F1"/>
            </postaction_field_int>
        ');
        
        $post_action = $this->factory->getInstanceFromXML($xml, $this->mapping, $this->transition);
        
        $this->assertIsA($post_action, 'Transition_PostAction_Field_Int');
        $this->assertEqual($post_action->getValue(), 440);
    }
    
    public function itReconstitutesFloatFieldPostActionsFromXML() {
        $xml = new SimpleXMLElement('
            <postaction_field_float value="64.42">
                <field_id REF="F1"/>
            </postaction_field_float>
        ');
        
        $post_action = $this->factory->getInstanceFromXML($xml, $this->mapping, $this->transition);
        
        $this->assertIsA($post_action, 'Transition_PostAction_Field_Float');
        $this->assertEqual($post_action->getValue(), 64.42);
    }
    
    public function itThrowsAnErrorWhenPostActionIsInvalid() {
        $xml = new SimpleXMLElement('
            <postaction_field_invalid foo="bar">
                <field_id REF="F1"/>
            </postaction_field_invalid>
        ');
        
        $this->expectException('Transition_PostAction_NotFoundException');
        
        $this->factory->getInstanceFromXML($xml, $this->mapping, $this->transition);
    }
}

class Transition_PostActionFactory_SaveObjectTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $this->factory = new Transition_PostActionFactoryTestVersion();
        
        $this->date_dao     = mock('Transition_PostAction_Field_DateDao');
        $this->int_dao      = mock('Transition_PostAction_Field_IntDao');
        $this->float_dao    = mock('Transition_PostAction_Field_FloatDao');
        $this->ci_build_dao = mock('Transition_PostAction_CIBuildDao');
        
        stub($this->factory)->getDao('field_date')->returns($this->date_dao);
        stub($this->factory)->getDao('field_int')->returns($this->int_dao);
        stub($this->factory)->getDao('field_float')->returns($this->float_dao);
        stub($this->factory)->getDao('ci_build')->returns($this->ci_build_dao);
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

    public function itSavesCIBuildPostActions() {
        $ci_client = mock('Jenkins_Client');
        $post_action = aCIBuildPostAction()->withTransitionId(123)
                                           ->withValue('http://www')
                                           ->withCIClient($ci_client)
                                           ->build();
        $this->ci_build_dao->expectOnce('save', array(123, 'http://www'));
        $this->factory->saveObject($post_action);
    }
}

class Transition_PostActionFactory_DeleteWorkflowTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $this->factory = new Transition_PostActionFactoryTestVersion();
        
        $this->date_dao          = mock('Transition_PostAction_Field_DateDao');
        $this->int_dao           = mock('Transition_PostAction_Field_IntDao');
        $this->float_dao         = mock('Transition_PostAction_Field_FloatDao');
        $this->jenkins_build_dao = mock('Transition_PostAction_CIBuildDao');

        stub($this->factory)->getDao('field_date')->returns($this->date_dao);
        stub($this->factory)->getDao('field_int')->returns($this->int_dao);
        stub($this->factory)->getDao('field_float')->returns($this->float_dao);
        stub($this->factory)->getDao('ci_build')->returns($this->jenkins_build_dao);

        $this->workflow_id = 1;
    }
    
    public function itDeletesAllFieldsPostActions() {
        $this->date_dao->expectOnce('deletePostActionsByWorkflowId', array($this->workflow_id));
        $this->int_dao->expectOnce('deletePostActionsByWorkflowId', array($this->workflow_id));
        $this->float_dao->expectOnce('deletePostActionsByWorkflowId', array($this->workflow_id));
        $this->jenkins_build_dao->expectOnce('deletePostActionsByWorkflowId', array($this->workflow_id));

        $this->factory->deleteWorkflow($this->workflow_id);
    }
    
    public function itReturnsTrueWhenAllDeletionsSucceed() {
        stub($this->date_dao)->deletePostActionsByWorkflowId('*')->returns(true);
        stub($this->int_dao)->deletePostActionsByWorkflowId('*')->returns(true);
        stub($this->float_dao)->deletePostActionsByWorkflowId('*')->returns(true);
        stub($this->jenkins_build_dao)->deletePostActionsByWorkflowId('*')->returns(true);

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
        
        $this->date_dao          = mock('Transition_PostAction_Field_DateDao');
        $this->int_dao           = mock('Transition_PostAction_Field_IntDao');
        $this->float_dao         = mock('Transition_PostAction_Field_FloatDao');
        
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
