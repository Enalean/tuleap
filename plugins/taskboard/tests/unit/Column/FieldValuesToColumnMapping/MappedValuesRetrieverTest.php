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
use Tuleap\Cardwall\Test\Builders\ColumnTestBuilder;
use Tuleap\Option\Option;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappedFieldValuesRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\SearchMappedFieldValuesForColumnStub;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\VerifyMappingExistsStub;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MappedValuesRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ON_GOING_COLUMN_LABEL = 'On Going';
    private \Cardwall_FieldProviders_SemanticStatusFieldRetriever&MockObject $status_retriever;
    private VerifyMappingExistsStub $verify_mapping_exists;
    private SearchMappedFieldValuesForColumnStub $search_values;
    private \Tuleap\Tracker\Tracker $user_stories_tracker;
    private TaskboardTracker $taskboard_tracker;
    private \Cardwall_Column $column;

    #[\Override]
    protected function setUp(): void
    {
        $this->status_retriever      = $this->createMock(\Cardwall_FieldProviders_SemanticStatusFieldRetriever::class);
        $this->verify_mapping_exists = VerifyMappingExistsStub::withNoMapping();
        $this->search_values         = SearchMappedFieldValuesForColumnStub::withNoMappedValue();

        $this->user_stories_tracker = TrackerTestBuilder::aTracker()->withId(164)->build();
        $this->taskboard_tracker    = new TaskboardTracker(
            TrackerTestBuilder::aTracker()->build(),
            $this->user_stories_tracker
        );
        $this->column               = ColumnTestBuilder::aColumn()->withLabel(self::ON_GOING_COLUMN_LABEL)->build();
    }

    /** @return Option<MappedValues | EmptyMappedValues> | Option<MappedValues> */
    private function getMappedValues(): Option
    {
        $retriever = new MappedValuesRetriever(
            new FreestyleMappedFieldValuesRetriever(
                $this->verify_mapping_exists,
                $this->search_values,
            ),
            $this->status_retriever
        );

        return $retriever->getValuesMappedToColumn($this->taskboard_tracker, $this->column);
    }

    public function testItReturnsFreestyleMappingFirst(): void
    {
        $this->verify_mapping_exists = VerifyMappingExistsStub::withMapping();
        $this->search_values         = SearchMappedFieldValuesForColumnStub::withValues(
            $this->taskboard_tracker,
            $this->column,
            [231, 856]
        );

        $result = $this->getMappedValues()->unwrapOr(null);
        self::assertSame([231, 856], $result?->getValueIds());
    }

    public function testItReturnsNothingWhenNoStatusSemantic(): void
    {
        $this->verify_mapping_exists = VerifyMappingExistsStub::withNoMapping();
        $this->status_retriever->expects($this->once())
            ->method('getField')
            ->with($this->user_stories_tracker)
            ->willReturn(null);

        self::assertTrue($this->getMappedValues()->isNothing());
    }

    public function testItReturnsNothingWhenStatusHasNoVisibleValues(): void
    {
        $status_field = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(225)->build()
        )->withStaticValues([])->build()->getField();
        $this->status_retriever->expects($this->once())
            ->method('getField')
            ->with($this->user_stories_tracker)
            ->willReturn($status_field);

        self::assertTrue($this->getMappedValues()->isNothing());
    }

    public function testItReturnsTheStatusValueWithTheSameLabelAsTheGivenColumn(): void
    {
        $status_field = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(975)->thatIsRequired()->build()
        )->withStaticValues([564 => 'Todo', 756 => self::ON_GOING_COLUMN_LABEL])->build()->getField();
        $this->status_retriever->expects($this->once())
            ->method('getField')
            ->with($this->user_stories_tracker)
            ->willReturn($status_field);

        $result = $this->getMappedValues()->unwrapOr(null);
        self::assertSame([756], $result?->getValueIds());
    }
}
