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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_Selectbox;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappingFactory;
use Tuleap\Taskboard\Tracker\TaskboardTracker;

final class MappedValuesRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var MappedValuesRetriever */
    private $mapped_values_retriever;
    /** @var M\LegacyMockInterface|M\MockInterface|FreestyleMappingFactory */
    private $freestyle_mapping_factory;
    /** @var \Cardwall_FieldProviders_SemanticStatusFieldRetriever|M\LegacyMockInterface|M\MockInterface */
    private $status_retriever;

    protected function setUp(): void
    {
        $this->freestyle_mapping_factory = M::mock(FreestyleMappingFactory::class);
        $this->status_retriever          = M::mock(\Cardwall_FieldProviders_SemanticStatusFieldRetriever::class);
        $this->mapped_values_retriever   = new MappedValuesRetriever(
            $this->freestyle_mapping_factory,
            $this->status_retriever
        );
    }

    public function testGetValuesMappedToColumnReturnsFreestyleMappingWhenItExists(): void
    {
        $taskboard_tracker = new TaskboardTracker(M::mock(Tracker::class), M::mock(Tracker::class));
        $ongoing_column    = new \Cardwall_Column(7, 'On Going', 'clockwork-orange');
        $this->freestyle_mapping_factory->shouldReceive('doesFreestyleMappingExist')
            ->with($taskboard_tracker)
            ->once()
            ->andReturnTrue();
        $this->freestyle_mapping_factory->shouldReceive('getValuesMappedToColumn')
            ->with($taskboard_tracker, $ongoing_column)
            ->once()
            ->andReturn(new MappedValues([231, 856]));

        $result = $this->mapped_values_retriever->getValuesMappedToColumn($taskboard_tracker, $ongoing_column);
        $this->assertSame([231, 856], $result->getValueIds());
    }

    public function testGetValuesMappedToColumnReturnsEmptyWhenNoStatusSemantic(): void
    {
        $tracker           = M::mock(Tracker::class);
        $taskboard_tracker = new TaskboardTracker(M::mock(Tracker::class), $tracker);
        $ongoing_column    = new \Cardwall_Column(7, 'On Going', 'clockwork-orange');
        $this->freestyle_mapping_factory->shouldReceive('doesFreestyleMappingExist')
            ->with($taskboard_tracker)
            ->once()
            ->andReturnFalse();
        $this->status_retriever->shouldReceive('getField')
            ->with($tracker)
            ->once()
            ->andReturnNull();

        $result = $this->mapped_values_retriever->getValuesMappedToColumn($taskboard_tracker, $ongoing_column);
        $this->assertSame(0, count($result->getValueIds()));
    }

    public function testGetValuesMappedToColumnReturnsEmptyWhenStatusHasNoVisibleValues(): void
    {
        $tracker           = M::mock(Tracker::class);
        $taskboard_tracker = new TaskboardTracker(M::mock(Tracker::class), $tracker);
        $ongoing_column    = new \Cardwall_Column(7, 'On Going', 'clockwork-orange');
        $this->freestyle_mapping_factory->shouldReceive('doesFreestyleMappingExist')
            ->with($taskboard_tracker)
            ->once()
            ->andReturnFalse();
        $status_field = M::mock(Tracker_FormElement_Field_Selectbox::class);
        $status_field->shouldReceive('getVisibleValuesPlusNoneIfAny')
            ->once()
            ->andReturn([]);
        $this->status_retriever->shouldReceive('getField')
            ->with($tracker)
            ->once()
            ->andReturn($status_field);

        $result = $this->mapped_values_retriever->getValuesMappedToColumn($taskboard_tracker, $ongoing_column);
        $this->assertSame(0, count($result->getValueIds()));
    }

    public function testGetValuesMappedToColumnReturnsTheStatusValueWithTheSameLabelAsTheGivenColumn(): void
    {
        $tracker           = M::mock(Tracker::class);
        $taskboard_tracker = new TaskboardTracker(M::mock(Tracker::class), $tracker);
        $ongoing_column    = new \Cardwall_Column(7, 'On Going', 'clockwork-orange');
        $this->freestyle_mapping_factory->shouldReceive('doesFreestyleMappingExist')
            ->with($taskboard_tracker)
            ->once()
            ->andReturnFalse();
        $todo_list_value     = new Tracker_FormElement_Field_List_Bind_StaticValue(564, 'Todo', '', 1, false);
        $ongoing_list_value = new Tracker_FormElement_Field_List_Bind_StaticValue(756, 'On Going', '', 2, false);
        $status_field        = M::mock(Tracker_FormElement_Field_Selectbox::class);
        $status_field->shouldReceive('getVisibleValuesPlusNoneIfAny')
            ->once()
            ->andReturn([$todo_list_value, $ongoing_list_value]);
        $this->status_retriever->shouldReceive('getField')
            ->with($tracker)
            ->once()
            ->andReturn($status_field);

        $result = $this->mapped_values_retriever->getValuesMappedToColumn($taskboard_tracker, $ongoing_column);
        $this->assertSame([756], $result->getValueIds());
    }
}
