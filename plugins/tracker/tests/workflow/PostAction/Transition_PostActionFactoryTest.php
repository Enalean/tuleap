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

require_once dirname(__FILE__).'/../../builders/all.php';
require_once TRACKER_BASE_DIR .'/workflow/PostAction/Transition_PostActionFactory.class.php';
require_once TRACKER_BASE_DIR .'/workflow/PostAction/Field/dao/Transition_PostAction_Field_DateDao.class.php';
require_once TRACKER_BASE_DIR .'/workflow/PostAction/CIBuild/Transition_PostAction_CIBuildDao.class.php';
require_once TRACKER_BASE_DIR .'/Tracker/FormElement/Tracker_FormElement_Field_Date.class.php';

Mock::generatePartial('Transition_PostActionFactory',
                      'Transition_PostActionFactoryTestVersion',
                      array('getDao', 'getFormElementFactory'));

class Transition_PostActionFactory_BaseTest extends TuleapTestCase {

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
}

class Transition_PostActionFactory_AddPostActionTest extends Transition_PostActionFactory_BaseTest {

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

class Transition_PostActionFactory_DuplicateTest extends Transition_PostActionFactory_BaseTest {

    public function itDelegatesDuplicationToTheOtherPostActionFactories() {
        $field_mapping = array(
            1 => array('from'=>2066, 'to'=>3066),
            2 => array('from'=>2067, 'to'=>3067),
        );

        stub($this->field_factory)->duplicate($this->transition, 2, $field_mapping)->once();
        stub($this->cibuild_factory)->duplicate($this->transition, 2, $field_mapping)->once();

        $this->factory->duplicate($this->transition, 2, $field_mapping);
    }
}

class Transition_PostActionFactory_GetInstanceFromXmlTest extends Transition_PostActionFactory_BaseTest {

    public function itreturnsAFieldDatePostActionIfXmlCorrespondsToADate() {
        $xml = new SimpleXMLElement('
            <postactions>
                <postaction_field_date valuetype="1">
                    <field_id REF="F1"/>
                </postaction_field_date>
            </postactions>
        ');

        $mapping = array('F1' => 62334);

        stub($this->field_factory)
            ->getInstanceFromXML($xml->postaction_field_date, $mapping, $this->transition)
            ->returns(mock('Transition_PostAction_Field_Date'));

        $post_actions = $this->factory->getInstanceFromXML($xml, $mapping, $this->transition);
        $this->assertIsA($post_actions[0], 'Transition_PostAction_Field_Date');
    }

    public function itreturnsAFieldIntPostActionIfXmlCorrespondsToAInt() {
        $xml = new SimpleXMLElement('
            <postactions>
                <postaction_field_int valuetype="1">
                    <field_id REF="F1"/>
                </postaction_field_int>
            </postactions>
        ');

        $mapping = array('F1' => 62334);

        stub($this->field_factory)
            ->getInstanceFromXML($xml->postaction_field_int, $mapping, $this->transition)
            ->returns(mock('Transition_PostAction_Field_Int'));

        $post_actions = $this->factory->getInstanceFromXML($xml, $mapping, $this->transition);
        $this->assertIsA($post_actions[0], 'Transition_PostAction_Field_Int');
    }

    public function itreturnsAFieldFloatPostActionIfXmlCorrespondsToAFloat() {
        $xml = new SimpleXMLElement('
            <postactions>
                <postaction_field_float valuetype="3.14">
                    <field_id REF="F1"/>
                </postaction_field_float>
            </postactions>
        ');

        $mapping = array('F1' => 62334);

        stub($this->field_factory)
            ->getInstanceFromXML($xml->postaction_field_float, $mapping, $this->transition)
            ->returns(mock('Transition_PostAction_Field_Float'));

        $post_actions = $this->factory->getInstanceFromXML($xml, $mapping, $this->transition);
        $this->assertIsA($post_actions[0], 'Transition_PostAction_Field_Float');
    }

    public function itreturnsACIBuildPostActionIfXmlCorrespondsToACIBuild() {
        $xml = new SimpleXMLElement('
            <postactions>
                <postaction_ci_build job_url="http://www">
                </postaction_ci_build>
            </postactions>
        ');

        $mapping = array('F1' => 62334);

        stub($this->cibuild_factory)
            ->getInstanceFromXML($xml->postaction_ci_build, $mapping, $this->transition)
            ->returns(mock('Transition_PostAction_CIBuild'));

        $post_actions = $this->factory->getInstanceFromXML($xml, $mapping, $this->transition);
        $this->assertIsA($post_actions[0], 'Transition_PostAction_CIBuild');
    }

    public function itLoadsAllPostActionsFromXML() {
        $xml = new SimpleXMLElement('
            <postactions>
                <postaction_field_date valuetype="1">
                    <field_id REF="F1"/>
                </postaction_field_date>
                <postaction_ci_build job_url="http://www">
                </postaction_ci_build>
            </postactions>
        ');

        $mapping = array('F1' => 62334);

        stub($this->field_factory)
            ->getInstanceFromXML()
            ->returns(mock('Transition_PostAction_Field_Date'));

        stub($this->cibuild_factory)
            ->getInstanceFromXML()
            ->returns(mock('Transition_PostAction_CIBuild'));

        $post_actions = $this->factory->getInstanceFromXML($xml, $mapping, $this->transition);
        $this->assertIsA($post_actions[0], 'Transition_PostAction_Field_Date');
        $this->assertIsA($post_actions[1], 'Transition_PostAction_CIBuild');
    }
}
class Transition_PostActionFactory_SaveObjectTest extends Transition_PostActionFactory_BaseTest {

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

class Transition_PostActionFactory_DeleteWorkflowTest extends Transition_PostActionFactory_BaseTest {

    public function itDeletesAllPostActions() {
        $workflow_id = 10;
        stub($this->field_factory)->deleteWorkflow($workflow_id)->once()->returns(true);
        stub($this->cibuild_factory)->deleteWorkflow($workflow_id)->once()->returns(true);

        $this->factory->deleteWorkflow($workflow_id);
    }

}

class Transition_PostActionFactory_IsFieldUsedInPostActionsTest extends Transition_PostActionFactory_BaseTest {

    public function itChecksFieldIsUsedInEachTypeOfPostAction() {
        $field = mock('Tracker_FormElement_Field_Selectbox');

        stub($this->cibuild_factory)->isFieldUsedInPostActions($field)->once()->returns(false);
        stub($this->field_factory)->isFieldUsedInPostActions($field)->once()->returns(true);

        $this->factory->isFieldUsedInPostActions($field);
    }
}
?>
