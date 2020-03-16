<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

require_once __DIR__ . '/../../../bootstrap.php';

class Transition_PostAction_FieldFactory_BaseTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->transition_id = 123;
        $this->transition    = new Transition(
            $this->transition_id,
            0,
            null,
            null
        );

        $this->date_dao  = mock('Transition_PostAction_Field_DateDao');
        $this->int_dao   = mock('Transition_PostAction_Field_IntDao');
        $this->float_dao = mock('Transition_PostAction_Field_FloatDao');
        $this->element_factory = mock('Tracker_FormElementFactory');
        $this->factory = new Transition_PostAction_FieldFactory($this->element_factory, $this->date_dao, $this->int_dao, $this->float_dao);
    }
}

class Transition_PostAction_FieldFactoryTest extends Transition_PostAction_FieldFactory_BaseTest
{

    public function setUp()
    {
        parent::setUp();

        $this->field_id          = 456;
        $this->post_action_id    = 789;
        $this->post_action_value = 12;
        $this->post_action_rows  = array(
            'id'       => $this->post_action_id,
            'field_id' => $this->field_id,
            'value'    => $this->post_action_value
        );
    }

    public function itLoadsIntFieldPostActions()
    {
        stub($this->int_dao)
            ->searchByTransitionId($this->transition_id)
            ->returnsDar($this->post_action_rows);
        stub($this->float_dao)
            ->searchByTransitionId($this->transition_id)
            ->returnsEmptyDar();
        stub($this->date_dao)
            ->searchByTransitionId($this->transition_id)
            ->returnsEmptyDar();

        $post_action_array = $this->factory->loadPostActions($this->transition);
        $this->assertCount($post_action_array, 1);

        $first_pa = $post_action_array[0];
        $this->assertEqual($first_pa->getId(), $this->post_action_id);
        $this->assertEqual($first_pa->getTransition(), $this->transition);
        $this->assertEqual($first_pa->getValue(), $this->post_action_value);
    }

    public function itLoadsFloatFieldPostActions()
    {
        stub($this->int_dao)
            ->searchByTransitionId($this->transition_id)
            ->returnsEmptyDar();
        stub($this->float_dao)
            ->searchByTransitionId($this->transition_id)
            ->returnsDar($this->post_action_rows);
        stub($this->date_dao)
            ->searchByTransitionId($this->transition_id)
            ->returnsEmptyDar();

        $post_action_array = $this->factory->loadPostActions($this->transition);
        $this->assertCount($post_action_array, 1);

        $first_pa = $post_action_array[0];
        $this->assertEqual($first_pa->getId(), $this->post_action_id);
        $this->assertEqual($first_pa->getTransition(), $this->transition);
        $this->assertEqual($first_pa->getValue(), $this->post_action_value);
    }

    public function itLoadsDateFieldPostActions()
    {
        $post_action_rows  = array(
            'id'         => $this->post_action_id,
            'field_id'   => $this->field_id,
            'value_type' => $this->post_action_value
        );

        stub($this->int_dao)
            ->searchByTransitionId($this->transition_id)
            ->returnsEmptyDar();
        stub($this->float_dao)
            ->searchByTransitionId($this->transition_id)
            ->returnsEmptyDar();
        stub($this->date_dao)
            ->searchByTransitionId($this->transition_id)
            ->returnsDar($post_action_rows);

        $post_action_array = $this->factory->loadPostActions($this->transition);
        $this->assertCount($post_action_array, 1);

        $first_pa = $post_action_array[0];
        $this->assertEqual($first_pa->getId(), $this->post_action_id);
        $this->assertEqual($first_pa->getTransition(), $this->transition);
        $this->assertEqual($first_pa->getValueType(), $this->post_action_value);
    }
}

class Transition_PostActionFieldFactory_DuplicateTest extends Transition_PostAction_FieldFactory_BaseTest
{

    public function setUp()
    {
        parent::setUp();

        stub($this->element_factory)->getFormElementById(2065)->returns(aSelectBoxField()->withId(2065)->build());
        stub($this->element_factory)->getFormElementById(2066)->returns(aSelectBoxField()->withId(2066)->build());
        stub($this->element_factory)->getFormElementById(2067)->returns(aSelectBoxField()->withId(2067)->build());

        stub($this->date_dao)->searchByTransitionId($this->transition_id)->returnsEmptyDar();
        stub($this->float_dao)->searchByTransitionId($this->transition_id)->returnsDar(
            array(
                'id'       => 1,
                'field_id' => 2065,
                'value'    => 0
            )
        );
        stub($this->int_dao)->searchByTransitionId($this->transition_id)->returnsDar(
            array(
                'id'       => 1,
                'field_id' => 2066,
                'value'    => 666
            ),
            array(
                'id'       => 2,
                'field_id' => 2067,
                'value'    => 42
            )
        );
    }

    public function itDelegatesDuplicationToTheCorrespondingDao()
    {
        $field_mapping = array(1 => array('from' => 2066, 'to' => 3066),
                               2 => array('from' => 2067, 'to' => 3067),
                               3 => array('from' => 2065, 'to' => 3065),);

        expect($this->float_dao)->duplicate(123, 124, 2065, 3065)->once();
        expect($this->int_dao)->duplicate()->count(2);
        $this->factory->duplicate($this->transition, 124, $field_mapping);
    }
}

class Transition_PostActionFieldFactory_GetInstanceFromXmlTest extends Transition_PostAction_FieldFactory_BaseTest
{

    public function setUp()
    {
        parent::setUp();

        $this->mapping = array('F1' => 62334);
    }

