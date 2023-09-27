<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Admin\MoveArtifacts;

use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class MoveActionAllowedCheckerTest extends TestCase
{
    public function testItReturnsOKIfMoveIsAllowed(): void
    {
        $checker = new MoveActionAllowedChecker(
            new class extends MoveActionAllowedDAO {
                public function isMoveActionAllowedInTracker(int $tracker_id): bool
                {
                    return true;
                }
            }
        );

        $result = $checker->checkMoveActionIsAllowedInTracker(TrackerTestBuilder::aTracker()->withId(101)->build());

        self:self::assertTrue(Result::isOk($result));
    }

    public function testItReturnsErrorIfMoveIsForbidden(): void
    {
        $checker = new MoveActionAllowedChecker(
            new class extends MoveActionAllowedDAO {
                public function isMoveActionAllowedInTracker(int $tracker_id): bool
                {
                    return false;
                }
            }
        );

        $result = $checker->checkMoveActionIsAllowedInTracker(TrackerTestBuilder::aTracker()->withId(101)->build());

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(MoveActionForbiddenFault::class, $result->error);
    }
}
