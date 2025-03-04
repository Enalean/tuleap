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
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementCreationBuilder;
use Tuleap\Queue\PersistentQueue;
use Tuleap\Queue\QueueFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramIncrementCreationDispatcherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_ID = 18;
    private const USER_ID              = 120;
    private const CHANGESET_ID         = 4043;
    /**
     * @var Stub&QueueFactory
     */
    private $queue_factory;
    private ProgramIncrementCreation $creation;

    protected function setUp(): void
    {
        $this->queue_factory = $this->createStub(QueueFactory::class);

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
            $this->queue_factory,
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
}
