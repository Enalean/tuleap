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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrementTracker;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerConfiguration;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\RetrieveProgramIncrementLabelsStub;
use Tuleap\ProgramManagement\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramIncrementTrackerConfigurationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsAProgramIncrementTrackerConfiguration(): void
    {
        $program_increment_tracker = TrackerTestBuilder::aTracker()->withId(101)->build();

        $user    = UserTestBuilder::aUser()->build();
        $program = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 101, $user);

        $configuration = ProgramIncrementTrackerConfiguration::fromProgram(
            RetrieveVisibleProgramIncrementTrackerStub::withValidTracker($program_increment_tracker),
            RetrieveProgramIncrementLabelsStub::buildLabels('Program Increments', 'program increment'),
            $program,
            $user
        );
        self::assertSame(101, $configuration->getProgramIncrementTrackerId());
        self::assertFalse($configuration->canCreateProgramIncrement());
        self::assertSame('Program Increments', $configuration->getProgramIncrementLabel());
        self::assertSame('program increment', $configuration->getProgramIncrementSubLabel());
    }
}
