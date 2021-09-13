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

namespace Tuleap\ProgramManagement\Adapter\Workspace;

use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TrackerFactoryAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testReturnArrayOfTrackerReference(): void
    {
        $tracker_factory = $this->createStub(\TrackerFactory::class);
        $tracker_factory->method('getTrackersByGroupId')->willReturn(
            [
                TrackerTestBuilder::aTracker()->withId(20)->withName('Sprint')->build(),
                TrackerTestBuilder::aTracker()->withId(30)->withName('Feature')->build()
            ]
        );

        $adapter            = new TrackerFactoryAdapter($tracker_factory);
        $trackers_reference = $adapter->retrieveAllTrackersFromProgramId(
            ProgramForAdministrationIdentifierBuilder::build()
        );

        self::assertCount(2, $trackers_reference);
        self::assertSame(20, $trackers_reference[0]->id);
        self::assertSame('Sprint', $trackers_reference[0]->label);
        self::assertSame(30, $trackers_reference[1]->id);
        self::assertSame('Feature', $trackers_reference[1]->label);
    }

    public function testReturnNullWhenNoTracker(): void
    {
        $tracker_factory = $this->createStub(\TrackerFactory::class);
        $tracker_factory->method('getTrackerById')->willReturn(null);

        $adapter = new TrackerFactoryAdapter($tracker_factory);

        self::assertNull($adapter->getTrackerById(85));
    }

    public function testReturnProgramTracker(): void
    {
        $tracker_factory = $this->createStub(\TrackerFactory::class);
        $tracker_factory->method('getTrackerById')->willReturn(TrackerTestBuilder::aTracker()->withId(85)
                                                 ->withProject(new \Project(['group_id' => 101, 'group_name' => "A project"]))
                                                 ->build());

        $adapter = new TrackerFactoryAdapter($tracker_factory);

        $tracker = $adapter->getTrackerById(85);

        self::assertInstanceOf(ProgramTracker::class, $tracker);
        self::assertSame(85, $tracker->getId());
    }
}
