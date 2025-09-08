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

use Tuleap\ProgramManagement\Domain\Events\ProgramIncrementCreationEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\DispatchProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\Worker;

/**
 * I push a single Queue message to trigger the creation of mirrored Program Increments.
 */
final readonly class ProgramIncrementCreationDispatcher implements DispatchProgramIncrementCreation
{
    public function __construct(
        private QueueFactory $queue_factory,
    ) {
    }

    #[\Override]
    public function dispatchCreation(ProgramIncrementCreation $creation): void
    {
        $queue = $this->queue_factory->getPersistentQueue(Worker::EVENT_QUEUE_NAME);
        $queue->pushSinglePersistentMessage(
            ProgramIncrementCreationEvent::TOPIC,
            [
                'artifact_id'  => $creation->getProgramIncrement()->getId(),
                'user_id'      => $creation->getUser()->getId(),
                'changeset_id' => $creation->getChangeset()->getId(),
            ]
        );
    }
}
