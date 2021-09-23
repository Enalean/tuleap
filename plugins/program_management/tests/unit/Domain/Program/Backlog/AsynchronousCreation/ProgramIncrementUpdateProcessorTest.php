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

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementUpdateBuilder;
use Tuleap\ProgramManagement\Tests\Stub\GatherFieldValuesStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveChangesetSubmissionDateStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFieldValuesGathererStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerOfArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchMirroredTimeboxesStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\ProgramManagement\Tests\Stub\TrackerIdentifierStub;

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
    private MockObject|AddChangeset $changeset_adder;
    private ProgramIncrementUpdate $update;
    private MockObject|DeletePendingProgramIncrementUpdates $pending_update_deleter;

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

        $this->mirror_searcher        = SearchMirroredTimeboxesStub::withIds(
            self::FIRST_MIRRORED_ID,
            self::SECOND_MIRRORED_ID
        );
        $this->changeset_adder        = $this->createMock(AddChangeset::class);
        $this->pending_update_deleter = $this->createMock(DeletePendingProgramIncrementUpdates::class);
    }

    private function getProcessor(): ProgramIncrementUpdateProcessor
    {
        return new ProgramIncrementUpdateProcessor(
            $this->logger,
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
            RetrieveTrackerOfArtifactStub::withTrackers(
                TrackerIdentifierStub::withId(72),
                TrackerIdentifierStub::withId(53)
            ),
            MapStatusByValueStub::withValues(1607, 8889),
            $this->changeset_adder,
            $this->pending_update_deleter
        );
    }

    public function testItProcessesProgramIncrementUpdate(): void
    {
        $this->changeset_adder->expects(self::exactly(2))
            ->method('addChangeset')
            ->with(
                $this->callback(function (MirroredTimeboxChangeset $changeset): bool {
                    $mirrored_timebox_id = $changeset->mirrored_timebox->getId();
                    $timebox_ids_are_set = $mirrored_timebox_id === self::FIRST_MIRRORED_ID || $mirrored_timebox_id === self::SECOND_MIRRORED_ID;
                    $values_are_set      = ! (empty($changeset->values->toFieldsDataArray()));

                    return $changeset->user->getId() === self::USER_ID
                        && $changeset->submission_date->getValue() === self::SUBMISSION_DATE
                        && $timebox_ids_are_set
                        && $values_are_set;
                })
            );
        $this->pending_update_deleter->expects(self::once())->method('deletePendingProgramIncrementUpdate');

        $this->getProcessor()->processProgramIncrementUpdate($this->update);

        self::assertTrue(
            $this->logger->hasDebug(
                sprintf(
                    'Processing program increment update with program increment #%d for user #%d',
                    self::PROGRAM_INCREMENT_ID,
                    self::USER_ID
                )
            )
        );
    }

    public function testItLogsAnErrorIfProgramIncrementHasNoMirroredProgramIncrements(): void
    {
        $this->mirror_searcher = SearchMirroredTimeboxesStub::withNoMirrors();

        $this->getProcessor()->processProgramIncrementUpdate($this->update);

        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testItStopsExecutionIfThereIsAnIssueInTheSourceProgramIncrement(): void
    {
        $this->fields_gatherer = GatherSynchronizedFieldsStub::withError();
        $this->changeset_adder->expects(self::never())->method('addChangeset');
        $this->pending_update_deleter->expects(self::never())->method('deletePendingProgramIncrementUpdate');

        $this->getProcessor()->processProgramIncrementUpdate($this->update);

        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testItContinuesExecutionIfThereIsAnIssueInAMirroredProgramIncrement(): void
    {
        // We can fix the failing mirror by making another update to the source Program Increment.
        // It will apply all values from this update too.
        $this->changeset_adder->expects(self::exactly(2))->method('addChangeset')
            ->willThrowException(
                new NewChangesetCreationException(
                    self::FIRST_MIRRORED_ID,
                    new \Exception('Parent exception')
                )
            );
        $this->pending_update_deleter->expects(self::once())->method('deletePendingProgramIncrementUpdate');

        $this->getProcessor()->processProgramIncrementUpdate($this->update);

        self::assertTrue($this->logger->hasErrorRecords());
    }
}
