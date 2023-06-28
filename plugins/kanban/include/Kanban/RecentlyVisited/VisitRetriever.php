<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban\RecentlyVisited;

use Tuleap\User\History\HistoryEntry;
use Tuleap\User\History\HistoryEntryCollection;

final class VisitRetriever
{
    public const TYPE = 'kanban';

    public function __construct(
        private RecentlyVisitedKanbanDao $dao,
        private \AgileDashboard_KanbanFactory $kanban_factory,
        private \TrackerFactory $tracker_factory,
    ) {
    }

    public function getVisitHistory(HistoryEntryCollection $collection, int $max_length_history): void
    {
        $recently_visited_rows = $this->dao->searchVisitByUserId(
            (int) $collection->getUser()->getId(),
            $max_length_history
        );

        foreach ($recently_visited_rows as $recently_visited_row) {
            $this->addEntry(
                $collection,
                (int) $recently_visited_row['created_on'],
                (int) $recently_visited_row['kanban_id']
            );
        }
    }

    private function addEntry(
        HistoryEntryCollection $collection,
        int $created_on,
        int $kanban_id,
    ): void {
        try {
            $kanban = $this->kanban_factory->getKanban($collection->getUser(), $kanban_id);
        } catch (\AgileDashboard_KanbanCannotAccessException | \AgileDashboard_KanbanNotFoundException) {
            return;
        }

        $tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());
        if ($tracker === null) {
            return;
        }

        $collection->addEntry(
            new HistoryEntry(
                $created_on,
                null,
                AGILEDASHBOARD_BASE_URL . '/?' . http_build_query(
                    [
                        'group_id' => $tracker->getProject()->getID(),
                        'action'   => 'showKanban',
                        'id'       => $kanban->getId(),
                    ]
                ),
                $kanban->getName(),
                $tracker->getColor()->getName(),
                self::TYPE,
                $kanban->getId(),
                null,
                null,
                'fa-columns',
                $tracker->getProject(),
                [],
                [],
            )
        );
    }
}
