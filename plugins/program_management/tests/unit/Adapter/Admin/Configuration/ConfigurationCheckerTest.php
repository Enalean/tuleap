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

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\RetrieveVisibleIterationTrackerStub;
use Tuleap\ProgramManagement\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ConfigurationCheckerTest extends TestCase
{
    public function testItReturnsNoErrorWhenProjectIsNotAProgram(): void
    {
        $program           = BuildProgramStub::stubInvalidProgram();
        $increment_tracker = RetrieveVisibleProgramIncrementTrackerStub::withValidTracker(TrackerTestBuilder::aTracker()->build());
        $iteration_tracker = RetrieveVisibleIterationTrackerStub::withValidTracker(TrackerTestBuilder::aTracker()->build());
        $event_manager     = $this->createMock(\EventManager::class);

        self::assertEquals([], ConfigurationChecker::buildErrorsPresenter(
            $program,
            $increment_tracker,
            $iteration_tracker,
            $event_manager,
            1,
            UserTestBuilder::aUser()->build()
        ));
    }

    public function testItReturnsNoErrorWhenProgramDoesNotHaveAProgramIncrementTracker(): void
    {
        $program           = BuildProgramStub::stubValidProgram();
        $increment_tracker = RetrieveVisibleProgramIncrementTrackerStub::withNoProgramIncrementTracker();
        $iteration_tracker = RetrieveVisibleIterationTrackerStub::withValidTracker(TrackerTestBuilder::aTracker()->build());
        $event_manager     = $this->createMock(\EventManager::class);

        self::assertEquals([], ConfigurationChecker::buildErrorsPresenter(
            $program,
            $increment_tracker,
            $iteration_tracker,
            $event_manager,
            1,
            UserTestBuilder::aUser()->build()
        ));
    }

    public function testItReturnsNoErrorWhenNoIterationTrackerIsFound(): void
    {
        $program           = BuildProgramStub::stubValidProgram();
        $increment_tracker = RetrieveVisibleProgramIncrementTrackerStub::withValidTracker(TrackerTestBuilder::aTracker()->build());
        $iteration_tracker = RetrieveVisibleIterationTrackerStub::withNotVisibleIterationTracker();
        $event_manager     = $this->createMock(\EventManager::class);
        $event_manager->method('dispatch');

        self::assertEquals([], ConfigurationChecker::buildErrorsPresenter(
            $program,
            $increment_tracker,
            $iteration_tracker,
            $event_manager,
            1,
            UserTestBuilder::aUser()->build()
        ));
    }

    public function testItReturnsNoErrorWhenUserCanSubmitArtifact(): void
    {
        $program           = BuildProgramStub::stubValidProgram();
        $increment_tracker = RetrieveVisibleProgramIncrementTrackerStub::withValidTracker(TrackerTestBuilder::aTracker()->build());
        $iteration_tracker = RetrieveVisibleIterationTrackerStub::withValidTracker(TrackerTestBuilder::aTracker()->build());
        $event_manager     = $this->createMock(\EventManager::class);
        $event_manager->method('dispatch');

        self::assertEquals([], ConfigurationChecker::buildErrorsPresenter(
            $program,
            $increment_tracker,
            $iteration_tracker,
            $event_manager,
            1,
            UserTestBuilder::aUser()->build()
        ));
    }
}
