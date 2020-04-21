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
final class Cardwall_OnTop_ConfigTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Cardwall_OnTop_Config
     */
    private $config;

    protected function setUp(): void
    {
        parent::setUp();

        $value_none       = new Tracker_FormElement_Field_List_Bind_StaticValue(100, 'None', '', 0, 0);
        $value_todo       = new Tracker_FormElement_Field_List_Bind_StaticValue(101, 'Todo', '', 0, 0);
        $value_inprogress = new Tracker_FormElement_Field_List_Bind_StaticValue(102, 'In Progress', '', 0, 0);
        $value_done       = new Tracker_FormElement_Field_List_Bind_StaticValue(103, 'Done', '', 0, 0);

        $mapping_none    = new Cardwall_OnTop_Config_ValueMapping($value_none, 10);
        $mapping_todo    = new Cardwall_OnTop_Config_ValueMapping($value_todo, 10);
        $mapping_ongoing = new Cardwall_OnTop_Config_ValueMapping($value_inprogress, 11);
        $mapping_done    = new Cardwall_OnTop_Config_ValueMapping($value_done, 12);

        $value_mappings = array(
            100 => $mapping_none,
            101 => $mapping_todo,
            102 => $mapping_ongoing,
            103 => $mapping_done,
        );
        $this->mapping = new Cardwall_OnTop_Config_TrackerMappingStatus(\Mockery::spy(\Tracker::class), array(), $value_mappings, Mockery::mock(Tracker_FormElement_Field_Selectbox::class));

        $this->config   = \Mockery::mock(\Cardwall_OnTop_Config::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $changset = new Tracker_Artifact_Changeset_Null();

        $this->artifact = Mockery::mock(Tracker_Artifact::class);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::mock(Tracker::class));
        $this->artifact->shouldReceive('getLastChangeset')->andReturn($changset);
    }

    public function testItAsksForMappingByGivenListOfColumns(): void
    {
        $tracker                 = $this->buildTracker(4);
        $dao                     = \Mockery::spy(\Cardwall_OnTop_Dao::class);
        $column_factory          = \Mockery::spy(\Cardwall_OnTop_Config_ColumnFactory::class);
        $tracker_mapping_factory = \Mockery::spy(\Cardwall_OnTop_Config_TrackerMappingFactory::class);

        $columns = new Cardwall_OnTop_Config_ColumnCollection(['of', 'columns']);
        $column_factory->shouldReceive('getDashboardColumns')->with($tracker)->andReturns($columns);
        $tracker_mapping_factory->shouldReceive('getMappings')->with($tracker, $columns)->once()->andReturns('whatever');

        $config = new Cardwall_OnTop_Config($tracker, $dao, $column_factory, $tracker_mapping_factory);
        $this->assertEquals('whatever', $config->getMappings());
    }

    public function testItReturnsNullIfThereIsNoMapping(): void
    {
        $tracker = $this->buildTracker(1);
        $mapping_tracker = $this->buildTracker(2);

        $dao                     = \Mockery::spy(\Cardwall_OnTop_Dao::class);
        $column_factory          = \Mockery::spy(\Cardwall_OnTop_Config_ColumnFactory::class);
        $tracker_mapping_factory = \Mockery::spy(\Cardwall_OnTop_Config_TrackerMappingFactory::class);
        $column_factory->shouldReceive('getDashboardColumns')->with($tracker)->andReturns(new Cardwall_OnTop_Config_ColumnCollection());
        $config = new Cardwall_OnTop_Config($tracker, $dao, $column_factory, $tracker_mapping_factory);
        $this->assertNull($config->getMappingFor($mapping_tracker));
    }

    public function testItReturnsTheCorrespondingMapping(): void
    {
        $tracker                 = $this->buildTracker(1);
        $mapping_tracker         = $this->buildTracker(99);

        $dao                     = \Mockery::spy(\Cardwall_OnTop_Dao::class);
        $column_factory          = \Mockery::spy(\Cardwall_OnTop_Config_ColumnFactory::class);
        $mapping                 = \Mockery::spy(\Cardwall_OnTop_Config_TrackerMapping::class);
        $tracker_mapping_factory = \Mockery::mock(\Cardwall_OnTop_Config_TrackerMappingFactory::class);
        $tracker_mapping_factory->shouldReceive('getMappings')->andReturn(array(99 => $mapping));
        $column_factory->shouldReceive('getDashboardColumns')->with($tracker)->andReturns(new Cardwall_OnTop_Config_ColumnCollection());
        $config = new Cardwall_OnTop_Config($tracker, $dao, $column_factory, $tracker_mapping_factory);
        $this->assertEquals($mapping, $config->getMappingFor($mapping_tracker));
    }

    public function testItIsNotInColumnWhenNoFieldAndNoMapping(): void
    {
        $this->config->shouldReceive('getMappingFor')->andReturns(null);
        $field_provider = \Mockery::spy(\Cardwall_FieldProviders_CustomFieldRetriever::class);
        $column         = new Cardwall_Column(10, 'In ', '');

        $this->assertFalse($this->config->isInColumn($this->artifact, $field_provider, $column));
    }

    public function testItIsMappedToAColumnWhenTheStatusValueMatchColumnMapping(): void
    {
        $this->config->shouldReceive('getMappingFor')->andReturns($this->mapping);

        $field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $field->shouldReceive('getFirstValueFor')->andReturn('In Progress');
        $field_provider = Mockery::mock(\Cardwall_FieldProviders_CustomFieldRetriever::class);
        $field_provider->shouldReceive('getField')->andReturn($field);
        $column         = new Cardwall_Column(11, 'Ongoing', '');

        $this->assertTrue($this->config->isInColumn($this->artifact, $field_provider, $column));
    }

    public function testItIsNotMappedWhenTheStatusValueDoesntMatchColumnMapping(): void
    {
        $this->config->shouldReceive('getMappingFor')->andReturns($this->mapping);

        $field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $field->shouldReceive('getFirstValueFor')->andReturn('Todo');
        $field_provider = Mockery::mock(\Cardwall_FieldProviders_CustomFieldRetriever::class);
        $field_provider->shouldReceive('getField')->andReturn($field);
        $column         = new Cardwall_Column(11, 'Ongoing', '');

        $this->assertFalse($this->config->isInColumn($this->artifact, $field_provider, $column));
    }

    public function testItIsMappedToAColumnWhenStatusIsNullAndNoneIsMappedToColumn(): void
    {
        $this->config->shouldReceive('getMappingFor')->andReturns($this->mapping);

        $field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $field->shouldReceive('getFirstValueFor')->andReturn(null);
        $field_provider = Mockery::mock(\Cardwall_FieldProviders_CustomFieldRetriever::class);
        $field_provider->shouldReceive('getField')->andReturn($field);
        $column         = new Cardwall_Column(10, 'Todo', '');

        $this->assertTrue($this->config->isInColumn($this->artifact, $field_provider, $column));
    }

    public function testItIsMappedToColumnIfStatusMatchColumn(): void
    {
        $this->config->shouldReceive('getMappingFor')->andReturns($this->mapping);

        $field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $field->shouldReceive('getFirstValueFor')->andReturn('Ongoing');
        $field_provider = Mockery::mock(\Cardwall_FieldProviders_CustomFieldRetriever::class);
        $field_provider->shouldReceive('getField')->andReturn($field);
        $column         = new Cardwall_Column(11, 'Ongoing', '');

        $this->assertTrue($this->config->isInColumn($this->artifact, $field_provider, $column));
    }

    public function testItIsMappedToColumnIfStatusIsNullAndMatchColumnNone(): void
    {
        $this->config->shouldReceive('getMappingFor')->andReturns($this->mapping);

        $field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $field->shouldReceive('getFirstValueFor')->andReturn(null);
        $field_provider = Mockery::mock(\Cardwall_FieldProviders_CustomFieldRetriever::class);
        $field_provider->shouldReceive('getField')->andReturn($field);
        $column         = new Cardwall_Column(100, 'Ongoing', '');

        $this->assertTrue($this->config->isInColumn($this->artifact, $field_provider, $column));
    }

    private function buildTracker(int $id): Tracker
    {
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn($id);

        return $tracker;
    }
}
