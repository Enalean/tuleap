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

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredIterationCreationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\TeamHasNoMirroredIterationTrackerException;
use Tuleap\ProgramManagement\Tests\Builder\IterationCreationBuilder;
use Tuleap\ProgramManagement\Tests\Builder\SourceTimeboxChangesetValuesBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Stub\CreateArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class IterationsCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private RetrieveMirroredIterationTrackerStub $milestone_retriever;
    private CreateArtifactStub $artifact_creator;
    private SourceTimeboxChangesetValues $field_values;
    private TeamProjectsCollection $teams;
    private IterationCreation $creation;

    protected function setUp(): void
    {
        $this->milestone_retriever = RetrieveMirroredIterationTrackerStub::withValidTrackers(
            TrackerReferenceStub::withId(32),
            TrackerReferenceStub::withId(57),
        );
        $this->artifact_creator    = CreateArtifactStub::withCount();

        $this->field_values = SourceTimeboxChangesetValuesBuilder::build();
        $this->teams        = TeamProjectsCollectionBuilder::withProjects(
            ProjectReferenceStub::withId(168),
            ProjectReferenceStub::withId(160),
        );

        $this->creation = IterationCreationBuilder::buildWithIds(25, 9, 26, 149, 9017);
    }

    private function getCreator(): IterationsCreator
    {
        return new IterationsCreator(
            new DBTransactionExecutorPassthrough(),
            $this->milestone_retriever,
            MapStatusByValueStub::withSuccessiveBindValueIds(2061, 2130),
            GatherSynchronizedFieldsStub::withFieldsPreparations(
                new SynchronizedFieldsStubPreparation(211, 556, 596, 500, 614, 793),
                new SynchronizedFieldsStubPreparation(436, 975, 992, 145, 424, 439),
            ),
            $this->artifact_creator
        );
    }

    public function testItCreatesMirroredIterations(): void
    {
        $this->getCreator()->createIterations($this->field_values, $this->teams, $this->creation);

        self::assertSame(2, $this->artifact_creator->getCallCount());
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
}
