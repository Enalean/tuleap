<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document;

use ColinODell\PsrTestLogger\TestLogger;
use Psr\Log\NullLogger;
use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Stubs\Document\SearchConfiguredTrackerStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ConfiguredTrackerRetrieverTest extends TestCase
{
    public function testNullWhenNoConfiguredTracker(): void
    {
        $retriever = new ConfiguredTrackerRetriever(
            SearchConfiguredTrackerStub::withoutResults(),
            RetrieveTrackerStub::withoutTracker(),
            new NullLogger(),
        );

        $document = new ArtidocDocument(['item_id' => 101]);

        self::assertNull($retriever->getTracker($document));
    }

    public function testNullWhenConfiguredTrackerCannotBeFound(): void
    {
        $logger    = new TestLogger();
        $retriever = new ConfiguredTrackerRetriever(
            SearchConfiguredTrackerStub::withResults(1001),
            RetrieveTrackerStub::withoutTracker(),
            $logger,
        );

        $document = new ArtidocDocument(['item_id' => 101]);

        self::assertNull($retriever->getTracker($document));
        self::assertTrue($logger->hasWarningRecords());
    }

    public function testNullWhenConfiguredTrackerIsDeleted(): void
    {
        $tracker = TrackerTestBuilder::aTracker()
            ->withId(1001)
            ->withDeletionDate(1)
            ->build();

        $logger    = new TestLogger();
        $retriever = new ConfiguredTrackerRetriever(
            SearchConfiguredTrackerStub::withResults($tracker->getId()),
            RetrieveTrackerStub::withTracker($tracker),
            $logger,
        );

        $document = new ArtidocDocument(['item_id' => 101]);

        self::assertNull($retriever->getTracker($document));
        self::assertTrue($logger->hasWarningRecords());
    }

    public function testHappyPath(): void
    {
        $tracker = TrackerTestBuilder::aTracker()
            ->withId(1001)
            ->build();

        $retriever = new ConfiguredTrackerRetriever(
            SearchConfiguredTrackerStub::withResults($tracker->getId()),
            RetrieveTrackerStub::withTracker($tracker),
            new NullLogger(),
        );

        $document = new ArtidocDocument(['item_id' => 101]);

        self::assertSame($tracker, $retriever->getTracker($document));
    }
}
