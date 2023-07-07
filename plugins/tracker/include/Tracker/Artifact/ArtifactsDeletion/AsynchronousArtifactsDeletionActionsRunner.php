<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ArtifactsDeletion;

use Exception;
use PFUser;
use Psr\Log\LoggerInterface;
use Tuleap\Queue\IsAsyncTaskProcessingAvailable;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\Worker;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Tracker\Artifact\Artifact;

class AsynchronousArtifactsDeletionActionsRunner
{
    public const TOPIC = 'tuleap.tracker.artifact.deletion';


    public function __construct(
        private readonly PendingArtifactRemovalDao $pending_artifact_removal_dao,
        private readonly LoggerInterface $logger,
        private readonly \UserManager $user_manager,
        private readonly QueueFactory $queue_factory,
        private readonly IsAsyncTaskProcessingAvailable $worker_availability,
        private readonly ArchiveAndDeleteArtifactTaskBuilder $task_builder,
    ) {
    }

    public function addListener(WorkerEvent $event): void
    {
        if ($event->getEventName() === self::TOPIC) {
            $message = $event->getPayload();

            $pending_artifact = $this->pending_artifact_removal_dao->getPendingArtifactById($message['artifact_id']);
            $artifact         = new Artifact(
                $pending_artifact['id'],
                $pending_artifact['tracker_id'],
                $pending_artifact['submitted_by'],
                (int) $pending_artifact['submitted_on'],
                $pending_artifact['use_artifact_permissions']
            );

            $user = $this->user_manager->getUserById($message['user_id']);
            if (! $user) {
                return;
            }

            $context = DeletionContext::regularDeletion((int) $message['source_project_id']);
            if ($message["context"] === DeletionContext::MOVE_TYPE) {
                $context = DeletionContext::moveContext((int) $message['source_project_id'], (int) $message['destination_project_id']);
            }

            $this->processArchiveAndArtifactDeletion($artifact, $user, $context);
        }
    }

    private function processArchiveAndArtifactDeletion(Artifact $artifact, PFUser $user, DeletionContext $context): void
    {
        $task = $this->task_builder->build($this->logger);

        $task->archive($artifact, $user, $context);
    }

    public function executeArchiveAndArtifactDeletion(Artifact $artifact, PFUser $user, DeletionContext $context): void
    {
        if (! $this->worker_availability->canProcessAsyncTasks()) {
            $this->processArchiveAndArtifactDeletion($artifact, $user, $context);
            return;
        }

        try {
            $queue = $this->queue_factory->getPersistentQueue(Worker::EVENT_QUEUE_NAME, QueueFactory::REDIS);
            $queue->pushSinglePersistentMessage(
                self::TOPIC,
                [
                    'artifact_id' => $artifact->getId(),
                    'user_id' => (int) $user->getId(),
                    'source_project_id' => $context->getSourceProjectId(),
                    'destination_project_id' => $context->getDestinationProjectId(),
                    'context' => $context->getType(),
                ]
            );
        } catch (Exception $exception) {
            $this->logger->error("Unable to queue deletion for {$artifact->getId()}");
            $this->processArchiveAndArtifactDeletion($artifact, $user, $context);
        }
    }
}
