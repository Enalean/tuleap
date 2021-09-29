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
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\JustLinkedIterationCollection;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\PendingIterationCreationBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ProgramIncrementUpdateEventStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveLastChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIterationHasBeenLinkedBeforeStub;

final class IterationCreationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const USER_ID                        = 101;
    private const PROGRAM_INCREMENT_ID           = 54;
    private const PROGRAM_INCREMENT_CHANGESET_ID = 8769;
    private const FIRST_ITERATION_ID             = 573;
    private const SECOND_ITERATION_ID            = 268;
    private const FIRST_CHANGESET_ID             = 4021;
    private const SECOND_CHANGESET_ID            = 4997;
    private UserIdentifier $user;
    private JustLinkedIterationCollection $just_linked_iterations;
    private RetrieveLastChangesetStub $changeset_retriever;
    private TestLogger $logger;
    private ProgramIncrementUpdateEventStub $update_event;

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

        $first_iteration    = PendingIterationCreationBuilder::buildWithIds(
            self::FIRST_ITERATION_ID,
            self::FIRST_CHANGESET_ID
        );
        $second_iteration   = PendingIterationCreationBuilder::buildWithIds(
            self::SECOND_ITERATION_ID,
            self::SECOND_CHANGESET_ID
        );
        $this->update_event = ProgramIncrementUpdateEventStub::withIds(
            self::PROGRAM_INCREMENT_ID,
            self::USER_ID,
            self::PROGRAM_INCREMENT_CHANGESET_ID,
            $first_iteration,
            $second_iteration
        );
    }

    public function testItRetrievesLastChangesetOfEachIterationAndBuildsCollection(): void
    {
        [$first_creation, $second_creation] = IterationCreation::buildCollectionFromJustLinkedIterations(
            $this->changeset_retriever,
            $this->logger,
            $this->just_linked_iterations,
            $this->user
        );
        self::assertSame(self::FIRST_ITERATION_ID, $first_creation->getIteration()->getId());
        self::assertSame(self::PROGRAM_INCREMENT_ID, $first_creation->getProgramIncrement()->getId());
        self::assertSame(self::USER_ID, $first_creation->getUser()->getId());
        self::assertSame(self::FIRST_CHANGESET_ID, $first_creation->getChangeset()->getId());

        self::assertSame(self::SECOND_ITERATION_ID, $second_creation->getIteration()->getId());
        self::assertSame(self::PROGRAM_INCREMENT_ID, $second_creation->getProgramIncrement()->getId());
        self::assertSame(self::USER_ID, $second_creation->getUser()->getId());
        self::assertSame(self::SECOND_CHANGESET_ID, $second_creation->getChangeset()->getId());
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

    public function testItBuildsCollectionFromProgramIncrementUpdateEvent(): void
    {
        [$first_creation, $second_creation] = IterationCreation::buildCollectionFromProgramIncrementUpdateEvent(
            $this->update_event
        );
        self::assertSame(self::FIRST_ITERATION_ID, $first_creation->getIteration()->getId());
        self::assertSame(self::PROGRAM_INCREMENT_ID, $first_creation->getProgramIncrement()->getId());
        self::assertSame(self::USER_ID, $first_creation->getUser()->getId());
        self::assertSame(self::FIRST_CHANGESET_ID, $first_creation->getChangeset()->getId());

        self::assertSame(self::SECOND_ITERATION_ID, $second_creation->getIteration()->getId());
        self::assertSame(self::PROGRAM_INCREMENT_ID, $second_creation->getProgramIncrement()->getId());
        self::assertSame(self::USER_ID, $second_creation->getUser()->getId());
        self::assertSame(self::SECOND_CHANGESET_ID, $second_creation->getChangeset()->getId());
    }

    public function testItBuildsEmptyCollectionWhenEventHasNoPendingIterations(): void
    {
        $update_event = ProgramIncrementUpdateEventStub::withNoIterations(
            self::PROGRAM_INCREMENT_ID,
            self::USER_ID,
            self::PROGRAM_INCREMENT_CHANGESET_ID
        );

        self::assertEmpty(
            IterationCreation::buildCollectionFromProgramIncrementUpdateEvent($update_event)
        );
    }
}
