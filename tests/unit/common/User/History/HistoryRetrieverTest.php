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

use Mockery;
use PHPUnit\Framework\TestCase;

class HistoryRetrieverTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItRetrievesHistorySortedByVisitTime()
    {
        $entries = [
            $this->getHistoryEntryAt(300),
            $this->getHistoryEntryAt(100),
            $this->getHistoryEntryAt(200),
        ];

        $history = $this
            ->buildHistoryRetriever($entries)
            ->getHistory(Mockery::mock(\PFUser::class));

        $this->assertCount(3, $history);
        $this->assertEquals(300, $history[0]->getVisitTime());
        $this->assertEquals(200, $history[1]->getVisitTime());
        $this->assertEquals(100, $history[2]->getVisitTime());
    }

    public function testItTruncatesHistoryToTheMaxLength(): void
    {
        $entries = [];
        foreach (range(1, HistoryRetriever::MAX_LENGTH_HISTORY * 2) as $n) {
            $entries[] = $this->getHistoryEntryAt($n);
        }

        $history = $this
            ->buildHistoryRetriever($entries)
            ->getHistory(Mockery::mock(\PFUser::class));

        $this->assertCount(HistoryRetriever::MAX_LENGTH_HISTORY, $history);
    }

    private function getHistoryEntryAt($visit_time): HistoryEntry
    {
        return new HistoryEntry(
            $visit_time,
            '',
            '',
            '',
            '',
            Mockery::mock(\Tuleap\Glyph\Glyph::class),
            Mockery::mock(\Tuleap\Glyph\Glyph::class),
            '',
            Mockery::mock(\Project::class),
            array()
        );
    }

    private function buildHistoryRetriever(array $entries): HistoryRetriever
    {
        $event_manager = new class ($entries) extends \EventManager
        {
            /**
             * @var HistoryEntry[]
             */
            private $entries;

            public function __construct(array $entries)
            {
                $this->entries = $entries;
            }

            public function processEvent($event, $params = [])
            {
                assert($event instanceof HistoryEntryCollection);
                foreach ($this->entries as $entry) {
                    $event->addEntry($entry);
                }
            }
        };

        return new HistoryRetriever($event_manager);
    }
}
