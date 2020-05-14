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

namespace Tuleap\Tracker\Creation\JiraImporter;

use Psr\Log\LoggerInterface;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\Worker;

class JiraRunner
{
    /**
     * @var QueueFactory
     */
    private $queue_factory;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var PendingJiraImportDao
     */
    private $dao;

    public function __construct(
        LoggerInterface $logger,
        QueueFactory $queue_factory,
        PendingJiraImportDao $dao
    ) {
        $this->logger           = $logger;
        $this->queue_factory    = $queue_factory;
        $this->dao              = $dao;
    }

    public function queueJiraImportEvent(int $pending_jira_import_id): void
    {
        try {
            $queue = $this->queue_factory->getPersistentQueue(Worker::EVENT_QUEUE_NAME, QueueFactory::REDIS);
            $queue->pushSinglePersistentMessage(
                AsynchronousJiraRunner::TOPIC,
                [
                    'pending_jira_import_id' => $pending_jira_import_id,
                ]
            );
        } catch (\Exception $exception) {
            $this->logger->error("Unable to queue notification for Jira import #{$pending_jira_import_id}.");
        }
    }

    public function processAsyncJiraImport(PendingJiraImport $pending_import): void
    {
        $this->dao->deleteById($pending_import->getId());
        $this->logger->error('Not implemented yet');
    }
}
