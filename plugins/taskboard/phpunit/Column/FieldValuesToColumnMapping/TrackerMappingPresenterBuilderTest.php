<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping;

use Cardwall_OnTop_Config_TrackerMapping;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class TrackerMappingPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TrackerMappingPresenterBuilder
     */
    private $builder;
    /**
     * @var \Cardwall_OnTop_Dao|M\LegacyMockInterface|M\MockInterface
     */
    private $cardwall_dao;
    /**
     * @var \Cardwall_OnTop_Config_ColumnFactory|M\LegacyMockInterface|M\MockInterface
     */
    private $column_factory;
    /**
     * @var \Cardwall_OnTop_Config_TrackerMappingFactory|M\LegacyMockInterface|M\MockInterface
     */
    private $tracker_mapping_factory;

    protected function setUp(): void
    {
        $this->cardwall_dao            = M::mock(\Cardwall_OnTop_Dao::class);
        $this->column_factory          = M::mock(\Cardwall_OnTop_Config_ColumnFactory::class);
        $this->tracker_mapping_factory = M::mock(\Cardwall_OnTop_Config_TrackerMappingFactory::class);
        $this->builder                 = new TrackerMappingPresenterBuilder(
            $this->cardwall_dao,
            $this->column_factory,
            $this->tracker_mapping_factory
        );
    }

    public function testNoTrackers(): void
    {
        $this->tracker_mapping_factory->shouldReceive('getTrackers')
            ->once()
            ->andReturn([]);
        $planning = $this->mockPlanning();

        $this->assertEquals([], $this->builder->buildMappings(25, $planning));
    }

    public function testNoValuesForTracker(): void
    {
        list($tracker, $mapping) = $this->mockMappingForATracker('76', []);
        $this->tracker_mapping_factory->shouldReceive('getTrackers')
            ->once()
            ->andReturn([$tracker]);
        $this->tracker_mapping_factory->shouldReceive('getMappings')
            ->once()
            ->andReturn(['76' => $mapping]);
        $planning = $this->mockPlanning();

        $result = $this->builder->buildMappings(25, $planning);

        $expected_empty_mapping = new TrackerMappingPresenter(76, []);
        $this->assertEquals([$expected_empty_mapping], $result);
    }

    public function testBuildMappingsOnlyReturnsMappingsForGivenColumn(): void
    {
        list($tracker, $mapping) = $this->mockMappingForATracker('76', [1674 => 25, 1676 => 28]);
        $this->tracker_mapping_factory->shouldReceive('getTrackers')
            ->once()
            ->andReturn([$tracker]);
        $this->tracker_mapping_factory->shouldReceive('getMappings')
            ->once()
            ->andReturn(['76' => $mapping]);
        $planning = $this->mockPlanning();

        $result = $this->builder->buildMappings(25, $planning);

        $expected_mapping = new TrackerMappingPresenter(76, [new ListFieldValuePresenter(1674)]);
        $this->assertEquals([$expected_mapping], $result);
    }

    public function testBuildMappingsMultipleTrackers(): void
    {
        list($first_tracker, $first_mapping) = $this->mockMappingForATracker('76', [1674 => 25]);
        list($second_tracker, $second_mapping) = $this->mockMappingForATracker('83', [1857 => 25, 1858 => 25]);
        $this->tracker_mapping_factory->shouldReceive('getTrackers')
            ->once()
            ->andReturn([$first_tracker, $second_tracker]);
        $this->tracker_mapping_factory->shouldReceive('getMappings')
            ->once()
            ->andReturn(['76' => $first_mapping, '83' => $second_mapping]);
        $planning = $this->mockPlanning();

        $result = $this->builder->buildMappings(25, $planning);

        $expected_first_mapping  = new TrackerMappingPresenter(76, [new ListFieldValuePresenter(1674)]);
        $expected_second_mapping = new TrackerMappingPresenter(
            83,
            [new ListFieldValuePresenter(1857), new ListFieldValuePresenter(1858)]
        );
        $this->assertEquals([$expected_first_mapping, $expected_second_mapping], $result);
    }

    /**
     * @return M\MockInterface|\Planning
     */
    private function mockPlanning()
    {
        $cardwall_tracker = M::mock(\Tracker::class);
        $cardwall_tracker->shouldReceive('getId')->andReturn('89');
        $planning = M::mock(\Planning::class);
        $planning->shouldReceive('getPlanningTracker')
            ->once()
            ->andReturn($cardwall_tracker);
        return $planning;
    }

    private function getValueMapping(int $list_value_id, int $column_id): \Cardwall_OnTop_Config_ValueMapping
    {
        $list_value = M::mock(\Tracker_FormElement_Field_List_Value::class);
        $list_value->shouldReceive('getId')->andReturn($list_value_id);
        return new \Cardwall_OnTop_Config_ValueMapping(
            $list_value,
            $column_id
        );
    }

    private function mockMappingForATracker(string $tracker_id, array $list_values_to_column_mapping)
    {
        $tracker = M::mock(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturn($tracker_id);

        $value_mappings = [];
        $columns        = [];
        foreach ($list_values_to_column_mapping as $list_value_id => $column_id) {
            $value_mappings[] = $this->getValueMapping($list_value_id, $column_id);
            $columns[]        = new \Cardwall_Column($column_id, 'whatever', 'irrelevant');
        }
        $mapping = M::mock(Cardwall_OnTop_Config_TrackerMapping::class);
        $mapping->shouldReceive('getValueMappings')
            ->once()
            ->andReturn($value_mappings);
        $this->column_factory->shouldReceive('getDashboardColumns')
            ->andReturn(new \Cardwall_OnTop_Config_ColumnCollection($columns));
        return [$tracker, $mapping];
    }
}
