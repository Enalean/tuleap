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

use Exception;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\RunProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\BuildReplicationData;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\ReplicationData;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\Worker;
use Tuleap\Queue\WorkerEvent;

final class CreateProgramIncrementsRunner implements RunProgramIncrementCreation
{
    private const TOPIC = 'tuleap.program_management.program_increment.creation';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var QueueFactory
     */
    private $queue_factory;
    /**
     * @var BuildReplicationData
     */
    private $replication_data_adapter;
    /**
     * @var TaskBuilder
     */
    private $task_builder;


    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        QueueFactory $queue_factory,
        BuildReplicationData $replication_data_adapter,
        TaskBuilder $task_builder
    ) {
        $this->logger                   = $logger;
        $this->queue_factory            = $queue_factory;
        $this->replication_data_adapter = $replication_data_adapter;
        $this->task_builder             = $task_builder;
    }

    /**
     * @throw ProgramIncrementCreationException
     */
    public function addListener(WorkerEvent $event): void
    {
        if ((string) $event->getEventName() === self::TOPIC) {
            $message = $event->getPayload();

            $replication_data = $this->replication_data_adapter->buildFromArtifactAndUserId(
                $message['artifact_id'],
                $message['user_id']
            );

            if ($replication_data === null) {
                return;
            }

            $this->processProgramIncrementCreation($replication_data);
        }
    }

    public function processProgramIncrementCreation(ReplicationData $replication_data): void
    {
        $task = $this->task_builder->build();
        $task->createProgramIncrements($replication_data);
    }

    public function executeProgramIncrementsCreation(ReplicationData $replication_data): void
    {
        $artifact_id = $replication_data->getArtifact()->getId();
        try {
            $queue = $this->queue_factory->getPersistentQueue(Worker::EVENT_QUEUE_NAME, QueueFactory::REDIS);
            $queue->pushSinglePersistentMessage(
                self::TOPIC,
                [
                    'artifact_id' => (int) $artifact_id,
                    'user_id'     => (int) $replication_data->getUser()->getId(),
                ]
            );
        } catch (Exception $exception) {
            $this->logger->error("Unable to queue artifact mirrors creation for artifact #{$artifact_id}", ['exception' => $exception]);


            $this->processProgramIncrementCreation($replication_data);
        }
    }
}
