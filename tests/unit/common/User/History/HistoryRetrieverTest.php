<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\User\History;

use Tuleap\Glyph\Glyph;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\EventDispatcherStub;

final class HistoryRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @param HistoryEntry[] $entries
     * @return HistoryEntry[]
     */
    private function getHistory(array $entries): array
    {
        $event_manager = EventDispatcherStub::withCallback(
            static function (HistoryEntryCollection $event) use ($entries) {
                foreach ($entries as $entry) {
                    $event->addEntry($entry);
                }
                return $event;
            }
        );
        $retriever     = new HistoryRetriever($event_manager);
        return $retriever->getHistory(UserTestBuilder::buildWithDefaults());
    }

    public function testItRetrievesHistorySortedByVisitTime(): void
    {
        $entries = [
            $this->getHistoryEntryAt(300),
            $this->getHistoryEntryAt(100),
            $this->getHistoryEntryAt(200),
        ];

        $history = $this->getHistory($entries);

        $this->assertCount(3, $history);
        $this->assertSame(300, $history[0]->getVisitTime());
        $this->assertSame(200, $history[1]->getVisitTime());
        $this->assertSame(100, $history[2]->getVisitTime());
    }

    public function testItTruncatesHistoryToTheMaxLength(): void
    {
        $entries = [];
        foreach (range(1, HistoryRetriever::MAX_LENGTH_HISTORY * 2) as $n) {
            $entries[] = $this->getHistoryEntryAt($n);
        }

        $history = $this->getHistory($entries);

        $this->assertCount(HistoryRetriever::MAX_LENGTH_HISTORY, $history);
    }

    private function getHistoryEntryAt(int $visit_time): HistoryEntry
    {
        return new HistoryEntry(
            $visit_time,
            '',
            '',
            '',
            '',
            $this->createMock(Glyph::class),
            $this->createMock(Glyph::class),
            '',
            ProjectTestBuilder::aProject()->build(),
            [],
            [],
        );
    }
}
