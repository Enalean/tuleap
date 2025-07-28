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

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Semantic\Description\RetrieveSemanticDescriptionFieldStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CachedSemanticDescriptionFieldRetrieverTest extends TestCase
{
    public function testItUsesCacheToLimitDBQueriesToRetrieveDescriptionFields(): void
    {
        $tracker                    = TrackerTestBuilder::aTracker()->withId(12)->build();
        $description_field          = TextFieldBuilder::aTextField(1002)->inTracker($tracker)->build();
        $retrieve_description_field = RetrieveSemanticDescriptionFieldStub::build()->withDescriptionField($description_field);

        $cache = new CachedSemanticDescriptionFieldRetriever($retrieve_description_field);

        $cache->fromTracker($tracker);
        $cache->fromTracker($tracker);

        self::assertSame(1, $retrieve_description_field->getCallCount());
        self::assertSame($description_field, $cache->fromTracker($tracker));
    }
}
