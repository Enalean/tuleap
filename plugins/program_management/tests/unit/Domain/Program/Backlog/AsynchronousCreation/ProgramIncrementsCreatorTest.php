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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\SourceTimeboxChangesetValuesBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Stub\CreateArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class ProgramIncrementsCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CreateArtifactStub $artifact_creator;
    private SourceTimeboxChangesetValues $field_values;
    private TrackerCollection $mirrored_program_increment_trackers;
    private UserIdentifier $user_identifier;

    protected function setUp(): void
    {
        $this->artifact_creator = CreateArtifactStub::withCount();

        $this->user_identifier = UserIdentifierStub::buildGenericUser();
        $this->field_values    = SourceTimeboxChangesetValuesBuilder::build();

        $teams = TeamProjectsCollectionBuilder::withProjects(
            ProjectReferenceStub::withId(101),
            ProjectReferenceStub::withId(102),
        );

        $this->mirrored_program_increment_trackers = TrackerCollection::buildRootPlanningMilestoneTrackers(
            RetrieveMirroredProgramIncrementTrackerStub::withValidTrackers(
                TrackerReferenceStub::withId(1024),
                TrackerReferenceStub::withId(2048),
            ),
            $teams,
            $this->user_identifier,
            new ConfigurationErrorsCollector(false)
        );
    }

    private function getCreator(): ProgramIncrementsCreator
    {
        return new ProgramIncrementsCreator(
            new DBTransactionExecutorPassthrough(),
            MapStatusByValueStub::withValues(5000),
            $this->artifact_creator,
            GatherSynchronizedFieldsStub::withFieldsPreparations(
                new SynchronizedFieldsStubPreparation(492, 244, 413, 959, 431, 921),
                new SynchronizedFieldsStubPreparation(752, 242, 890, 705, 660, 182),
            )
        );
    }

    public function testItCreatesMirrorProgramIncrements(): void
    {
        $this->getCreator()->createProgramIncrements(
            $this->field_values,
            $this->mirrored_program_increment_trackers,
            $this->user_identifier
        );

        self::assertSame(2, $this->artifact_creator->getCallCount());
    }

    public function testItThrowsWhenThereIsAnErrorDuringCreation(): void
    {
        $this->artifact_creator = CreateArtifactStub::withError();

        $this->expectException(ProgramIncrementArtifactCreationException::class);
        $this->getCreator()->createProgramIncrements(
            $this->field_values,
            $this->mirrored_program_increment_trackers,
            $this->user_identifier
        );
    }
}
