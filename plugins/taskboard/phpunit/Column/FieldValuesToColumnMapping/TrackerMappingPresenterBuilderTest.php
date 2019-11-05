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

use Cardwall_Column;
use Cardwall_OnTop_Config;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Planning;
use Planning_Milestone;
use Tracker;
use Tracker_FormElement_Field_Selectbox;

final class TrackerMappingPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var TrackerMappingPresenterBuilder */
    private $builder;
    /** @var \Cardwall_OnTop_ConfigFactory|M\LegacyMockInterface|M\MockInterface */
    private $config_factory;
    /** @var M\LegacyMockInterface|M\MockInterface|MappedFieldRetriever */
    private $mapped_field_retriever;
    /** @var M\LegacyMockInterface|M\MockInterface|MappedValuesRetriever */
    private $mapped_values_retriever;

    protected function setUp(): void
    {
        $this->config_factory          = M::mock(\Cardwall_OnTop_ConfigFactory::class);
        $this->mapped_field_retriever  = M::mock(MappedFieldRetriever::class);
        $this->mapped_values_retriever = M::mock(MappedValuesRetriever::class);
        $this->builder                 = new TrackerMappingPresenterBuilder(
            $this->config_factory,
            $this->mapped_field_retriever,
            $this->mapped_values_retriever
        );
    }

    public function testNoTrackers(): void
    {
        $planning          = M::mock(Planning::class);
        $milestone_tracker = M::mock(Tracker::class);
        $milestone         = $this->mockMilestone($planning, $milestone_tracker);
        $ongoing_column    = new Cardwall_Column(25, 'On Going', 'graffiti-yellow');
        $empty_config      = $this->mockConfig($planning);
        $empty_config->shouldReceive('getTrackers')
            ->once()
            ->andReturn([]);

        $this->assertEquals([], $this->builder->buildMappings($milestone, $ongoing_column));
    }

    public function testNoValuesForTracker(): void
    {
        $planning          = M::mock(Planning::class);
        $milestone_tracker = M::mock(Tracker::class);
        $milestone         = $this->mockMilestone($planning, $milestone_tracker);
        $ongoing_column    = new Cardwall_Column(25, 'On Going', 'graffiti-yellow');
        $tracker           = $this->mockTracker('76');
        $config            = $this->mockConfig($planning);
        $config->shouldReceive('getTrackers')
            ->once()
            ->andReturn([$tracker]);

        $this->mockMappedField('3086', $tracker);
        $this->mockMappedValues([], $milestone_tracker, $tracker, $ongoing_column);

        $result = $this->builder->buildMappings($milestone, $ongoing_column);

        $expected_empty_mapping = new TrackerMappingPresenter(76, 3086, []);
        $this->assertEquals([$expected_empty_mapping], $result);
    }

    public function testBuildMappingsReturnsMappingsForGivenColumn(): void
    {
        $planning          = M::mock(Planning::class);
        $milestone_tracker = M::mock(Tracker::class);
        $milestone         = $this->mockMilestone($planning, $milestone_tracker);
        $ongoing_column    = new Cardwall_Column(25, 'On Going', 'graffiti-yellow');
        $tracker           = $this->mockTracker('76');
        $config            = $this->mockConfig($planning);
        $config->shouldReceive('getTrackers')
            ->once()
            ->andReturn([$tracker]);
        $this->mockMappedField('3086', $tracker);
        $this->mockMappedValues([1674], $milestone_tracker, $tracker, $ongoing_column);

        $result = $this->builder->buildMappings($milestone, $ongoing_column);

        $expected_mapping = new TrackerMappingPresenter(76, 3086, [new ListFieldValuePresenter(1674)]);
        $this->assertEquals([$expected_mapping], $result);
    }

    public function testBuildMappingsMultipleTrackers(): void
    {
        $planning          = M::mock(Planning::class);
        $milestone_tracker = M::mock(Tracker::class);
        $milestone         = $this->mockMilestone($planning, $milestone_tracker);
        $ongoing_column    = new Cardwall_Column(25, 'On Going', 'graffiti-yellow');
        $first_tracker     = $this->mockTracker('76');
        $second_tracker    = $this->mockTracker('83');
        $config            = $this->mockConfig($planning);
        $config->shouldReceive('getTrackers')
            ->once()
            ->andReturn([$first_tracker, $second_tracker]);

        $this->mockMappedField('3086', $first_tracker);
        $this->mockMappedValues([1674], $milestone_tracker, $first_tracker, $ongoing_column);
        $this->mockMappedField('4597', $second_tracker);
        $this->mockMappedValues([1857, 1858], $milestone_tracker, $second_tracker, $ongoing_column);

        $result = $this->builder->buildMappings($milestone, $ongoing_column);

        $expected_first_mapping  = new TrackerMappingPresenter(76, 3086, [new ListFieldValuePresenter(1674)]);
        $expected_second_mapping = new TrackerMappingPresenter(
            83,
            4597,
            [new ListFieldValuePresenter(1857), new ListFieldValuePresenter(1858)]
        );
        $this->assertEquals([$expected_first_mapping, $expected_second_mapping], $result);
    }

    /**
     * @return Cardwall_OnTop_Config|M\LegacyMockInterface|M\MockInterface
     */
    private function mockConfig(Planning $planning)
    {
        $config = M::mock(Cardwall_OnTop_Config::class);
        $this->config_factory->shouldReceive('getOnTopConfigByPlanning')
            ->with($planning)
            ->once()
            ->andReturn($config);
        return $config;
    }

    /**
     * @return M\LegacyMockInterface|M\MockInterface|Tracker
     */
    private function mockTracker(string $tracker_id)
    {
        $tracker = M::mock(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturn($tracker_id);
        return $tracker;
    }

    private function mockMappedField(string $field_id, Tracker $tracker): void
    {
        $mapped_field = M::mock(Tracker_FormElement_Field_Selectbox::class);
        $mapped_field->shouldReceive('getId')->andReturn($field_id);
        $this->mapped_field_retriever->shouldReceive('getField')
            ->with(M::any(), $tracker)
            ->andReturn($mapped_field);
    }

    private function mockMappedValues(
        array $value_ids,
        Tracker $milestone_tracker,
        Tracker $tracker,
        Cardwall_Column $column
    ): void {
        $mapped_values = new MappedValues($value_ids);
        $this->mapped_values_retriever->shouldReceive('getValuesMappedToColumn')
            ->with($milestone_tracker, $tracker, $column)
            ->once()
            ->andReturn($mapped_values);
    }

    /**
     * @return M\LegacyMockInterface|M\MockInterface|Planning_Milestone
     */
    private function mockMilestone(Planning $planning, Tracker $milestone_tracker)
    {
        $milestone = M::mock(Planning_Milestone::class);
        $milestone
            ->shouldReceive('getPlanning')
            ->andReturn($planning);
        $milestone->shouldReceive('getArtifact')
            ->andReturn(
                M::mock(\Tracker_Artifact::class)->shouldReceive(['getTracker' => $milestone_tracker])
                    ->getMock()
            );
        return $milestone;
    }
}
