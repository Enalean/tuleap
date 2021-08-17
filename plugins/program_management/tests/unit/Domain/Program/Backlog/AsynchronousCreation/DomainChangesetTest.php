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
use Tuleap\ProgramManagement\Tests\Stub\RetrieveLastChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;

final class DomainChangesetTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private IterationIdentifier $iteration;

    protected function setUp(): void
    {
        $this->iteration = IterationIdentifier::fromId(
            VerifyIsIterationStub::withValidIteration(),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            37,
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItBuildsFromId(): void
    {
        $changeset = DomainChangeset::fromId(VerifyIsChangesetStub::withValidChangeset(), 7715);
        self::assertSame(7715, $changeset->getId());
    }

    public function testItReturnsNullWhenIdIsNotAChangeset(): void
    {
        self::assertNull(DomainChangeset::fromId(VerifyIsChangesetStub::withNotValidChangeset(), -1));
    }

    public function testItBuildsFromIterationLastChangeset(): void
    {
        $last_changeset_id = 94;
        $changeset         = DomainChangeset::fromIterationLastChangeset(
            RetrieveLastChangesetStub::withLastChangesetIds($last_changeset_id),
            $this->iteration
        );
        self::assertSame($last_changeset_id, $changeset->getId());
    }

    public function testItReturnsNullWhenThereIsNoLastChangeset(): void
    {
        self::assertNull(
            DomainChangeset::fromIterationLastChangeset(
                RetrieveLastChangesetStub::withNoLastChangeset(),
                $this->iteration
            )
        );
    }
}
