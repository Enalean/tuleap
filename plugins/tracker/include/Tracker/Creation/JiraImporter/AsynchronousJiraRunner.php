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

use Tuleap\Queue\WorkerEvent;

class AsynchronousJiraRunner
{
    public const string TOPIC = 'tuleap.tracker.creation.jira';

    /**
     * @var JiraRunner
     */
    private $jira_runner;
    /**
     * @var PendingJiraImportDao
     */
    private $dao;
    /**
     * @var PendingJiraImportBuilder
     */
    private $builder;

    public function __construct(JiraRunner $jira_runner, PendingJiraImportDao $dao, PendingJiraImportBuilder $builder)
    {
        $this->jira_runner = $jira_runner;
        $this->dao         = $dao;
        $this->builder     = $builder;
    }

    public static function addListener(
        WorkerEvent $event,
        PendingJiraImportDao $dao,
        PendingJiraImportBuilder $builder,
        JiraRunner $jira_runner,
    ): void {
        if ($event->getEventName() !== self::TOPIC) {
            return;
        }

        $async_runner = new self(
            $jira_runner,
            $dao,
            $builder
        );
        $async_runner->process($event);
    }

    public function process(WorkerEvent $event): void
    {
        $message = $event->getPayload();
        if (! isset($message['pending_jira_import_id'])) {
            $event_name = $event->getEventName();
            $event->getLogger()->error("The payload for $event_name seems to be malformed");
            $event->getLogger()->debug("Malformed payload for $event_name: " . var_export($event->getPayload(), true));

            return;
        }

        $pending_import_row = $this->dao->searchById((int) $message['pending_jira_import_id']);
        if (! $pending_import_row) {
            $event->getLogger()->error(
                'Not able to process an event ' . $event->getEventName(
                ) . ', the pending jira import #' . $message['pending_jira_import_id'] . ' ' .
                'can not be found.'
            );

            return;
        }

        try {
            $pending_import = $this->builder->buildFromRow($pending_import_row);
            $this->jira_runner->processAsyncJiraImport($pending_import);
        } catch (UnableToBuildPendingJiraImportException $exception) {
            $event->getLogger()->error(
                'Not able to process an event ' . $event->getEventName(
                ) . ', the pending jira import #' . $message['pending_jira_import_id'] . ' ' .
                'can not be built: ' . $exception->getMessage()
            );
        }
    }
}
