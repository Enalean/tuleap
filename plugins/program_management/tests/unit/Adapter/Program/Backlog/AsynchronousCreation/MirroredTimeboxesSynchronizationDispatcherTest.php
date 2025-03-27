<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
use Tuleap\ProgramManagement\Tests\Stub\CommandTeamSynchronizationStub;
use Tuleap\Queue\PersistentQueue;
use Tuleap\Queue\QueueFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MirroredTimeboxesSynchronizationDispatcherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_ID = 1;
    private const TEAM_ID    = 123;
    private const USER_ID    = 456;

    /**
     * @var Stub&QueueFactory
     */
    private $queue_factory;
    private CommandTeamSynchronizationStub $command;

    protected function setUp(): void
    {
        $this->queue_factory = $this->createStub(QueueFactory::class);

        $this->command = CommandTeamSynchronizationStub::withProgramAndTeamIdsAndUserId(
            self::PROGRAM_ID,
            self::TEAM_ID,
            self::USER_ID
        );
    }

    private function getDispatcher(): MirroredTimeboxesSynchronizationDispatcher
    {
        return new MirroredTimeboxesSynchronizationDispatcher(
            $this->queue_factory,
        );
    }

    public function testDispatchSynchronizationCommand(): void
    {
        $queue = $this->createMock(PersistentQueue::class);
        $this->queue_factory->method('getPersistentQueue')->willReturn($queue);

        $queue->expects($this->once())
            ->method('pushSinglePersistentMessage')
            ->with(
                'tuleap.program_management.team.synchronize',
                [
                    'program_id' => self::PROGRAM_ID,
                    'team_id' => self::TEAM_ID,
                    'user_id' => self::USER_ID,
                ]
            );

        $this->getDispatcher()->dispatchSynchronizationCommand($this->command);
    }
}
