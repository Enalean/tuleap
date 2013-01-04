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

Mock::generatePartial('Transition_PostActionFactory',
                      'Transition_PostActionFactoryTestVersion',
                      array('getDao', 'getFormElementFactory'));

class Transition_PostActionFactory_AddPostActionTest extends TuleapTestCase {
    
    protected $factory;
    protected $field_factory;
    protected $cibuild_factory;


    public function setUp() {
        parent::setUp();
        
        $this->transition_id = 123;
        $this->transition    = stub('Transition')->getTransitionId()->returns($this->transition_id);
        
        $mocked_methods = array('getFieldFactory', 'getCIBuildFactory');
        $this->factory = partial_mock('Transition_PostActionFactory', $mocked_methods);
        
        $this->field_factory = mock('Transition_PostAction_FieldFactory');
        $this->cibuild_factory = mock('Transition_PostAction_CIBuildFactory');
        
        stub($this->field_factory)->getTypes()->returns(
            array(
                Transition_PostAction_Field_Date::SHORT_NAME,
                Transition_PostAction_Field_Int::SHORT_NAME,
                Transition_PostAction_Field_Float::SHORT_NAME,
            )
        );
        
        stub($this->cibuild_factory)->getTypes()->returns(
            array(
                Transition_PostAction_CIBuild::SHORT_NAME
            )
        );
        
        stub($this->factory)->getFieldFactory()->returns($this->field_factory);
        stub($this->factory)->getCIBuildFactory()->returns($this->cibuild_factory);
    }
    
    public function itCanAddAPostActionToAnIntField() {
        stub($this->field_factory)->addPostAction()->once();
        stub($this->factory)->getCIBuildFactory()->never();
        
        $this->factory->addPostAction($this->transition, Transition_PostAction_Field_Int::SHORT_NAME);
    }
    
    public function itCanAddAPostActionToAFloatField() {
        stub($this->field_factory)->addPostAction()->once();
        stub($this->factory)->getCIBuildFactory()->never();
        
        $this->factory->addPostAction($this->transition, Transition_PostAction_Field_Float::SHORT_NAME);
    }

}

class Transition_PostActionFactory_DuplicateTest extends Transition_PostActionFactory_AddPostActionTest {
    
    public function itDelegatesDuplicationToTheOtherPostActionFactories() {
        $post_actions = array();
        
        $field_mapping = array(
            1 => array('from'=>2066, 'to'=>3066),
            2 => array('from'=>2067, 'to'=>3067),
        );
        
        stub($this->field_factory)->duplicate(1, 2, $post_actions, $field_mapping)->once();
        stub($this->cibuild_factory)->duplicate(1, 2, $post_actions, $field_mapping)->once();

        $this->factory->duplicate(1, 2, $post_actions, $field_mapping);
    }
}

class Transition_PostActionFactory_GetInstanceFromXmlTest extends Transition_PostActionFactory_AddPostActionTest {

    public function itreturnsAFieldPostActionIfXmlCorrespondsToField() {
        $xml = new SimpleXMLElement('
            <postaction_field_date valuetype="1">
                <field_id REF="F1"/>
            </postaction_field_date>
        ');
        
        $mapping = array('F1' => 62334);
        
        stub($this->field_factory)
            ->getInstanceFromXML($xml, $mapping, $this->transition)
            ->returns(mock('Transition_PostAction_Field_Date'));
        
        $post_action = $this->factory->getInstanceFromXML($xml, $mapping, $this->transition);
        $this->assertIsA($post_action, 'Transition_PostAction_Field_Date');
    }
    
    public function itreturnsACIBuildPostActionIfXmlCorrespondsToACIBuild() {
        $xml = new SimpleXMLElement('
            <postaction_ci_build valuetype="1">
                <field_id REF="F1"/>
            </postaction_ci_build>
        ');
        
        $mapping = array('F1' => 62334);
        
        stub($this->field_factory)
            ->getInstanceFromXML($xml, $mapping, $this->transition)
            ->returns(null);
        
        stub($this->cibuild_factory)
            ->getInstanceFromXML($xml, $mapping, $this->transition)
            ->returns(mock('Transition_PostAction_CIBuild'));
        
        $post_action = $this->factory->getInstanceFromXML($xml, $mapping, $this->transition);
        $this->assertIsA($post_action, 'Transition_PostAction_CIBuild');
    }
}

class Transition_PostActionFactory_SaveObjectTest extends Transition_PostActionFactory_AddPostActionTest {
    
    public function itSavesDateFieldPostActions() {
        $post_action = mock('Transition_PostAction_Field_Date');
        stub($this->cibuild_factory)->saveObject()->never();
        stub($this->field_factory)->saveObject($post_action)->once();

        $this->factory->saveObject($post_action);
    }
    
    public function itSavesIntFieldPostActions() {
        $post_action = mock('Transition_PostAction_Field_Int');
        stub($this->cibuild_factory)->saveObject()->never();
        stub($this->field_factory)->saveObject($post_action)->once();

        $this->factory->saveObject($post_action);
    }
    
    public function itSavesFloatFieldPostActions() {
        $post_action = mock('Transition_PostAction_Field_Float');
        stub($this->cibuild_factory)->saveObject()->never();
        stub($this->field_factory)->saveObject($post_action)->once();

        $this->factory->saveObject($post_action);
    }
    
    public function itSavesCIBuildPostActions() {
        $post_action = mock('Transition_PostAction_CIBuild');
        stub($this->field_factory)->saveObject()->never();
        stub($this->cibuild_factory)->saveObject($post_action)->once();

        $this->factory->saveObject($post_action);
    }
    
}

class Transition_PostActionFactory_DeleteWorkflowTest extends Transition_PostActionFactory_AddPostActionTest {
    
    public function itDeletesAllPostActions() {
        $workflow_id = 10;
        stub($this->field_factory)->deleteWorkflow($workflow_id)->once()->returns(true);
        stub($this->cibuild_factory)->deleteWorkflow($workflow_id)->once()->returns(true);
        
        $this->factory->deleteWorkflow($workflow_id);
    }
    
}

class Transition_PostActionFactory_IsFieldUsedInPostActionsTest extends Transition_PostActionFactory_AddPostActionTest {
    
    public function itChecksFieldIsUsedInEachTypeOfPostAction() {
        $field = mock('Tracker_FormElement_Field');
        
        stub($this->cibuild_factory)->isFieldUsedInPostActions($field)->once()->returns(false);
        stub($this->field_factory)->isFieldUsedInPostActions($field)->once()->returns(true);
        
        $this->factory->isFieldUsedInPostActions($field);
    }
}
?>
