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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Workflow_Transition_Condition_FieldNotEmpty_FactoryTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $field_id = 3;
    private $field;
    private $dao;
    private $factory;
    private $transition;
    private $field_string;
    private $field_string_f15;
    private $xml_mapping;

    protected function setUp(): void
    {
        parent::setUp();
        $this->field            = \Mockery::spy(\Tracker_FormElement_Field_String::class)->shouldReceive('getId')->andReturns($this->field_id)->getMock();
        $element_factory        = \Mockery::spy(\Tracker_FormElementFactory::class);
        $element_factory->shouldReceive('getFormElementById')->with($this->field_id)->andReturns($this->field);
        Tracker_FormElementFactory::setInstance($element_factory);
        $this->dao              = \Mockery::spy(\Workflow_Transition_Condition_FieldNotEmpty_Dao::class);
        $this->factory          = new Workflow_Transition_Condition_FieldNotEmpty_Factory($this->dao, $element_factory);
        $this->transition       = \Mockery::spy(\Transition::class)->shouldReceive('getId')->andReturns(42)->getMock();
        $this->field_string     = \Mockery::spy(\Tracker_FormElement_Field_String::class)->shouldReceive('getId')->andReturns(0)->getMock();
        $this->field_string_f15 = \Mockery::spy(\Tracker_FormElement_Field_String::class)->shouldReceive('getId')->andReturns(1)->getMock();
        $this->xml_mapping  = array(
            'F14' => $this->field_string,
            'F15' => $this->field_string_f15
        );
    }

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

        $expected  = new Workflow_Transition_Condition_FieldNotEmpty($this->transition, $this->dao);
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
        $field_mapping     = array('some fields mapping');
        $ugroup_mapping    = array('some ugroups mapping');
        $duplicate_type    = PermissionsDao::DUPLICATE_NEW_PROJECT;

        $this->dao->shouldReceive('duplicate')->with($this->transition->getId(), $new_transition_id, $field_mapping)->once();
        $this->factory->duplicate($this->transition, $new_transition_id, $field_mapping, $ugroup_mapping, $duplicate_type);
    }

    public function testItChecksThatFieldIsNotUsed(): void
    {
        $this->dao->shouldReceive('isFieldUsed')->with($this->field_id)->once()->andReturns(true);
        $this->assertTrue($this->factory->isFieldUsedInConditions($this->field));
    }
}
