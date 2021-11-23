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

use Psr\Log\NullLogger;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\ProgramIncrementCreatorChecker;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;

final class ProgramIncrementCreatorCheckerBuilder
{
    public static function build(): ProgramIncrementCreatorChecker
    {
        $tracker_reference = TrackerReferenceStub::withDefaults();
        return new ProgramIncrementCreatorChecker(
            TimeboxCreatorCheckerBuilder::buildValid(),
            VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement(),
            RetrieveMirroredProgramIncrementTrackerStub::withValidTrackers($tracker_reference),
            RetrieveVisibleProgramIncrementTrackerStub::withValidTracker(TrackerReferenceStub::withId(2)),
            MessageLog::buildFromLogger(new NullLogger())
        );
    }
}
