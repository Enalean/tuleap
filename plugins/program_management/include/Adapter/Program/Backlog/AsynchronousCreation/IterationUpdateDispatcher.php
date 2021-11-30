<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Psr\Log\LoggerInterface;
use Tuleap\ProgramManagement\Adapter\JSON\PendingIterationUpdateRepresentation;
use Tuleap\ProgramManagement\Domain\Events\IterationUpdateEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\BuildIterationUpdateProcessor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\DispatchIterationUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationUpdate;
use Tuleap\Queue\NoQueueSystemAvailableException;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\QueueServerConnectionException;
use Tuleap\Queue\Worker;

final class IterationUpdateDispatcher implements DispatchIterationUpdate
{

    public function __construct(
        private LoggerInterface $logger,
        private BuildIterationUpdateProcessor $processor_builder,
        private QueueFactory $queue_factory,
    ) {
    }

    public function dispatchUpdate(IterationUpdate $update): void
    {
        $representation = PendingIterationUpdateRepresentation::fromIterationUpdate($update);
        try {
            $this->processUpdateAsynchronously($representation);
        } catch (NoQueueSystemAvailableException | QueueServerConnectionException $e) {
            $this->processUpdateSynchronously($update, $e);
        }
    }

    private function processUpdateSynchronously(
        IterationUpdate $update,
        \Exception $exception,
    ): void {
        $this->logger->error(
            sprintf(
                'Unable to queue iteration mirrors update for Iteration #%d',
                $update->getIteration()->getId()
            ),
            ['exception' => $exception]
        );
        $processor = $this->processor_builder->getProcessor();
        $processor->processUpdate($update);
    }

    /**
     * @throws NoQueueSystemAvailableException
     * @throws QueueServerConnectionException
     */
    private function processUpdateAsynchronously(PendingIterationUpdateRepresentation $representation): void
    {
        $queue = $this->queue_factory->getPersistentQueue(Worker::EVENT_QUEUE_NAME, QueueFactory::REDIS);
        $queue->pushSinglePersistentMessage(IterationUpdateEvent::TOPIC, $representation);
    }
}
