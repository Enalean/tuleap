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

namespace Tuleap\AgileDashboard\Kanban\RecentlyVisited;

use AgileDashboard_KanbanFactory;
use TrackerFactory;
use Tuleap\User\History\HistoryEntry;
use Tuleap\User\History\HistoryEntryCollection;

class VisitRetriever
{
    /**
     * @var RecentlyVisitedKanbanDao
     */
    private $dao;
    /**
     * @var AgileDashboard_KanbanFactory
     */
    private $kanban_factory;
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    public function __construct(
        RecentlyVisitedKanbanDao $dao,
        AgileDashboard_KanbanFactory $kanban_factory,
        TrackerFactory $tracker_factory
    ) {
        $this->dao             = $dao;
        $this->kanban_factory  = $kanban_factory;
        $this->tracker_factory = $tracker_factory;
    }

    public function getVisitHistory(HistoryEntryCollection $collection, int $max_length_history): void
    {
        $recently_visited_rows = $this->dao->searchVisitByUserId(
            (int) $collection->getUser()->getId(),
            $max_length_history
        );

        foreach ($recently_visited_rows as $recently_visited_row) {
            try {
                $kanban = $this->kanban_factory->getKanban(
                    $collection->getUser(),
                    $recently_visited_row['kanban_id']
                );
            } catch (\AgileDashboard_KanbanCannotAccessException | \AgileDashboard_KanbanNotFoundException $e) {
                continue;
            }

            $tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());
            if ($tracker === null) {
                continue;
            }

            $collection->addEntry(
                new HistoryEntry(
                    $recently_visited_row['created_on'],
                    null,
                    AGILEDASHBOARD_BASE_URL . '/?' . http_build_query(
                        [
                            'group_id' => $tracker->getProject()->getID(),
                            'action'   => 'showKanban',
                            'id'       => $kanban->getId()
                        ]
                    ),
                    $kanban->getName(),
                    $tracker->getColor()->getName(),
                    null,
                    null,
                    'fa-columns',
                    $tracker->getProject(),
                    []
                )
            );
        }
    }
}
