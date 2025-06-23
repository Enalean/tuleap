<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Description;

use Tuleap\Option\Option;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Stub\SearchDescriptionFieldStub;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveFieldByIdStub;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SemanticDescriptionFieldRetrieverTest extends TestCase
{
    private \Tracker_FormElement_Field_Text $description_field;
    private Tracker $tracker;

    protected function setUp(): void
    {
        $this->tracker           = TrackerTestBuilder::aTracker()->withId(12)->build();
        $this->description_field = TextFieldBuilder::aTextField(1005)->inTracker($this->tracker)->build();
    }

    public function testItReturnsTheFieldBoundToTheDescriptionSemanticWhenItIsDefined(): void
    {
        $retriever = new SemanticDescriptionFieldRetriever(
            SearchDescriptionFieldStub::withCallback(function (int $tracker_id) {
                if ($tracker_id !== $this->tracker->getId()) {
                    return Option::nothing(\Psl\Type\int());
                }

                return Option::fromValue($this->description_field->getId());
            }),
            RetrieveFieldByIdStub::withCallback(function (int $field_id) {
                if ($field_id !== $this->description_field->getId()) {
                    return null;
                }

                return $this->description_field;
            }),
        );

        self::assertSame($this->description_field, $retriever->fromTracker($this->tracker));
    }

    public function testItReturnsNullWhenFieldBoundToSemanticIsNotFound(): void
    {
        $retriever = new SemanticDescriptionFieldRetriever(
            SearchDescriptionFieldStub::withCallback(function (int $tracker_id) {
                if ($tracker_id !== $this->tracker->getId()) {
                    return Option::nothing(\Psl\Type\int());
                }

                return Option::fromValue($this->description_field->getId());
            }),
            RetrieveFieldByIdStub::withCallback(static fn () => null),
        );

        $this->assertNull($retriever->fromTracker($this->tracker));
    }

    public function testItReturnsNullWhenSemanticDescriptionIsNotDefined(): void
    {
        $retriever = new SemanticDescriptionFieldRetriever(
            SearchDescriptionFieldStub::withCallback(static fn () => Option::nothing(\Psl\Type\int())),
            RetrieveFieldByIdStub::withCallback(static fn () => null),
        );

        $this->assertNull($retriever->fromTracker($this->tracker));
    }
}
