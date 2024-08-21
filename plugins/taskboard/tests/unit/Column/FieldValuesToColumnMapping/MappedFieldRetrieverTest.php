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
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappedFieldRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\SearchMappedFieldStub;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Tracker\FormElement\Field\ListFields\RetrieveUsedListFieldStub;

final class MappedFieldRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \Cardwall_FieldProviders_SemanticStatusFieldRetriever&MockObject $status_retriever;
    private RetrieveUsedListFieldStub $form_element_factory;
    private SearchMappedFieldStub $search_mapped_field;
    private \Tracker $user_stories_tracker;

    protected function setUp(): void
    {
        $this->status_retriever     = $this->createMock(
            \Cardwall_FieldProviders_SemanticStatusFieldRetriever::class
        );
        $this->form_element_factory = RetrieveUsedListFieldStub::withNoField();
        $this->search_mapped_field  = SearchMappedFieldStub::withNoField();

        $this->user_stories_tracker = TrackerTestBuilder::aTracker()->withId(64)->build();
    }

    private function getField(): ?\Tracker_FormElement_Field_Selectbox
    {
        $taskboard_tracker = new TaskboardTracker(
            TrackerTestBuilder::aTracker()->build(),
            $this->user_stories_tracker
        );
        $retriever         = new MappedFieldRetriever(
            $this->status_retriever,
            new FreestyleMappedFieldRetriever($this->search_mapped_field, $this->form_element_factory)
        );
        return $retriever->getField($taskboard_tracker);
    }

    public function testReturnsFreestyleMappedField(): void
    {
        $this->search_mapped_field  = SearchMappedFieldStub::withMappedField(747);
        $field                      = ListFieldBuilder::aListField(747)->build();
        $this->form_element_factory = RetrieveUsedListFieldStub::withField($field);

        $result = $this->getField();
        self::assertSame($field, $result);
    }

    public function testReturnsStatusSemanticWhenNoMapping(): void
    {
        $field = ListFieldBuilder::aListField(133)->build();
        $this->status_retriever->expects(self::once())
            ->method('getField')
            ->with($this->user_stories_tracker)
            ->willReturn($field);

        $result = $this->getField();
        self::assertSame($field, $result);
    }
}
