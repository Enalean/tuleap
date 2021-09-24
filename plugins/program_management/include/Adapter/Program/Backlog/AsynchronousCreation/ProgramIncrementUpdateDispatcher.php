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

use Psr\Log\LoggerInterface;
use Tuleap\ProgramManagement\Domain\Events\ProgramIncrementUpdateEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\DispatchProgramIncrementUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProcessIterationCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProcessProgramIncrementUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\Queue\NoQueueSystemAvailableException;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\QueueServerConnectionException;
use Tuleap\Queue\Worker;

/**
 * I push a single Queue message to trigger the update of mirrored Program Increments and the
 * creation of mirrored Iterations. It must be a single message because both of those operations
 * will modify the mirrored program increments (for artifact links). If we do them in parallel,
 * due to the way changesets are stored, we will lose modifications.
 */
final class ProgramIncrementUpdateDispatcher implements DispatchProgramIncrementUpdate
{
    public function __construct(
        private LoggerInterface $logger,
        private QueueFactory $queue_factory,
        private ProcessProgramIncrementUpdate $update_processor,
        private ProcessIterationCreation $iteration_processor,
    ) {
    }

    public function dispatchUpdate(ProgramIncrementUpdate $update, IterationCreation ...$creations): void
    {
        try {
            $queue = $this->queue_factory->getPersistentQueue(Worker::EVENT_QUEUE_NAME, QueueFactory::REDIS);
            $queue->pushSinglePersistentMessage(
                ProgramIncrementUpdateEvent::TOPIC,
                [
                    'artifact_id'  => $update->getProgramIncrement()->getId(),
                    'user_id'      => $update->getUser()->getId(),
                    'changeset_id' => $update->getChangeset()->getId()
                ]
            );
        } catch (NoQueueSystemAvailableException | QueueServerConnectionException $exception) {
            $this->processUpdateSynchronously($exception, $update, ...$creations);
        }
    }

    private function processUpdateSynchronously(
        \Exception $exception,
        ProgramIncrementUpdate $update,
        IterationCreation ...$creations
    ): void {
        $this->logger->error(
            sprintf(
                'Unable to queue program increment mirrors update for program increment #%d',
                $update->getProgramIncrement()->getId()
            ),
            ['exception' => $exception]
        );
        $this->update_processor->processProgramIncrementUpdate($update);
        foreach ($creations as $iteration_creation) {
            $this->iteration_processor->processIterationCreation($iteration_creation);
        }
    }
}
