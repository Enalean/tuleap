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

namespace Tuleap\Tracker\Semantic\Status;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveSemanticStatusFieldStub;
use Tuleap\Tracker\Test\Stub\Semantic\Status\SearchStatusOpenValuesStub;

#[DisableReturnValueGenerationForTestDoubles]
final class SemanticStatusRetrieverTest extends TestCase
{
    public function testItReturnsEmptySemanticWhenNoField(): void
    {
        $retriever = new SemanticStatusRetriever(
            RetrieveSemanticStatusFieldStub::build(),
            SearchStatusOpenValuesStub::withCallback(static fn() => self::fail('Should not be called')),
        );

        $tracker  = TrackerTestBuilder::aTracker()->build();
        $semantic = $retriever->fromTracker($tracker);
        self::assertSame($tracker, $semantic->getTracker());
        self::assertNull($semantic->getField());
        self::assertEmpty($semantic->getOpenValues());
    }

    public function testItReturnsSemanticWithOpenValues(): void
    {
        $tracker     = TrackerTestBuilder::aTracker()->build();
        $field       = SelectboxFieldBuilder::aSelectboxField(854)->inTracker($tracker)->build();
        $open_values = [12, 15, 16];

        $retriever = new SemanticStatusRetriever(
            RetrieveSemanticStatusFieldStub::build()->withField($field),
            SearchStatusOpenValuesStub::withCallback(static function (int $field_id) use ($field, $open_values) {
                self::assertSame($field->getId(), $field_id);
                return $open_values;
            }),
        );

        $semantic = $retriever->fromTracker($tracker);
        self::assertSame($tracker, $semantic->getTracker());
        self::assertSame($field, $semantic->getField());
        self::assertSame($open_values, $semantic->getOpenValues());
    }
}
