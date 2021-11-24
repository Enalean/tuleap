<?php
/**
 * Copyright (c) Enalean 2020 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementArtifactCreationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredProgramIncrementTrackerIdentifierCollection;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\MirroredProgramIncrementTrackerIdentifierCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Builder\SourceTimeboxChangesetValuesBuilder;
use Tuleap\ProgramManagement\Tests\Stub\CreateArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class ProgramIncrementsCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CreateArtifactStub $artifact_creator;
    private SourceTimeboxChangesetValues $field_values;
    private MirroredProgramIncrementTrackerIdentifierCollection $mirrored_trackers;
    private UserIdentifier $user_identifier;

    protected function setUp(): void
    {
        $this->artifact_creator = CreateArtifactStub::withIds(39, 40);

        $this->user_identifier = UserIdentifierStub::buildGenericUser();
        $this->field_values    = SourceTimeboxChangesetValuesBuilder::build();

        $this->mirrored_trackers = MirroredProgramIncrementTrackerIdentifierCollectionBuilder::buildWithIds(1024, 2048);
    }

    private function getCreator(): ProgramIncrementsCreator
    {
        return new ProgramIncrementsCreator(
            new DBTransactionExecutorPassthrough(),
            MapStatusByValueStub::withSuccessiveBindValueIds(5000, 3698),
            $this->artifact_creator,
            GatherSynchronizedFieldsStub::withFieldsPreparations(
                SynchronizedFieldsStubPreparation::withAllFields(492, 244, 413, 959, 431, 921),
                SynchronizedFieldsStubPreparation::withAllFields(752, 242, 890, 705, 660, 182),
            )
        );
    }

    public function testItCreatesMirrorProgramIncrements(): void
    {
        $this->getCreator()->createProgramIncrements(
            $this->field_values,
            $this->mirrored_trackers,
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
            $this->mirrored_trackers,
            $this->user_identifier
        );
    }
}
