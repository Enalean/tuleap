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

use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\PendingIterationCreationProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\JustLinkedIterationCollection;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveLastChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsUserStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIterationHasBeenLinkedBeforeStub;

final class IterationCreationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const USER_ID              = 101;
    private const PROGRAM_INCREMENT_ID = 54;
    private const FIRST_ITERATION_ID   = 573;
    private const SECOND_ITERATION_ID  = 268;
    private const FIRST_CHANGESET_ID   = 4021;
    private const SECOND_CHANGESET_ID  = 4997;
    private UserIdentifier $user;
    private JustLinkedIterationCollection $just_linked_iterations;
    private RetrieveLastChangesetStub $changeset_retriever;
    private TestLogger $logger;
    private VerifyIsUserStub $user_verifier;
    private VerifyIsIterationStub $iteration_verifier;
    private VerifyIsVisibleArtifactStub $visibility_verifier;
    private VerifyIsProgramIncrementStub $program_increment_verifier;
    private VerifyIsChangesetStub $changeset_verifier;
    private PendingIterationCreation $pending_iteration;

    protected function setUp(): void
    {
        $this->user                   = UserIdentifierStub::withId(self::USER_ID);
        $program_increment            = ProgramIncrementIdentifierBuilder::buildWithIdAndUser(
            self::PROGRAM_INCREMENT_ID,
            $this->user
        );
        $iterations                   = IterationIdentifier::buildCollectionFromProgramIncrement(
            SearchIterationsStub::withIterationIds(self::FIRST_ITERATION_ID, self::SECOND_ITERATION_ID),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            $program_increment,
            $this->user
        );
        $this->just_linked_iterations = JustLinkedIterationCollection::fromIterations(
            VerifyIterationHasBeenLinkedBeforeStub::withNoIteration(),
            $program_increment,
            ...$iterations
        );
        $this->changeset_retriever    = RetrieveLastChangesetStub::withLastChangesetIds(
            self::FIRST_CHANGESET_ID,
            self::SECOND_CHANGESET_ID
        );
        $this->logger                 = new TestLogger();

        $this->pending_iteration = new PendingIterationCreationProxy(
            self::FIRST_ITERATION_ID,
            self::PROGRAM_INCREMENT_ID,
            self::USER_ID,
            self::FIRST_CHANGESET_ID
        );

        $this->user_verifier              = VerifyIsUserStub::withValidUser();
        $this->iteration_verifier         = VerifyIsIterationStub::withValidIteration();
        $this->visibility_verifier        = VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts();
        $this->program_increment_verifier = VerifyIsProgramIncrementStub::withValidProgramIncrement();
        $this->changeset_verifier         = VerifyIsChangesetStub::withValidChangeset();
    }

    public function testItRetrievesLastChangesetOfEachIterationAndBuildsCollection(): void
    {
        [$first_creation, $second_creation] = IterationCreation::buildCollectionFromJustLinkedIterations(
            $this->changeset_retriever,
            $this->logger,
            $this->just_linked_iterations,
            $this->user
        );
        self::assertSame(self::FIRST_ITERATION_ID, $first_creation->iteration->id);
        self::assertSame(self::PROGRAM_INCREMENT_ID, $first_creation->program_increment->getId());
        self::assertSame(self::USER_ID, $first_creation->user->getId());
        self::assertSame(self::FIRST_CHANGESET_ID, $first_creation->changeset->getId());

        self::assertSame(self::SECOND_ITERATION_ID, $second_creation->iteration->id);
        self::assertSame(self::PROGRAM_INCREMENT_ID, $second_creation->program_increment->getId());
        self::assertSame(self::USER_ID, $second_creation->user->getId());
        self::assertSame(self::SECOND_CHANGESET_ID, $second_creation->changeset->getId());
    }

    public function testItSkipsIterationWhenItHasNoLastChangeset(): void
    {
        $this->changeset_retriever = RetrieveLastChangesetStub::withNoLastChangeset();

        self::assertEmpty(
            IterationCreation::buildCollectionFromJustLinkedIterations(
                $this->changeset_retriever,
                $this->logger,
                $this->just_linked_iterations,
                $this->user
            )
        );
        self::assertTrue(
            $this->logger->hasErrorThatMatches('/Could not retrieve last changeset of iteration #[0-9]+, skipping it$/')
        );
    }

    public function testItBuildsFromPendingIterationCreation(): void
    {
        $iteration_creation = IterationCreation::fromPendingIterationCreation(
            $this->user_verifier,
            $this->iteration_verifier,
            $this->visibility_verifier,
            $this->program_increment_verifier,
            $this->changeset_verifier,
            $this->pending_iteration
        );
        self::assertSame(self::FIRST_ITERATION_ID, $iteration_creation->iteration->id);
        self::assertSame(self::PROGRAM_INCREMENT_ID, $iteration_creation->program_increment->getId());
        self::assertSame(self::USER_ID, $iteration_creation->user->getId());
        self::assertSame(self::FIRST_CHANGESET_ID, $iteration_creation->changeset->getId());
    }

    public function testItReturnsNullWhenStoredUserIsNotValid(): void
    {
        // It's not supposed to happen as users cannot be deleted in Tuleap. They change status.
        self::assertNull(
            IterationCreation::fromPendingIterationCreation(
                VerifyIsUserStub::withNotValidUser(),
                $this->iteration_verifier,
                $this->visibility_verifier,
                $this->program_increment_verifier,
                $this->changeset_verifier,
                $this->pending_iteration
            )
        );
    }

    public function testItThrowsWhenStoredIterationIsNotValid(): void
    {
        // It can happen if Program configuration changes between storage and processing; for example someone
        // changed the Iteration tracker.
        $this->expectException(StoredIterationNoLongerValidException::class);
        IterationCreation::fromPendingIterationCreation(
            $this->user_verifier,
            VerifyIsIterationStub::withNotIteration(),
            $this->visibility_verifier,
            $this->program_increment_verifier,
            $this->changeset_verifier,
            $this->pending_iteration
        );
    }

    public function testItThrowsWhenStoredProgramIncrementIsNotValid(): void
    {
        // It can happen if Program configuration changes between storage and processing; for example someone
        // changed the Program Increment tracker.
        $this->expectException(StoredProgramIncrementNoLongerValidException::class);
        IterationCreation::fromPendingIterationCreation(
            $this->user_verifier,
            $this->iteration_verifier,
            $this->visibility_verifier,
            VerifyIsProgramIncrementStub::withNotProgramIncrement(),
            $this->changeset_verifier,
            $this->pending_iteration
        );
    }

    public function testItReturnsNullWhenStoredChangesetIsNotValid(): void
    {
        // It's not supposed to happen as changesets cannot be deleted in Tuleap.
        self::assertNull(
            IterationCreation::fromPendingIterationCreation(
                $this->user_verifier,
                $this->iteration_verifier,
                $this->visibility_verifier,
                $this->program_increment_verifier,
                VerifyIsChangesetStub::withNotValidChangeset(),
                $this->pending_iteration
            )
        );
    }
}
