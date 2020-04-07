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
final class Cardwall_OnTop_Config_ValueMappingFactoryTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $element_factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $this->dao      = \Mockery::spy(\Cardwall_OnTop_ColumnMappingFieldValueDao::class);
        $this->factory  = new Cardwall_OnTop_Config_ValueMappingFactory($element_factory, $this->dao);

        $this->field_123    = Mockery::mock(Tracker_FormElement_Field::class);
        $this->field_123->shouldReceive('getId')->andReturn(123);
        $this->field_124    = Mockery::mock(Tracker_FormElement_Field::class);
        $this->field_124->shouldReceive('getId')->andReturn(124);
        $this->status_field = Mockery::mock(Tracker_FormElement_Field::class);
        $this->status_field->shouldReceive('getId')->andReturn(125);

        $this->field_124->shouldReceive('getListValueById')->with(1001)->andReturns(\Mockery::spy(\Tracker_FormElement_Field_List_Value::class)->shouldReceive('getId')->andReturns(1001)->getMock());
        $this->field_124->shouldReceive('getListValueById')->with(1002)->andReturns(\Mockery::spy(\Tracker_FormElement_Field_List_Value::class)->shouldReceive('getId')->andReturns(1002)->getMock());
        $this->status_field->shouldReceive('getListValueById')->with(1000)->andReturns(\Mockery::spy(\Tracker_FormElement_Field_List_Value::class)->shouldReceive('getId')->andReturns(1000)->getMock());

        $element_factory->shouldReceive('getFieldById')->with(123)->andReturns($this->field_123);
        $element_factory->shouldReceive('getFieldById')->with(124)->andReturns($this->field_124);
        $element_factory->shouldReceive('getFieldById')->with(125)->andReturns($this->status_field);

        $group_id           = 234;
        $this->tracker      = Mockery::mock(Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(3);
        $this->tracker->shouldReceive('getGroupId')->andReturn($group_id);
        $this->tracker_10   = Mockery::mock(Tracker::class);
        $this->tracker_10->shouldReceive('getId')->andReturn(10);
        $this->tracker_10->shouldReceive('getStatusField')->andReturn($this->status_field);
        $this->tracker_20   = Mockery::mock(Tracker::class);
        $this->tracker_20->shouldReceive('getId')->andReturn(20);

        $this->dao->shouldReceive('searchMappingFieldValues')->with($this->tracker->getId())->andReturns(TestHelper::arrayToDar(
            array(
                'tracker_id' => 10,
                'field_id'   => 125,
                'value_id'   => 1000,
                'column_id'  => 1,
            ),
            array(
                'tracker_id' => 20,
                'field_id'   => 124,
                'value_id'   => 1001,
                'column_id'  => 1,
            ),
            array(
                'tracker_id' => 20,
                'field_id'   => 124,
                'value_id'   => 1002,
                'column_id'  => 2,
            )
        ));

        $this->status_field->shouldReceive('getVisibleValuesPlusNoneIfAny')->andReturns(array(
            new Tracker_FormElement_Field_List_Bind_StaticValue(1001, 'Todo', '', 0, 0),
            new Tracker_FormElement_Field_List_Bind_StaticValue(1002, 'On Going', '', 0, 0),
            new Tracker_FormElement_Field_List_Bind_StaticValue(1003, 'Done', '', 0, 0),
        ));
    }

    public function testItLoadsMappingsFromTheDatabase(): void
    {
        $mappings = $this->factory->getMappings($this->tracker, $this->tracker_20, $this->field_124);
        $this->assertCount(2, $mappings);
        $this->assertEquals(1002, $mappings[1002]->getValueId());
    }

    public function testItLoadStatusValues(): void
    {
        $columns = new Cardwall_OnTop_Config_ColumnFreestyleCollection(
            array(
                new Cardwall_Column(1, 'Todo', 'white'),
                new Cardwall_Column(2, 'In Progress', 'white'),
                new Cardwall_Column(3, 'Done', 'white'),
            )
        );

        $mappings = $this->factory->getStatusMappings($this->tracker_10, $columns);
        $this->assertEquals(1, $mappings[1001]->getColumnId());
        $this->assertFalse(isset($mappings[1002]));
        $this->assertEquals(3, $mappings[1003]->getColumnId());
    }

    public function testItLoadsMappingsFromTheDatabase2(): void
    {
        $element_factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $dao      = \Mockery::spy(\Cardwall_OnTop_ColumnMappingFieldValueDao::class);
        $factory  = new Cardwall_OnTop_Config_ValueMappingFactory($element_factory, $dao);

        $field_124 = Mockery::spy(Tracker_FormElement_Field::class);
        $field_124->shouldReceive('getId')->andReturn(124);

        $element_factory->shouldReceive('getFieldById')->with(124)->andReturns($field_124);

        $group_id           = 234;
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(3);
        $tracker->shouldReceive('getGroupId')->andReturn($group_id);
        $tracker_20   = Mockery::mock(Tracker::class);
        $tracker_20->shouldReceive('getId')->andReturn(20);

        $dao->shouldReceive('searchMappingFieldValues')->with($tracker->getId())->andReturns(\TestHelper::arrayToDar(array(
            'tracker_id' => 10,
            'field_id'   => 125,
            'value_id'   => 1000,
            'column_id'  => 1,
        ), array(
            'tracker_id' => 20,
            'field_id'   => 124,
            'value_id'   => 1001,
            'column_id'  => 1,
        )));

        $mappings = $factory->getMappings($tracker, $tracker_20, $field_124);
        $this->assertEquals(array(), $mappings);
    }
}
