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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappingFactory;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class MappedFieldRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MappedFieldRetriever $mapped_field_retriever;
    private \Cardwall_FieldProviders_SemanticStatusFieldRetriever&MockObject $status_retriever;
    private MockObject&FreestyleMappingFactory $freestyle_mapping_factory;

    protected function setUp(): void
    {
        $this->status_retriever          = $this->createMock(\Cardwall_FieldProviders_SemanticStatusFieldRetriever::class);
        $this->freestyle_mapping_factory = $this->createMock(FreestyleMappingFactory::class);
        $this->mapped_field_retriever    = new MappedFieldRetriever(
            $this->status_retriever,
            $this->freestyle_mapping_factory
        );
    }

    public function testReturnsFreestyleMappedField(): void
    {
        $taskboard_tracker = new TaskboardTracker(TrackerTestBuilder::aTracker()->build(), TrackerTestBuilder::aTracker()->build());
        $field             = $this->createMock(\Tracker_FormElement_Field_Selectbox::class);
        $this->freestyle_mapping_factory->expects(self::once())
            ->method('getMappedField')
            ->with($taskboard_tracker)
            ->willReturn($field);

        $result = $this->mapped_field_retriever->getField($taskboard_tracker);
        self::assertSame($field, $result);
    }

    public function testReturnsStatusSemanticWhenNoMapping(): void
    {
        $tracker           = TrackerTestBuilder::aTracker()->build();
        $taskboard_tracker = new TaskboardTracker(TrackerTestBuilder::aTracker()->build(), $tracker);
        $field             = $this->createMock(\Tracker_FormElement_Field_Selectbox::class);
        $this->freestyle_mapping_factory->expects(self::once())
            ->method('getMappedField')
            ->with($taskboard_tracker)
            ->willReturn(null);
        $this->status_retriever->expects(self::once())
            ->method('getField')
            ->with($tracker)
            ->willReturn($field);

        $result = $this->mapped_field_retriever->getField($taskboard_tracker);

        self::assertSame($field, $result);
    }
}
