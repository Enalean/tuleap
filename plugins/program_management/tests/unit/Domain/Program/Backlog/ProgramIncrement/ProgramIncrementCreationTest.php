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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Tests\Stub\ArtifactCreatedEventStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;

final class ProgramIncrementCreationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_ID         = 195;
    private const PROGRAM_INCREMENT_TRACKER_ID = 88;
    private const USER_ID                      = 106;
    private const CHANGESET_ID                 = 6023;
    private VerifyIsProgramIncrementTrackerStub $tracker_verifier;
    private ArtifactCreatedEventStub $artifact_created;

    protected function setUp(): void
    {
        $this->tracker_verifier = VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement();

        $this->artifact_created = ArtifactCreatedEventStub::withIds(
            self::PROGRAM_INCREMENT_ID,
            TrackerIdentifierStub::withId(self::PROGRAM_INCREMENT_TRACKER_ID),
            UserIdentifierStub::withId(self::USER_ID),
            self::CHANGESET_ID
        );
    }

    public function testItBuildsFromArtifactCreatedEvent(): void
    {
        $creation = ProgramIncrementCreation::fromArtifactCreatedEvent(
            $this->tracker_verifier,
            $this->artifact_created
        );
        self::assertSame(self::PROGRAM_INCREMENT_ID, $creation->program_increment->getId());
        self::assertSame(self::PROGRAM_INCREMENT_TRACKER_ID, $creation->tracker->getId());
        self::assertSame(self::CHANGESET_ID, $creation->changeset->getId());
        self::assertSame(self::USER_ID, $creation->user->getId());
    }
}
