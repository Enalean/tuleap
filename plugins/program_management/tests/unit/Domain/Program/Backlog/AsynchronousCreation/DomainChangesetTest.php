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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\IterationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveLastChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsChangesetStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DomainChangesetTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const CHANGESET_ID = 7715;
    private IterationIdentifier $iteration;

    protected function setUp(): void
    {
        $this->iteration = IterationIdentifierBuilder::buildWithId(37);
    }

    public function testItBuildsFromId(): void
    {
        $changeset = DomainChangeset::fromId(VerifyIsChangesetStub::withValidChangeset(), self::CHANGESET_ID);
        self::assertSame(self::CHANGESET_ID, $changeset?->getId());
    }

    public function testItReturnsNullWhenIdIsNotAChangeset(): void
    {
        self::assertNull(DomainChangeset::fromId(VerifyIsChangesetStub::withNotValidChangeset(), -1));
    }

    public function testItBuildsFromIterationLastChangeset(): void
    {
        $changeset = DomainChangeset::fromIterationLastChangeset(
            RetrieveLastChangesetStub::withLastChangesetIds(self::CHANGESET_ID),
            $this->iteration
        );
        self::assertSame(self::CHANGESET_ID, $changeset?->getId());
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
