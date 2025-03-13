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

use Tuleap\Cardwall\OnTop\Config\ColumnCollection;
use Tuleap\Cardwall\Test\Builders\ColumnTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Cardwall_OnTop_ConfigTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * @var Artifact&\PHPUnit\Framework\MockObject\MockObject
     */
    private $artifact;
    /**
     * @var Cardwall_OnTop_Config&\PHPUnit\Framework\MockObject\MockObject
     */
    private $config;
    private Cardwall_OnTop_Config_TrackerMappingStatus $mapping;

    protected function setUp(): void
    {
        parent::setUp();

        $value_none       = ListStaticValueBuilder::noneStaticValue()->build();
        $value_todo       = ListStaticValueBuilder::aStaticValue('Todo')->withId(101)->build();
        $value_inprogress = ListStaticValueBuilder::aStaticValue('In Progress')->withId(102)->build();
        $value_done       = ListStaticValueBuilder::aStaticValue('Done')->withId(103)->build();

        $mapping_none    = new Cardwall_OnTop_Config_ValueMapping($value_none, 10);
        $mapping_todo    = new Cardwall_OnTop_Config_ValueMapping($value_todo, 10);
        $mapping_ongoing = new Cardwall_OnTop_Config_ValueMapping($value_inprogress, 11);
        $mapping_done    = new Cardwall_OnTop_Config_ValueMapping($value_done, 12);

        $value_mappings = [
            100 => $mapping_none,
            101 => $mapping_todo,
            102 => $mapping_ongoing,
            103 => $mapping_done,
        ];
        $this->mapping  = new Cardwall_OnTop_Config_TrackerMappingStatus($this->createStub(\Tracker::class), [], $value_mappings, $this->createMock(Tracker_FormElement_Field_Selectbox::class));

        $this->config = $this->createPartialMock(\Cardwall_OnTop_Config::class, ['getMappingFor']);

        $this->artifact = $this->createMock(Artifact::class);
        $this->artifact->method('getTracker')->willReturn($this->createMock(Tracker::class));
        $this->artifact->method('getLastChangeset')->willReturn(new Tracker_Artifact_Changeset_Null());
    }

    public function testItAsksForMappingByGivenListOfColumns(): void
    {
        $tracker                 = $this->buildTracker(4);
        $dao                     = $this->createStub(\Cardwall_OnTop_Dao::class);
        $column_factory          = $this->createStub(\Tuleap\Cardwall\OnTop\Config\ColumnFactory::class);
        $tracker_mapping_factory = $this->createMock(\Cardwall_OnTop_Config_TrackerMappingFactory::class);

        $columns = new ColumnCollection([
            ColumnTestBuilder::aColumn()->withLabel('of')->build(),
            ColumnTestBuilder::aColumn()->withLabel('columns')->build(),
        ]);
        $column_factory->method('getDashboardColumns')->with($tracker)->willReturn($columns);
        $tracker_mapping_factory->expects(self::once())->method('getMappings')->with($tracker, $columns)->willReturn('whatever');

        $config = new Cardwall_OnTop_Config($tracker, $dao, $column_factory, $tracker_mapping_factory);
        self::assertEquals('whatever', $config->getMappings());
    }

    public function testItReturnsNullIfThereIsNoMapping(): void
    {
        $tracker         = $this->buildTracker(1);
        $mapping_tracker = $this->buildTracker(2);

        $dao                     = $this->createStub(\Cardwall_OnTop_Dao::class);
        $column_factory          = $this->createStub(\Tuleap\Cardwall\OnTop\Config\ColumnFactory::class);
        $tracker_mapping_factory = $this->createStub(\Cardwall_OnTop_Config_TrackerMappingFactory::class);
        $tracker_mapping_factory->method('getMappings')->willReturn([]);
        $column_factory->method('getDashboardColumns')->with($tracker)->willReturn(new ColumnCollection());
        $config = new Cardwall_OnTop_Config($tracker, $dao, $column_factory, $tracker_mapping_factory);

        self::assertNull($config->getMappingFor($mapping_tracker));
    }

    public function testItReturnsTheCorrespondingMapping(): void
    {
        $tracker         = $this->buildTracker(1);
        $mapping_tracker = $this->buildTracker(99);

        $dao                     = $this->createStub(\Cardwall_OnTop_Dao::class);
        $column_factory          = $this->createStub(\Tuleap\Cardwall\OnTop\Config\ColumnFactory::class);
        $mapping                 = $this->createStub(\Cardwall_OnTop_Config_TrackerMapping::class);
        $tracker_mapping_factory = $this->createMock(\Cardwall_OnTop_Config_TrackerMappingFactory::class);
        $tracker_mapping_factory->method('getMappings')->willReturn([99 => $mapping]);
        $column_factory->method('getDashboardColumns')->with($tracker)->willReturn(new ColumnCollection());
        $config = new Cardwall_OnTop_Config($tracker, $dao, $column_factory, $tracker_mapping_factory);
        self::assertEquals($mapping, $config->getMappingFor($mapping_tracker));
    }

    public function testItIsNotInColumnWhenNoFieldAndNoMapping(): void
    {
        $this->config->method('getMappingFor')->willReturn(null);
        $field_provider = $this->createStub(\Cardwall_FieldProviders_CustomFieldRetriever::class);
        $column         = new Cardwall_Column(10, 'In ', '');

        $field_provider->method('getField')->willReturn(null);

        self::assertFalse($this->config->isInColumn($this->artifact, $field_provider, $column));
    }

    public function testItIsMappedToAColumnWhenTheStatusValueMatchColumnMapping(): void
    {
        $this->config->method('getMappingFor')->willReturn($this->mapping);

        $field = $this->createMock(\Tracker_FormElement_Field_List::class);
        $field->method('getFirstValueFor')->willReturn('In Progress');
        $field_provider = $this->createMock(\Cardwall_FieldProviders_CustomFieldRetriever::class);
        $field_provider->method('getField')->willReturn($field);
        $column = new Cardwall_Column(11, 'Ongoing', '');

        self::assertTrue($this->config->isInColumn($this->artifact, $field_provider, $column));
    }

    public function testItIsNotMappedWhenTheStatusValueDoesntMatchColumnMapping(): void
    {
        $this->config->method('getMappingFor')->willReturn($this->mapping);

        $field = $this->createMock(\Tracker_FormElement_Field_List::class);
        $field->method('getFirstValueFor')->willReturn('Todo');
        $field_provider = $this->createMock(\Cardwall_FieldProviders_CustomFieldRetriever::class);
        $field_provider->method('getField')->willReturn($field);
        $column = new Cardwall_Column(11, 'Ongoing', '');

        self::assertFalse($this->config->isInColumn($this->artifact, $field_provider, $column));
    }

    public function testItIsMappedToAColumnWhenStatusIsNullAndNoneIsMappedToColumn(): void
    {
        $this->config->method('getMappingFor')->willReturn($this->mapping);

        $field = $this->createMock(\Tracker_FormElement_Field_List::class);
        $field->method('getFirstValueFor')->willReturn(null);
        $field_provider = $this->createMock(\Cardwall_FieldProviders_CustomFieldRetriever::class);
        $field_provider->method('getField')->willReturn($field);
        $column = new Cardwall_Column(10, 'Todo', '');

        self::assertTrue($this->config->isInColumn($this->artifact, $field_provider, $column));
    }

    public function testItIsMappedToColumnIfStatusMatchColumn(): void
    {
        $this->config->method('getMappingFor')->willReturn($this->mapping);

        $field = $this->createMock(\Tracker_FormElement_Field_List::class);
        $field->method('getFirstValueFor')->willReturn('Ongoing');
        $field_provider = $this->createMock(\Cardwall_FieldProviders_CustomFieldRetriever::class);
        $field_provider->method('getField')->willReturn($field);
        $column = new Cardwall_Column(11, 'Ongoing', '');

        self::assertTrue($this->config->isInColumn($this->artifact, $field_provider, $column));
    }

    public function testItIsMappedToColumnIfStatusIsNullAndMatchColumnNone(): void
    {
        $this->config->method('getMappingFor')->willReturn($this->mapping);

        $field = $this->createMock(\Tracker_FormElement_Field_List::class);
        $field->method('getFirstValueFor')->willReturn(null);
        $field_provider = $this->createMock(\Cardwall_FieldProviders_CustomFieldRetriever::class);
        $field_provider->method('getField')->willReturn($field);
        $column = new Cardwall_Column(100, 'Ongoing', '');

        self::assertTrue($this->config->isInColumn($this->artifact, $field_provider, $column));
    }

    private function buildTracker(int $id): Tracker
    {
        return TrackerTestBuilder::aTracker()->withId($id)->build();
    }
}
