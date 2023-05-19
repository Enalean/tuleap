<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationUpdate;
use Tuleap\ProgramManagement\Tests\Builder\IterationUpdateBuilder;
use Tuleap\ProgramManagement\Tests\Stub\AddChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherFieldValuesStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveChangesetSubmissionDateStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFieldValuesGathererStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerOfArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchMirroredTimeboxesStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;

final class IterationUpdateProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ITERATION_ID                       = 63;
    private const USER_ID                            = 122;
    private const ITERATION_TRACKER_ID               = 74;
    private const FIRST_MIRRORED_ID                  = 137;
    private const SECOND_MIRRORED_ID                 = 194;
    private const SUBMISSION_DATE                    = 1408642745;
    private const TITLE_VALUE                        = 'nocuously';
    private const DESCRIPTION_VALUE                  = 'unbowsome';
    private const DESCRIPTION_FORMAT                 = 'text';
    private const FIRST_MAPPED_STATUS_BIND_VALUE_ID  = 8889;
    private const SECOND_MAPPED_STATUS_BIND_VALUE_ID = 9771;
    private const START_DATE_VALUE                   = 1442752621;
    private const END_DATE_VALUE                     = 1465344427;
    private const FIRST_TITLE_FIELD_ID               = 205;
    private const FIRST_DESCRIPTION_FIELD_ID         = 430;
    private const FIRST_STATUS_FIELD_ID              = 472;
    private const FIRST_START_DATE_FIELD_ID          = 104;
    private const FIRST_END_DATE_FIELD_ID            = 844;
    private const FIRST_ARTIFACT_LINK_FIELD_ID       = 393;
    private const SECOND_TITLE_FIELD_ID              = 751;
    private const SECOND_DESCRIPTION_FIELD_ID        = 586;
    private const SECOND_STATUS_FIELD_ID             = 537;
    private const SECOND_START_DATE_FIELD_ID         = 629;
    private const SECOND_END_DATE_FIELD_ID           = 104;
    private const SECOND_ARTIFACT_LINK_FIELD_ID      = 762;
    private TestLogger $logger;
    private GatherSynchronizedFieldsStub $fields_gatherer;
    private SearchMirroredTimeboxesStub $mirror_searcher;
    private AddChangesetStub $changeset_adder;
    private IterationUpdate $update;

    protected function setUp(): void
    {
        $this->update = IterationUpdateBuilder::buildWithIds(
            self::USER_ID,
            self::ITERATION_ID,
            self::ITERATION_TRACKER_ID,
            3882
        );

        $this->logger          = new TestLogger();
        $this->fields_gatherer = GatherSynchronizedFieldsStub::withFieldsPreparations(
            SynchronizedFieldsStubPreparation::withAllFields(199, 885, 826, 351, 986, 536),
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

        $this->mirror_searcher = SearchMirroredTimeboxesStub::withIds(
            self::FIRST_MIRRORED_ID,
            self::SECOND_MIRRORED_ID
        );
        $this->changeset_adder = AddChangesetStub::withCount();
    }

    private function getProcessor(): IterationUpdateProcessor
    {
        return new IterationUpdateProcessor(
            MessageLog::buildFromLogger($this->logger),
            $this->fields_gatherer,
            RetrieveFieldValuesGathererStub::withGatherer(
                GatherFieldValuesStub::withValues(
                    self::TITLE_VALUE,
                    self::DESCRIPTION_VALUE,
                    self::DESCRIPTION_FORMAT,
                    self::START_DATE_VALUE,
                    self::END_DATE_VALUE,
                    ['challote']
                )
            ),
            RetrieveChangesetSubmissionDateStub::withDate(self::SUBMISSION_DATE),
            $this->mirror_searcher,
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            RetrieveTrackerOfArtifactStub::withIds(72, 53),
            MapStatusByValueStub::withSuccessiveBindValueIds(
                self::FIRST_MAPPED_STATUS_BIND_VALUE_ID,
                self::SECOND_MAPPED_STATUS_BIND_VALUE_ID
            ),
            $this->changeset_adder
        );
    }

    public function testItProcessesIterationUpdate(): void
    {
        $this->getProcessor()->processUpdate($this->update);

        self::assertTrue(
            $this->logger->hasDebug(
                sprintf(
                    'Processing iteration update of the iteration #%d from user #%d',
                    self::ITERATION_ID,
                    self::USER_ID
                )
            )
        );
        self::assertSame(2, $this->changeset_adder->getCallCount());
        [$first_changeset, $second_changeset] = $this->changeset_adder->getArguments();
        $first_values                         = $first_changeset->values;
        self::assertSame(self::FIRST_MIRRORED_ID, $first_changeset->mirrored_timebox->getId());
        self::assertSame(self::FIRST_TITLE_FIELD_ID, $first_values->title_field->getId());
        self::assertSame(self::FIRST_DESCRIPTION_FIELD_ID, $first_values->description_field->getId());
        self::assertSame(self::FIRST_STATUS_FIELD_ID, $first_values->status_field->getId());
        self::assertEquals([self::FIRST_MAPPED_STATUS_BIND_VALUE_ID], $first_values->mapped_status_value->getValues());
        self::assertSame(self::FIRST_START_DATE_FIELD_ID, $first_values->start_date_field->getId());
        self::assertSame(self::FIRST_END_DATE_FIELD_ID, $first_values->end_period_field->getId());
        self::assertSame(self::FIRST_ARTIFACT_LINK_FIELD_ID, $first_values->artifact_link_field->getId());

        $second_values = $second_changeset->values;
        self::assertSame(self::SECOND_MIRRORED_ID, $second_changeset->mirrored_timebox->getId());
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

        foreach ($this->changeset_adder->getArguments() as $changeset) {
            $values = $changeset->values;
            self::assertSame(self::TITLE_VALUE, $values->title_value->getValue());
            self::assertSame(self::DESCRIPTION_VALUE, $values->description_value->value);
            self::assertSame(self::DESCRIPTION_FORMAT, $values->description_value->format);
            self::assertSame(self::START_DATE_VALUE, $values->start_date_value->getValue());
            self::assertSame(self::END_DATE_VALUE, $values->end_period_value->getValue());
            self::assertNull($values->artifact_link_value);
            self::assertSame(self::USER_ID, $changeset->user->getId());
            self::assertSame(self::SUBMISSION_DATE, $changeset->submission_date->getValue());
        }
    }

    public function testItLogsAnErrorIfIterationHasNoMirroredIterations(): void
    {
        $this->mirror_searcher = SearchMirroredTimeboxesStub::withNoMirrors();

        $this->getProcessor()->processUpdate($this->update);

        self::assertTrue($this->logger->hasErrorRecords());
        self::assertSame(0, $this->changeset_adder->getCallCount());
    }

    public function testItStopsExecutionIfThereIsAnIssueInTheSourceIteration(): void
    {
        $this->fields_gatherer = GatherSynchronizedFieldsStub::withError();

        $this->getProcessor()->processUpdate($this->update);

        self::assertTrue($this->logger->hasErrorRecords());
        self::assertSame(0, $this->changeset_adder->getCallCount());
    }

    public function testItContinuesExecutionIfThereIsAnIssueInAMirroredIteration(): void
    {
        // We can fix the failing mirror by making another update to the source Iteration.
        // It will apply all values from this update too.
        $this->changeset_adder = AddChangesetStub::withError();

        $this->getProcessor()->processUpdate($this->update);

        self::assertTrue($this->logger->hasErrorRecords());
        self::assertSame(2, $this->changeset_adder->getCallCount());
    }
}
