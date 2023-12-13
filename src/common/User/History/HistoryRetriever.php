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

use Psr\EventDispatcher\EventDispatcherInterface;

final class HistoryRetriever
{
    public const MAX_LENGTH_HISTORY = 30;

    public function __construct(
        private readonly EventDispatcherInterface $event_manager,
        private readonly GetVisitHistory $project_dashboard_visit_retriever,
    ) {
    }

    /**
     * @return HistoryEntry[]
     */
    public function getHistory(\PFUser $user): array
    {
        $collection = new HistoryEntryCollection($user);

        $this->project_dashboard_visit_retriever->getVisitHistory($collection, self::MAX_LENGTH_HISTORY);

        $this->event_manager->dispatch($collection);
        $history = $collection->getEntries();

        $this->sortHistoryByVisitTime($history);

        return array_slice($history, 0, self::MAX_LENGTH_HISTORY);
    }

    private function sortHistoryByVisitTime(array &$history): void
    {
        usort($history, static function (HistoryEntry $a, HistoryEntry $b) {
            if ($a->getVisitTime() === $b->getVisitTime()) {
                return 0;
            }
            return $a->getVisitTime() > $b->getVisitTime() ? -1 : 1;
        });
    }
}
