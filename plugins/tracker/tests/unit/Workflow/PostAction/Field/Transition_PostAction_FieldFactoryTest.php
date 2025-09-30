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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\FormElement\Field\Date\DateField;
use Tuleap\Tracker\FormElement\Field\Float\FloatField;
use Tuleap\Tracker\FormElement\Field\Integer\IntegerField;
use Tuleap\Tracker\FormElement\Field\List\SelectboxField;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Transition_PostAction_FieldFactoryTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    private array $post_action_rows;

    /**
     * @var int[]
     */
    private array $mapping;

    private Tracker_FormElementFactory&MockObject $element_factory;
    private int $field_id;
    private int $post_action_value;
    private int $post_action_id;
    private Transition $transition;
    private int $transition_id;
    private Transition_PostAction_FieldFactory $factory;
    private Transition_PostAction_Field_FloatDao&MockObject $float_dao;
    private Transition_PostAction_Field_IntDao&MockObject $int_dao;
    private Transition_PostAction_Field_DateDao&MockObject $date_dao;
    private Workflow&MockObject $workflow;

    #[\Override]
    protected function setUp(): void
    {
        $workflow_id    = '1112';
        $this->workflow = $this->createMock(Workflow::class);
        $this->workflow->method('getId')->willReturn($workflow_id);

        $this->transition_id = 123;
        $this->transition    = new Transition(
            $this->transition_id,
            $workflow_id,
            null,
            ListStaticValueBuilder::aStaticValue('field')->build()
        );
        $this->transition->setWorkflow($this->workflow);

        $this->date_dao        = $this->createMock(\Transition_PostAction_Field_DateDao::class);
        $this->int_dao         = $this->createMock(\Transition_PostAction_Field_IntDao::class);
        $this->float_dao       = $this->createMock(\Transition_PostAction_Field_FloatDao::class);
        $this->element_factory = $this->createMock(\Tracker_FormElementFactory::class);
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
            'value'    => $this->post_action_value,
        ];

        $this->mapping = ['F1' => 62334];
    }

    public function testItLoadsIntFieldPostActions(): void
    {
        $this->element_factory
            ->method('getFormElementById')
            ->with($this->field_id)
            ->willReturn(new IntegerField(null, null, null, null, null, null, null, null, null, null, null));

        $this->int_dao->method('searchByTransitionId')->with($this->transition_id)->willReturn(
            \TestHelper::arrayToDar($this->post_action_rows)
        );
        $this->float_dao->method('searchByTransitionId')->with($this->transition_id)->willReturn(
            \TestHelper::emptyDar()
        );
        $this->date_dao->method('searchByTransitionId')->with($this->transition_id)->willReturn(
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
        $this->element_factory
            ->method('getFormElementById')
            ->with($this->field_id)
            ->willReturn(new FloatField(null, null, null, null, null, null, null, null, null, null, null));

        $this->int_dao->method('searchByTransitionId')->with($this->transition_id)->willReturn(
            \TestHelper::emptyDar()
        );
        $this->float_dao->method('searchByTransitionId')->with($this->transition_id)->willReturn(
            \TestHelper::arrayToDar($this->post_action_rows)
        );
        $this->date_dao->method('searchByTransitionId')->with($this->transition_id)->willReturn(
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
        $this->element_factory
            ->method('getFormElementById')
            ->with($this->field_id)
            ->willReturn(new DateField(null, null, null, null, null, null, null, null, null, null, null));

        $post_action_rows = [
            'id'         => $this->post_action_id,
            'field_id'   => $this->field_id,
            'value_type' => $this->post_action_value,
        ];

        $this->int_dao->method('searchByTransitionId')->with($this->transition_id)->willReturn(
            \TestHelper::emptyDar()
        );
        $this->float_dao->method('searchByTransitionId')->with($this->transition_id)->willReturn(
            \TestHelper::emptyDar()
        );
        $this->date_dao->method('searchByTransitionId')->with($this->transition_id)->willReturn(
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
        $select_box1 = $this->createMock(SelectboxField::class);
        $select_box1->method('getId')->willReturn(2065);
        $select_box2 = $this->createMock(SelectboxField::class);
        $select_box2->method('getId')->willReturn(2066);
        $select_box3 = $this->createMock(SelectboxField::class);
        $select_box3->method('getId')->willReturn(2067);
        $this->element_factory->method('getFormElementById')->willReturnCallback(static fn (int $id) => match ($id) {
            $select_box1->getId() => $select_box1,
            $select_box2->getId() => $select_box2,
            $select_box3->getId() => $select_box3,
        });

        $this->date_dao->method('searchByTransitionId')->with($this->transition_id)->willReturn(
            \TestHelper::emptyDar()
        );
        $this->float_dao->method('searchByTransitionId')->with($this->transition_id)->willReturn(
            \TestHelper::arrayToDar(
                [
                    'id'       => 1,
                    'field_id' => 2065,
                    'value'    => 0,
                ]
            )
        );
        $this->int_dao->method('searchByTransitionId')->with($this->transition_id)->willReturn(
            \TestHelper::arrayToDar(
                [
                    'id'       => 1,
                    'field_id' => 2066,
                    'value'    => 666,
                ],
                [
                    'id'       => 2,
                    'field_id' => 2067,
                    'value'    => 42,
                ]
            )
        );

        $field_mapping = [
            1 => ['from' => 2066, 'to' => 3066],
            2 => ['from' => 2067, 'to' => 3067],
            3 => ['from' => 2065, 'to' => 3065],
        ];

        $this->float_dao->expects($this->once())->method('duplicate')->with(123, 124, 2065, 3065);
        $this->int_dao->expects($this->exactly(2))->method('duplicate');
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
        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(123);

        $field_date = $this->createMock(DateField::class);
        $field_date->method('getId')->willReturn(456);

        $post_action = new Transition_PostAction_Field_Date(
            $transition,
            0,
            $field_date,
            1
        );

        $this->date_dao->expects($this->once())->method('save')->with(123, 456, 1);
        $this->factory->saveObject($post_action);
    }

    public function testItSavesIntFieldPostActions(): void
    {
        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(123);

        $field_int = $this->createMock(IntegerField::class);
        $field_int->method('getId')->willReturn(456);

        $post_action = new Transition_PostAction_Field_Int(
            $transition,
            0,
            $field_int,
            0
        );

        $this->int_dao->expects($this->once())->method('save')->with(123, 456, 0);
        $this->factory->saveObject($post_action);
    }

    public function testItSavesFloatFieldPostActions(): void
    {
        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(123);

        $field_float = $this->createMock(FloatField::class);
        $field_float->method('getId')->willReturn(456);

        $post_action = new Transition_PostAction_Field_Float(
            $transition,
            0,
            $field_float,
            0
        );

        $this->float_dao->expects($this->once())->method('save')->with(123, 456, 0);
        $this->factory->saveObject($post_action);
    }

    public function testItIsTrueWhenFieldIsUsedInADatePostAction(): void
    {
        $field_id = 45617;
        $field    = $this->createMock(\Tuleap\Tracker\FormElement\Field\TrackerField::class);
        $field->method('getId')->willReturn($field_id);
        $this->date_dao->method('countByFieldId')->with($field_id)->willReturn(1);
        $this->int_dao->method('countByFieldId')->with($field_id)->willReturn(0);
        $this->float_dao->method('countByFieldId')->with($field_id)->willReturn(0);

        $this->assertTrue($this->factory->isFieldUsedInPostActions($field));
    }

    public function testItIsTrueWhenFieldIsUsedInAnIntPostAction(): void
    {
        $field_id = 45617;
        $field    = $this->createMock(\Tuleap\Tracker\FormElement\Field\TrackerField::class);
        $field->method('getId')->willReturn($field_id);
        $this->date_dao->method('countByFieldId')->with($field_id)->willReturn(0);
        $this->int_dao->method('countByFieldId')->with($field_id)->willReturn(2);
        $this->float_dao->method('countByFieldId')->with($field_id)->willReturn(0);

        $this->assertTrue($this->factory->isFieldUsedInPostActions($field));
    }

    public function testItIsTrueWhenFieldIsUsedInAFloatPostAction(): void
    {
        $field_id = 45617;
        $field    = $this->createMock(\Tuleap\Tracker\FormElement\Field\TrackerField::class);
        $field->method('getId')->willReturn($field_id);
        $this->date_dao->method('countByFieldId')->with($field_id)->willReturn(0);
        $this->int_dao->method('countByFieldId')->with($field_id)->willReturn(0);
        $this->float_dao->method('countByFieldId')->with($field_id)->willReturn(3);

        $this->assertTrue($this->factory->isFieldUsedInPostActions($field));
    }

    public function testItIsTrueWhenFieldIsUsedInMultiplePostActions(): void
    {
        $field_id = 45617;
        $field    = $this->createMock(\Tuleap\Tracker\FormElement\Field\TrackerField::class);
        $field->method('getId')->willReturn($field_id);
        $this->date_dao->method('countByFieldId')->with($field_id)->willReturn(1);
        $this->int_dao->method('countByFieldId')->with($field_id)->willReturn(2);
        $this->float_dao->method('countByFieldId')->with($field_id)->willReturn(3);

        $this->assertTrue($this->factory->isFieldUsedInPostActions($field));
    }

    public function testItIsFalseWhenFieldIsNotUsedInAnyPostAction(): void
    {
        $field_id = 45617;
        $field    = $this->createMock(\Tuleap\Tracker\FormElement\Field\TrackerField::class);
        $field->method('getId')->willReturn($field_id);
        $this->date_dao->method('countByFieldId')->with($field_id)->willReturn(0);
        $this->int_dao->method('countByFieldId')->with($field_id)->willReturn(0);
        $this->float_dao->method('countByFieldId')->with($field_id)->willReturn(0);

        $this->assertFalse($this->factory->isFieldUsedInPostActions($field));
    }
}
