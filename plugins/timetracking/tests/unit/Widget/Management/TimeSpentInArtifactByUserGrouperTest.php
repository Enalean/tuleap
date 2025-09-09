<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\Widget\Management;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TimeSpentInArtifactByUserGrouperTest extends TestCase
{
    private const int ALICE_ID = 101;
    private const int BOB_ID   = 102;

    public function testGroupByUser(): void
    {
        $alice = UserTestBuilder::aUser()->withId(self::ALICE_ID)->build();
        $bob   = UserTestBuilder::aUser()->withId(self::BOB_ID)->build();

        $project1 = ProjectTestBuilder::aProject()->withId(1)->withUnixName('project-1')->build();
        $project2 = ProjectTestBuilder::aProject()->withId(2)->withUnixName('project-2')->build();

        $bugs    = TrackerTestBuilder::aTracker()->withProject($project1)->build();
        $stories = TrackerTestBuilder::aTracker()->withProject($project1)->build();
        $tasks   = TrackerTestBuilder::aTracker()->withProject($project2)->build();

        $result = new TimeSpentInArtifactByUserGrouper()->groupByUser([
            new TimeSpentInArtifact($alice, ArtifactTestBuilder::anArtifact(1)->inTracker($bugs)->build(), 1),
            new TimeSpentInArtifact($alice, ArtifactTestBuilder::anArtifact(2)->inTracker($bugs)->build(), 10),
            new TimeSpentInArtifact($bob, ArtifactTestBuilder::anArtifact(1)->inTracker($bugs)->build(), 100),
            new TimeSpentInArtifact($bob, ArtifactTestBuilder::anArtifact(3)->inTracker($stories)->build(), 1000),
            new TimeSpentInArtifact($alice, ArtifactTestBuilder::anArtifact(4)->inTracker($tasks)->build(), 10000),
        ]);

        self::assertCount(2, $result);

        $alice_times = $result[0]->times;
        self::assertSame(self::ALICE_ID, $result[0]->user->getId());
        self::assertCount(2, $alice_times);
        self::assertSame($project1, $alice_times[0]->project);
        self::assertSame(11, $alice_times[0]->minutes);
        self::assertSame($project2, $alice_times[1]->project);
        self::assertSame(10000, $alice_times[1]->minutes);

        self::assertSame(self::BOB_ID, $result[1]->user->getId());
        $bob_times = $result[1]->times;
        self::assertCount(1, $bob_times);
        self::assertSame($project1, $bob_times[0]->project);
        self::assertSame(1100, $bob_times[0]->minutes);
    }
}
