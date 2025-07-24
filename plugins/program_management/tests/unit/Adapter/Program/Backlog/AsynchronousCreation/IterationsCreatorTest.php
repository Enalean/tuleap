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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Psr\Log\NullLogger;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\AddArtifactLinkChangesetException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredIterationCreationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\TeamHasNoMirroredIterationTrackerException;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifierCollection;
use Tuleap\ProgramManagement\Tests\Builder\IterationCreationBuilder;
use Tuleap\ProgramManagement\Tests\Builder\SourceTimeboxChangesetValuesBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TeamIdentifierCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Stub\AddArtifactLinkChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\CreateArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredProgramIncrementFromTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerOfArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class IterationsCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_TEAM_ID  = 168;
    private const SECOND_TEAM_ID = 160;
    private RetrieveMirroredIterationTrackerStub $milestone_retriever;
    private CreateArtifactStub $artifact_creator;
    private RetrieveMirroredProgramIncrementFromTeamStub $mirrored_program_increment_retriever;
    private AddArtifactLinkChangesetStub $link_adder;
    private SourceTimeboxChangesetValues $field_values;
    private TeamIdentifierCollection $teams;
    private IterationCreation $creation;

    #[\Override]
    protected function setUp(): void
    {
        $this->milestone_retriever = RetrieveMirroredIterationTrackerStub::withValidTrackers(
            TrackerReferenceStub::withId(32),
            TrackerReferenceStub::withId(57),
        );
        $this->artifact_creator    = CreateArtifactStub::withIds(26, 27);
        $this->link_adder          = AddArtifactLinkChangesetStub::withCount();

        $this->mirrored_program_increment_retriever = RetrieveMirroredProgramIncrementFromTeamStub::withIds(60, 61);

        $this->field_values = SourceTimeboxChangesetValuesBuilder::build();
        $this->teams        = TeamIdentifierCollectionBuilder::buildWithIds(self::FIRST_TEAM_ID, self::SECOND_TEAM_ID);

        $this->creation = IterationCreationBuilder::buildWithIds(25, 9, 59, 149, 9017);
    }

    private function getCreator(): IterationsCreator
    {
        return new IterationsCreator(
            new NullLogger(),
            new DBTransactionExecutorPassthrough(),
            $this->milestone_retriever,
            MapStatusByValueStub::withSuccessiveBindValueIds(2061, 2130),
            GatherSynchronizedFieldsStub::withFieldsPreparations(
                SynchronizedFieldsStubPreparation::withAllFields(211, 556, 596, 500, 614, 793),
                SynchronizedFieldsStubPreparation::withOnlyArtifactLinkField(476),
                SynchronizedFieldsStubPreparation::withAllFields(436, 975, 992, 145, 424, 439),
                SynchronizedFieldsStubPreparation::withOnlyArtifactLinkField(385),
            ),
            $this->artifact_creator,
            $this->mirrored_program_increment_retriever,
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            RetrieveTrackerOfArtifactStub::withIds(95, 64),
            $this->link_adder,
            RetrieveProjectReferenceStub::withProjects(
                ProjectReferenceStub::withId(self::FIRST_TEAM_ID),
                ProjectReferenceStub::withId(self::SECOND_TEAM_ID)
            )
        );
    }

    public function testItCreatesMirroredIterations(): void
    {
        $this->getCreator()->createIterations($this->field_values, $this->teams, $this->creation);

        self::assertSame(2, $this->artifact_creator->getCallCount());
        self::assertSame(2, $this->link_adder->getCallCount());
    }

    public function testItThrowsWhenTeamHasNoMirroredIterationTracker(): void
    {
        $this->milestone_retriever = RetrieveMirroredIterationTrackerStub::withNoVisibleRootPlanning();

        $this->expectException(TeamHasNoMirroredIterationTrackerException::class);
        $this->getCreator()->createIterations($this->field_values, $this->teams, $this->creation);

        self::assertSame(0, $this->artifact_creator->getCallCount());
    }

    public function testItThrowsWhenThereIsAnErrorDuringCreation(): void
    {
        $this->artifact_creator = CreateArtifactStub::withError();

        $this->expectException(MirroredIterationCreationException::class);
        $this->getCreator()->createIterations($this->field_values, $this->teams, $this->creation);
    }

    public function testItThrowsWhenItCannotFindMirroredProgramIncrementInTheSameTeam(): void
    {
        $this->mirrored_program_increment_retriever = RetrieveMirroredProgramIncrementFromTeamStub::withNoMirror();

        $this->expectException(MirroredProgramIncrementNotFoundException::class);
        $this->getCreator()->createIterations($this->field_values, $this->teams, $this->creation);
    }

    public function testItThrowsWhenThereIsAnErrorWhileLinkingMirroredProgramIncrementToMirroredIteration(): void
    {
        $this->link_adder = AddArtifactLinkChangesetStub::withError();

        $this->expectException(AddArtifactLinkChangesetException::class);
        $this->getCreator()->createIterations($this->field_values, $this->teams, $this->creation);
    }
}
