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
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementUpdateBuilder;
use Tuleap\ProgramManagement\Tests\Stub\AddChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherFieldValuesStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveChangesetSubmissionDateStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFieldValuesGathererStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerOfArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchMirroredTimeboxesStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\ProgramManagement\Tests\Stub\TrackerIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;

final class ProgramIncrementUpdateProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_ID         = 63;
    private const USER_ID                      = 122;
    private const PROGRAM_INCREMENT_TRACKER_ID = 74;
    private const FIRST_MIRRORED_ID            = 137;
    private const SECOND_MIRRORED_ID           = 194;
    private const SUBMISSION_DATE              = 1408642745;
    private TestLogger $logger;
    private GatherSynchronizedFieldsStub $fields_gatherer;
    private SearchMirroredTimeboxesStub $mirror_searcher;
    private AddChangesetStub $changeset_adder;
    private ProgramIncrementUpdate $update;

    protected function setUp(): void
    {
        $this->update = ProgramIncrementUpdateBuilder::buildWithIds(
            self::USER_ID,
            self::PROGRAM_INCREMENT_ID,
            self::PROGRAM_INCREMENT_TRACKER_ID,
            3882
        );

        $this->logger          = new TestLogger();
        $this->fields_gatherer = GatherSynchronizedFieldsStub::withFieldsPreparations(
            new SynchronizedFieldsStubPreparation(199, 885, 826, 351, 986, 536),
            new SynchronizedFieldsStubPreparation(205, 430, 472, 104, 844, 393),
            new SynchronizedFieldsStubPreparation(751, 586, 537, 629, 104, 762)
        );

        $this->mirror_searcher = SearchMirroredTimeboxesStub::withIds(
            self::FIRST_MIRRORED_ID,
            self::SECOND_MIRRORED_ID
        );
        $this->changeset_adder = AddChangesetStub::withCount();
    }

    private function getProcessor(): ProgramIncrementUpdateProcessor
    {
        return new ProgramIncrementUpdateProcessor(
            MessageLog::buildFromLogger($this->logger),
            $this->fields_gatherer,
            RetrieveFieldValuesGathererStub::withGatherer(
                GatherFieldValuesStub::withValues(
                    'nocuously',
                    'unbowsome',
                    'text',
                    '2015-09-20',
                    '2016-06-08',
                    ['challote']
                )
            ),
            RetrieveChangesetSubmissionDateStub::withDate(self::SUBMISSION_DATE),
            $this->mirror_searcher,
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            RetrieveTrackerOfArtifactStub::withTrackers(
                TrackerIdentifierStub::withId(72),
                TrackerIdentifierStub::withId(53)
            ),
            MapStatusByValueStub::withValues(1607, 8889),
            $this->changeset_adder
        );
    }

    public function testItProcessesProgramIncrementUpdate(): void
    {
        $this->getProcessor()->processUpdate($this->update);

        self::assertTrue(
            $this->logger->hasDebug(
                sprintf(
                    'Processing program increment update with program increment #%d for user #%d',
                    self::PROGRAM_INCREMENT_ID,
                    self::USER_ID
                )
            )
        );
        self::assertSame(2, $this->changeset_adder->getCallCount());
        foreach ($this->changeset_adder->getArguments() as $changeset) {
            self::assertContains(
                $changeset->mirrored_timebox->getId(),
                [self::FIRST_MIRRORED_ID, self::SECOND_MIRRORED_ID]
            );
            self::assertNotEmpty($changeset->values->toFieldsDataArray());
            self::assertSame(self::USER_ID, $changeset->user->getId());
            self::assertSame(self::SUBMISSION_DATE, $changeset->submission_date->getValue());
        }
    }

    public function testItLogsAnErrorIfProgramIncrementHasNoMirroredProgramIncrements(): void
    {
        $this->mirror_searcher = SearchMirroredTimeboxesStub::withNoMirrors();

        $this->getProcessor()->processUpdate($this->update);

        self::assertTrue($this->logger->hasErrorRecords());
        self::assertSame(0, $this->changeset_adder->getCallCount());
    }

    public function testItStopsExecutionIfThereIsAnIssueInTheSourceProgramIncrement(): void
    {
        $this->fields_gatherer = GatherSynchronizedFieldsStub::withError();

        $this->getProcessor()->processUpdate($this->update);

        self::assertTrue($this->logger->hasErrorRecords());
        self::assertSame(0, $this->changeset_adder->getCallCount());
    }

    public function testItContinuesExecutionIfThereIsAnIssueInAMirroredProgramIncrement(): void
    {
        // We can fix the failing mirror by making another update to the source Program Increment.
        // It will apply all values from this update too.
        $this->changeset_adder = AddChangesetStub::withError();

        $this->getProcessor()->processUpdate($this->update);

        self::assertTrue($this->logger->hasErrorRecords());
        self::assertSame(2, $this->changeset_adder->getCallCount());
    }
}
