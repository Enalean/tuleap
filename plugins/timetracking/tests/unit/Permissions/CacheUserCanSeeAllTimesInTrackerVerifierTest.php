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

namespace Tuleap\Timetracking\Permissions;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Timetracking\Tests\Stub\VerifyUserCanSeeAllTimesInTrackerStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CacheUserCanSeeAllTimesInTrackerVerifierTest extends TestCase
{
    public function testItCachesCallsToSaveRainforest(): void
    {
        $alice = UserTestBuilder::aUser()->withId(101)->build();
        $bob   = UserTestBuilder::aUser()->withId(102)->build();

        $story = TrackerTestBuilder::aTracker()->withId(201)->build();
        $task  = TrackerTestBuilder::aTracker()->withId(202)->build();

        $verifier = VerifyUserCanSeeAllTimesInTrackerStub::withAllowedUser($bob);

        $cache = new CacheUserCanSeeAllTimesInTrackerVerifier($verifier);

        self::assertFalse($cache->userCanSeeAllTimesInTracker($alice, $story));
        self::assertFalse($cache->userCanSeeAllTimesInTracker($alice, $story));
        self::assertTrue($cache->userCanSeeAllTimesInTracker($bob, $story));
        self::assertFalse($cache->userCanSeeAllTimesInTracker($alice, $task));
        self::assertFalse($cache->userCanSeeAllTimesInTracker($alice, $task));
        self::assertTrue($cache->userCanSeeAllTimesInTracker($bob, $story));
        self::assertFalse($cache->userCanSeeAllTimesInTracker($alice, $task));

        self::assertSame(3, $verifier->getNbCalled());
    }
}
