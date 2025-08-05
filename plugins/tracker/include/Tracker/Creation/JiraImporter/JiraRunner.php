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
use SodiumException;
use Tracker_Exception;
use Tuleap\Cryptography\Exception\CannotPerformIOOperationException;
use Tuleap\Cryptography\Exception\InvalidCiphertextException;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\SymmetricLegacy2025\SymmetricCrypto;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\Worker;
use Tuleap\Tracker\Creation\JiraImporter\Import\ImportNotifier\JiraErrorImportNotifier;
use Tuleap\Tracker\Creation\JiraImporter\Import\ImportNotifier\JiraSuccessImportNotifier;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserOnTuleapCache;
use Tuleap\Tracker\Creation\TrackerCreationHasFailedException;
use Tuleap\Tracker\TrackerIsInvalidException;
use Tuleap\User\CurrentUserWithLoggedInInformation;
use UserManager;
use XML_ParseException;

class JiraRunner
{
    public function __construct(
        private LoggerInterface $logger,
        private QueueFactory $queue_factory,
        private KeyFactory $key_factory,
        private FromJiraTrackerCreator $tracker_creator,
        private PendingJiraImportDao $dao,
        private JiraSuccessImportNotifier $success_notifier,
        private JiraErrorImportNotifier $error_notifier,
        private UserManager $user_manager,
        private JiraUserOnTuleapCache $jira_user_on_tuleap_cache,
        private ClientWrapperBuilder $jira_client_builder,
    ) {
    }

    public function queueJiraImportEvent(int $pending_jira_import_id): void
    {
        $queue = $this->getPersistentQueue();
        $queue->pushSinglePersistentMessage(
            AsynchronousJiraRunner::TOPIC,
            [
                'pending_jira_import_id' => $pending_jira_import_id,
            ]
        );
    }

    private function getPersistentQueue(): \Tuleap\Queue\PersistentQueue
    {
        return $this->queue_factory->getPersistentQueue(Worker::EVENT_QUEUE_NAME);
    }

    public function processAsyncJiraImport(PendingJiraImport $pending_import): void
    {
        try {
            $this->dao->deleteById($pending_import->getId());

            $user = $this->user_manager->forceLogin($pending_import->getUser()->getUserName());
            if (! $user->isAlive()) {
                $this->logger->error('Unable to log in as the user who originated the event');
                return;
            }

            $token = SymmetricCrypto::decrypt(
                $pending_import->getEncryptedJiraToken(),
                $this->key_factory->getEncryptionKey()
            );

            $jira_credentials = new JiraCredentials(
                $pending_import->getJiraServer(),
                $pending_import->getJiraUser(),
                $token
            );

            $jira_client = $this->jira_client_builder->build($jira_credentials, $this->logger);

            $tracker = $this->tracker_creator->createFromJira(
                $pending_import->getProject(),
                $pending_import->getTrackerName(),
                $pending_import->getTrackerShortname(),
                $pending_import->getTrackerDescription(),
                $pending_import->getTrackerColor(),
                $jira_credentials,
                $jira_client,
                $pending_import->getJiraProjectId(),
                $pending_import->getJiraIssueTypeId(),
                $pending_import->getUser()
            );
            $this->success_notifier->warnUserAboutSuccess($pending_import, $tracker, $this->jira_user_on_tuleap_cache);
        } catch (InvalidCiphertextException | CannotPerformIOOperationException | SodiumException $exception) {
            $message = $exception->getMessage();
            if ($message) {
                $this->logger->error($message);
            }
            $this->logError($pending_import, 'Unable to access to the token to do the import.');
        } catch (XML_ParseException $exception) {
            $this->logError($pending_import, 'Unable to parse the XML used to import from Jira.');
        } catch (JiraConnectionException $exception) {
            $this->logError($pending_import, $exception->getI18nMessage());
        } catch (Tracker_Exception | TrackerCreationHasFailedException | TrackerIsInvalidException $exception) {
            $this->logError($pending_import, $exception->getMessage());
        } finally {
            $this->user_manager->setCurrentUser(CurrentUserWithLoggedInInformation::fromAnonymous($this->user_manager));
        }
    }

    private function logError(PendingJiraImport $pending_import, string $message): void
    {
        $this->logger->error($message);
        $this->error_notifier->warnUserAboutError($pending_import, $message);
    }
}
