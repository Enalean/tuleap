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
final class Cardwall_OnTop_Config_TrackerMappingFactoryTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp() : void
    {
        parent::setUp();
        $this->field_122    = $this->buildSelectBoxField(122);
        $this->field_123    = $this->buildSelectBoxField(123);
        $this->field_124    = $this->buildSelectBoxField(124);
        $this->status_field = $this->buildSelectBoxField(125);

        $group_id           = 234;
        $this->tracker      = Mockery::mock(Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(3);
        $this->tracker->shouldReceive('getGroupId')->andReturn($group_id);
        $this->tracker_10   = Mockery::mock(Tracker::class);
        $this->tracker_10->shouldReceive('getId')->andReturn(10);
        $this->tracker_10->shouldReceive('getStatusField')->andReturn($this->status_field);
        $this->tracker_20   = Mockery::mock(Tracker::class);
        $this->tracker_20->shouldReceive('getId')->andReturn(20);
        $this->tracker_20->shouldReceive('getStatusField')->andReturn(null);
        $project_trackers = array(
            3  => $this->tracker,
            10 => $this->tracker_10,
            20 => $this->tracker_20
        );

        $tracker_factory = \Mockery::spy(\TrackerFactory::class)->shouldReceive('getTrackersByGroupId')->with($group_id)->andReturns($project_trackers)->getMock();
        foreach ($project_trackers as $t) {
            $tracker_factory->shouldReceive('getTrackerById')->with($t->getId())->andReturns($t);
        }

        $element_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        $element_factory->shouldReceive('getFieldById')->with(123)->andReturns($this->field_123);
        $element_factory->shouldReceive('getFieldById')->with(124)->andReturns($this->field_124);
        $element_factory->shouldReceive('getUsedSbFields')->with($this->tracker_10)->andReturns(array($this->field_122, $this->field_123));
        $element_factory->shouldReceive('getUsedSbFields')->andReturns(array());

        $this->dao                   = \Mockery::spy(\Cardwall_OnTop_ColumnMappingFieldDao::class);
        $this->value_mapping_factory = \Mockery::spy(\Cardwall_OnTop_Config_ValueMappingFactory::class);

        $this->columns = new Cardwall_OnTop_Config_ColumnFreestyleCollection(
            array(
                new Cardwall_Column(1, 'Todo', 'white'),
                new Cardwall_Column(2, 'On Going', 'white'),
                new Cardwall_Column(3, 'Done', 'white'),
            )
        );

        $this->factory = new Cardwall_OnTop_Config_TrackerMappingFactory($tracker_factory, $element_factory, $this->dao, $this->value_mapping_factory);
    }

    private function buildSelectBoxField(int $id): Tracker_FormElement_Field_Selectbox
    {
        $selectbox_field = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $selectbox_field->shouldReceive('getId')->andReturn($id);

        return $selectbox_field;
    }

    public function testItRemovesTheCurrentTrackerFromTheProjectTrackers() : void
    {
        $expected_trackers = array(
            10 => $this->tracker_10,
            20 => $this->tracker_20
        );

        $this->assertSame($expected_trackers, $this->factory->getTrackers($this->tracker));
    }

    public function testItLoadsMappingsFromTheDatabase() : void
    {
        $this->value_mapping_factory->shouldReceive('getMappings')->andReturns([]);
        $this->dao->shouldReceive('searchMappingFields')->with($this->tracker->getId())->andReturns(TestHelper::arrayToDar(
            array('tracker_id' => 10, 'field_id' => 123),
            array('tracker_id' => 20, 'field_id' => 124)
        ));

        $mappings = $this->factory->getMappings($this->tracker, $this->columns);
        $this->assertEquals(2, count($mappings));
        $this->assertEquals($this->tracker_10, $mappings[10]->getTracker());
        $this->assertEquals($this->field_123, $mappings[10]->getField());
        $this->assertInstanceOf(\Cardwall_OnTop_Config_TrackerMappingFreestyle::class, $mappings[10]);
        $this->assertEquals(array($this->field_122, $this->field_123), $mappings[10]->getAvailableFields());
        $this->assertEquals($this->tracker_20, $mappings[20]->getTracker());
        $this->assertEquals($this->field_124, $mappings[20]->getField());
    }

    public function testItUsesStatusFieldIfNoField() : void
    {
        $this->value_mapping_factory->shouldReceive('getStatusMappings')->andReturns([]);
        $this->dao->shouldReceive('searchMappingFields')->with($this->tracker->getId())->andReturns(TestHelper::arrayToDar(
            array('tracker_id' => 10, 'field_id' => null)
        ));

        $mappings = $this->factory->getMappings($this->tracker, $this->columns);
        $this->assertCount(1, $mappings);
        $this->assertEquals($this->status_field, $mappings[10]->getField());
        $this->assertInstanceOf(\Cardwall_OnTop_Config_TrackerMappingStatus::class, $mappings[10]);
    }

    public function testItReturnsANoFieldMappingIfNothingInDBAndNoStatus() : void
    {
        $this->dao->shouldReceive('searchMappingFields')->with($this->tracker->getId())->andReturns(TestHelper::arrayToDar(
            array('tracker_id' => 20, 'field_id' => null)
        ));

        $mappings = $this->factory->getMappings($this->tracker, $this->columns);
        $this->assertCount(1, $mappings);
        $this->assertInstanceOf(\Cardwall_OnTop_Config_TrackerMappingNoField::class, $mappings[20]);
    }

    public function testItReturnsEmptyMappingIfNoStatus() : void
    {
        $this->dao->shouldReceive('searchMappingFields')->with($this->tracker->getId())->andReturns(TestHelper::arrayToDar(
            array('tracker_id' => 20, 'field_id' => null)
        ));

        $mappings = $this->factory->getMappings($this->tracker, $this->columns);
        $this->assertCount(1, $mappings);
        //TDOD: check type of what is returned
    }

    public function testItLoadValueMappings() : void
    {
        $this->dao->shouldReceive('searchMappingFields')->with($this->tracker->getId())->andReturns(TestHelper::arrayToDar(
            array('tracker_id' => 20, 'field_id' => 124)
        ));
        $this->value_mapping_factory->shouldReceive('getMappings')->with($this->tracker, $this->tracker_20, $this->field_124)
            ->once()->andReturns([]);

        $this->factory->getMappings($this->tracker, $this->columns);
    }

    public function testItLoadValueMappingsEvenForStatusField() : void
    {
        $this->dao->shouldReceive('searchMappingFields')->with($this->tracker->getId())->andReturns(TestHelper::arrayToDar(
            array('tracker_id' => 10, 'field_id' => null)
        ));
        $this->value_mapping_factory->shouldReceive('getStatusMappings')->with($this->tracker_10, $this->columns)
            ->once()->andReturns([]);

        $this->factory->getMappings($this->tracker, $this->columns);
    }
}
