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
use Tuleap\ProgramManagement\Adapter\JSON\PendingProgramIncrementUpdateRepresentation;
use Tuleap\ProgramManagement\Domain\Events\ProgramIncrementUpdateEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\BuildIterationCreationProcessor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\BuildProgramIncrementUpdateProcessor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\DispatchProgramIncrementUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationCreation;
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
        private BuildProgramIncrementUpdateProcessor $update_processor_builder,
        private BuildIterationCreationProcessor $iteration_processor_builder,
    ) {
    }

    public function dispatchUpdate(ProgramIncrementUpdate $update, IterationCreation ...$creations): void
    {
        $representation = PendingProgramIncrementUpdateRepresentation::fromUpdateAndCreations($update, ...$creations);
        try {
            $queue = $this->queue_factory->getPersistentQueue(Worker::EVENT_QUEUE_NAME, QueueFactory::REDIS);
            $queue->pushSinglePersistentMessage(ProgramIncrementUpdateEvent::TOPIC, $representation);
        } catch (NoQueueSystemAvailableException | QueueServerConnectionException $exception) {
            $this->processUpdateSynchronously($exception, $update, ...$creations);
        }
    }

    private function processUpdateSynchronously(
        \Exception $exception,
        ProgramIncrementUpdate $update,
        IterationCreation ...$creations,
    ): void {
        $this->logger->error(
            sprintf(
                'Unable to queue program increment mirrors update for program increment #%d',
                $update->getProgramIncrement()->getId()
            ),
            ['exception' => $exception]
        );
        $update_processor = $this->update_processor_builder->getProcessor();
        $update_processor->processUpdate($update);
        $iteration_processor = $this->iteration_processor_builder->getProcessor();
        foreach ($creations as $iteration_creation) {
            $iteration_processor->processCreation($iteration_creation);
        }
    }
}
