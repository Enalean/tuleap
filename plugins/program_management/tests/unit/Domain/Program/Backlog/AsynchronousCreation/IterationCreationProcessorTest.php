<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\IterationsCreator;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Tests\Builder\IterationCreationBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\CreateArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherFieldValuesStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveChangesetSubmissionDateStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFieldValuesGathererStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramOfIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class IterationCreationProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ITERATION_ID                         = 20;
    private const USER_ID                              = 191;
    private const FIRST_MIRRORED_ITERATION_TRACKER_ID  = 55;
    private const SECOND_MIRRORED_ITERATION_TRACKER_ID = 42;
    private const SUBMISSION_DATE                      = 1781713922;
    private TestLogger $logger;
    private IterationCreation $creation;
    private GatherSynchronizedFieldsStub $fields_gatherer;
    private CreateArtifactStub $artifact_creator;

    protected function setUp(): void
    {
        $this->creation = IterationCreationBuilder::buildWithIds(self::ITERATION_ID, 2, 53, self::USER_ID, 8612);

        $this->logger           = new TestLogger();
        $this->artifact_creator = CreateArtifactStub::withCount();
        $this->fields_gatherer  = GatherSynchronizedFieldsStub::withFieldsPreparations(
            new SynchronizedFieldsStubPreparation(444, 819, 242, 757, 123, 226),
            new SynchronizedFieldsStubPreparation(958, 686, 947, 627, 844, 883),
            new SynchronizedFieldsStubPreparation(799, 130, 999, 679, 847, 880),
        );
    }

    private function getProcessor(): IterationCreationProcessor
    {
        return new IterationCreationProcessor(
            MessageLog::buildFromLogger($this->logger),
            $this->fields_gatherer,
            RetrieveFieldValuesGathererStub::withGatherer(
                GatherFieldValuesStub::withDefault()
            ),
            RetrieveChangesetSubmissionDateStub::withDate(self::SUBMISSION_DATE),
            RetrieveProgramOfIterationStub::withProgram(154),
            BuildProgramStub::stubValidProgram(),
            SearchTeamsOfProgramStub::buildTeams(122, 127),
            RetrieveProjectReferenceStub::withProjects(
                ProjectReferenceStub::withId(122),
                ProjectReferenceStub::withId(127),
            ),
            new IterationsCreator(
                new DBTransactionExecutorPassthrough(),
                RetrieveMirroredIterationTrackerStub::withValidTrackers(
                    TrackerReferenceStub::withIdAndLabel(self::FIRST_MIRRORED_ITERATION_TRACKER_ID, 'Sprints'),
                    TrackerReferenceStub::withIdAndLabel(self::SECOND_MIRRORED_ITERATION_TRACKER_ID, 'Week'),
                ),
                MapStatusByValueStub::withValues(5621),
                $this->fields_gatherer,
                $this->artifact_creator
            )
        );
    }

    public function testItProcessesIterationCreation(): void
    {
        $this->getProcessor()->processCreation($this->creation);
        self::assertTrue(
            $this->logger->hasDebug(
                sprintf(
                    'Processing iteration creation with iteration #%d for user #%d',
                    self::ITERATION_ID,
                    self::USER_ID
                )
            )
        );
        self::assertSame(2, $this->artifact_creator->getCallCount());
        foreach ($this->artifact_creator->getArguments() as $changeset) {
            self::assertContains(
                $changeset->mirrored_timebox_tracker->getId(),
                [self::FIRST_MIRRORED_ITERATION_TRACKER_ID, self::SECOND_MIRRORED_ITERATION_TRACKER_ID]
            );
            self::assertNotEmpty($changeset->values->toFieldsDataArray());
            self::assertSame(self::USER_ID, $changeset->user->getId());
            self::assertSame(self::SUBMISSION_DATE, $changeset->submission_date->getValue());
        }
    }

    public function testItStopsExecutionIfThereIsAnIssueInTheSourceIteration(): void
    {
        $this->fields_gatherer = GatherSynchronizedFieldsStub::withError();

        $this->getProcessor()->processCreation($this->creation);

        self::assertSame(0, $this->artifact_creator->getCallCount());
        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testItStopsExecutionIfThereIsAnIssueWhileCreatingAMirroredIteration(): void
    {
        $this->artifact_creator = CreateArtifactStub::withError();

        $this->getProcessor()->processCreation($this->creation);

        self::assertSame(1, $this->artifact_creator->getCallCount());
        self::assertTrue($this->logger->hasErrorRecords());
    }
}
