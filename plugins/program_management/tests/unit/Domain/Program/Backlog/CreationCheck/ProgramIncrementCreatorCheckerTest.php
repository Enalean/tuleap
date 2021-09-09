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

use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveVisibleProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\VerifyIsProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrievePlanningMilestoneTracker;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramTrackerBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramIncrementCreatorCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TimeboxCreatorChecker
     */
    private $timebox_creator_checker;
    private ProgramTracker $tracker;
    private \PFUser $user;
    private ProgramIdentifier $program;
    private VerifyIsProgramIncrementTracker $program_verifier;
    private RetrieveVisibleProgramIncrementTracker $program_increment_tracker_retriever;
    private RetrievePlanningMilestoneTracker $root_milestone_retriever;
    private UserIdentifierStub $user_identifier;

    protected function setUp(): void
    {
        $this->timebox_creator_checker = $this->createMock(TimeboxCreatorChecker::class);
        $this->program_verifier        = VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement();

        $program_increment_tracker = TrackerTestBuilder::aTracker()->withId(102)->build();
        $this->tracker             = new ProgramTracker($program_increment_tracker);

        $this->program_increment_tracker_retriever = RetrieveVisibleProgramIncrementTrackerStub::withValidTracker(
            $program_increment_tracker
        );

        $this->root_milestone_retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackers(
            ProgramTrackerBuilder::buildWithId(103)
        );

        $this->user            = UserTestBuilder::aUser()->build();
        $this->user_identifier = UserIdentifierStub::buildGenericUser();
        $this->program         = ProgramIdentifierBuilder::build();
    }

    public function testDisallowArtifactCreationWhenItIsAProgramIncrementTrackerAndOtherChecksDoNotPass(): void
    {
        $this->timebox_creator_checker->method('canTimeboxBeCreated')->willReturn(false);

        self::assertFalse(
            $this->getChecker()->canCreateAProgramIncrement(
                $this->user,
                $this->tracker,
                $this->program,
                TeamProjectsCollection::fromProgramIdentifier(
                    SearchTeamsOfProgramStub::buildTeams(104),
                    new BuildProjectStub(),
                    $this->program
                ),
                new ConfigurationErrorsCollector(true),
                $this->user_identifier
            )
        );
    }

    public function testAllowArtifactCreationWhenTrackerIsNotProgramIncrement(): void
    {
        $this->timebox_creator_checker->expects(self::never())->method('canTimeboxBeCreated');
        $this->program_verifier = VerifyIsProgramIncrementTrackerStub::buildNotProgramIncrement();

        self::assertTrue(
            $this->getChecker()->canCreateAProgramIncrement(
                $this->user,
                $this->tracker,
                $this->program,
                TeamProjectsCollection::fromProgramIdentifier(
                    SearchTeamsOfProgramStub::buildTeams(),
                    new BuildProjectStub(),
                    $this->program
                ),
                new ConfigurationErrorsCollector(true),
                $this->user_identifier
            )
        );
    }

    public function testAllowArtifactCreationWhenOtherChecksPass(): void
    {
        $this->timebox_creator_checker->method('canTimeboxBeCreated')->willReturn(true);

        self::assertTrue(
            $this->getChecker()->canCreateAProgramIncrement(
                $this->user,
                $this->tracker,
                $this->program,
                TeamProjectsCollection::fromProgramIdentifier(
                    SearchTeamsOfProgramStub::buildTeams(104),
                    new BuildProjectStub(),
                    $this->program
                ),
                new ConfigurationErrorsCollector(true),
                $this->user_identifier
            )
        );
    }

    public function testAllowArtifactCreationWhenAProjectHasNoTeamProjects(): void
    {
        $this->timebox_creator_checker->expects(self::never())->method('canTimeboxBeCreated');

        self::assertTrue(
            $this->getChecker()->canCreateAProgramIncrement(
                $this->user,
                $this->tracker,
                $this->program,
                TeamProjectsCollection::fromProgramIdentifier(
                    SearchTeamsOfProgramStub::buildTeams(),
                    new BuildProjectStub(),
                    $this->program
                ),
                new ConfigurationErrorsCollector(true),
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
                $this->user,
                $this->tracker,
                $this->program,
                TeamProjectsCollection::fromProgramIdentifier(
                    SearchTeamsOfProgramStub::buildTeams(104),
                    new BuildProjectStub(),
                    $this->program
                ),
                new ConfigurationErrorsCollector(true),
                $this->user_identifier
            )
        );
    }

    public function testDisallowArtifactCreationIfOneProjectDoesNotHaveARootPlanningWithAMilestoneTracker(): void
    {
        $this->root_milestone_retriever = RetrievePlanningMilestoneTrackerStub::withNoPlanning();

        self::assertFalse($this->getChecker()->canCreateAProgramIncrement(
            $this->user,
            $this->tracker,
            $this->program,
            TeamProjectsCollection::fromProgramIdentifier(
                SearchTeamsOfProgramStub::buildTeams(104),
                new BuildProjectStub(),
                $this->program
            ),
            new ConfigurationErrorsCollector(true),
            $this->user_identifier
        ));
    }

    private function getChecker(): ProgramIncrementCreatorChecker
    {
        return new ProgramIncrementCreatorChecker(
            $this->timebox_creator_checker,
            $this->program_verifier,
            $this->root_milestone_retriever,
            $this->program_increment_tracker_retriever,
            new TestLogger()
        );
    }
}
