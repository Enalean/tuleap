<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

use PHPUnit\Framework\MockObject\Stub;
use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementCreationBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ProcessProgramIncrementCreationStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramIncrementCreationProcessorStub;
use Tuleap\Queue\NoQueueSystemAvailableException;
use Tuleap\Queue\PersistentQueue;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\QueueServerConnectionException;

final class ProgramIncrementCreationDispatcherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_ID = 18;
    private const USER_ID              = 120;
    private const CHANGESET_ID         = 4043;
    private TestLogger $logger;
    /**
     * @var Stub&QueueFactory
     */
    private $queue_factory;
    private ProcessProgramIncrementCreationStub $processor;
    private ProgramIncrementCreation $creation;

    protected function setUp(): void
    {
        $this->logger        = new TestLogger();
        $this->queue_factory = $this->createStub(QueueFactory::class);
        $this->processor     = ProcessProgramIncrementCreationStub::withCount();

        $this->creation = ProgramIncrementCreationBuilder::buildWithIds(
            self::USER_ID,
            self::PROGRAM_INCREMENT_ID,
            73,
            self::CHANGESET_ID
        );
    }

    private function getDispatcher(): ProgramIncrementCreationDispatcher
    {
        return new ProgramIncrementCreationDispatcher(
            $this->logger,
            $this->queue_factory,
            BuildProgramIncrementCreationProcessorStub::withProcessor($this->processor)
        );
    }

    public function testItDispatchesAMessageForProgramIncrementCreation(): void
    {
        $queue = $this->createMock(PersistentQueue::class);
        $this->queue_factory->method('getPersistentQueue')->willReturn($queue);

        $queue->expects(self::once())
            ->method('pushSinglePersistentMessage')
            ->with(
                'tuleap.program_management.program_increment.creation',
                [
                    'artifact_id'  => self::PROGRAM_INCREMENT_ID,
                    'user_id'      => self::USER_ID,
                    'changeset_id' => self::CHANGESET_ID,
                ]
            );

        $this->getDispatcher()->dispatchCreation($this->creation);
    }

    public function testWhenThereIsNoQueueSystemItProcessesCreationImmediately(): void
    {
        $this->queue_factory->method('getPersistentQueue')->willThrowException(
            new NoQueueSystemAvailableException('No queue system')
        );

        $this->getDispatcher()->dispatchCreation($this->creation);

        self::assertSame(1, $this->processor->getCallCount());
        self::assertTrue(
            $this->logger->hasError(
                sprintf(
                    'Unable to queue program increment mirrors creation for program increment #%d',
                    self::PROGRAM_INCREMENT_ID
                )
            )
        );
    }

    public function testWhenThereIsAProblemWithQueueItProcessesCreationImmediately(): void
    {
        $queue = $this->createStub(PersistentQueue::class);
        $queue->method('pushSinglePersistentMessage')->willThrowException(
            new QueueServerConnectionException('Error with queue')
        );
        $this->queue_factory->method('getPersistentQueue')->willReturn($queue);

        $this->getDispatcher()->dispatchCreation($this->creation);

        self::assertSame(1, $this->processor->getCallCount());
        self::assertTrue(
            $this->logger->hasError(
                sprintf(
                    'Unable to queue program increment mirrors creation for program increment #%d',
                    self::PROGRAM_INCREMENT_ID
                )
            )
        );
    }
}
