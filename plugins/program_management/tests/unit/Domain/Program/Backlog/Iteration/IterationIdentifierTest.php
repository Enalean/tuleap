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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration;

use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;

final class IterationIdentifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_NOT_VISIBLE_ARTIFACT_ID  = 307;
    private const SECOND_NOT_VISIBLE_ARTIFACT_ID = 100;
    private const FIRST_VISIBLE_ARTIFACT_ID      = 271;
    private const SECOND_VISIBLE_ARTIFACT_ID     = 124;
    private UserIdentifier $user;
    private VerifyIsIterationStub $iteration_verifier;
    private VerifyIsVisibleArtifactStub $visibility_verifier;

    protected function setUp(): void
    {
        $this->user                = UserIdentifierStub::buildGenericUser();
        $this->iteration_verifier  = VerifyIsIterationStub::withValidIteration();
        $this->visibility_verifier = VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts();
    }

    public function testItBuildsFromId(): void
    {
        $iteration = IterationIdentifier::fromId(
            $this->iteration_verifier,
            $this->visibility_verifier,
            32,
            $this->user
        );
        self::assertSame(32, $iteration?->getId());
    }

    public function testItReturnsNullWhenIdIsNotAnIteration(): void
    {
        $iteration_verifier = VerifyIsIterationStub::withNotIteration();
        self::assertNull(
            IterationIdentifier::fromId(
                $iteration_verifier,
                $this->visibility_verifier,
                48,
                $this->user
            )
        );
    }

    public function testItReturnsNullWhenArtifactIsNotVisibleByUser(): void
    {
        $visibility_verifier = VerifyIsVisibleArtifactStub::withNoVisibleArtifact();
        self::assertNull(
            IterationIdentifier::fromId(
                $this->iteration_verifier,
                $visibility_verifier,
                404,
                $this->user
            )
        );
    }

    public function testItFiltersIterationsThatAreNotVisible(): void
    {
        $iterations = IterationIdentifier::buildCollectionFromProgramIncrement(
            SearchIterationsStub::withIterationIds(
                self::FIRST_NOT_VISIBLE_ARTIFACT_ID,
                self::SECOND_NOT_VISIBLE_ARTIFACT_ID,
                self::FIRST_VISIBLE_ARTIFACT_ID,
                self::SECOND_VISIBLE_ARTIFACT_ID
            ),
            VerifyIsVisibleArtifactStub::withVisibleIds(
                self::FIRST_VISIBLE_ARTIFACT_ID,
                self::SECOND_VISIBLE_ARTIFACT_ID
            ),
            ProgramIncrementIdentifierBuilder::buildWithIdAndUser(36, $this->user),
            $this->user
        );

        $ids = array_map(static fn(IterationIdentifier $iteration): int => $iteration->getId(), $iterations);
        self::assertNotContains(self::FIRST_NOT_VISIBLE_ARTIFACT_ID, $ids);
        self::assertNotContains(self::SECOND_NOT_VISIBLE_ARTIFACT_ID, $ids);
        self::assertContains(self::FIRST_VISIBLE_ARTIFACT_ID, $ids);
        self::assertContains(self::SECOND_VISIBLE_ARTIFACT_ID, $ids);
    }
}
