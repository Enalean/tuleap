<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Roadmap\REST\v1;

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TrackersWithUnreadableStatusCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItDoesNotLogAnythingIfThereIsNoTracker(): void
    {
        $logger     = new TestLogger();
        $collection = new TrackersWithUnreadableStatusCollection($logger);

        $collection->informLoggerIfWeHaveTrackersWithUnreadableStatus();

        self::assertFalse($logger->hasInfoRecords());
    }

    public function testItLogsOneInfoForOneTracker(): void
    {
        $logger     = new TestLogger();
        $collection = new TrackersWithUnreadableStatusCollection($logger);

        $collection->add(TrackerTestBuilder::aTracker()->withId(101)->build());

        $collection->informLoggerIfWeHaveTrackersWithUnreadableStatus();

        self::assertTrue($logger->hasInfo('[Roadmap widget] User cannot read status of tracker #101. Hence, its artifacts won\'t be displayed.'));
    }

    public function testItLogOneInfoForManyTrackers(): void
    {
        $logger     = new TestLogger();
        $collection = new TrackersWithUnreadableStatusCollection($logger);

        $collection->add(TrackerTestBuilder::aTracker()->withId(101)->build());
        $collection->add(TrackerTestBuilder::aTracker()->withId(102)->build());
        $collection->add(TrackerTestBuilder::aTracker()->withId(103)->build());
        $collection->add(TrackerTestBuilder::aTracker()->withId(101)->build());

        $collection->informLoggerIfWeHaveTrackersWithUnreadableStatus();

        self::assertTrue($logger->hasInfo('[Roadmap widget] User cannot read status of trackers #101, #102, #103. Hence, their artifacts won\'t be displayed.'));
    }
}
