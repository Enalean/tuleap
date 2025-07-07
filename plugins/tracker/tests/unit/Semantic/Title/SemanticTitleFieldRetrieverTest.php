<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Title;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Option\Option;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveFieldByIdStub;
use Tuleap\Tracker\Test\Stub\Semantic\Title\SearchTitleFieldStub;
use Tuleap\Tracker\Tracker;
use function Psl\Type\int;

#[DisableReturnValueGenerationForTestDoubles]
final class SemanticTitleFieldRetrieverTest extends TestCase
{
    private TextField $title_field;
    private Tracker $tracker;

    protected function setUp(): void
    {
        $this->tracker     = TrackerTestBuilder::aTracker()->withId(12)->build();
        $this->title_field = TextFieldBuilder::aTextField(1005)->inTracker($this->tracker)->build();
    }

    public function testItReturnsTheFieldBoundToTheTitleSemanticWhenItIsDefined(): void
    {
        $retriever = new SemanticTitleFieldRetriever(
            SearchTitleFieldStub::withCallback(function (int $tracker_id) {
                if ($tracker_id !== $this->tracker->getId()) {
                    return Option::nothing(int());
                }
                return Option::fromValue($this->title_field->getId());
            }),
            RetrieveFieldByIdStub::withCallback(function (int $field_id) {
                if ($field_id !== $this->title_field->getId()) {
                    return null;
                }
                return $this->title_field;
            }),
        );
        self::assertSame($this->title_field, $retriever->fromTracker($this->tracker));
    }

    public function testItReturnsNullWhenFieldBoundToSemanticIsNotFound(): void
    {
        $retriever = new SemanticTitleFieldRetriever(
            SearchTitleFieldStub::withCallback(function (int $tracker_id) {
                if ($tracker_id !== $this->tracker->getId()) {
                    return Option::nothing(int());
                }
                return Option::fromValue($this->title_field->getId());
            }),
            RetrieveFieldByIdStub::withCallback(static fn() => null),
        );
        self::assertNull($retriever->fromTracker($this->tracker));
    }

    public function testItReturnsNullWhenSemanticTitleIsNotDefined(): void
    {
        $retriever = new SemanticTitleFieldRetriever(
            SearchTitleFieldStub::withCallback(static fn() => Option::nothing(int())),
            RetrieveFieldByIdStub::withCallback(static fn() => null),
        );
        self::assertNull($retriever->fromTracker($this->tracker));
    }
}
