<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\TeamSynchronization;

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Domain\Events\TeamSynchronizationEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\SearchOpenProgramIncrements;
use Tuleap\ProgramManagement\Domain\ProjectReference;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\ProcessProgramIncrementCreationStub;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveLastChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchMirrorTimeboxesFromProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchOpenProgramIncrementsStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchVisibleTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\TeamSynchronizationEventStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\Test\PHPUnit\TestCase;

final class MissingProgramIncrementCreatorTest extends TestCase
{
    private const PROGRAM_INCREMENT_ID = 1;
    private const TEAM_ID              = 101;
    private TestLogger $logger;
    private TeamSynchronizationEvent $event;
    private UserIdentifier $user;
    private ProjectReference $team;
    private SearchOpenProgramIncrements $search_open_program_increment;

    protected function setUp(): void
    {
        $this->logger                        = new TestLogger();
        $this->event                         = TeamSynchronizationEventStub::buildWithIds(1, self::TEAM_ID, 456);
        $this->user                          = UserIdentifierStub::buildGenericUser();
        $this->team                          = ProjectReferenceStub::buildGeneric();
        $this->search_open_program_increment = SearchOpenProgramIncrementsStub::withProgramIncrements(
            ProgramIncrementBuilder::buildWithId(self::PROGRAM_INCREMENT_ID)
        );
    }

    public function testsItDoesNothingWhenNoMilestoneAreMissing(): void
    {
        $processor = ProcessProgramIncrementCreationStub::withCount();
        $this->getCreator(
            SearchMirrorTimeboxesFromProgramStub::buildWithoutMissingMirror(),
            $processor
        )->detectAndCreateMissingProgramIncrements($this->event, $this->user, $this->team, MessageLog::buildFromLogger($this->logger));

        self::assertFalse($this->logger->hasDebugRecords());
        self::assertEquals(0, $processor->getCallCount());
    }

    public function testItLogsMissingMilestones(): void
    {
        $processor = ProcessProgramIncrementCreationStub::withCount();
        $this->getCreator(
            SearchMirrorTimeboxesFromProgramStub::buildWithMissingMirror(),
            $processor
        )->detectAndCreateMissingProgramIncrements(
            $this->event,
            $this->user,
            $this->team,
            MessageLog::buildFromLogger($this->logger)
        );

        self::assertTrue($this->logger->hasDebugThatContains("Missing Program Increments #1"));
        self::assertEquals(1, $processor->getCallCount());
    }

    private function getCreator(
        SearchMirrorTimeboxesFromProgramStub $search_mirror_timeboxes,
        ProcessProgramIncrementCreationStub $increment_creation_processor,
    ): MissingProgramIncrementCreator {
        return new MissingProgramIncrementCreator(
            $this->search_open_program_increment,
            $search_mirror_timeboxes,
            VerifyIsProgramIncrementStub::withValidProgramIncrement(),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            RetrieveProgramIncrementTrackerStub::withValidTracker(888),
            VerifyIsChangesetStub::withValidChangeset(),
            RetrieveLastChangesetStub::withLastChangesetIds(111),
            $increment_creation_processor,
            SearchVisibleTeamsOfProgramStub::withTeamIds(self::TEAM_ID),
            BuildProgramStub::stubValidProgram()
        );
    }
}
