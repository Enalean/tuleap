<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);


namespace Tuleap\ProgramManagement\Tests\Builder;

use Tracker;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramTrackerBuilder
{
    public static function buildWithId(int $id): ProgramTracker
    {
        return new ProgramTracker(TrackerTestBuilder::aTracker()->withId($id)->build());
    }

    /**
     * @var \PHPUnit\Framework\MockObject\Stub|Tracker $tracker
     */
    public static function buildWithMockedTracker($tracker): ProgramTracker
    {
        return new ProgramTracker($tracker);
    }

    public static function buildWithTracker(Tracker $tracker_team_01): ProgramTracker
    {
        return new ProgramTracker($tracker_team_01);
    }
}
