<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

declare(strict_types=1);

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Workflow_Transition_Condition_FieldNotEmpty_FactoryTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    private int $field_id = 3;
    private StringField $field;
    private Workflow_Transition_Condition_FieldNotEmpty_Dao&MockObject $dao;
    private Workflow_Transition_Condition_FieldNotEmpty_Factory $factory;
    private Transition $transition;
    private StringField $field_string;
    private StringField $field_string_f15;
    private array $xml_mapping;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->field     = StringFieldBuilder::aStringField($this->field_id)->build();
        $element_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $element_factory->method('getFormElementById')->with($this->field_id)->willReturn($this->field);
        Tracker_FormElementFactory::setInstance($element_factory);
        $this->dao              = $this->createMock(\Workflow_Transition_Condition_FieldNotEmpty_Dao::class);
        $this->factory          = new Workflow_Transition_Condition_FieldNotEmpty_Factory($this->dao, $element_factory);
        $this->transition       = new \Transition(
            42,
            101,
            null,
            ListStaticValueBuilder::aStaticValue('Done')->build(),
        );
        $this->field_string     = StringFieldBuilder::aStringField(1)->build();
        $this->field_string_f15 = StringFieldBuilder::aStringField(2)->build();
        $this->xml_mapping      = [
            'F14' => $this->field_string,
            'F15' => $this->field_string_f15,
        ];
    }

    #[\Override]
    protected function tearDown(): void
    {
        Tracker_FormElementFactory::clearInstance();
    }

    public function testItReconstitutesANotEmptyCondition(): void
    {
        $xml = new SimpleXMLElement('
            <condition type="notempty">
                <field REF="F14"/>
                <field REF="F15"/>
            </condition>
        ');

        $expected = new Workflow_Transition_Condition_FieldNotEmpty($this->transition, $this->dao);
        $expected->addField($this->field_string);
        $expected->addField($this->field_string_f15);

        $condition = $this->factory->getInstanceFromXML($xml, $this->xml_mapping, $this->transition);
        $this->assertEquals($expected, $condition);
    }

    public function testItDoesNotReconstitutesAnythingIfThereIsNoRefToField(): void
    {
        $xml = new SimpleXMLElement('
            <condition type="notempty" />
        ');

        $condition = $this->factory->getInstanceFromXML($xml, $this->xml_mapping, $this->transition);
        $this->assertNull($condition);
    }

    public function testItDuplicateConditionInDatabase(): void
    {
        $new_transition_id = 2;
        $field_mapping     = ['some fields mapping'];

        $this->dao->expects($this->once())->method('duplicate')->with($this->transition->getId(), $new_transition_id, $field_mapping);
        $this->factory->duplicate($this->transition, $new_transition_id, $field_mapping);
    }

    public function testItChecksThatFieldIsNotUsed(): void
    {
        $this->dao->expects($this->once())->method('isFieldUsed')->with($this->field_id)->willReturn(true);
        $this->assertTrue($this->factory->isFieldUsedInConditions($this->field));
    }
}
