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

use Tuleap\Test\Builders\HistoryEntryBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\User\History\GetVisitHistoryStub;

final class HistoryRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @param HistoryEntry[] $other_entries
     * @return HistoryEntry[]
     */
    private function getHistory(GetVisitHistory $project_dashboard_visit_retriever, array $other_entries): array
    {
        $event_manager = EventDispatcherStub::withCallback(
            static function (HistoryEntryCollection $event) use ($other_entries) {
                foreach ($other_entries as $entry) {
                    $event->addEntry($entry);
                }
                return $event;
            }
        );

        $retriever = new HistoryRetriever(
            $event_manager,
            $project_dashboard_visit_retriever,
        );

        return $retriever->getHistory(UserTestBuilder::buildWithDefaults());
    }

    public function testItRetrievesHistorySortedByVisitTime(): void
    {
        $entries = [
            HistoryEntryBuilder::anEntryVisitedAt(300)->build(),
            HistoryEntryBuilder::anEntryVisitedAt(100)->build(),
            HistoryEntryBuilder::anEntryVisitedAt(200)->build(),
        ];

        $history = $this->getHistory(
            GetVisitHistoryStub::withEntries(
                HistoryEntryBuilder::anEntryVisitedAt(150)->build()
            ),
            $entries
        );

        $this->assertCount(4, $history);
        $this->assertSame(300, $history[0]->getVisitTime());
        $this->assertSame(200, $history[1]->getVisitTime());
        $this->assertSame(150, $history[2]->getVisitTime());
        $this->assertSame(100, $history[3]->getVisitTime());
    }

    public function testItTruncatesHistoryToTheMaxLength(): void
    {
        $entries = [];
        foreach (range(1, HistoryRetriever::MAX_LENGTH_HISTORY * 2) as $n) {
            $entries[] = HistoryEntryBuilder::anEntryVisitedAt($n)->build();
        }

        $history = $this->getHistory(GetVisitHistoryStub::withoutEntries(), $entries);

        $this->assertCount(HistoryRetriever::MAX_LENGTH_HISTORY, $history);
    }
}
