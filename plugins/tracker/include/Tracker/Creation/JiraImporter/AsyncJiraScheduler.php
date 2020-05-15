<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

use Project;
use Psr\Log\LoggerInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Exception\CannotPerformIOOperationException;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;
use Tuleap\Tracker\Creation\TrackerCreationHasFailedException;

class AsyncJiraScheduler
{
    /**
     * @var KeyFactory
     */
    private $key_factory;
    /**
     * @var PendingJiraImportDao
     */
    private $dao;
    /**
     * @var JiraRunner
     */
    private $jira_runner;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        LoggerInterface $logger,
        KeyFactory $key_factory,
        PendingJiraImportDao $dao,
        JiraRunner $jira_runner
    ) {
        $this->key_factory = $key_factory;
        $this->dao         = $dao;
        $this->jira_runner = $jira_runner;
        $this->logger      = $logger;
    }

    /**
     * @throws TrackerCreationHasFailedException
     */
    public function scheduleCreation(
        Project $project,
        \PFUser $user,
        string $jira_server,
        string $jira_user_email,
        ConcealedString $jira_token,
        string $jira_project_id,
        string $jira_issue_type_name,
        string $tracker_name,
        string $tracker_shortname,
        string $tracker_color,
        string $tracker_description
    ): void {
        if (! $this->jira_runner->canBeProcessedAsynchronously()) {
            $this->logger->error('Unable to schedule the import of Jira: misconfiguration of the platform to queue the event.');
            throw new TrackerCreationHasFailedException('Unable to schedule the import of Jira');
        }

        try {
            $encryption_key = $this->key_factory->getEncryptionKey();
        } catch (CannotPerformIOOperationException $exception) {
            $this->logger->error('Unable to schedule the import of Jira: ' . $exception->getMessage());
            throw new TrackerCreationHasFailedException('Unable to schedule the import of Jira');
        }

        $id = $this->dao->create(
            (int) $project->getID(),
            (int) $user->getId(),
            $jira_server,
            $jira_user_email,
            SymmetricCrypto::encrypt($jira_token, $encryption_key),
            $jira_project_id,
            $jira_issue_type_name,
            $tracker_name,
            $tracker_shortname,
            $tracker_color,
            $tracker_description
        );
        if (! $id) {
            $this->logger->error(
                'Unable to schedule the import of Jira: the pending jira import cannot be saved in DB.'
            );
            throw new TrackerCreationHasFailedException('Unable to schedule the import of Jira');
        }
        $this->jira_runner->queueJiraImportEvent($id);
    }
}
