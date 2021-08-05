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
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProcessIterationCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\RunIterationsCreation;
use Tuleap\Queue\NoQueueSystemAvailableException;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\QueueServerConnectionException;
use Tuleap\Queue\Worker;

final class IterationCreationsRunner implements RunIterationsCreation
{
    private LoggerInterface $logger;
    private QueueFactory $queue_factory;
    private ProcessIterationCreation $iteration_creator;

    public function __construct(
        LoggerInterface $logger,
        QueueFactory $queue_factory,
        ProcessIterationCreation $iteration_creator
    ) {
        $this->logger            = $logger;
        $this->queue_factory     = $queue_factory;
        $this->iteration_creator = $iteration_creator;
    }

    public function scheduleIterationCreations(IterationCreation ...$creations): void
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
        IterationCreation $creation
    ): void {
        $iteration_id = $creation->iteration->id;
        $this->logger->error(
            "Unable to queue iteration mirrors creation for iteration #{$iteration_id}",
            ['exception' => $exception]
        );
        $this->iteration_creator->processIterationCreation($creation);
    }
}
