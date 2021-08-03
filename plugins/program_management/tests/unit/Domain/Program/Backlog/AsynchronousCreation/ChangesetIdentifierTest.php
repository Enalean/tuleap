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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Stub\CheckProgramIncrementStub;
use Tuleap\ProgramManagement\Stub\RetrieveLastChangesetStub;
use Tuleap\ProgramManagement\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class ChangesetIdentifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ProgramIncrementIdentifier $program_increment;
    private IterationIdentifier $iteration;

    protected function setUp(): void
    {
        $user                    = UserTestBuilder::aUser()->build();
        $this->program_increment = ProgramIncrementIdentifier::fromId(
            CheckProgramIncrementStub::buildProgramIncrementChecker(),
            101,
            $user
        );

        [$this->iteration] = IterationIdentifier::buildCollectionFromProgramIncrement(
            SearchIterationsStub::withIterationIds(296),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            $this->program_increment,
            UserIdentifier::fromPFUser($user)
        );
    }

    public function testItBuildsFromIterationLastChangeset(): void
    {
        $last_changeset_id = 94;
        $changeset         = ChangesetIdentifier::fromIterationLastChangeset(
            RetrieveLastChangesetStub::withLastChangesetIds($last_changeset_id),
            $this->iteration
        );
        self::assertSame($last_changeset_id, $changeset->id);
    }

    public function testItReturnsNullWhenThereIsNoLastChangeset(): void
    {
        self::assertNull(
            ChangesetIdentifier::fromIterationLastChangeset(
                RetrieveLastChangesetStub::withNoLastChangeset(),
                $this->iteration
            )
        );
    }
}
