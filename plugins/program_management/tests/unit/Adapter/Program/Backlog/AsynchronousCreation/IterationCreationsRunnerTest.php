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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Adapter\Events\IterationCreationEventProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProcessIterationCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\JustLinkedIterationCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Stub\CheckProgramIncrementStub;
use Tuleap\ProgramManagement\Stub\RetrieveLastChangesetStub;
use Tuleap\ProgramManagement\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Stub\VerifyIterationHasBeenLinkedBeforeStub;
use Tuleap\Queue\NoQueueSystemAvailableException;
use Tuleap\Queue\PersistentQueue;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\QueueServerConnectionException;
use Tuleap\Test\Builders\UserTestBuilder;

final class IterationCreationsRunnerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const USER_ID             = 110;
    private const FIRST_ITERATION_ID  = 54;
    private const SECOND_ITERATION_ID = 89;
    private TestLogger $logger;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\Stub|QueueFactory
     */
    private $queue_factory;
    /**
     * @var IterationCreation[]
     */
    private array $iteration_creations;
    private ProcessIterationCreation $iteration_creator;

    protected function setUp(): void
    {
        $this->logger        = new TestLogger();
        $this->queue_factory = $this->createStub(QueueFactory::class);

        $pfuser                    = UserTestBuilder::aUser()->withId(self::USER_ID)->build();
        $user                      = UserIdentifier::fromPFUser($pfuser);
        $program_increment         = ProgramIncrementIdentifier::fromId(
            CheckProgramIncrementStub::buildProgramIncrementChecker(),
            101,
            $pfuser
        );
        $iterations                = IterationIdentifier::buildCollectionFromProgramIncrement(
            SearchIterationsStub::withIterationIds(self::FIRST_ITERATION_ID, self::SECOND_ITERATION_ID),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            $program_increment,
            $user
        );
        $just_linked_iterations    = JustLinkedIterationCollection::fromIterations(
            VerifyIterationHasBeenLinkedBeforeStub::withNoIteration(),
            $program_increment,
            ...$iterations
        );
        $this->iteration_creations = IterationCreation::buildCollectionFromJustLinkedIterations(
            RetrieveLastChangesetStub::withLastChangesetIds(5539, 5174),
            $this->logger,
            $just_linked_iterations,
            $user
        );
        $this->iteration_creator   = new class implements ProcessIterationCreation {
            public function processIterationCreation(IterationCreation $iteration_creation): void
            {
                // Side effects
            }
        };
    }

    private function getRunner(): IterationCreationsRunner
    {
        return new IterationCreationsRunner($this->logger, $this->queue_factory, $this->iteration_creator);
    }

    public function testItPushesAMessageForEachIterationCreation(): void
    {
        $queue = $this->createMock(PersistentQueue::class);
        $queue->expects(self::exactly(2))
            ->method('pushSinglePersistentMessage')
            ->withConsecutive([
                IterationCreationEventProxy::TOPIC,
                ['artifact_id' => self::FIRST_ITERATION_ID, 'user_id' => self::USER_ID]
            ], [
                IterationCreationEventProxy::TOPIC,
                ['artifact_id' => self::SECOND_ITERATION_ID, 'user_id' => self::USER_ID]
            ]);
        $this->queue_factory->method('getPersistentQueue')->willReturn($queue);

        $this->getRunner()->scheduleIterationCreations(...$this->iteration_creations);
    }

    public function testItDoesNothingWhenIterationCreationsAreEmpty(): void
    {
        $queue = $this->createMock(PersistentQueue::class);
        $queue->expects(self::never())->method('pushSinglePersistentMessage');
        $this->queue_factory->method('getPersistentQueue')->willReturn($queue);

        $this->getRunner()->scheduleIterationCreations();
    }

    public function testWhenThereIsNoQueueSystemItProcessesIterationCreationImmediately(): void
    {
        $this->queue_factory->method('getPersistentQueue')->willThrowException(
            new NoQueueSystemAvailableException('No queue system')
        );

        $this->getRunner()->scheduleIterationCreations(...$this->iteration_creations);

        self::assertTrue($this->logger->hasError('Unable to queue iteration mirrors creation for iteration #54'));
        self::assertTrue($this->logger->hasError('Unable to queue iteration mirrors creation for iteration #89'));
    }

    public function testWhenThereIsAProblemWithQueueItProcessesIterationCreationImmediately(): void
    {
        $queue = $this->createStub(PersistentQueue::class);
        $queue->method('pushSinglePersistentMessage')->willThrowException(
            new QueueServerConnectionException('Error with queue')
        );
        $this->queue_factory->method('getPersistentQueue')->willReturn($queue);

        $this->getRunner()->scheduleIterationCreations(...$this->iteration_creations);

        self::assertTrue($this->logger->hasError('Unable to queue iteration mirrors creation for iteration #54'));
        self::assertTrue($this->logger->hasError('Unable to queue iteration mirrors creation for iteration #89'));
    }
}
