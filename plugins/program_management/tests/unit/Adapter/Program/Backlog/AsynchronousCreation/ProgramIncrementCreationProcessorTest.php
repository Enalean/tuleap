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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementCreationProcessor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementsCreator;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementCreationBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\CreateArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherFieldValuesStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\PlanUserStoriesInMirroredProgramIncrementsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveChangesetSubmissionDateStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFieldValuesGathererStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramOfProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class ProgramIncrementCreationProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_ID = 43;
    private const USER_ID              = 119;
    private const SUBMISSION_DATE      = 1395687908;
    private PlanUserStoriesInMirroredProgramIncrementsStub $user_stories_planner;
    private TestLogger $logger;
    private CreateArtifactStub $artifact_creator;
    private GatherSynchronizedFieldsStub $fields_gatherer;
    private ProgramIncrementCreation $creation;

    protected function setUp(): void
    {
        $this->artifact_creator     = CreateArtifactStub::withCount();
        $this->logger               = new TestLogger();
        $this->user_stories_planner = PlanUserStoriesInMirroredProgramIncrementsStub::withCount();

        $this->fields_gatherer = GatherSynchronizedFieldsStub::withFieldsPreparations(
            new SynchronizedFieldsStubPreparation(467, 822, 436, 762, 752, 711),
            new SynchronizedFieldsStubPreparation(604, 335, 772, 876, 790, 608),
            new SynchronizedFieldsStubPreparation(810, 887, 506, 873, 524, 866),
        );

        $this->creation = ProgramIncrementCreationBuilder::buildWithIds(
            self::USER_ID,
            self::PROGRAM_INCREMENT_ID,
            54,
            6053
        );
    }

    private function getProcessor(): ProgramIncrementCreationProcessor
    {
        return new ProgramIncrementCreationProcessor(
            RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(99, 34),
            new ProgramIncrementsCreator(
                new DBTransactionExecutorPassthrough(),
                MapStatusByValueStub::withValues(2271),
                $this->artifact_creator,
                $this->fields_gatherer
            ),
            $this->logger,
            $this->user_stories_planner,
            SearchTeamsOfProgramStub::buildTeams(102, 149),
            new BuildProjectStub(),
            $this->fields_gatherer,
            RetrieveFieldValuesGathererStub::withGatherer(
                GatherFieldValuesStub::withDefault()
            ),
            RetrieveChangesetSubmissionDateStub::withDate(self::SUBMISSION_DATE),
            RetrieveProgramOfProgramIncrementStub::withProgram(146),
            BuildProgramStub::stubValidProgram()
        );
    }

    public function testItProcessesProgramIncrementCreation(): void
    {
        $this->getProcessor()->processCreation($this->creation);

        self::assertSame(2, $this->artifact_creator->getCallCount());
        self::assertSame(1, $this->user_stories_planner->getCallCount());
        self::assertTrue(
            $this->logger->hasDebug(
                sprintf(
                    'Processing program increment creation with program increment #%d for user #%d',
                    self::PROGRAM_INCREMENT_ID,
                    self::USER_ID
                )
            )
        );
    }

    public function testItStopsExecutionIfThereIsAnIssueInTheSourceProgramIncrement(): void
    {
        $this->fields_gatherer = GatherSynchronizedFieldsStub::withError();

        $this->getProcessor()->processCreation($this->creation);

        self::assertSame(0, $this->artifact_creator->getCallCount());
        self::assertSame(0, $this->user_stories_planner->getCallCount());
        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testItStopsExecutionIfThereIsAnIssueWhileCreatingAMirroredProgramIncrement(): void
    {
        $this->artifact_creator = CreateArtifactStub::withError();

        $this->getProcessor()->processCreation($this->creation);

        self::assertSame(0, $this->user_stories_planner->getCallCount());
        self::assertTrue($this->logger->hasErrorRecords());
    }
}
