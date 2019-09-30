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
     * @var \Cardwall_OnTop_ConfigFactory|M\LegacyMockInterface|M\MockInterface
     */
    private $config_factory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|MappedFieldRetriever
     */
    private $mapped_field_retriever;

    protected function setUp(): void
    {
        $this->config_factory         = M::mock(\Cardwall_OnTop_ConfigFactory::class);
        $this->mapped_field_retriever = M::mock(MappedFieldRetriever::class);
        $this->builder                = new TrackerMappingPresenterBuilder(
            $this->config_factory,
            $this->mapped_field_retriever
        );
    }

    public function testNoTrackers(): void
    {
        $empty_config = $this->mockConfig();
        $empty_config->shouldReceive('getTrackers')
            ->once()
            ->andReturn([]);
        $planning = M::mock(\Planning::class);

        $this->assertEquals([], $this->builder->buildMappings(25, $planning));
    }

    public function testNoValuesForTracker(): void
    {
        $config  = $this->mockConfig();
        $tracker = $this->mockMappingForATracker($config, '76', []);
        $config->shouldReceive('getTrackers')
            ->once()
            ->andReturn([$tracker]);
        $planning = M::mock(\Planning::class);
        $this->mockMappedField('3086', $tracker);

        $result = $this->builder->buildMappings(25, $planning);

        $expected_empty_mapping = new TrackerMappingPresenter(76, 3086, []);
        $this->assertEquals([$expected_empty_mapping], $result);
    }

    public function testBuildMappingsOnlyReturnsMappingsForGivenColumn(): void
    {
        $config  = $this->mockConfig();
        $tracker = $this->mockMappingForATracker($config, '76', [1674 => 25, 1676 => 28]);
        $config->shouldReceive('getTrackers')
            ->once()
            ->andReturn([$tracker]);
        $planning = M::mock(\Planning::class);
        $this->mockMappedField('3086', $tracker);

        $result = $this->builder->buildMappings(25, $planning);

        $expected_mapping = new TrackerMappingPresenter(76, 3086, [new ListFieldValuePresenter(1674)]);
        $this->assertEquals([$expected_mapping], $result);
    }

    public function testBuildMappingsMultipleTrackers(): void
    {
        $config         = $this->mockConfig();
        $first_tracker  = $this->mockMappingForATracker($config, '76', [1674 => 25]);
        $second_tracker = $this->mockMappingForATracker($config, '83', [1857 => 25, 1858 => 25]);
        $config->shouldReceive('getTrackers')
            ->once()
            ->andReturn([$first_tracker, $second_tracker]);
        $planning = M::mock(\Planning::class);

        $this->mockMappedField('3086', $first_tracker);
        $this->mockMappedField('4597', $second_tracker);

        $result = $this->builder->buildMappings(25, $planning);

        $expected_first_mapping  = new TrackerMappingPresenter(76, 3086, [new ListFieldValuePresenter(1674)]);
        $expected_second_mapping = new TrackerMappingPresenter(
            83,
            4597,
            [new ListFieldValuePresenter(1857), new ListFieldValuePresenter(1858)]
        );
        $this->assertEquals([$expected_first_mapping, $expected_second_mapping], $result);
    }

    /**
     * @return \Cardwall_OnTop_Config|M\LegacyMockInterface|M\MockInterface
     */
    private function mockConfig()
    {
        $config = M::mock(\Cardwall_OnTop_Config::class);
        $this->config_factory->shouldReceive('getOnTopConfigByPlanning')
            ->once()
            ->andReturn($config);
        return $config;
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

    /**
     * @return \Tracker
     */
    private function mockMappingForATracker(
        M\MockInterface $config,
        string $tracker_id,
        array $list_values_to_column_mapping
    ) {
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
        $config->shouldReceive('getMappingFor')
            ->with($tracker)
            ->andReturn($mapping);

        return $tracker;
    }

    /**
     * @param $tracker
     */
    private function mockMappedField(string $field_id, $tracker): void
    {
        $mapped_field = M::mock(\Tracker_FormElement_Field_List::class);
        $mapped_field->shouldReceive('getId')->andReturn($field_id);
        $this->mapped_field_retriever->shouldReceive('getField')
            ->with(M::any(), $tracker)
            ->andReturn($mapped_field);
    }
}
