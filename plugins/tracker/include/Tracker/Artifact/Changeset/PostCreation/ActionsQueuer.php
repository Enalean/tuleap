<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation;

use Exception;
use Psr\Log\LoggerInterface;
use Tracker_Artifact_Changeset;
use Tuleap\DB\ThereIsAnOngoingTransactionChecker;
use Tuleap\Queue\IsAsyncTaskProcessingAvailable;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\Worker;
use Tuleap\Queue\WorkerAvailability;

final class ActionsQueuer implements PostCreationActionsQueuer
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly QueueFactory $queue_factory,
        private readonly IsAsyncTaskProcessingAvailable $worker_availability,
    ) {
    }

    public static function build(LoggerInterface $logger): self
    {
        return new self(
            $logger,
            new QueueFactory($logger, new ThereIsAnOngoingTransactionChecker()),
            new WorkerAvailability(),
        );
    }

    public function queuePostCreation(Tracker_Artifact_Changeset $changeset, bool $send_notifications): void
    {
        if ($this->worker_availability->canProcessAsyncTasks()) {
            $this->queueForAsynchronousExecution($changeset, $send_notifications);
        } else {
            $this->executeNow($changeset, $send_notifications);
        }
    }

    private function queueForAsynchronousExecution(Tracker_Artifact_Changeset $changeset, bool $send_notifications): void
    {
        try {
            $queue = $this->queue_factory->getPersistentQueue(Worker::EVENT_QUEUE_NAME, QueueFactory::REDIS);
            $queue->pushSinglePersistentMessage(
                AsynchronousActionsRunner::TOPIC,
                [
                    'artifact_id'        => $changeset->getArtifact()->getId(),
                    'changeset_id'       => (int) $changeset->getId(),
                    'send_notifications' => $send_notifications,
                ]
            );
        } catch (Exception $exception) {
            $this->logger->error("Unable to queue notification for {$changeset->getId()}, fallback to online notif", ['exception' => $exception]);
            $this->executeNow($changeset, $send_notifications);
        }
    }

    private function executeNow(Tracker_Artifact_Changeset $changeset, bool $send_notifications): void
    {
        $runner = ActionsRunner::build($this->logger);
        $runner->processSyncPostCreationActions($changeset, $send_notifications);
    }
}
