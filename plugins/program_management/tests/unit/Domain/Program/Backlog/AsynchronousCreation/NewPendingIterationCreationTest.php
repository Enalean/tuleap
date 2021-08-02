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
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Stub\CheckProgramIncrementStub;
use Tuleap\ProgramManagement\Stub\RetrieveLastChangesetStub;
use Tuleap\ProgramManagement\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Stub\VerifyIterationHasBeenLinkedBeforeStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class NewPendingIterationCreationTest extends \Tuleap\Test\PHPUnit\TestCase
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

    protected function setUp(): void
    {
        $user                         = UserTestBuilder::aUser()->withId(self::USER_ID)->build();
        $this->user                   = UserIdentifier::fromPFUser($user);
        $program_increment            = ProgramIncrementIdentifier::fromId(
            CheckProgramIncrementStub::buildProgramIncrementChecker(),
            self::PROGRAM_INCREMENT_ID,
            $user
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
    }

    public function testItRetrievesLastChangesetOfEachIterationAndBuildsCollection(): void
    {
        [$first_creation, $second_creation] = NewPendingIterationCreation::buildCollectionFromJustLinkedIterations(
            $this->changeset_retriever,
            $this->logger,
            $this->just_linked_iterations,
            $this->user
        );
        self::assertSame(self::FIRST_ITERATION_ID, $first_creation->iteration->id);
        self::assertSame(self::PROGRAM_INCREMENT_ID, $first_creation->program_increment->getId());
        self::assertSame(self::USER_ID, $first_creation->user->user_id);
        self::assertSame(self::FIRST_CHANGESET_ID, $first_creation->changeset->id);

        self::assertSame(self::SECOND_ITERATION_ID, $second_creation->iteration->id);
        self::assertSame(self::PROGRAM_INCREMENT_ID, $second_creation->program_increment->getId());
        self::assertSame(self::USER_ID, $second_creation->user->user_id);
        self::assertSame(self::SECOND_CHANGESET_ID, $second_creation->changeset->id);
    }

    public function testItSkipsIterationWhenItHasNoLastChangeset(): void
    {
        $this->changeset_retriever = RetrieveLastChangesetStub::withNoLastChangeset();

        self::assertEmpty(
            NewPendingIterationCreation::buildCollectionFromJustLinkedIterations(
                $this->changeset_retriever,
                $this->logger,
                $this->just_linked_iterations,
                $this->user
            )
        );
        self::assertTrue($this->logger->hasErrorThatMatches('/Could not retrieve last changeset of iteration #[0-9]+, skipping it$/'));
    }
}
