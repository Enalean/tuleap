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

use Tuleap\Option\Option;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\OpenListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Tracker\FormElement\Field\ListFields\RetrieveUsedListFieldStub;

final class FreestyleMappedFieldRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SearchMappedFieldStub $search_mapped_field;
    private RetrieveUsedListFieldStub $form_element_factory;
    private TaskboardTracker $taskboard_tracker;

    protected function setUp(): void
    {
        $this->search_mapped_field  = SearchMappedFieldStub::withNoField();
        $this->form_element_factory = RetrieveUsedListFieldStub::withNoField();
        $this->taskboard_tracker    = new TaskboardTracker(
            TrackerTestBuilder::aTracker()->withId(276)->build(),
            TrackerTestBuilder::aTracker()->withId(587)->build()
        );
    }

    /** @return Option<\Tracker_FormElement_Field_Selectbox> */
    private function getMappedField(): Option
    {
        $retriever = new FreestyleMappedFieldRetriever($this->search_mapped_field, $this->form_element_factory);
        return $retriever->getMappedField($this->taskboard_tracker);
    }

    public function testGetMappedFieldReturnsNothingWhenNoMapping(): void
    {
        $this->search_mapped_field = SearchMappedFieldStub::withNoField();
        self::assertTrue($this->getMappedField()->isNothing());
    }

    public function testGetMappedFieldReturnsNothingWhenFieldIsNotSelectbox(): void
    {
        $this->search_mapped_field  = SearchMappedFieldStub::withMappedField($this->taskboard_tracker, 123);
        $field                      = OpenListFieldBuilder::anOpenListField()->withId(123)->build();
        $this->form_element_factory = RetrieveUsedListFieldStub::withField($field);

        self::assertTrue($this->getMappedField()->isNothing());
    }

    public function testGetMappedFieldReturnsMappedSelectbox(): void
    {
        $this->search_mapped_field  = SearchMappedFieldStub::withMappedField($this->taskboard_tracker, 123);
        $field                      = ListFieldBuilder::aListField(123)->build();
        $this->form_element_factory = RetrieveUsedListFieldStub::withField($field);

        self::assertSame($field, $this->getMappedField()->unwrapOr(null));
    }

    public function testGetMappedFieldReturnsMappedMultiSelectbox(): void
    {
        $this->search_mapped_field  = SearchMappedFieldStub::withMappedField($this->taskboard_tracker, 123);
        $field                      = ListFieldBuilder::aListField(123)->withMultipleValues()->build();
        $this->form_element_factory = RetrieveUsedListFieldStub::withField($field);

        self::assertSame($field, $this->getMappedField()->unwrapOr(null));
    }
}
