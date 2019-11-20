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
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappingFactory;
use Tuleap\Taskboard\Tracker\TaskboardTracker;

final class MappedFieldRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MappedFieldRetriever
     */
    private $mapped_field_retriever;
    /**
     * @var \Cardwall_FieldProviders_SemanticStatusFieldRetriever|M\LegacyMockInterface|M\MockInterface
     */
    private $status_retriever;
    /** @var M\LegacyMockInterface|M\MockInterface|FreestyleMappingFactory */
    private $freestyle_mapping_factory;

    protected function setUp(): void
    {
        $this->status_retriever = M::mock(\Cardwall_FieldProviders_SemanticStatusFieldRetriever::class);
        $this->freestyle_mapping_factory = M::mock(FreestyleMappingFactory::class);
        $this->mapped_field_retriever = new MappedFieldRetriever(
            $this->status_retriever,
            $this->freestyle_mapping_factory
        );
    }

    public function testReturnsFreestyleMappedField(): void
    {
        $taskboard_tracker = new TaskboardTracker(M::mock(Tracker::class), M::mock(Tracker::class));
        $field             = M::mock(\Tracker_FormElement_Field_Selectbox::class);
        $this->freestyle_mapping_factory->shouldReceive('getMappedField')
            ->with($taskboard_tracker)
            ->once()
            ->andReturn($field);

        $result = $this->mapped_field_retriever->getField($taskboard_tracker);
        $this->assertSame($field, $result);
    }

    public function testReturnsStatusSemanticWhenNoMapping(): void
    {
        $tracker           = M::mock(Tracker::class);
        $taskboard_tracker = new TaskboardTracker(M::mock(Tracker::class), $tracker);
        $field             = M::mock(\Tracker_FormElement_Field_Selectbox::class);
        $this->freestyle_mapping_factory->shouldReceive('getMappedField')
            ->with($taskboard_tracker)
            ->once()
            ->andReturnNull();
        $this->status_retriever->shouldReceive('getField')
            ->with($tracker)
            ->once()
            ->andReturn($field);

        $result = $this->mapped_field_retriever->getField($taskboard_tracker);

        $this->assertSame($field, $result);
    }
}