    public function itReconstitutesDateFieldPostActionsFromXML()
    {
        $xml = new SimpleXMLElement('
            <postaction_field_date valuetype="1">
                <field_id REF="F1"/>
            </postaction_field_date>
        ');

        $post_action = $this->factory->getInstanceFromXML($xml, $this->mapping, $this->transition);

        $this->assertIsA($post_action, 'Transition_PostAction_Field_Date');
        $this->assertEqual($post_action->getValueType(), 1);
    }

    public function itReconstitutesIntFieldPostActionsFromXML()
    {
        $xml = new SimpleXMLElement('
            <postaction_field_int value="440">
                <field_id REF="F1"/>
            </postaction_field_int>
        ');

        $post_action = $this->factory->getInstanceFromXML($xml, $this->mapping, $this->transition);

        $this->assertIsA($post_action, 'Transition_PostAction_Field_Int');
        $this->assertEqual($post_action->getValue(), 440);
    }

    public function itReconstitutesFloatFieldPostActionsFromXML()
    {
        $xml = new SimpleXMLElement('
            <postaction_field_float value="64.42">
                <field_id REF="F1"/>
            </postaction_field_float>
        ');

        $post_action = $this->factory->getInstanceFromXML($xml, $this->mapping, $this->transition);

        $this->assertIsA($post_action, 'Transition_PostAction_Field_Float');
        $this->assertEqual($post_action->getValue(), 64.42);
    }

    public function itThrowsAnErrorWhenPostActionIsInvalid()
    {
        $xml = new SimpleXMLElement('
            <postaction_field_invalid foo="bar">
                <field_id REF="F1"/>
            </postaction_field_invalid>
        ');

        $this->expectException('Transition_PostAction_NotFoundException');

        $this->factory->getInstanceFromXML($xml, $this->mapping, $this->transition);
    }
}

class Transition_PostActionFieldFactory_SaveObjectTest extends Transition_PostAction_FieldFactory_BaseTest
{

    public function itSavesDateFieldPostActions()
    {
        $post_action = new Transition_PostAction_Field_Date(
            Mockery::mock(Transition::class)->shouldReceive('getId')->andReturn(123)->getMock(),
            0,
            Mockery::mock(Tracker_FormElement_Field_Date::class)->shouldReceive('getId')->andReturn(456)->getMock(),
            1
        );

        $this->date_dao->expectOnce('save', array(123, 456, 1));
        $this->factory->saveObject($post_action);
    }

    public function itSavesIntFieldPostActions()
    {
        $post_action = new Transition_PostAction_Field_Int(
            Mockery::mock(Transition::class)->shouldReceive('getId')->andReturn(123)->getMock(),
            0,
            Mockery::mock(Tracker_FormElement_Field_Integer::class)->shouldReceive('getId')->andReturn(456)->getMock(),
            0
        );

        $this->int_dao->expectOnce('save', array(123, 456, 0));
        $this->factory->saveObject($post_action);
    }

    public function itSavesFloatFieldPostActions()
    {
        $post_action = new Transition_PostAction_Field_Float(
            Mockery::mock(Transition::class)->shouldReceive('getId')->andReturn(123)->getMock(),
            0,
            Mockery::mock(Tracker_FormElement_Field_Float::class)->shouldReceive('getId')->andReturn(456)->getMock(),
            0
        );

        $this->float_dao->expectOnce('save', array(123, 456, 0));
        $this->factory->saveObject($post_action);
    }
}

class Transition_PostActionFieldFactory_IsFieldUsedInPostActionsTest extends Transition_PostAction_FieldFactory_BaseTest
{

    public function setUp()
    {
        parent::setUp();

        $this->field_id = 45617;
        $this->field    = aMockField()->withId($this->field_id)->build();
    }

    public function itIsTrueWhenFieldIsUsedInADatePostAction()
    {
        stub($this->date_dao)->countByFieldId($this->field_id)->returns(1);
        stub($this->int_dao)->countByFieldId($this->field_id)->returns(0);
        stub($this->float_dao)->countByFieldId($this->field_id)->returns(0);

        $this->assertTrue($this->factory->isFieldUsedInPostActions($this->field));
    }

    public function itIsTrueWhenFieldIsUsedInAnIntPostAction()
    {
        stub($this->date_dao)->countByFieldId($this->field_id)->returns(0);
        stub($this->int_dao)->countByFieldId($this->field_id)->returns(2);
        stub($this->float_dao)->countByFieldId($this->field_id)->returns(0);

        $this->assertTrue($this->factory->isFieldUsedInPostActions($this->field));
    }

    public function itIsTrueWhenFieldIsUsedInAFloatPostAction()
    {
        stub($this->date_dao)->countByFieldId($this->field_id)->returns(0);
        stub($this->int_dao)->countByFieldId($this->field_id)->returns(0);
        stub($this->float_dao)->countByFieldId($this->field_id)->returns(3);

        $this->assertTrue($this->factory->isFieldUsedInPostActions($this->field));
    }

    public function itIsTrueWhenFieldIsUsedInMultiplePostActions()
    {
        stub($this->date_dao)->countByFieldId($this->field_id)->returns(1);
        stub($this->int_dao)->countByFieldId($this->field_id)->returns(2);
        stub($this->float_dao)->countByFieldId($this->field_id)->returns(3);

        $this->assertTrue($this->factory->isFieldUsedInPostActions($this->field));
    }
    public function itIsFalseWhenFieldIsNotUsedInAnyPostAction()
    {
        stub($this->date_dao)->countByFieldId($this->field_id)->returns(0);
        stub($this->int_dao)->countByFieldId($this->field_id)->returns(0);
        stub($this->float_dao)->countByFieldId($this->field_id)->returns(0);

        $this->assertFalse($this->factory->isFieldUsedInPostActions($this->field));
    }
}
