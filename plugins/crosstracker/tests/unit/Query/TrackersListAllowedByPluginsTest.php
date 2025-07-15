<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

use Tuleap\Event\Dispatchable;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackersListAllowedByPluginsTest extends TestCase
{
    public function testItDispatchesARetrievedTrackerIdsEvent(): void
    {
        $dispatched_events       = [];
        $event_dispatcher        = EventDispatcherStub::withCallback(
            static function (Dispatchable $event) use (&$dispatched_events): Dispatchable {
                $dispatched_events[] = $event;

                return $event;
            }
        );
        $trackers_list_retriever = new TrackersListAllowedByPlugins(
            $event_dispatcher,
            RetrieveTrackerStub::withTrackers(
                TrackerTestBuilder::aTracker()->withId(123)->build(),
                TrackerTestBuilder::aTracker()->withId(456)->build(),
            )
        );

        $trackers_list_retriever->getTrackers([123, 456]);

        self::assertEquals(1, $event_dispatcher->getCallCount());
        self::assertInstanceOf(RetrievedQueryTrackerIds::class, $dispatched_events[0]);
    }

    public function testItReturnsTrackersFilteredByEvent(): void
    {
        $tracker_id_1 = 123;
        $tracker_id_2 = 456;

        $retrieved_query_tracker_ids_event = new RetrievedQueryTrackerIds([$tracker_id_1]);
        $event_dispatcher                  = EventDispatcherStub::withCallback(static fn () => $retrieved_query_tracker_ids_event);
        $trackers_list_retriever           = new TrackersListAllowedByPlugins(
            $event_dispatcher,
            RetrieveTrackerStub::withTrackers(
                TrackerTestBuilder::aTracker()->withId($tracker_id_1)->build(),
                TrackerTestBuilder::aTracker()->withId($tracker_id_2)->build(),
            )
        );

        $retrieved_trackers = $trackers_list_retriever->getTrackers([$tracker_id_1, $tracker_id_2]);

        self::assertCount(1, $retrieved_trackers);
        self::assertEquals($tracker_id_1, $retrieved_trackers[0]->getTracker()->getId());
    }
}
