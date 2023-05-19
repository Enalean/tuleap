<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TeamSynchronization\MissingProgramIncrementCreator;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\ClearPendingTeamSynchronizationStub;
use Tuleap\ProgramManagement\Tests\Stub\ProcessProgramIncrementCreationStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveLastChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchMirrorTimeboxesFromProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchOpenProgramIncrementsStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchVisibleTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\StoreTeamSynchronizationErrorHasOccurredStub;
use Tuleap\ProgramManagement\Tests\Stub\TeamSynchronizationEventStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class SynchronizeTeamProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_ID = 1;

    public function testItHandlesTeamSynchronizationEvents(): void
    {
        $logger       = new TestLogger();
        $event        = TeamSynchronizationEventStub::buildWithIds(self::PROGRAM_ID, 123, 456);
        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->method('getUserById')->willReturn(UserTestBuilder::buildWithDefaults());
        $project_manager = $this->createMock(\ProjectManager::class);
        $project_manager->method('getProject')->willReturn(new \Project(['group_id' => self::PROGRAM_ID, 'group_name' => "project", "unix_group_name" => "project", "icon_codepoint" => ""]));
        $clear_pending_synchronisation = ClearPendingTeamSynchronizationStub::withCount();
        (new SynchronizeTeamProcessor(
            MessageLog::buildFromLogger($logger),
            $project_manager,
            $user_manager,
            new MissingProgramIncrementCreator(
                SearchOpenProgramIncrementsStub::withProgramIncrements(ProgramIncrementBuilder::buildWithId(self::PROGRAM_ID)),
                SearchMirrorTimeboxesFromProgramStub::buildWithoutMissingMirror(),
                VerifyIsProgramIncrementStub::withValidProgramIncrement(),
                VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
                RetrieveProgramIncrementTrackerStub::withValidTracker(888),
                VerifyIsChangesetStub::withValidChangeset(),
                RetrieveLastChangesetStub::withLastChangesetIds(),
                ProcessProgramIncrementCreationStub::withCount(),
                SearchVisibleTeamsOfProgramStub::withTeamIds(123),
                BuildProgramStub::stubValidProgram()
            ),
            $clear_pending_synchronisation,
            BuildProgramStub::stubValidProgram(),
            SearchVisibleTeamsOfProgramStub::withTeamIds(123),
            StoreTeamSynchronizationErrorHasOccurredStub::withCount(),
        ))->processTeamSynchronization($event);

        self::assertTrue($logger->hasDebugThatContains("Team 123 of Program 1 needs PI and Iterations synchronization"));
        self::assertSame(1, $clear_pending_synchronisation->getCallCount());
    }

    public function testItStoresAndLogsErrorWhenSomethingHappened(): void
    {
        $logger       = new TestLogger();
        $event        = TeamSynchronizationEventStub::buildWithIds(self::PROGRAM_ID, 123, 456);
        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->method('getUserById')->willReturn(UserTestBuilder::buildWithDefaults());
        $project_manager = $this->createMock(\ProjectManager::class);
        $project_manager->method('getProject')->willReturn(new \Project(['group_id' => self::PROGRAM_ID, 'group_name' => "project", "unix_group_name" => "project", "icon_codepoint" => ""]));
        $clear_pending_synchronisation = ClearPendingTeamSynchronizationStub::withCount();
        $store_error_has_occurred      = StoreTeamSynchronizationErrorHasOccurredStub::withCount();
        (new SynchronizeTeamProcessor(
            MessageLog::buildFromLogger($logger),
            $project_manager,
            $user_manager,
            new MissingProgramIncrementCreator(
                SearchOpenProgramIncrementsStub::withProgramIncrements(ProgramIncrementBuilder::buildWithId(self::PROGRAM_ID)),
                SearchMirrorTimeboxesFromProgramStub::buildWithMissingMirror(),
                VerifyIsProgramIncrementStub::withValidProgramIncrement(),
                VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
                RetrieveProgramIncrementTrackerStub::withValidTracker(888),
                VerifyIsChangesetStub::withValidChangeset(),
                RetrieveLastChangesetStub::withLastChangesetIds(),
                ProcessProgramIncrementCreationStub::withCount(),
                SearchVisibleTeamsOfProgramStub::withTeamIds(123),
                BuildProgramStub::stubValidProgram()
            ),
            $clear_pending_synchronisation,
            BuildProgramStub::stubValidProgram(),
            SearchVisibleTeamsOfProgramStub::withTeamIds(123),
            $store_error_has_occurred,
        ))->processTeamSynchronization($event);

        self::assertSame(1, $store_error_has_occurred->getCallCount());
        self::assertSame(0, $clear_pending_synchronisation->getCallCount());
    }
}
