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

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\RetrieveVisibleIterationTrackerStub;
use Tuleap\ProgramManagement\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Stub\VerifyProjectPermissionStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ConfigurationCheckerTest extends TestCase
{
    private \PFUser $user;
    private ProgramForAdministrationIdentifier $program;
    private BuildProgramStub $program_builder;

    protected function setUp(): void
    {
        $this->user            = UserTestBuilder::aUser()->build();
        $this->program         = ProgramForAdministrationIdentifier::fromProject(
            VerifyIsTeamStub::withNotValidTeam(),
            VerifyProjectPermissionStub::withAdministrator(),
            $this->user,
            ProjectTestBuilder::aProject()->withId(101)->build()
        );
        $this->program_builder = BuildProgramStub::stubValidProgram();
    }

    public function testItReturnsNoErrorWhenProjectIsNotAProgram(): void
    {
        $this->program_builder = BuildProgramStub::stubInvalidProgram();
        $increment_tracker     = RetrieveVisibleProgramIncrementTrackerStub::withValidTracker(
            TrackerTestBuilder::aTracker()->build()
        );
        $iteration_tracker     = RetrieveVisibleIterationTrackerStub::withValidTracker(
            TrackerTestBuilder::aTracker()->build()
        );
        $event_manager         = $this->createMock(\EventManager::class);

        self::assertEquals(
            [],
            ConfigurationChecker::buildErrorsPresenter(
                $this->program_builder,
                $increment_tracker,
                $iteration_tracker,
                $event_manager,
                $this->program,
                $this->user
            )
        );
    }

    public function testItReturnsNoErrorWhenProgramDoesNotHaveAProgramIncrementTracker(): void
    {
        $increment_tracker = RetrieveVisibleProgramIncrementTrackerStub::withNoProgramIncrementTracker();
        $iteration_tracker = RetrieveVisibleIterationTrackerStub::withValidTracker(TrackerTestBuilder::aTracker()->build());
        $event_manager     = $this->createMock(\EventManager::class);

        self::assertEquals([], ConfigurationChecker::buildErrorsPresenter(
            $this->program_builder,
            $increment_tracker,
            $iteration_tracker,
            $event_manager,
            $this->program,
            $this->user
        ));
    }

    public function testItReturnsNoErrorWhenNoIterationTrackerIsFound(): void
    {
        $increment_tracker = RetrieveVisibleProgramIncrementTrackerStub::withValidTracker(TrackerTestBuilder::aTracker()->build());
        $iteration_tracker = RetrieveVisibleIterationTrackerStub::withNotVisibleIterationTracker();
        $event_manager     = $this->createMock(\EventManager::class);
        $event_manager->method('dispatch');

        self::assertEquals([], ConfigurationChecker::buildErrorsPresenter(
            $this->program_builder,
            $increment_tracker,
            $iteration_tracker,
            $event_manager,
            $this->program,
            $this->user
        ));
    }

    public function testItReturnsNoErrorWhenUserCanSubmitArtifact(): void
    {
        $increment_tracker = RetrieveVisibleProgramIncrementTrackerStub::withValidTracker(TrackerTestBuilder::aTracker()->build());
        $iteration_tracker = RetrieveVisibleIterationTrackerStub::withValidTracker(TrackerTestBuilder::aTracker()->build());
        $event_manager     = $this->createMock(\EventManager::class);
        $event_manager->method('dispatch');

        self::assertEquals([], ConfigurationChecker::buildErrorsPresenter(
            $this->program_builder,
            $increment_tracker,
            $iteration_tracker,
            $event_manager,
            $this->program,
            $this->user
        ));
    }
}
