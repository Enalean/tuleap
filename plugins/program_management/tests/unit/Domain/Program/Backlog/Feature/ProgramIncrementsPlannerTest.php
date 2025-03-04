<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature;

use Psr\Log\NullLogger;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifierCollection;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementCreationBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TeamIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\CreateProgramIncrementsStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherFieldValuesStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\ProcessIterationCreationStub;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveChangesetSubmissionDateStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFieldValuesGathererStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveLastChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramOfProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramIncrementsPlannerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_ID         = 43;
    private const USER_ID                      = 119;
    private const FIRST_TEAM_ID                = 102;
    private const SECOND_TEAM_ID               = 149;
    private const CHANGESET_ID                 = 6053;
    private const PROGRAM_INCREMENT_TRACKER_ID = 54;
    private const ITERATION_TRACKER_ID         = 89;

    private GatherSynchronizedFieldsStub $fields_gatherer;
    private ProgramIncrementCreation $creation;
    private TeamIdentifierCollection $teams;
    private CreateProgramIncrementsStub $program_increment_creator;

    protected function setUp(): void
    {
        $this->fields_gatherer = GatherSynchronizedFieldsStub::withFieldsPreparations(
            SynchronizedFieldsStubPreparation::withAllFields(
                467,
                822,
                436,
                762,
                752,
                711
            ),
            SynchronizedFieldsStubPreparation::withAllFields(
                604,
                335,
                772,
                876,
                790,
                608
            ),
            SynchronizedFieldsStubPreparation::withAllFields(
                810,
                887,
                506,
                873,
                524,
                866
            ),
        );

        $this->creation = ProgramIncrementCreationBuilder::buildWithIds(
            self::USER_ID,
            self::PROGRAM_INCREMENT_ID,
            self::PROGRAM_INCREMENT_TRACKER_ID,
            self::CHANGESET_ID
        );

        $this->teams                     = TeamIdentifierCollection::fromSingleTeam(TeamIdentifierBuilder::buildWithId(self::FIRST_TEAM_ID));
        $this->program_increment_creator = CreateProgramIncrementsStub::build();
    }

    public function getBuilder(): ProgramIncrementsPlanner
    {
        return new ProgramIncrementsPlanner(
            MessageLog::buildFromLogger(new NullLogger()),
            RetrieveMirroredProgramIncrementTrackerStub::withValidTrackers(
                TrackerReferenceStub::withId(99),
                TrackerReferenceStub::withId(34)
            ),
            $this->program_increment_creator,
            RetrieveProjectReferenceStub::withProjects(
                ProjectReferenceStub::withId(self::FIRST_TEAM_ID),
                ProjectReferenceStub::withId(self::SECOND_TEAM_ID),
            ),
            $this->fields_gatherer,
            RetrieveFieldValuesGathererStub::withGatherer(
                GatherFieldValuesStub::withValues(
                    'outstream',
                    '',
                    'commonmark',
                    1607291762,
                    1755522942,
                    ['improvisational']
                )
            ),
            RetrieveChangesetSubmissionDateStub::withDate(1395687908),
            ProcessIterationCreationStub::withCount(),
            RetrieveProgramOfProgramIncrementStub::withProgram(self::PROGRAM_INCREMENT_ID),
            BuildProgramStub::stubValidProgram(),
            RetrieveIterationTrackerStub::withValidTracker(self::ITERATION_TRACKER_ID),
            VerifyIsIterationStub::withValidIteration(),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            SearchIterationsStub::withIterations([
                ['id' => 123, 'changeset_id' => self::CHANGESET_ID ],
            ]),
            RetrieveLastChangesetStub::withLastChangesetIds(self::CHANGESET_ID)
        );
    }

    public function testItBuildsAPlanChange(): void
    {
        $plan_change = $this->getBuilder()->createProgramIncrementAndReturnPlanChange($this->creation, $this->teams);

        self::assertSame(self::CHANGESET_ID, $plan_change->changeset->getId());
        self::assertSame(self::CHANGESET_ID, $plan_change->old_changeset->getId());
        self::assertSame(self::PROGRAM_INCREMENT_TRACKER_ID, $plan_change->tracker->getId());
        self::assertSame(self::PROGRAM_INCREMENT_ID, $plan_change->program_increment->getId());
        self::assertSame(self::USER_ID, $plan_change->user->getId());

        self::assertEquals(1, $this->program_increment_creator->getCallsCount());
    }

    public function testItStopsExecutionIfThereIsAnIssueInTheSourceProgramIncrement(): void
    {
        $this->fields_gatherer = GatherSynchronizedFieldsStub::withError();

        $this->expectException(FieldRetrievalException::class);
        $this->getBuilder()->createProgramIncrementAndReturnPlanChange($this->creation, $this->teams);

        self::assertEquals(1, $this->program_increment_creator->getCallsCount());
    }
}
