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

declare(strict_types=1);

final class Transition_PostAction_FieldFactoryTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var array
     */
    private $post_action_rows;

    /**
     * @var int[]
     */
    private $mapping;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $element_factory;
    /**
     * @var int
     */
    private $field_id;
    /**
     * @var int
     */
    private $post_action_value;
    /**
     * @var int
     */
    private $post_action_id;
    /**
     * @var Transition
     */
    private $transition;
    /**
     * @var int
     */
    private $transition_id;
    /**
     * @var Transition_PostAction_FieldFactory
     */
    private $factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Transition_PostAction_Field_FloatDao
     */
    private $float_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Transition_PostAction_Field_IntDao
     */
    private $int_dao;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Transition_PostAction_Field_DateDao
     */
    private $date_dao;

    protected function setUp(): void
    {
        $this->transition_id = 123;
        $this->transition    = new Transition(
            $this->transition_id,
            0,
            null,
            null
        );

        $this->date_dao        = \Mockery::spy(\Transition_PostAction_Field_DateDao::class);
        $this->int_dao         = \Mockery::spy(\Transition_PostAction_Field_IntDao::class);
        $this->float_dao       = \Mockery::spy(\Transition_PostAction_Field_FloatDao::class);
        $this->element_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        $this->factory         = new Transition_PostAction_FieldFactory(
            $this->element_factory,
            $this->date_dao,
            $this->int_dao,
            $this->float_dao
        );

        $this->field_id          = 456;
        $this->post_action_id    = 789;
        $this->post_action_value = 12;

        $this->post_action_rows = [
            'id'       => $this->post_action_id,
            'field_id' => $this->field_id,
            'value'    => $this->post_action_value
        ];

        $this->mapping = ['F1' => 62334];
    }

    public function testItLoadsIntFieldPostActions(): void
    {
        $this->int_dao->shouldReceive('searchByTransitionId')->with($this->transition_id)->andReturns(
            \TestHelper::arrayToDar($this->post_action_rows)
        );
        $this->float_dao->shouldReceive('searchByTransitionId')->with($this->transition_id)->andReturns(
            \TestHelper::emptyDar()
        );
        $this->date_dao->shouldReceive('searchByTransitionId')->with($this->transition_id)->andReturns(
            \TestHelper::emptyDar()
        );

        $post_action_array = $this->factory->loadPostActions($this->transition);
        $this->assertCount(1, $post_action_array);

        $first_pa = $post_action_array[0];
        $this->assertEquals($this->post_action_id, $first_pa->getId());
        $this->assertEquals($this->transition, $first_pa->getTransition());
        $this->assertEquals($this->post_action_value, $first_pa->getValue());
    }

    public function testItLoadsFloatFieldPostActions(): void
    {
        $this->int_dao->shouldReceive('searchByTransitionId')->with($this->transition_id)->andReturns(
            \TestHelper::emptyDar()
        );
        $this->float_dao->shouldReceive('searchByTransitionId')->with($this->transition_id)->andReturns(
            \TestHelper::arrayToDar($this->post_action_rows)
        );
        $this->date_dao->shouldReceive('searchByTransitionId')->with($this->transition_id)->andReturns(
            \TestHelper::emptyDar()
        );

        $post_action_array = $this->factory->loadPostActions($this->transition);
        $this->assertCount(1, $post_action_array);

        $first_pa = $post_action_array[0];
        $this->assertEquals($this->post_action_id, $first_pa->getId());
        $this->assertEquals($this->transition, $first_pa->getTransition());
        $this->assertEquals($this->post_action_value, $first_pa->getValue());
    }

    public function testItLoadsDateFieldPostActions(): void
    {
        $post_action_rows = [
            'id'         => $this->post_action_id,
            'field_id'   => $this->field_id,
            'value_type' => $this->post_action_value
        ];

        $this->int_dao->shouldReceive('searchByTransitionId')->with($this->transition_id)->andReturns(
            \TestHelper::emptyDar()
        );
        $this->float_dao->shouldReceive('searchByTransitionId')->with($this->transition_id)->andReturns(
            \TestHelper::emptyDar()
        );
        $this->date_dao->shouldReceive('searchByTransitionId')->with($this->transition_id)->andReturns(
            \TestHelper::arrayToDar($post_action_rows)
        );

        $post_action_array = $this->factory->loadPostActions($this->transition);
        $this->assertCount(1, $post_action_array);

        $first_pa = $post_action_array[0];
        $this->assertEquals($this->post_action_id, $first_pa->getId());
        $this->assertEquals($this->transition, $first_pa->getTransition());
        $this->assertEquals($this->post_action_value, $first_pa->getValueType());
    }

    public function testItDelegatesDuplicationToTheCorrespondingDao(): void
    {
        $select_box1 = Mockery::spy(Tracker_FormElement_Field_Selectbox::class);
        $select_box1->shouldReceive('getId')->andReturn(2065);
        $select_box2 = Mockery::spy(Tracker_FormElement_Field_Selectbox::class);
        $select_box2->shouldReceive('getId')->andReturn(2066);
        $select_box3 = Mockery::spy(Tracker_FormElement_Field_Selectbox::class);
        $select_box3->shouldReceive('getId')->andReturn(2067);
        $this->element_factory->shouldReceive('getFormElementById')->with(2065)->andReturns($select_box1);
        $this->element_factory->shouldReceive('getFormElementById')->with(2066)->andReturns($select_box2);
        $this->element_factory->shouldReceive('getFormElementById')->with(2067)->andReturns($select_box3);

        $this->date_dao->shouldReceive('searchByTransitionId')->with($this->transition_id)->andReturns(
            \TestHelper::emptyDar()
        );
        $this->float_dao->shouldReceive('searchByTransitionId')->with($this->transition_id)->andReturns(
            \TestHelper::arrayToDar(
                [
                    'id'       => 1,
                    'field_id' => 2065,
                    'value'    => 0
                ]
            )
        );
        $this->int_dao->shouldReceive('searchByTransitionId')->with($this->transition_id)->andReturns(
            \TestHelper::arrayToDar(
                [
                    'id'       => 1,
                    'field_id' => 2066,
                    'value'    => 666
                ],
                [
                    'id'       => 2,
                    'field_id' => 2067,
                    'value'    => 42
                ]
            )
        );

        $field_mapping = [
            1 => ['from' => 2066, 'to' => 3066],
            2 => ['from' => 2067, 'to' => 3067],
            3 => ['from' => 2065, 'to' => 3065],
        ];

        $this->float_dao->shouldReceive('duplicate')->with(123, 124, 2065, 3065)->once();
        $this->int_dao->shouldReceive('duplicate')->times(2);
        $this->factory->duplicate($this->transition, 124, $field_mapping);
    }

    public function testItReconstitutesDateFieldPostActionsFromXML(): void
    {
        $xml = new SimpleXMLElement(
            '
            <postaction_field_date valuetype="1">
                <field_id REF="F1"/>
            </postaction_field_date>
        '
        );

        $post_action = $this->factory->getInstanceFromXML($xml, $this->mapping, $this->transition);

        $this->assertInstanceOf(Transition_PostAction_Field_Date::class, $post_action);
        $this->assertEquals(1, $post_action->getValueType());
    }

    public function testItReconstitutesIntFieldPostActionsFromXML(): void
    {
        $xml = new SimpleXMLElement(
            '
            <postaction_field_int value="440">
                <field_id REF="F1"/>
            </postaction_field_int>
        '
        );

        $post_action = $this->factory->getInstanceFromXML($xml, $this->mapping, $this->transition);

        $this->assertInstanceOf(Transition_PostAction_Field_Int::class, $post_action);
        $this->assertEquals(440, $post_action->getValue());
    }

    public function testItReconstitutesFloatFieldPostActionsFromXML(): void
    {
        $xml = new SimpleXMLElement(
            '
            <postaction_field_float value="64.42">
                <field_id REF="F1"/>
            </postaction_field_float>
        '
        );

        $post_action = $this->factory->getInstanceFromXML($xml, $this->mapping, $this->transition);

        $this->assertInstanceOf(Transition_PostAction_Field_Float::class, $post_action);
        $this->assertEquals(64.42, $post_action->getValue());
    }

    public function testItThrowsAnErrorWhenPostActionIsInvalid(): void
    {
        $xml = new SimpleXMLElement(
            '
            <postaction_field_invalid foo="bar">
                <field_id REF="F1"/>
            </postaction_field_invalid>
        '
        );

        $this->expectException(\Transition_PostAction_NotFoundException::class);

        $this->factory->getInstanceFromXML($xml, $this->mapping, $this->transition);
    }

    public function testItSavesDateFieldPostActions(): void
    {
        $post_action = new Transition_PostAction_Field_Date(
            Mockery::mock(Transition::class)->shouldReceive('getId')->andReturn(123)->getMock(),
            0,
            Mockery::mock(Tracker_FormElement_Field_Date::class)->shouldReceive('getId')->andReturn(456)->getMock(),
            1
        );

        $this->date_dao->shouldReceive('save')->with(123, 456, 1)->once();
        $this->factory->saveObject($post_action);
    }

    public function testItSavesIntFieldPostActions(): void
    {
        $post_action = new Transition_PostAction_Field_Int(
            Mockery::mock(Transition::class)->shouldReceive('getId')->andReturn(123)->getMock(),
            0,
            Mockery::mock(Tracker_FormElement_Field_Integer::class)->shouldReceive('getId')->andReturn(456)->getMock(),
            0
        );

        $this->int_dao->shouldReceive('save')->with(123, 456, 0)->once();
        $this->factory->saveObject($post_action);
    }

    public function testItSavesFloatFieldPostActions(): void
    {
        $post_action = new Transition_PostAction_Field_Float(
            Mockery::mock(Transition::class)->shouldReceive('getId')->andReturn(123)->getMock(),
            0,
            Mockery::mock(Tracker_FormElement_Field_Float::class)->shouldReceive('getId')->andReturn(456)->getMock(),
            0
        );

        $this->float_dao->shouldReceive('save')->with(123, 456, 0)->once();
        $this->factory->saveObject($post_action);
    }

    public function testItIsTrueWhenFieldIsUsedInADatePostAction(): void
    {
        $field_id = 45617;
        $field    = Mockery::mock(\Tracker_FormElement_Field::class);
        $field->shouldReceive('getId')->andReturn($field_id);
        $this->date_dao->shouldReceive('countByFieldId')->with($field_id)->andReturns(1);
        $this->int_dao->shouldReceive('countByFieldId')->with($field_id)->andReturns(0);
        $this->float_dao->shouldReceive('countByFieldId')->with($field_id)->andReturns(0);

        $this->assertTrue($this->factory->isFieldUsedInPostActions($field));
    }

    public function testItIsTrueWhenFieldIsUsedInAnIntPostAction(): void
    {
        $field_id = 45617;
        $field    = Mockery::mock(\Tracker_FormElement_Field::class);
        $field->shouldReceive('getId')->andReturn($field_id);
        $this->date_dao->shouldReceive('countByFieldId')->with($field_id)->andReturns(0);
        $this->int_dao->shouldReceive('countByFieldId')->with($field_id)->andReturns(2);
        $this->float_dao->shouldReceive('countByFieldId')->with($field_id)->andReturns(0);

        $this->assertTrue($this->factory->isFieldUsedInPostActions($field));
    }

    public function testItIsTrueWhenFieldIsUsedInAFloatPostAction(): void
    {
        $field_id = 45617;
        $field    = Mockery::mock(\Tracker_FormElement_Field::class);
        $field->shouldReceive('getId')->andReturn($field_id);
        $this->date_dao->shouldReceive('countByFieldId')->with($field_id)->andReturns(0);
        $this->int_dao->shouldReceive('countByFieldId')->with($field_id)->andReturns(0);
        $this->float_dao->shouldReceive('countByFieldId')->with($field_id)->andReturns(3);

        $this->assertTrue($this->factory->isFieldUsedInPostActions($field));
    }

    public function testItIsTrueWhenFieldIsUsedInMultiplePostActions(): void
    {
        $field_id = 45617;
        $field    = Mockery::mock(\Tracker_FormElement_Field::class);
        $field->shouldReceive('getId')->andReturn($field_id);
        $this->date_dao->shouldReceive('countByFieldId')->with($field_id)->andReturns(1);
        $this->int_dao->shouldReceive('countByFieldId')->with($field_id)->andReturns(2);
        $this->float_dao->shouldReceive('countByFieldId')->with($field_id)->andReturns(3);

        $this->assertTrue($this->factory->isFieldUsedInPostActions($field));
    }

    public function testItIsFalseWhenFieldIsNotUsedInAnyPostAction(): void
    {
        $field_id = 45617;
        $field    = Mockery::mock(\Tracker_FormElement_Field::class);
        $field->shouldReceive('getId')->andReturn($field_id);
        $this->date_dao->shouldReceive('countByFieldId')->with($field_id)->andReturns(0);
        $this->int_dao->shouldReceive('countByFieldId')->with($field_id)->andReturns(0);
        $this->float_dao->shouldReceive('countByFieldId')->with($field_id)->andReturns(0);

        $this->assertFalse($this->factory->isFieldUsedInPostActions($field));
    }
}
