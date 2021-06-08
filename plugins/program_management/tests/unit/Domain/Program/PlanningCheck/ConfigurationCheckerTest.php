<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Plan;

use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ConfigurationCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItChecksIfAProgramIncrementTrackerCanBeBuilt(): void
    {
        $planning_adapter = $this->createStub(BuildPlanProgramConfiguration::class);
        $tracker          = TrackerTestBuilder::aTracker()->withId(78)->build();
        $checker          = new ConfigurationChecker(
            $planning_adapter,
            RetrieveVisibleProgramIncrementTrackerStub::withValidTracker($tracker)
        );
        $user             = UserTestBuilder::aUser()->build();
        $program          = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 101, $user);
        $planning_adapter->method('buildProgramIdentifierFromTeamProject')->willReturn($program);

        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $checker->checkProgramIncrementTracker($user, $project);
        $this->addToAssertionCount(1);
    }

    public function testItReturnsVoidWhenThereIsNOProgram(): void
    {
        $planning_adapter = $this->createStub(BuildPlanProgramConfiguration::class);
        $checker          = new ConfigurationChecker(
            $planning_adapter,
            RetrieveVisibleProgramIncrementTrackerStub::withNoProgramIncrementTracker()
        );

        $planning_adapter->method('buildProgramIdentifierFromTeamProject')->willReturn(null);

        $user    = UserTestBuilder::aUser()->build();
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $checker->checkProgramIncrementTracker($user, $project);
        $this->addToAssertionCount(1);
    }
}
