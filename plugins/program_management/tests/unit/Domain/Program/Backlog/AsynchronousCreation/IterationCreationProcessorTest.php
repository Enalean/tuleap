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
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxArtifactLinkType;
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
    private const TITLE_VALUE                          = 'outstream';
    private const DESCRIPTION_VALUE                    = 'slump recompense';
    private const DESCRIPTION_FORMAT                   = 'commonmark';
    private const FIRST_MAPPED_STATUS_BIND_VALUE_ID    = 5621;
    private const SECOND_MAPPED_STATUS_BIND_VALUE_ID   = 9829;
    private const START_DATE_VALUE                     = '2020-12-06';
    private const END_DATE_VALUE                       = '2025-08-18';
    private const FIRST_TITLE_FIELD_ID                 = 958;
    private const FIRST_DESCRIPTION_FIELD_ID           = 686;
    private const FIRST_STATUS_FIELD_ID                = 947;
    private const FIRST_START_DATE_FIELD_ID            = 627;
    private const FIRST_END_DATE_FIELD_ID              = 844;
    private const FIRST_ARTIFACT_LINK_FIELD_ID         = 883;
    private const SECOND_TITLE_FIELD_ID                = 799;
    private const SECOND_DESCRIPTION_FIELD_ID          = 130;
    private const SECOND_STATUS_FIELD_ID               = 999;
    private const SECOND_START_DATE_FIELD_ID           = 679;
    private const SECOND_END_DATE_FIELD_ID             = 847;
    private const SECOND_ARTIFACT_LINK_FIELD_ID        = 880;
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
            new SynchronizedFieldsStubPreparation(
                self::FIRST_TITLE_FIELD_ID,
                self::FIRST_DESCRIPTION_FIELD_ID,
                self::FIRST_STATUS_FIELD_ID,
                self::FIRST_START_DATE_FIELD_ID,
                self::FIRST_END_DATE_FIELD_ID,
                self::FIRST_ARTIFACT_LINK_FIELD_ID
            ),
            new SynchronizedFieldsStubPreparation(
                self::SECOND_TITLE_FIELD_ID,
                self::SECOND_DESCRIPTION_FIELD_ID,
                self::SECOND_STATUS_FIELD_ID,
                self::SECOND_START_DATE_FIELD_ID,
                self::SECOND_END_DATE_FIELD_ID,
                self::SECOND_ARTIFACT_LINK_FIELD_ID
            ),
        );
    }

    private function getProcessor(): IterationCreationProcessor
    {
        return new IterationCreationProcessor(
            MessageLog::buildFromLogger($this->logger),
            $this->fields_gatherer,
            RetrieveFieldValuesGathererStub::withGatherer(
                GatherFieldValuesStub::withValues(
                    self::TITLE_VALUE,
                    self::DESCRIPTION_VALUE,
                    self::DESCRIPTION_FORMAT,
                    self::START_DATE_VALUE,
                    self::END_DATE_VALUE,
                    ['stretto']
                )
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
                MapStatusByValueStub::withSuccessiveBindValueIds(
                    self::FIRST_MAPPED_STATUS_BIND_VALUE_ID,
                    self::SECOND_MAPPED_STATUS_BIND_VALUE_ID
                ),
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
        [$first_changeset, $second_changeset] = $this->artifact_creator->getArguments();
        $first_values                         = $first_changeset->values;
        self::assertSame(
            self::FIRST_MIRRORED_ITERATION_TRACKER_ID,
            $first_changeset->mirrored_timebox_tracker->getId()
        );
        self::assertSame(self::FIRST_TITLE_FIELD_ID, $first_values->title_field->getId());
        self::assertSame(self::FIRST_DESCRIPTION_FIELD_ID, $first_values->description_field->getId());
        self::assertSame(self::FIRST_STATUS_FIELD_ID, $first_values->status_field->getId());
        self::assertEquals([self::FIRST_MAPPED_STATUS_BIND_VALUE_ID], $first_values->mapped_status_value->getValues());
        self::assertSame(self::FIRST_START_DATE_FIELD_ID, $first_values->start_date_field->getId());
        self::assertSame(self::FIRST_END_DATE_FIELD_ID, $first_values->end_period_field->getId());
        self::assertSame(self::FIRST_ARTIFACT_LINK_FIELD_ID, $first_values->artifact_link_field->getId());

        $second_values = $second_changeset->values;
        self::assertSame(
            self::SECOND_MIRRORED_ITERATION_TRACKER_ID,
            $second_changeset->mirrored_timebox_tracker->getId()
        );
        self::assertSame(self::SECOND_TITLE_FIELD_ID, $second_values->title_field->getId());
        self::assertSame(self::SECOND_DESCRIPTION_FIELD_ID, $second_values->description_field->getId());
        self::assertSame(self::SECOND_STATUS_FIELD_ID, $second_values->status_field->getId());
        self::assertEquals(
            [self::SECOND_MAPPED_STATUS_BIND_VALUE_ID],
            $second_values->mapped_status_value->getValues()
        );
        self::assertSame(self::SECOND_START_DATE_FIELD_ID, $second_values->start_date_field->getId());
        self::assertSame(self::SECOND_END_DATE_FIELD_ID, $second_values->end_period_field->getId());
        self::assertSame(self::SECOND_ARTIFACT_LINK_FIELD_ID, $second_values->artifact_link_field->getId());

        foreach ($this->artifact_creator->getArguments() as $changeset) {
            $values = $changeset->values;
            self::assertSame(self::TITLE_VALUE, $values->title_value->getValue());
            self::assertSame(self::DESCRIPTION_VALUE, $values->description_value->value);
            self::assertSame(self::DESCRIPTION_FORMAT, $values->description_value->format);
            self::assertSame(self::START_DATE_VALUE, $values->start_date_value->getValue());
            self::assertSame(self::END_DATE_VALUE, $values->end_period_value->getValue());
            self::assertSame(self::ITERATION_ID, $values->artifact_link_value->linked_artifact->getId());
            self::assertSame(TimeboxArtifactLinkType::ART_LINK_SHORT_NAME, (string) $values->artifact_link_value->type);
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
