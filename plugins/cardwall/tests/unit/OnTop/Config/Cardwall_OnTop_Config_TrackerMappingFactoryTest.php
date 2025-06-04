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

namespace Tuleap\Cardwall\OnTop\Config;

use Cardwall_Column;
use Cardwall_OnTop_ColumnMappingFieldDao;
use Cardwall_OnTop_Config_TrackerMappingFactory;
use Cardwall_OnTop_Config_TrackerMappingFreestyle;
use Cardwall_OnTop_Config_TrackerMappingNoField;
use Cardwall_OnTop_Config_TrackerMappingStatus;
use Cardwall_OnTop_Config_ValueMappingFactory;
use PHPUnit\Framework\MockObject\MockObject;
use TestHelper;
use Tracker;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElementFactory;
use TrackerFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Cardwall_OnTop_Config_TrackerMappingFactoryTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Tracker_FormElement_Field_Selectbox $field_122;
    private Tracker_FormElement_Field_Selectbox $field_123;
    private Tracker_FormElement_Field_Selectbox $field_124;
    private Tracker_FormElement_Field_Selectbox $status_field;
    private Tracker $tracker;
    private Tracker $tracker_10;
    private Tracker $tracker_20;
    private Cardwall_OnTop_ColumnMappingFieldDao&MockObject $dao;
    private Cardwall_OnTop_Config_ValueMappingFactory&MockObject $value_mapping_factory;
    private ColumnCollection $columns;
    private Cardwall_OnTop_Config_TrackerMappingFactory $factory;

    protected function setUp(): void
    {
        $this->field_122    = ListFieldBuilder::aListField(122)->build();
        $this->field_123    = ListFieldBuilder::aListField(123)->build();
        $this->field_124    = ListFieldBuilder::aListField(124)->build();
        $this->status_field = ListFieldBuilder::aListField(125)->build();

        $group_id         = 234;
        $project          = ProjectTestBuilder::aProject()->withId($group_id)->build();
        $this->tracker    = TrackerTestBuilder::aTracker()->withId(3)->withProject($project)->build();
        $this->tracker_10 = TrackerTestBuilder::aTracker()->withId(10)->build();
        TrackerSemanticStatus::setInstance(
            new TrackerSemanticStatus($this->tracker_10, $this->status_field),
            $this->tracker_10,
        );
        $this->tracker_20 = TrackerTestBuilder::aTracker()->withId(20)->build();
        TrackerSemanticStatus::setInstance(new TrackerSemanticStatus($this->tracker_20, null), $this->tracker_20);
        $project_trackers = [
            3  => $this->tracker,
            10 => $this->tracker_10,
            20 => $this->tracker_20,
        ];

        $tracker_factory = $this->createMock(TrackerFactory::class);
        $tracker_factory->method('getTrackersByGroupId')->with($group_id)->willReturn($project_trackers);
        $tracker_factory->method('getTrackerById')->willReturnCallback(fn(int $tracker_id) => match ($tracker_id) {
            3       => $this->tracker,
            10      => $this->tracker_10,
            20      => $this->tracker_20,
            default => self::fail("Should not have been called with $tracker_id"),
        });

        $element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $element_factory->method('getFieldById')->willReturnCallback(fn(?int $field_id) => match ($field_id) {
            123     => $this->field_123,
            124     => $this->field_124,
            default => null,
        });
        $element_factory->method('getUsedSbFields')->willReturnCallback(fn(Tracker $tracker) => match ($tracker->getId()) {
            10      => [$this->field_122, $this->field_123],
            default => [],
        });

        $this->dao                   = $this->createMock(Cardwall_OnTop_ColumnMappingFieldDao::class);
        $this->value_mapping_factory = $this->createMock(Cardwall_OnTop_Config_ValueMappingFactory::class);

        $this->columns = new ColumnCollection([
            new Cardwall_Column(1, 'Todo', 'white'),
            new Cardwall_Column(2, 'On Going', 'white'),
            new Cardwall_Column(3, 'Done', 'white'),
        ]);

        $this->factory = new Cardwall_OnTop_Config_TrackerMappingFactory($tracker_factory, $element_factory, $this->dao, $this->value_mapping_factory);
    }

    public function testItRemovesTheCurrentTrackerFromTheProjectTrackers(): void
    {
        $expected_trackers = [
            10 => $this->tracker_10,
            20 => $this->tracker_20,
        ];

        self::assertSame($expected_trackers, $this->factory->getTrackers($this->tracker));
    }

    public function testItLoadsMappingsFromTheDatabase(): void
    {
        $this->value_mapping_factory->method('getMappings')->willReturn([]);
        $this->dao->method('searchMappingFields')->with($this->tracker->getId())->willReturn(TestHelper::arrayToDar(
            ['tracker_id' => 10, 'field_id' => 123],
            ['tracker_id' => 20, 'field_id' => 124]
        ));

        $mappings = $this->factory->getMappings($this->tracker, $this->columns);
        self::assertEquals(2, count($mappings));
        self::assertEquals($this->tracker_10, $mappings[10]->getTracker());
        self::assertEquals($this->field_123, $mappings[10]->getField());
        self::assertInstanceOf(Cardwall_OnTop_Config_TrackerMappingFreestyle::class, $mappings[10]);
        self::assertEquals([$this->field_122, $this->field_123], $mappings[10]->getAvailableFields());
        self::assertEquals($this->tracker_20, $mappings[20]->getTracker());
        self::assertEquals($this->field_124, $mappings[20]->getField());
    }

    public function testItUsesStatusFieldIfNoField(): void
    {
        $this->value_mapping_factory->method('getStatusMappings')->willReturn([]);
        $this->dao->method('searchMappingFields')->with($this->tracker->getId())->willReturn(TestHelper::arrayToDar(
            ['tracker_id' => 10, 'field_id' => null]
        ));

        $mappings = $this->factory->getMappings($this->tracker, $this->columns);
        self::assertCount(1, $mappings);
        self::assertEquals($this->status_field, $mappings[10]->getField());
        self::assertInstanceOf(Cardwall_OnTop_Config_TrackerMappingStatus::class, $mappings[10]);
    }

    public function testItReturnsANoFieldMappingIfNothingInDBAndNoStatus(): void
    {
        $this->dao->method('searchMappingFields')->with($this->tracker->getId())->willReturn(TestHelper::arrayToDar(
            ['tracker_id' => 20, 'field_id' => null]
        ));

        $mappings = $this->factory->getMappings($this->tracker, $this->columns);
        self::assertCount(1, $mappings);
        self::assertInstanceOf(Cardwall_OnTop_Config_TrackerMappingNoField::class, $mappings[20]);
    }

    public function testItReturnsEmptyMappingIfNoStatus(): void
    {
        $this->dao->method('searchMappingFields')->with($this->tracker->getId())->willReturn(TestHelper::arrayToDar(
            ['tracker_id' => 20, 'field_id' => null]
        ));

        $mappings = $this->factory->getMappings($this->tracker, $this->columns);
        self::assertCount(1, $mappings);
        self::assertInstanceOf(Cardwall_OnTop_Config_TrackerMappingNoField::class, $mappings[20]);
    }

    public function testItLoadValueMappings(): void
    {
        $this->dao->method('searchMappingFields')->with($this->tracker->getId())->willReturn(TestHelper::arrayToDar(
            ['tracker_id' => 20, 'field_id' => 124]
        ));
        $this->value_mapping_factory->expects($this->once())->method('getMappings')->with($this->tracker, $this->tracker_20, $this->field_124)
            ->willReturn([]);

        $this->factory->getMappings($this->tracker, $this->columns);
    }

    public function testItLoadValueMappingsEvenForStatusField(): void
    {
        $this->dao->method('searchMappingFields')->with($this->tracker->getId())->willReturn(TestHelper::arrayToDar(
            ['tracker_id' => 10, 'field_id' => null]
        ));
        $this->value_mapping_factory->expects($this->once())->method('getStatusMappings')->with($this->tracker_10, $this->columns)
            ->willReturn([]);

        $this->factory->getMappings($this->tracker, $this->columns);
    }
}
