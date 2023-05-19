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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck;

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\UserReference;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TimeboxCreatorCheckerBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\Test\PHPUnit\TestCase;

final class IterationCreatorCheckerTest extends TestCase
{
    private ProgramIdentifier $program;
    private TestLogger $logger;
    private RetrieveMirroredIterationTrackerStub $milestone_retriever;
    private VerifyIsIterationTrackerStub $iteration_tracker_verifier;
    private TrackerReference $tracker;
    private RetrieveVisibleIterationTrackerStub $iteration_tracker_retriever;
    private TimeboxCreatorChecker $timebox_creator_checker;
    private UserReference $user_identifier;
    private TeamProjectsCollection $teams;

    protected function setUp(): void
    {
        $this->logger                     = new TestLogger();
        $this->user_identifier            = UserReferenceStub::withDefaults();
        $this->program                    = ProgramIdentifierBuilder::build();
        $this->milestone_retriever        = RetrieveMirroredIterationTrackerStub::withValidTrackers(
            TrackerReferenceStub::withId(77),
            TrackerReferenceStub::withId(45),
        );
        $this->iteration_tracker_verifier = VerifyIsIterationTrackerStub::buildValidIteration();
        $this->tracker                    = TrackerReferenceStub::withId(102);

        $this->timebox_creator_checker = TimeboxCreatorCheckerBuilder::buildValid();

        $this->iteration_tracker_retriever = RetrieveVisibleIterationTrackerStub::withValidTracker(
            $this->tracker
        );

        $this->teams = TeamProjectsCollectionBuilder::withProjects(
            ProjectReferenceStub::withId(104),
            ProjectReferenceStub::withId(146),
        );
    }

    private function getChecker(): IterationCreatorChecker
    {
        return new IterationCreatorChecker(
            $this->milestone_retriever,
            $this->iteration_tracker_verifier,
            $this->iteration_tracker_retriever,
            $this->timebox_creator_checker,
            MessageLog::buildFromLogger($this->logger)
        );
    }

    public function testAllowArtifactCreationWhenTrackerIsNotIterationTracker(): void
    {
        $this->iteration_tracker_verifier = VerifyIsIterationTrackerStub::buildNotIteration();
        self::assertTrue(
            $this->getChecker()->canCreateAnIteration(
                $this->tracker,
                $this->program,
                $this->teams,
                new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true),
                $this->user_identifier
            )
        );
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testAllowArtifactCreationWhenNoTeamLinkedToProgram(): void
    {
        self::assertTrue(
            $this->getChecker()->canCreateAnIteration(
                $this->tracker,
                $this->program,
                TeamProjectsCollectionBuilder::withEmptyTeams(),
                new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true),
                $this->user_identifier
            )
        );
    }

    public function testDisallowArtifactCreationAndLogsExceptionWhenAtLeastOneTeamHasBrokenPlanning(): void
    {
        $this->milestone_retriever = RetrieveMirroredIterationTrackerStub::withBrokenPlanning();

        self::assertFalse(
            $this->getChecker()->canCreateAnIteration(
                $this->tracker,
                $this->program,
                $this->teams,
                new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true),
                $this->user_identifier
            )
        );

        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testAllowArtifactCreationWhenUserCanNotSeeIterationTracker(): void
    {
        $this->iteration_tracker_retriever = RetrieveVisibleIterationTrackerStub::withNotVisibleIterationTracker();

        self::assertTrue(
            $this->getChecker()->canCreateAnIteration(
                $this->tracker,
                $this->program,
                $this->teams,
                new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true),
                $this->user_identifier
            )
        );
    }

    public function testAllowArtifactCreationWhenUserCanSeeTrackerAndAllChecksAreGoods(): void
    {
        self::assertTrue(
            $this->getChecker()->canCreateAnIteration(
                $this->tracker,
                $this->program,
                $this->teams,
                new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true),
                $this->user_identifier
            )
        );
    }

    public function testDisallowArtifactCreationWhenUserCanSeeTrackerButAllChecksAreNotGoods(): void
    {
        $this->timebox_creator_checker = TimeboxCreatorCheckerBuilder::buildInvalid();

        self::assertFalse(
            $this->getChecker()->canCreateAnIteration(
                $this->tracker,
                $this->program,
                $this->teams,
                new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true),
                $this->user_identifier
            )
        );
    }
}
