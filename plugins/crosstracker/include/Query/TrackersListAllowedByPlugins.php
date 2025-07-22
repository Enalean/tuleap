<?php
/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\CrossTracker\Query;

use LogicException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Tracker\RetrieveTracker;
use Tuleap\Tracker\Tracker;

final readonly class TrackersListAllowedByPlugins implements InstantiateRetrievedQueryTrackers
{
    public function __construct(
        private EventDispatcherInterface $event_dispatcher,
        private RetrieveTracker $tracker_retriever,
    ) {
    }

    /**
     * @param int[] $trackers_ids
     * @return Tracker[]
     */
    #[\Override]
    public function getTrackers(array $trackers_ids): array
    {
        $event = $this->event_dispatcher->dispatch(new RetrievedQueryTrackerIds($trackers_ids));

        $trackers = [];
        foreach ($event->getTrackerIds() as $id) {
            $tracker = $this->tracker_retriever->getTrackerById($id);
            if ($tracker === null) {
                throw new LogicException("Tracker #$id found in db but unable to find it again");
            }
            $trackers[] = $tracker;
        }
        return $trackers;
    }
}
