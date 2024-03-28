<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Tests\Builder;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\TrackerIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;

final class ProgramIncrementTrackerIdentifierBuilder
{
    public static function buildWithId(int $id): ProgramIncrementTrackerIdentifier
    {
        $program_increment_tracker_identifier = ProgramIncrementTrackerIdentifier::fromId(
            VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement(),
            TrackerIdentifierStub::withId($id)
        );

        if (! $program_increment_tracker_identifier) {
            throw new \LogicException('Program incrmeent tracker identifier have not been created');
        }

        return $program_increment_tracker_identifier;
    }
}
