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
use Psr\Log\LoggerInterface;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Tracker\Creation\JiraImporter\Import\ImportNotifier\CancellationOfJiraImportNotifier;

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
     * @var CancellationOfJiraImportNotifier
     */
    private $notifier;
    /**
     * @var PendingJiraImportBuilder
     */
    private $builder;

    public function __construct(
        LoggerInterface $logger,
        PendingJiraImportDao $dao,
        DBTransactionExecutor $transaction_executor,
        PendingJiraImportBuilder $builder,
        CancellationOfJiraImportNotifier $notifier
    ) {
        $this->logger               = $logger;
        $this->dao                  = $dao;
        $this->transaction_executor = $transaction_executor;
        $this->builder              = $builder;
        $this->notifier             = $notifier;
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
                    try {
                        $pending_jira_import = $this->builder->buildFromRow($import);
                        $this->notifier->warnUserAboutDeletion($pending_jira_import);
                    } catch (UnableToBuildPendingJiraImportException $exception) {
                        // silently ignore it
                    }
                }
                $this->dao->deleteExpiredImports($expiration_timestamp);
            }
        );
    }
}
