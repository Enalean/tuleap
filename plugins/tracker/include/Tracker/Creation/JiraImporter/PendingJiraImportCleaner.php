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

use DateInterval;
use DateTimeImmutable;
use ProjectManager;
use Psr\Log\LoggerInterface;
use Tuleap\DB\DBTransactionExecutor;
use UserManager;

class PendingJiraImportCleaner
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var PendingJiraImportDao
     */
    private $dao;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var CancellationOfJiraImportNotifier
     */
    private $notifier;

    public function __construct(
        LoggerInterface $logger,
        PendingJiraImportDao $dao,
        DBTransactionExecutor $transaction_executor,
        ProjectManager $project_manager,
        UserManager $user_manager,
        CancellationOfJiraImportNotifier $notifier
    ) {
        $this->logger                    = $logger;
        $this->dao                       = $dao;
        $this->transaction_executor      = $transaction_executor;
        $this->project_manager           = $project_manager;
        $this->user_manager              = $user_manager;
        $this->notifier                  = $notifier;
    }

    public function deleteDanglingPendingJiraImports(DateTimeImmutable $current_time): void
    {
        $this->logger->info('Deleting dangling pending jira imports.');
        $this->transaction_executor->execute(
            function () use ($current_time): void {
                $expiration_timestamp = $current_time->sub(new DateInterval('P1D'))->getTimestamp();

                $expired_imports = $this->dao->searchExpiredImports($expiration_timestamp);
                $this->logger->info('Found ' . count($expired_imports) . ' expired imports.');
                foreach ($expired_imports as $import) {
                    $this->warnUserAboutDeletion($import);
                }
                $this->dao->deleteExpiredImports($expiration_timestamp);
            }
        );
    }

    /**
     * @param array{project_id: int, user_id: int, created_on: int, jira_server: string, jira_project_id: string, jira_issue_type_name: string, tracker_name: string, tracker_shortname: string} $expired_import
     */
    private function warnUserAboutDeletion(array $expired_import): void
    {
        $project = $this->project_manager->getProject($expired_import['project_id']);
        if ($project->isError() || ! $project->isActive()) {
            return;
        }

        $user = $this->user_manager->getUserById($expired_import['user_id']);
        if (! $user || ! $user->isAlive()) {
            return;
        }

        $this->notifier->warnUserAboutDeletion(PendingJiraImport::buildFromRow($project, $user, $expired_import));
    }
}
