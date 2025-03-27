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
use Tuleap\Option\Option;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappedFieldRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\SearchMappedFieldStub;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\FormElement\Field\ListFields\RetrieveUsedListFieldStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MappedFieldRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \Cardwall_FieldProviders_SemanticStatusFieldRetriever&MockObject $status_retriever;
    private RetrieveUsedListFieldStub $form_element_factory;
    private SearchMappedFieldStub $search_mapped_field;
    private \Tracker $user_stories_tracker;
    private TaskboardTracker $taskboard_tracker;

    protected function setUp(): void
    {
        $this->status_retriever     = $this->createMock(
            \Cardwall_FieldProviders_SemanticStatusFieldRetriever::class
        );
        $this->form_element_factory = RetrieveUsedListFieldStub::withNoField();
        $this->search_mapped_field  = SearchMappedFieldStub::withNoField();

        $this->user_stories_tracker = TrackerTestBuilder::aTracker()->withId(64)->build();
        $this->taskboard_tracker    = new TaskboardTracker(
            TrackerTestBuilder::aTracker()->withId(17)->build(),
            $this->user_stories_tracker
        );
    }

    /** @return Option<\Tracker_FormElement_Field_Selectbox> */
    private function getField(): Option
    {
        $retriever = new MappedFieldRetriever(
            $this->status_retriever,
            new FreestyleMappedFieldRetriever($this->search_mapped_field, $this->form_element_factory)
        );
        return $retriever->getField($this->taskboard_tracker);
    }

    public function testItReturnsFreestyleMappedField(): void
    {
        $this->search_mapped_field  = SearchMappedFieldStub::withMappedField($this->taskboard_tracker, 747);
        $field                      = ListFieldBuilder::aListField(747)->build();
        $this->form_element_factory = RetrieveUsedListFieldStub::withField($field);

        self::assertSame($field, $this->getField()->unwrapOr(null));
    }

    public function testItReturnsStatusSemanticWhenNoFreestyleMapping(): void
    {
        $field = ListFieldBuilder::aListField(133)->build();
        $this->status_retriever->expects($this->once())
            ->method('getField')
            ->with($this->user_stories_tracker)
            ->willReturn($field);

        self::assertSame($field, $this->getField()->unwrapOr(null));
    }

    public function testItReturnsNothingWhenNoFreestyleMappingAndNoStatusSemantic(): void
    {
        $this->status_retriever->method('getField')->willReturn(null);

        self::assertTrue($this->getField()->isNothing());
    }
}
