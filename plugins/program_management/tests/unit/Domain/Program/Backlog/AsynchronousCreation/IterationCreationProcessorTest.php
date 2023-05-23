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

use Psr\Log\NullLogger;
use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\IterationsCreator;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxArtifactLinkType;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\ArtifactLinkChangeset;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifierCollection;
use Tuleap\ProgramManagement\Tests\Builder\IterationCreationBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TeamIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\AddArtifactLinkChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\CreateArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherFieldValuesStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveChangesetSubmissionDateStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFieldValuesGathererStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredProgramIncrementFromTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramOfIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerOfArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchVisibleTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class IterationCreationProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_TEAM_ID                                            = 122;
    private const SECOND_TEAM_ID                                           = 127;
    private const ITERATION_ID                                             = 20;
    private const FIRST_MIRRORED_ITERATION_ID                              = 21;
    private const SECOND_MIRRORED_ITERATION_ID                             = 22;
    private const USER_ID                                                  = 191;
    private const FIRST_MIRRORED_ITERATION_TRACKER_ID                      = 55;
    private const SECOND_MIRRORED_ITERATION_TRACKER_ID                     = 42;
    private const SUBMISSION_DATE                                          = 1781713922;
    private const TITLE_VALUE                                              = 'outstream';
    private const DESCRIPTION_VALUE                                        = 'slump recompense';
    private const DESCRIPTION_FORMAT                                       = 'commonmark';
    private const FIRST_MAPPED_STATUS_BIND_VALUE_ID                        = 5621;
    private const SECOND_MAPPED_STATUS_BIND_VALUE_ID                       = 9829;
    private const START_DATE_VALUE                                         = 1607258528;
    private const END_DATE_VALUE                                           = 1755478239;
    private const FIRST_TITLE_FIELD_ID                                     = 958;
    private const FIRST_DESCRIPTION_FIELD_ID                               = 686;
    private const FIRST_STATUS_FIELD_ID                                    = 947;
    private const FIRST_START_DATE_FIELD_ID                                = 627;
    private const FIRST_END_DATE_FIELD_ID                                  = 844;
    private const FIRST_ARTIFACT_LINK_FIELD_ID                             = 883;
    private const SECOND_TITLE_FIELD_ID                                    = 799;
    private const SECOND_DESCRIPTION_FIELD_ID                              = 130;
    private const SECOND_STATUS_FIELD_ID                                   = 999;
    private const SECOND_START_DATE_FIELD_ID                               = 679;
    private const SECOND_END_DATE_FIELD_ID                                 = 847;
    private const SECOND_ARTIFACT_LINK_FIELD_ID                            = 880;
    private const FIRST_MIRRORED_PROGRAM_INCREMENT_ID                      = 17;
    private const FIRST_MIRRORED_PROGRAM_INCREMENT_ARTIFACT_LINK_FIELD_ID  = 120;
    private const SECOND_MIRRORED_PROGRAM_INCREMENT_ID                     = 40;
    private const SECOND_MIRRORED_PROGRAM_INCREMENT_ARTIFACT_LINK_FIELD_ID = 768;

    private TestLogger $logger;
    private IterationCreation $creation;
    private GatherSynchronizedFieldsStub $fields_gatherer;
    private CreateArtifactStub $artifact_creator;
    private AddArtifactLinkChangesetStub $link_adder;
    private SearchVisibleTeamsOfProgramStub $teams_searcher;

    protected function setUp(): void
    {
        $this->creation = IterationCreationBuilder::buildWithIds(self::ITERATION_ID, 2, 53, self::USER_ID, 8612);

        $this->logger           = new TestLogger();
        $this->artifact_creator = CreateArtifactStub::withIds(
            self::FIRST_MIRRORED_ITERATION_ID,
            self::SECOND_MIRRORED_ITERATION_ID
        );
        $this->link_adder       = AddArtifactLinkChangesetStub::withCount();
        $this->fields_gatherer  = GatherSynchronizedFieldsStub::withFieldsPreparations(
            SynchronizedFieldsStubPreparation::withAllFields(444, 819, 242, 757, 123, 226),
            SynchronizedFieldsStubPreparation::withAllFields(
                self::FIRST_TITLE_FIELD_ID,
                self::FIRST_DESCRIPTION_FIELD_ID,
                self::FIRST_STATUS_FIELD_ID,
                self::FIRST_START_DATE_FIELD_ID,
                self::FIRST_END_DATE_FIELD_ID,
                self::FIRST_ARTIFACT_LINK_FIELD_ID
            ),
            SynchronizedFieldsStubPreparation::withOnlyArtifactLinkField(
                self::FIRST_MIRRORED_PROGRAM_INCREMENT_ARTIFACT_LINK_FIELD_ID
            ),
            SynchronizedFieldsStubPreparation::withAllFields(
                self::SECOND_TITLE_FIELD_ID,
                self::SECOND_DESCRIPTION_FIELD_ID,
                self::SECOND_STATUS_FIELD_ID,
                self::SECOND_START_DATE_FIELD_ID,
                self::SECOND_END_DATE_FIELD_ID,
                self::SECOND_ARTIFACT_LINK_FIELD_ID
            ),
            SynchronizedFieldsStubPreparation::withOnlyArtifactLinkField(
                self::SECOND_MIRRORED_PROGRAM_INCREMENT_ARTIFACT_LINK_FIELD_ID
            ),
        );

        $this->teams_searcher = SearchVisibleTeamsOfProgramStub::withTeamIds(self::FIRST_TEAM_ID, self::SECOND_TEAM_ID);
    }

    private function getProcessor(): IterationCreationProcessor
    {
        $logger = MessageLog::buildFromLogger($this->logger);
        return new IterationCreationProcessor(
            $logger,
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
            $this->teams_searcher,
            new IterationsCreator(
                new NullLogger(),
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
                $this->artifact_creator,
                RetrieveMirroredProgramIncrementFromTeamStub::withIds(
                    self::FIRST_MIRRORED_PROGRAM_INCREMENT_ID,
                    self::SECOND_MIRRORED_PROGRAM_INCREMENT_ID
                ),
                VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
                RetrieveTrackerOfArtifactStub::withIds(84, 97),
                $this->link_adder,
                RetrieveProjectReferenceStub::withProjects(
                    ProjectReferenceStub::withId(self::FIRST_TEAM_ID),
                    ProjectReferenceStub::withId(self::SECOND_TEAM_ID),
                )
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
        $this->checkFirstMirroredIteration($first_changeset);

        // Check second Mirrored Iteration
        $this->checkSecondMirroredIteration($second_changeset);

        // Check both Mirrored Iterations have copied the source field values
        $this->checkMirroredIterationsHaveCopiedTheSourceFieldValues($first_changeset);
        $this->checkMirroredIterationsHaveCopiedTheSourceFieldValues($second_changeset);

        self::assertSame(2, $this->link_adder->getCallCount());
        [$first_link, $second_link] = $this->link_adder->getArguments();

        // Check link from first Mirrored Program Increment --> first Mirrored Iteration
        $this->checkLinkFromFirstMirroredProgramIncrementToFirstMirroredIteration($first_link);

        // Check link from second Mirrored Program Increment --> second Mirrored Iteration
        $this->checkLinkFromSecondMirroredProgramIncrementToSecondMirroredIteration($second_link);
    }

    public function testItProcessesIterationCreationForOnlyOneTeam(): void
    {
        $teams = TeamIdentifierCollection::fromSingleTeam(TeamIdentifierBuilder::buildWithId(self::FIRST_TEAM_ID));
        $this->getProcessor()->processCreationForTeams($this->creation, $teams);
        self::assertTrue(
            $this->logger->hasDebug(
                sprintf(
                    'Processing iteration creation with iteration #%d for user #%d and for teams: %s',
                    self::ITERATION_ID,
                    self::USER_ID,
                    self::FIRST_TEAM_ID,
                )
            )
        );
        self::assertSame(1, $this->artifact_creator->getCallCount());

        [$first_changeset] = $this->artifact_creator->getArguments();
        $this->checkFirstMirroredIteration($first_changeset);

        $this->checkMirroredIterationsHaveCopiedTheSourceFieldValues($first_changeset);

        self::assertSame(1, $this->link_adder->getCallCount());
        [$first_link] = $this->link_adder->getArguments();
        $this->checkLinkFromFirstMirroredProgramIncrementToFirstMirroredIteration($first_link);
    }

    private function checkFirstMirroredIteration(MirroredTimeboxFirstChangeset $changeset): void
    {
        $first_values = $changeset->values;
        self::assertSame(
            self::FIRST_MIRRORED_ITERATION_TRACKER_ID,
            $changeset->mirrored_timebox_tracker->getId()
        );
        self::assertSame(self::FIRST_TITLE_FIELD_ID, $first_values->title_field->getId());
        self::assertSame(self::FIRST_DESCRIPTION_FIELD_ID, $first_values->description_field->getId());
        self::assertSame(self::FIRST_STATUS_FIELD_ID, $first_values->status_field->getId());
        self::assertEquals([self::FIRST_MAPPED_STATUS_BIND_VALUE_ID], $first_values->mapped_status_value->getValues());
        self::assertSame(self::FIRST_START_DATE_FIELD_ID, $first_values->start_date_field->getId());
        self::assertSame(self::FIRST_END_DATE_FIELD_ID, $first_values->end_period_field->getId());
        self::assertSame(self::FIRST_ARTIFACT_LINK_FIELD_ID, $first_values->artifact_link_field->getId());
    }

    private function checkSecondMirroredIteration(MirroredTimeboxFirstChangeset $changeset): void
    {
        $second_values = $changeset->values;
        self::assertSame(
            self::SECOND_MIRRORED_ITERATION_TRACKER_ID,
            $changeset->mirrored_timebox_tracker->getId()
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
    }

    private function checkMirroredIterationsHaveCopiedTheSourceFieldValues(MirroredTimeboxFirstChangeset $changeset): void
    {
        $values = $changeset->values;
        self::assertSame(self::TITLE_VALUE, $values->title_value->getValue());
        self::assertSame(self::DESCRIPTION_VALUE, $values->description_value->value);
        self::assertSame(self::DESCRIPTION_FORMAT, $values->description_value->format);
        self::assertSame(self::START_DATE_VALUE, $values->start_date_value->getValue());
        self::assertSame(self::END_DATE_VALUE, $values->end_period_value->getValue());
        self::assertSame(self::ITERATION_ID, $values->artifact_link_value?->linked_artifact->getId());
        self::assertSame(TimeboxArtifactLinkType::ART_LINK_SHORT_NAME, (string) $values->artifact_link_value?->type);
        self::assertSame(self::USER_ID, $changeset->user->getId());
        self::assertSame(self::SUBMISSION_DATE, $changeset->submission_date->getValue());
    }

    private function checkLinkFromFirstMirroredProgramIncrementToFirstMirroredIteration(ArtifactLinkChangeset $link): void
    {
        self::assertSame(self::FIRST_MIRRORED_PROGRAM_INCREMENT_ID, $link->mirrored_program_increment->getId());
        self::assertSame(
            self::FIRST_MIRRORED_PROGRAM_INCREMENT_ARTIFACT_LINK_FIELD_ID,
            $link->artifact_link_field->getId()
        );
        self::assertSame(self::FIRST_MIRRORED_ITERATION_ID, $link->artifact_link_value->linked_artifact->getId());
        self::assertSame(
            \Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD,
            (string) $link->artifact_link_value->type
        );
        self::assertSame(self::USER_ID, $link->user->getId());
    }

    private function checkLinkFromSecondMirroredProgramIncrementToSecondMirroredIteration(ArtifactLinkChangeset $link): void
    {
        self::assertSame(self::SECOND_MIRRORED_PROGRAM_INCREMENT_ID, $link->mirrored_program_increment->getId());
        self::assertSame(
            self::SECOND_MIRRORED_PROGRAM_INCREMENT_ARTIFACT_LINK_FIELD_ID,
            $link->artifact_link_field->getId()
        );
        self::assertSame(
            self::SECOND_MIRRORED_ITERATION_ID,
            $link->artifact_link_value->linked_artifact->getId()
        );
        self::assertSame(
            \Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD,
            (string) $link->artifact_link_value->type
        );
        self::assertSame(self::USER_ID, $link->user->getId());
    }

    public function testItStopsExecutionIfProgramHasNoTeams(): void
    {
        $this->teams_searcher = SearchVisibleTeamsOfProgramStub::withNoTeam();

        $this->getProcessor()->processCreation($this->creation);

        self::assertSame(0, $this->artifact_creator->getCallCount());
        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testItStopsExecutionIfUserCannotSeeOneTeam(): void
    {
        $this->teams_searcher = SearchVisibleTeamsOfProgramStub::withNotVisibleTeam();

        $this->getProcessor()->processCreation($this->creation);

        self::assertSame(0, $this->artifact_creator->getCallCount());
        self::assertTrue($this->logger->hasErrorRecords());
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

    public function testItStopsExecutionIfThereIsAnIssueWhileLinkingMirroredProgramIncrementToMirroredIteration(): void
    {
        $this->link_adder = AddArtifactLinkChangesetStub::withError();

        $this->getProcessor()->processCreation($this->creation);

        self::assertSame(1, $this->artifact_creator->getCallCount());
        self::assertSame(1, $this->link_adder->getCallCount());
        self::assertTrue($this->logger->hasErrorRecords());
    }
}
