<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Tracker;

use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TrackerCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testMapReturnsArrayOfResultsOfClosure(): void
    {
        $milestone_tracker        = TrackerTestBuilder::aTracker()->withId(3)->build();
        $first_tracker            = TrackerTestBuilder::aTracker()->withId(1)->build();
        $second_tracker           = TrackerTestBuilder::aTracker()->withId(2)->build();
        $first_taskboard_tracker  = new TaskboardTracker($milestone_tracker, $first_tracker);
        $second_taskboard_tracker = new TaskboardTracker($milestone_tracker, $second_tracker);
        $collection               = new TrackerCollection([$first_taskboard_tracker, $second_taskboard_tracker]);

        $tracker_ids = $collection->map(function (TaskboardTracker $taskboard_tracker) {
            return $taskboard_tracker->getTrackerId();
        });

        self::assertEqualsCanonicalizing([1, 2], $tracker_ids);
    }
}
