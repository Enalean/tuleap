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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck;

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveVisibleProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\VerifyIsProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrieveMirroredProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Workspace\UserReference;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TimeboxCreatorCheckerBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\UserReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;

final class ProgramIncrementCreatorCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TimeboxCreatorChecker $timebox_creator_checker;
    private TrackerReference $tracker;
    private ProgramIdentifier $program;
    private VerifyIsProgramIncrementTracker $program_verifier;
    private RetrieveVisibleProgramIncrementTracker $program_increment_tracker_retriever;
    private RetrieveMirroredProgramIncrementTracker $root_milestone_retriever;
    private UserReference $user_identifier;
    private TeamProjectsCollection $teams;

    protected function setUp(): void
    {
        $this->timebox_creator_checker = TimeboxCreatorCheckerBuilder::buildValid();
        $this->program_verifier        = VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement();

        $this->tracker = TrackerReferenceStub::withDefaults();

        $this->program_increment_tracker_retriever = RetrieveVisibleProgramIncrementTrackerStub::withValidTracker(
            $this->tracker
        );

        $this->root_milestone_retriever = RetrieveMirroredProgramIncrementTrackerStub::withValidTrackers(
            TrackerReferenceStub::withId(90),
            TrackerReferenceStub::withId(57),
        );

        $this->user_identifier = UserReferenceStub::withDefaults();
        $this->program         = ProgramIdentifierBuilder::build();

        $this->teams = TeamProjectsCollectionBuilder::withProjects(
            ProjectReferenceStub::withId(104),
            ProjectReferenceStub::withId(176),
        );
    }

    private function getChecker(): ProgramIncrementCreatorChecker
    {
        return new ProgramIncrementCreatorChecker(
            $this->timebox_creator_checker,
            $this->program_verifier,
            $this->root_milestone_retriever,
            $this->program_increment_tracker_retriever,
            MessageLog::buildFromLogger(new TestLogger())
        );
    }

    public function testDisallowArtifactCreationWhenItIsAProgramIncrementTrackerAndOtherChecksDoNotPass(): void
    {
        $this->timebox_creator_checker = TimeboxCreatorCheckerBuilder::buildInvalid();

        self::assertFalse(
            $this->getChecker()->canCreateAProgramIncrement(
                $this->tracker,
                $this->program,
                $this->teams,
                new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true),
                $this->user_identifier
            )
        );
    }

    public function testAllowArtifactCreationWhenTrackerIsNotProgramIncrement(): void
    {
        $this->program_verifier = VerifyIsProgramIncrementTrackerStub::buildNotProgramIncrement();

        self::assertTrue(
            $this->getChecker()->canCreateAProgramIncrement(
                $this->tracker,
                $this->program,
                $this->teams,
                new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true),
                $this->user_identifier
            )
        );
    }

    public function testAllowArtifactCreationWhenOtherChecksPass(): void
    {
        self::assertTrue(
            $this->getChecker()->canCreateAProgramIncrement(
                $this->tracker,
                $this->program,
                $this->teams,
                new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true),
                $this->user_identifier
            )
        );
    }

    public function testAllowArtifactCreationWhenAProjectHasNoTeamProjects(): void
    {
        self::assertTrue(
            $this->getChecker()->canCreateAProgramIncrement(
                $this->tracker,
                $this->program,
                TeamProjectsCollectionBuilder::withEmptyTeams(),
                new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true),
                $this->user_identifier
            )
        );
    }

    public function testDisallowArtifactCreationIfProgramIncrementTrackerIsNotVisible(): void
    {
        $this->program_increment_tracker_retriever =
            RetrieveVisibleProgramIncrementTrackerStub::withNotVisibleProgramIncrementTracker();

        self::assertFalse(
            $this->getChecker()->canCreateAProgramIncrement(
                $this->tracker,
                $this->program,
                $this->teams,
                new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true),
                $this->user_identifier
            )
        );
    }

    public function testDisallowArtifactCreationIfOneProjectDoesNotHaveARootPlanningWithAMilestoneTracker(): void
    {
        $this->root_milestone_retriever = RetrieveMirroredProgramIncrementTrackerStub::withNoRootPlanning();

        self::assertFalse($this->getChecker()->canCreateAProgramIncrement(
            $this->tracker,
            $this->program,
            $this->teams,
            new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true),
            $this->user_identifier
        ));
    }
}
