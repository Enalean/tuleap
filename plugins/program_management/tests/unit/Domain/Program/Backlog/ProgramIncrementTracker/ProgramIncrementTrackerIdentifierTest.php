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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker;

use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramIncrementTrackerIdentifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_TRACKER_ID = 17;
    private TrackerIdentifierStub $tracker_identifier;

    protected function setUp(): void
    {
        $this->tracker_identifier = TrackerIdentifierStub::withId(self::PROGRAM_INCREMENT_TRACKER_ID);
    }

    public function testItReturnsNullWhenIdIsNotAProgramIncrementTracker(): void
    {
        $program_increment_tracker = ProgramIncrementTrackerIdentifier::fromId(
            VerifyIsProgramIncrementTrackerStub::buildNotProgramIncrement(),
            $this->tracker_identifier
        );
        self::assertNull($program_increment_tracker);
    }

    public function testItBuildsFromTrackerIdentifier(): void
    {
        $program_increment_tracker = ProgramIncrementTrackerIdentifier::fromId(
            VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement(),
            $this->tracker_identifier
        );
        self::assertSame(self::PROGRAM_INCREMENT_TRACKER_ID, $program_increment_tracker?->getId());
    }

    public function testItBuildsFromProgramIncrement(): void
    {
        $program_increment         = ProgramIncrementIdentifierBuilder::buildWithIdAndUser(
            41,
            UserIdentifierStub::buildGenericUser()
        );
        $program_increment_tracker = ProgramIncrementTrackerIdentifier::fromProgramIncrement(
            RetrieveProgramIncrementTrackerStub::withValidTracker(self::PROGRAM_INCREMENT_TRACKER_ID),
            $program_increment
        );
        self::assertSame(self::PROGRAM_INCREMENT_TRACKER_ID, $program_increment_tracker->getId());
    }
}
