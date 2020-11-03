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

namespace Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation;

use BackendLogger;
use Exception;
use Tracker_Artifact_ChangesetFactoryBuilder;
use Tracker_ArtifactFactory;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\Worker;
use Tuleap\Queue\WorkerEvent;
use Tuleap\ScaledAgile\Adapter\Program\ReplicationDataAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\ReplicationData;
use UserManager;

class CreateProgramIncrementsRunner
{

    private const TOPIC = 'tuleap.tracker.artifact.creation';
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var QueueFactory
     */
    private $queue_factory;
    /**
     * @var ReplicationDataAdapter
     */
    private $replication_data_adapter;


    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        QueueFactory $queue_factory,
        ReplicationDataAdapter $replication_data_adapter
    ) {
        $this->logger                   = $logger;
        $this->queue_factory            = $queue_factory;
        $this->replication_data_adapter = $replication_data_adapter;
    }

    public static function build(): self
    {
        $logger = BackendLogger::getDefaultLogger("scaled_agile_syslog");

        return new self(
            $logger,
            new QueueFactory($logger),
            new ReplicationDataAdapter(
                Tracker_ArtifactFactory::instance(),
                UserManager::instance(),
                new PendingArtifactCreationDao(),
                Tracker_Artifact_ChangesetFactoryBuilder::build()
            )
        );
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

            $this->processProgramIncrementCreation($replication_data);
        }
    }

    private function processProgramIncrementCreation(ReplicationData $replication_data): void
    {
        $task = CreateProgramIncrementsTask::build();
        $task->createProgramIncrements($replication_data);
    }

    public function executeProgramIncrementsCreation(ReplicationData $replication_data): void
    {
        $artifact_id = $replication_data->getArtifactData()->getId();
        try {
            $queue       = $this->queue_factory->getPersistentQueue(Worker::EVENT_QUEUE_NAME, QueueFactory::REDIS);
            $queue->pushSinglePersistentMessage(
                self::TOPIC,
                [
                    'artifact_id' => (int) $artifact_id,
                    'user_id'     => (int) $replication_data->getUser()->getId(),
                ]
            );
        } catch (Exception $exception) {
            $this->logger->error("Unable to queue artifact mirrors creation for artifact #{$artifact_id}");


            $this->processProgramIncrementCreation($replication_data);
        }
    }
}
