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
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveSemanticStatusFieldStub;

#[DisableReturnValueGenerationForTestDoubles]
final class CachedSemanticStatusFieldRetrieverTest extends TestCase
{
    public function testItUsesCacheToLimitDBQueriesToRetrieveStatusFields(): void
    {
        $tracker   = TrackerTestBuilder::aTracker()->withId(12)->build();
        $field     = ListFieldBuilder::aListField(1002)->inTracker($tracker)->build();
        $retriever = RetrieveSemanticStatusFieldStub::build()->withField($field);

        $cache = new CachedSemanticStatusFieldRetriever($retriever);

        $cache->fromTracker($tracker);
        $cache->fromTracker($tracker);

        self::assertSame($field, $cache->fromTracker($tracker));
        self::assertSame(1, $retriever->getCallCount());
    }
}
