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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle;

use Tuleap\Cardwall\Test\Builders\ColumnTestBuilder;
use Tuleap\Option\Option;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\EmptyMappedValues;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedValues;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FreestyleMappedFieldValuesRetrieverTest extends TestCase
{
    private VerifyMappingExistsStub $verify_mapping_exists;
    private SearchMappedFieldValuesForColumnStub $search_values;
    private TaskboardTracker $taskboard_tracker;
    private \Cardwall_Column $column;

    protected function setUp(): void
    {
        $this->verify_mapping_exists = VerifyMappingExistsStub::withNoMapping();
        $this->search_values         = SearchMappedFieldValuesForColumnStub::withNoMappedValue();

        $this->taskboard_tracker = new TaskboardTracker(
            TrackerTestBuilder::aTracker()->withId(736)->build(),
            TrackerTestBuilder::aTracker()->withId(246)->build(),
        );
        $this->column            = ColumnTestBuilder::aColumn()->build();
    }

    /**
     * @return Option<EmptyMappedValues>|Option<MappedValues>
     */
    private function getValues(): Option
    {
        $retriever = new FreestyleMappedFieldValuesRetriever(
            $this->verify_mapping_exists,
            $this->search_values,
        );
        return $retriever->getValuesMappedToColumn($this->taskboard_tracker, $this->column);
    }

    public function testItReturnsNothingWhenNoMapping(): void
    {
        $this->verify_mapping_exists = VerifyMappingExistsStub::withNoMapping();

        self::assertTrue($this->getValues()->isNothing());
    }

    public function testItReturnsEmptyMappingWhenNoMappedValues(): void
    {
        $this->verify_mapping_exists = VerifyMappingExistsStub::withMapping();
        $this->search_values         = SearchMappedFieldValuesForColumnStub::withNoMappedValue();

        $result = $this->getValues()->unwrapOr(null);
        self::assertNotNull($result);
        self::assertEmpty($result->getValueIds());
    }

    public function testItReturnsValues(): void
    {
        $this->verify_mapping_exists = VerifyMappingExistsStub::withMapping();
        $this->search_values         = SearchMappedFieldValuesForColumnStub::withValues(
            $this->taskboard_tracker,
            $this->column,
            [123, 127]
        );

        self::assertSame([123, 127], $this->getValues()->unwrapOr(null)?->getValueIds());
    }
}
