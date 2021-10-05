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
use Tuleap\ProgramManagement\Tests\Builder\IterationCreationBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherFieldValuesStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveChangesetSubmissionDateStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFieldValuesGathererStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramOfIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;

final class IterationCreationProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ITERATION_ID = 20;
    private const USER_ID      = 191;
    private TestLogger $logger;
    private IterationCreation $creation;
    private GatherSynchronizedFieldsStub $fields_gatherer;

    protected function setUp(): void
    {
        $this->creation        = IterationCreationBuilder::buildWithIds(self::ITERATION_ID, 2, 53, self::USER_ID, 8612);
        $this->logger          = new TestLogger();
        $this->fields_gatherer = GatherSynchronizedFieldsStub::withFieldsPreparations(
            new SynchronizedFieldsStubPreparation(444, 819, 242, 757, 123, 226)
        );
    }

    private function getProcessor(): IterationCreationProcessor
    {
        return new IterationCreationProcessor(
            $this->logger,
            $this->fields_gatherer,
            RetrieveFieldValuesGathererStub::withGatherer(
                GatherFieldValuesStub::withDefault()
            ),
            RetrieveChangesetSubmissionDateStub::withDate(1781713922),
            RetrieveProgramOfIterationStub::withProgram(154),
            BuildProgramStub::stubValidProgram(),
            SearchTeamsOfProgramStub::buildTeams(122, 127),
            new RetrieveProjectReferenceStub(),
            RetrieveMirroredIterationTrackerStub::withValidTrackers(
                TrackerReferenceStub::withIdAndLabel(55, 'Sprints'),
                TrackerReferenceStub::withIdAndLabel(42, 'Week'),
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
    }

    public function testItStopsExecutionIfThereIsAnIssueInTheSourceIteration(): void
    {
        $this->fields_gatherer = GatherSynchronizedFieldsStub::withError();

        $this->getProcessor()->processCreation($this->creation);

        self::assertTrue($this->logger->hasErrorRecords());
    }
}
