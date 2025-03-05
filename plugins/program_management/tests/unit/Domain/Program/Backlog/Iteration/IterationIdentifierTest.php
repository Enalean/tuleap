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
use Tuleap\ProgramManagement\Tests\Stub\ArtifactUpdatedEventStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
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
            SearchIterationsStub::withIterations([
                [ 'id' => self::FIRST_NOT_VISIBLE_ARTIFACT_ID, 'changeset_id' => 1],
                [ 'id' => self::SECOND_NOT_VISIBLE_ARTIFACT_ID, 'changeset_id' => 2],
                [ 'id' => self::FIRST_VISIBLE_ARTIFACT_ID, 'changeset_id' => 3],
                [ 'id' => self::SECOND_VISIBLE_ARTIFACT_ID, 'changeset_id' => 4],
            ]),
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

    public function testItReturnsNullIfTheUpdatedArtifactIsNotFromAnIterationTracker(): void
    {
        $iteration_tracker_verifier = VerifyIsIterationTrackerStub::buildNotIteration();
        $event                      = ArtifactUpdatedEventStub::withIds(10, 93, $this->user->getId(), 4208, 4207);

        self::assertNull(IterationIdentifier::fromArtifactUpdateEvent($iteration_tracker_verifier, $event));
    }

    public function testItReturnsAnIterationFromAnUpdateEvent(): void
    {
        $iteration_tracker_verifier = VerifyIsIterationTrackerStub::buildValidIteration();
        $iteration_id               = 87;
        $event                      = ArtifactUpdatedEventStub::withIds($iteration_id, 93, $this->user->getId(), 4208, 4207);

        $iteration = IterationIdentifier::fromArtifactUpdateEvent($iteration_tracker_verifier, $event);
        self::assertNotNull($iteration);
        self::assertSame($iteration_id, $iteration->getId());
    }
}
