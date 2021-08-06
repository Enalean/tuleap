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
use Tuleap\ProgramManagement\Adapter\Events\IterationCreationEventProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\NewPendingIterationCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\RunIterationsCreation;
use Tuleap\Queue\NoQueueSystemAvailableException;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\QueueServerConnectionException;
use Tuleap\Queue\Worker;

final class IterationCreationsRunner implements RunIterationsCreation
{
    private LoggerInterface $logger;
    private QueueFactory $queue_factory;

    public function __construct(LoggerInterface $logger, QueueFactory $queue_factory)
    {
        $this->logger        = $logger;
        $this->queue_factory = $queue_factory;
    }

    public function scheduleIterationCreations(NewPendingIterationCreation ...$creations): void
    {
        try {
            $queue = $this->queue_factory->getPersistentQueue(Worker::EVENT_QUEUE_NAME, QueueFactory::REDIS);
        } catch (NoQueueSystemAvailableException $exception) {
            foreach ($creations as $iteration_creation) {
                $this->processCreationSynchronously($exception, $iteration_creation);
            }
            return;
        }
        foreach ($creations as $iteration_creation) {
            try {
                $queue->pushSinglePersistentMessage(
                    IterationCreationEventProxy::TOPIC,
                    [
                        'artifact_id' => $iteration_creation->iteration->id,
                        'user_id'     => $iteration_creation->user->id,
                    ]
                );
            } catch (QueueServerConnectionException $exception) {
                $this->processCreationSynchronously($exception, $iteration_creation);
            }
        }
    }

    private function processCreationSynchronously(
        \Exception $exception,
        NewPendingIterationCreation $creation
    ): void {
        $iteration_id = $creation->iteration->id;
        $this->logger->error(
            "Unable to queue iteration mirrors creation for iteration #{$iteration_id}",
            ['exception' => $exception]
        );
        $this->processIterationCreation($iteration_id, $creation->user->id);
    }

    public function addListener(?IterationCreationEventProxy $event): void
    {
        if (! $event) {
            return;
        }
        $this->processIterationCreation($event->artifact_id, $event->user_id);
    }

    private function processIterationCreation(int $iteration_id, int $user_id): void
    {
        $this->logger->debug("Processing iteration creation with iteration #$iteration_id for user #$user_id");
    }
}
