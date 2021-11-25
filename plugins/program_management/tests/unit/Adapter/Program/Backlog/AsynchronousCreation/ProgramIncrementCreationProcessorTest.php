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
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementCreationProcessor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxArtifactLinkType;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementCreationBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\CreateArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherFieldValuesStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\PlanUserStoriesInMirroredProgramIncrementsStub;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveChangesetSubmissionDateStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFieldValuesGathererStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramOfProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchVisibleTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class ProgramIncrementCreationProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_TEAM_ID                                = 102;
    private const SECOND_TEAM_ID                               = 149;
    private const PROGRAM_INCREMENT_ID                         = 43;
    private const USER_ID                                      = 119;
    private const FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID  = 99;
    private const SECOND_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID = 34;
    private const SUBMISSION_DATE                              = 1395687908;
    private const TITLE_VALUE                                  = 'outstream';
    private const DESCRIPTION_VALUE                            = 'slump recompense';
    private const DESCRIPTION_FORMAT                           = 'commonmark';
    private const FIRST_MAPPED_STATUS_BIND_VALUE_ID            = 2271;
    private const SECOND_MAPPED_STATUS_BIND_VALUE_ID           = 6281;
    private const START_DATE_VALUE                             = 1607291762;
    private const END_DATE_VALUE                               = 1755522942;
    private const FIRST_TITLE_FIELD_ID                         = 604;
    private const FIRST_DESCRIPTION_FIELD_ID                   = 335;
    private const FIRST_STATUS_FIELD_ID                        = 772;
    private const FIRST_START_DATE_FIELD_ID                    = 876;
    private const FIRST_END_DATE_FIELD_ID                      = 790;
    private const FIRST_ARTIFACT_LINK_FIELD_ID                 = 608;
    private const SECOND_TITLE_FIELD_ID                        = 810;
    private const SECOND_DESCRIPTION_FIELD_ID                  = 887;
    private const SECOND_STATUS_FIELD_ID                       = 506;
    private const SECOND_START_DATE_FIELD_ID                   = 873;
    private const SECOND_END_DATE_FIELD_ID                     = 524;
    private const SECOND_ARTIFACT_LINK_FIELD_ID                = 866;
    private PlanUserStoriesInMirroredProgramIncrementsStub $user_stories_planner;
    private TestLogger $logger;
    private CreateArtifactStub $artifact_creator;
    private GatherSynchronizedFieldsStub $fields_gatherer;
    private ProgramIncrementCreation $creation;

    protected function setUp(): void
    {
        $this->artifact_creator     = CreateArtifactStub::withIds(44, 45);
        $this->logger               = new TestLogger();
        $this->user_stories_planner = PlanUserStoriesInMirroredProgramIncrementsStub::withCount();

        $this->fields_gatherer = GatherSynchronizedFieldsStub::withFieldsPreparations(
            SynchronizedFieldsStubPreparation::withAllFields(467, 822, 436, 762, 752, 711),
            SynchronizedFieldsStubPreparation::withAllFields(
                self::FIRST_TITLE_FIELD_ID,
                self::FIRST_DESCRIPTION_FIELD_ID,
                self::FIRST_STATUS_FIELD_ID,
                self::FIRST_START_DATE_FIELD_ID,
                self::FIRST_END_DATE_FIELD_ID,
                self::FIRST_ARTIFACT_LINK_FIELD_ID
            ),
            SynchronizedFieldsStubPreparation::withAllFields(
                self::SECOND_TITLE_FIELD_ID,
                self::SECOND_DESCRIPTION_FIELD_ID,
                self::SECOND_STATUS_FIELD_ID,
                self::SECOND_START_DATE_FIELD_ID,
                self::SECOND_END_DATE_FIELD_ID,
                self::SECOND_ARTIFACT_LINK_FIELD_ID
            ),
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
            RetrieveMirroredProgramIncrementTrackerStub::withValidTrackers(
                TrackerReferenceStub::withId(self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID),
                TrackerReferenceStub::withId(self::SECOND_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID)
            ),
            new ProgramIncrementsCreator(
                new DBTransactionExecutorPassthrough(),
                MapStatusByValueStub::withSuccessiveBindValueIds(
                    self::FIRST_MAPPED_STATUS_BIND_VALUE_ID,
                    self::SECOND_MAPPED_STATUS_BIND_VALUE_ID
                ),
                $this->artifact_creator,
                $this->fields_gatherer,
            ),
            MessageLog::buildFromLogger($this->logger),
            $this->user_stories_planner,
            SearchVisibleTeamsOfProgramStub::withTeamIds(self::FIRST_TEAM_ID, self::SECOND_TEAM_ID),
            RetrieveProjectReferenceStub::withProjects(
                ProjectReferenceStub::withId(self::FIRST_TEAM_ID),
                ProjectReferenceStub::withId(self::SECOND_TEAM_ID),
            ),
            $this->fields_gatherer,
            RetrieveFieldValuesGathererStub::withGatherer(
                GatherFieldValuesStub::withValues(
                    self::TITLE_VALUE,
                    self::DESCRIPTION_VALUE,
                    self::DESCRIPTION_FORMAT,
                    self::START_DATE_VALUE,
                    self::END_DATE_VALUE,
                    ['improvisational']
                )
            ),
            RetrieveChangesetSubmissionDateStub::withDate(self::SUBMISSION_DATE),
            RetrieveProgramOfProgramIncrementStub::withProgram(146),
            BuildProgramStub::stubValidProgram(),
        );
    }

    public function testItProcessesProgramIncrementCreation(): void
    {
        $this->getProcessor()->processCreation($this->creation);

        self::assertTrue(
            $this->logger->hasDebug(
                sprintf(
                    'Processing program increment creation with program increment #%d for user #%d',
                    self::PROGRAM_INCREMENT_ID,
                    self::USER_ID
                )
            )
        );
        self::assertSame(1, $this->user_stories_planner->getCallCount());
        self::assertSame(2, $this->artifact_creator->getCallCount());
        [$first_changeset, $second_changeset] = $this->artifact_creator->getArguments();
        $first_values                         = $first_changeset->values;
        self::assertSame(
            self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID,
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
            self::SECOND_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID,
            $second_changeset->mirrored_timebox_tracker->getId()
        );
        self::assertSame(self::SECOND_TITLE_FIELD_ID, $second_values->title_field->getId());
        self::assertSame(self::SECOND_DESCRIPTION_FIELD_ID, $second_values->description_field->getId());
        self::assertSame(self::SECOND_STATUS_FIELD_ID, $second_values->status_field->getId());
        self::assertEquals([self::SECOND_MAPPED_STATUS_BIND_VALUE_ID], $second_values->mapped_status_value->getValues());
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
            self::assertSame(self::PROGRAM_INCREMENT_ID, $values->artifact_link_value?->linked_artifact?->getId());
            self::assertSame(TimeboxArtifactLinkType::ART_LINK_SHORT_NAME, (string) $values->artifact_link_value?->type);
            self::assertSame(self::USER_ID, $changeset->user->getId());
            self::assertSame(self::SUBMISSION_DATE, $changeset->submission_date->getValue());
        }
    }

    public function testItStopsExecutionIfThereIsAnIssueInTheSourceProgramIncrement(): void
    {
        $this->fields_gatherer = GatherSynchronizedFieldsStub::withError();

        $this->getProcessor()->processCreation($this->creation);

        self::assertSame(0, $this->artifact_creator->getCallCount());
        self::assertSame(0, $this->user_stories_planner->getCallCount());
        self::assertTrue($this->logger->hasError('Error during creation of mirror program increments'));
    }

    public function testItStopsExecutionIfThereIsAnIssueWhileCreatingAMirroredProgramIncrement(): void
    {
        $this->artifact_creator = CreateArtifactStub::withError();

        $this->getProcessor()->processCreation($this->creation);

        self::assertSame(1, $this->artifact_creator->getCallCount());
        self::assertSame(0, $this->user_stories_planner->getCallCount());
        self::assertTrue($this->logger->hasError('Error during creation of mirror program increments'));
    }

    public function testItLogsErrorWhilePlanningUserStoriesInMirrors(): void
    {
        $this->user_stories_planner = PlanUserStoriesInMirroredProgramIncrementsStub::withError();

        $this->getProcessor()->processCreation($this->creation);

        self::assertTrue($this->logger->hasError('Error during planning of user stories in mirror program increments'));
    }
}
