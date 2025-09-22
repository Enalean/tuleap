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

namespace Tuleap\Timetracking\Widget\People;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Timetracking\Tests\Stub\VerifyManagerIsAllowedToSeeTimesStub;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ListOfTimeSpentInArtifactFilterTest extends TestCase
{
    public function testItResetToZeroTheMinutesIfManagerCannotSeeTimes(): void
    {
        $alice   = UserTestBuilder::aUser()->withId(101)->build();
        $bob     = UserTestBuilder::aUser()->withId(102)->build();
        $manager = UserTestBuilder::aUser()->withId(103)->build();

        $filter = new ListOfTimeSpentInArtifactFilter(
            VerifyManagerIsAllowedToSeeTimesStub::withAllowedUser($bob),
        );

        $filtered = $filter->filterForManager(
            [
                new TimeSpentInArtifact($alice, ArtifactTestBuilder::anArtifact(1)->build(), 123),
                new TimeSpentInArtifact($alice, ArtifactTestBuilder::anArtifact(2)->build(), 123),
                new TimeSpentInArtifact($bob, ArtifactTestBuilder::anArtifact(1)->build(), 123),
                new TimeSpentInArtifact($bob, ArtifactTestBuilder::anArtifact(3)->build(), 123),
                new TimeSpentInArtifact($alice, ArtifactTestBuilder::anArtifact(4)->build(), 123),
            ],
            $manager,
        );

        self::assertEquals(
            [0, 0, 123, 123, 0],
            array_map(
                static fn (TimeSpentInArtifact $time) => $time->minutes,
                $filtered,
            )
        );
    }
}
