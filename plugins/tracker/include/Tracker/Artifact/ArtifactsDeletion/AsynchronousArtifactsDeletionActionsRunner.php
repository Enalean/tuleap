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
    /**
     * @var PendingArtifactRemovalDao
     */
    private $pending_artifact_removal_dao;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var QueueFactory
     */
    private $queue_factory;
    /**
     * @var ArchiveAndDeleteArtifactTaskBuilder
     */
    private $task_builder;

    public function __construct(
        PendingArtifactRemovalDao $pending_artifact_removal_dao,
        LoggerInterface $logger,
        \UserManager $user_manager,
        QueueFactory $queue_factory,
        private IsAsyncTaskProcessingAvailable $worker_availability,
        ArchiveAndDeleteArtifactTaskBuilder $task_builder,
    ) {
        $this->pending_artifact_removal_dao = $pending_artifact_removal_dao;
        $this->logger                       = $logger;
        $this->user_manager                 = $user_manager;
        $this->queue_factory                = $queue_factory;
        $this->task_builder                 = $task_builder;
    }

    public function addListener(WorkerEvent $event)
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

            $this->processArchiveAndArtifactDeletion($artifact, $user);
        }
    }

    private function processArchiveAndArtifactDeletion(Artifact $artifact, PFUser $user): void
    {
        $task = $this->task_builder->build($this->logger);

        $task->archive($artifact, $user);
    }

    public function executeArchiveAndArtifactDeletion(Artifact $artifact, PFUser $user): void
    {
        if (! $this->worker_availability->canProcessAsyncTasks()) {
            $this->processArchiveAndArtifactDeletion($artifact, $user);
            return;
        }

        try {
            $queue = $this->queue_factory->getPersistentQueue(Worker::EVENT_QUEUE_NAME, QueueFactory::REDIS);
            $queue->pushSinglePersistentMessage(
                self::TOPIC,
                [
                    'artifact_id' => (int) $artifact->getId(),
                    'user_id'     => (int) $user->getId(),
                ]
            );
        } catch (Exception $exception) {
            $this->logger->error("Unable to queue deletion for {$artifact->getId()}");
            $this->processArchiveAndArtifactDeletion($artifact, $user);
        }
    }
}
