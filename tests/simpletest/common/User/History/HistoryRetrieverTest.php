<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\User\History;

require_once __DIR__ . '/MockedEventManager.php';

class HistoryRetrieverTest extends \TuleapTestCase
{
    public function itRetrievesHistorySortedByVisitTime()
    {
        $event_manager = new MockedEventManager(function ($name, $params) {
            $params['history'] = array(
                new HistoryEntry(300, '', '', ''),
                new HistoryEntry(100, '', '', ''),
                new HistoryEntry(200, '', '', '')
            );
        });
        $history_retriever = new HistoryRetriever($event_manager);

        $history = $history_retriever->getHistory(mock('PFUser'));

        $this->assertCount($history, 3);
        $this->assertEqual($history[0]->getVisitTime(), 300);
        $this->assertEqual($history[1]->getVisitTime(), 200);
        $this->assertEqual($history[2]->getVisitTime(), 100);
    }

    public function itTruncatesHistoryToTheMaxLength()
    {
        $event_manager = new MockedEventManager(function ($name, $params) {
            foreach (range(1, HistoryRetriever::MAX_LENGTH_HISTORY * 2) as $n) {
                $params['history'][] = new HistoryEntry($n, '', '', '');
            }
        });

        $history_retriever = new HistoryRetriever($event_manager);

        $history = $history_retriever->getHistory(mock('PFUser'));
        $this->assertCount($history, HistoryRetriever::MAX_LENGTH_HISTORY);
    }
}
