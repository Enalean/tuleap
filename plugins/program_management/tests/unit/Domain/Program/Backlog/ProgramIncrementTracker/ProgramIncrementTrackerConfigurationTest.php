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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker;

use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementLabelsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyPrioritizeFeaturesPermissionStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserCanSubmitStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramIncrementTrackerConfigurationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_TRACKER_ID = 101;

    public function testItBuildsFromProgram(): void
    {
        $program_increment_tracker = TrackerReferenceStub::withId(self::PROGRAM_INCREMENT_TRACKER_ID);

        $configuration = ProgramIncrementTrackerConfiguration::fromProgram(
            RetrieveVisibleProgramIncrementTrackerStub::withValidTracker($program_increment_tracker),
            RetrieveProgramIncrementLabelsStub::buildLabels('Program Increments', 'program increment'),
            VerifyPrioritizeFeaturesPermissionStub::canPrioritize(),
            VerifyUserCanSubmitStub::userCanNotSubmit(),
            ProgramIdentifierBuilder::build(),
            UserIdentifierStub::buildGenericUser()
        );
        self::assertSame(self::PROGRAM_INCREMENT_TRACKER_ID, $configuration->getProgramIncrementTrackerId());
        self::assertFalse($configuration->canCreateProgramIncrement());
        self::assertSame('Program Increments', $configuration->getProgramIncrementLabel());
        self::assertSame('program increment', $configuration->getProgramIncrementSubLabel());
    }
}
