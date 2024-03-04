<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Queue;

use Psr\Log\LoggerInterface;
use Tuleap\DB\ThereIsAnOngoingTransactionChecker;

final class EnqueueTask implements EnqueueTaskInterface
{
    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        if ($logger === null) {
            $this->logger = WorkerLogger::get();
        } else {
            $this->logger = $logger;
        }
    }

    public function enqueue(QueueTask $event): void
    {
        try {
            $this->logger->info($event->getPreEnqueueMessage());
            $queue_factory = new QueueFactory($this->logger, new ThereIsAnOngoingTransactionChecker());
            $queue         = $queue_factory->getPersistentQueue(Worker::EVENT_QUEUE_NAME, QueueFactory::REDIS);
            $queue->pushSinglePersistentMessage($event->getTopic(), $event->getPayload());
        } catch (\Exception $exception) {
            $this->logger->error(
                sprintf('Unable to enqueue in %s: %s (%s)', $event->getTopic(), $exception->getMessage(), $exception::class),
                ['exception' => $exception]
            );
        }
    }
}
