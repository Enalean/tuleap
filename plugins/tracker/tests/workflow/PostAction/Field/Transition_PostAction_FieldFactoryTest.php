<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once dirname(__FILE__).'/../../../builders/aPostActionFieldFactory.php';
require_once dirname(__FILE__).'/../../../builders/anIntFieldPostAction.php';
require_once dirname(__FILE__).'/../../../builders/aFloatFieldPostAction.php';

class Transition_PostAction_FieldFactory_BaseTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $this->date_dao  = mock('Transition_PostAction_Field_DateDao');
        $this->int_dao   = mock('Transition_PostAction_Field_IntDao');
        $this->float_dao = mock('Transition_PostAction_Field_FloatDao');
        $this->element_factory = mock('Tracker_FormElementFactory');
        $this->factory = new Transition_PostAction_FieldFactory($this->element_factory, $this->date_dao, $this->int_dao, $this->float_dao);
    }
}

class Transition_PostAction_FieldFactoryTest extends Transition_PostAction_FieldFactory_BaseTest {

    protected $factory;

    public function setUp() {
        parent::setUp();

        $this->transition_id  = 123;
        $this->field_id       = 456;
        $this->post_action_id = 789;

        $this->transition = aTransition()->withId($this->transition_id)->build();
        $this->factory    = partial_mock(
            'Transition_PostAction_FieldFactory',
            array('loadPostActionRows'),
            array($this->element_factory, $this->date_dao, $this->int_dao, $this->float_dao)
        );
    }

    public function itLoadsIntFieldPostActions() {
        $post_action_value = 12;
        $post_action_rows  = array(
            array(
                'id'       => $this->post_action_id,
                'field_id' => $this->field_id,
                'value'    => $post_action_value
                )
            );

        stub($this->factory)
            ->loadPostActionRows($this->transition, Transition_PostAction_Field_Int::SHORT_NAME)
            ->returns($post_action_rows);
        stub($this->factory)
            ->loadPostActionRows($this->transition, Transition_PostAction_Field_Float::SHORT_NAME)
            ->returns(array());
        stub($this->factory)
            ->loadPostActionRows($this->transition, Transition_PostAction_Field_Date::SHORT_NAME)
            ->returns(array());


        $post_action_array = $this->factory->loadPostActions($this->transition);
        $this->assertCount($post_action_array, 1);

        $first_pa = $post_action_array[0];
        $this->assertEqual($first_pa->getId(), $this->post_action_id);
        $this->assertEqual($first_pa->getTransition(), $this->transition);
        $this->assertEqual($first_pa->getValue(), $post_action_value);
    }

    public function itLoadsFloatFieldPostActions() {
        $post_action_value = 12;
        $post_action_rows  = array(
            array(
                'id'       => $this->post_action_id,
                'field_id' => $this->field_id,
                'value'    => $post_action_value
                )
            );

        stub($this->factory)
            ->loadPostActionRows($this->transition, Transition_PostAction_Field_Int::SHORT_NAME)
            ->returns(array());
        stub($this->factory)
            ->loadPostActionRows($this->transition, Transition_PostAction_Field_Float::SHORT_NAME)
            ->returns($post_action_rows);
        stub($this->factory)
            ->loadPostActionRows($this->transition, Transition_PostAction_Field_Date::SHORT_NAME)
            ->returns(array());


        $post_action_array = $this->factory->loadPostActions($this->transition);
        $this->assertCount($post_action_array, 1);

        $first_pa = $post_action_array[0];
        $this->assertEqual($first_pa->getId(), $this->post_action_id);
        $this->assertEqual($first_pa->getTransition(), $this->transition);
        $this->assertEqual($first_pa->getValue(), $post_action_value);
    }

    public function itLoadsDateFieldPostActions() {
        $post_action_value = 12;
        $post_action_rows  = array(
            array(
                'id'       => $this->post_action_id,
                'field_id' => $this->field_id,
                'value_type'    => $post_action_value
                )
            );

        stub($this->factory)
            ->loadPostActionRows($this->transition, Transition_PostAction_Field_Int::SHORT_NAME)
            ->returns(array());
        stub($this->factory)
            ->loadPostActionRows($this->transition, Transition_PostAction_Field_Float::SHORT_NAME)
            ->returns(array());
        stub($this->factory)
            ->loadPostActionRows($this->transition, Transition_PostAction_Field_Date::SHORT_NAME)
            ->returns($post_action_rows);


        $post_action_array = $this->factory->loadPostActions($this->transition);
        $this->assertCount($post_action_array, 1);

        $first_pa = $post_action_array[0];
        $this->assertEqual($first_pa->getId(), $this->post_action_id);
        $this->assertEqual($first_pa->getTransition(), $this->transition);
        $this->assertEqual($first_pa->getValueType(), $post_action_value);
    }
}

class Transition_PostActionFieldFactory_AddPostActionTest extends Transition_PostAction_FieldFactory_BaseTest {

    public function setUp() {
        parent::setUp();

        $this->transition_id = 123;
        $this->transition    = stub('Transition')->getTransitionId()->returns($this->transition_id);
    }

    public function itCanAddAPostActionToAnIntField() {
        $this->int_dao->expectOnce('create', array($this->transition_id));
        $this->factory->addPostAction($this->transition, 'field_int');
    }

    public function itCanAddAPostActionToAFloatField() {
        $this->float_dao->expectOnce('create', array($this->transition_id));
        $this->factory->addPostAction($this->transition, 'field_float');
    }

    public function itCanAddAPostActionToADateField() {
        $this->date_dao->expectOnce('create', array($this->transition_id));
        $this->factory->addPostAction($this->transition, 'field_date');
    }

}

class Transition_PostActionFieldFactory_DuplicateTest extends Transition_PostAction_FieldFactory_BaseTest {

    public function itDelegatesDuplicationToTheCorrespondingDao() {
        $post_actions = array(aDateFieldPostAction()->withFieldId(2066)->build(),
                              aDateFieldPostAction()->withFieldId(2067)->build());

        $field_mapping = array(1 => array('from'=>2066, 'to'=>3066),
                               2 => array('from'=>2067, 'to'=>3067));

        $this->date_dao->expectCallCount('duplicate', 2, 'Method getDao should be called 2 times.');
        $this->factory->duplicate(1, 2, $post_actions, $field_mapping);
    }
}

class Transition_PostActionFieldFactory_GetInstanceFromXmlTest extends Transition_PostAction_FieldFactory_BaseTest {

    public function setUp() {
        parent::setUp();

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

class Transition_PostActionFieldFactory_SaveObjectTest extends Transition_PostAction_FieldFactory_BaseTest {

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

class Transition_PostActionFieldFactory_DeleteWorkflowTest extends Transition_PostAction_FieldFactory_BaseTest {

    private $workflow_id = 1;

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

class Transition_PostActionFieldFactory_IsFieldUsedInPostActionsTest extends Transition_PostAction_FieldFactory_BaseTest {

    public function setUp() {
        parent::setUp();

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
