<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation;

use ConfigNotificationAssignedTo;
use ConfigNotificationAssignedToDao;
use Exception;
use ForgeConfig;
use Psr\Log\LoggerInterface;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_MailGateway_RecipientFactory;
use Tracker_FormElementFactory;
use Tracker_GlobalNotificationDao;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Mail\MailLogger;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\Worker;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfigDao;
use Tuleap\Tracker\Notifications\InvolvedNotificationDao;
use Tuleap\Tracker\Notifications\RecipientsManager;
use Tuleap\Tracker\Notifications\ConfigNotificationEmailCustomSender;
use Tuleap\Tracker\Notifications\ConfigNotificationEmailCustomSenderDao;
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettingsRetriever;
use Tuleap\Tracker\Notifications\UnsubscribersNotificationDAO;
use Tuleap\Tracker\Notifications\UserNotificationOnlyStatusChangeDAO;
use Tuleap\Tracker\Webhook\WebhookDao;
use Tuleap\Tracker\Webhook\WebhookFactory;
use Tuleap\Tracker\Webhook\WebhookStatusLogger;
use Tuleap\Webhook\Emitter as WebhookEmitter;
use UserHelper;
use UserManager;
use WrapperLogger;

class ActionsRunner
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ActionsRunnerDao
     */
    private $actions_runner_dao;
    /**
     * @var QueueFactory
     */
    private $queue_factory;
    /**
     * @var PostCreationTask[]
     */
    private $post_creation_tasks;

    public function __construct(
        LoggerInterface $logger,
        ActionsRunnerDao $actions_runner_dao,
        QueueFactory $queue_factory,
        PostCreationTask ...$post_creation_tasks
    ) {
        $this->logger              = new WrapperLogger($logger, self::class);
        $this->actions_runner_dao  = $actions_runner_dao;
        $this->queue_factory       = $queue_factory;
        $this->post_creation_tasks = $post_creation_tasks;
    }

    public static function build(LoggerInterface $logger)
    {
        $webhook_dao = new WebhookDao();

        return new ActionsRunner(
            $logger,
            new ActionsRunnerDao(),
            new QueueFactory($logger),
            new ClearArtifactChangesetCacheTask(),
            new EmailNotificationTask(
                new MailLogger(),
                UserHelper::instance(),
                new RecipientsManager(
                    Tracker_FormElementFactory::instance(),
                    UserManager::instance(),
                    new UnsubscribersNotificationDAO,
                    new UserNotificationSettingsRetriever(
                        new Tracker_GlobalNotificationDao(),
                        new UnsubscribersNotificationDAO(),
                        new UserNotificationOnlyStatusChangeDAO(),
                        new InvolvedNotificationDao()
                    ),
                    new UserNotificationOnlyStatusChangeDAO()
                ),
                Tracker_Artifact_MailGateway_RecipientFactory::build(),
                new MailGatewayConfig(
                    new MailGatewayConfigDao()
                ),
                new MailSender(),
                new ConfigNotificationAssignedTo(new ConfigNotificationAssignedToDao()),
                new ConfigNotificationEmailCustomSender(new ConfigNotificationEmailCustomSenderDao())
            ),
            new WebhookNotificationTask(
                $logger,
                new WebhookEmitter(
                    HTTPFactoryBuilder::requestFactory(),
                    HTTPFactoryBuilder::streamFactory(),
                    HttpClientFactory::createAsyncClient(),
                    new WebhookStatusLogger($webhook_dao)
                ),
                new WebhookFactory($webhook_dao)
            )
        );
    }

    /**
     * Manage notification for a changeset
     *
     */
    public function executePostCreationActions(Tracker_Artifact_Changeset $changeset)
    {
        if ($this->useAsyncNotifications($changeset)) {
            $this->queuePostCreationEvent($changeset);
        } else {
            $this->processPostCreationActions($changeset);
        }
    }

    /**
     * Process notification when executed in background (should not be called by front-end)
     *
     */
    public function processAsyncPostCreationActions(Tracker_Artifact_Changeset $changeset)
    {
        $this->actions_runner_dao->addStartDate($changeset->getId());
        $this->processPostCreationActions($changeset);
        $this->actions_runner_dao->addEndDate($changeset->getId());
    }

    private function useAsyncNotifications(Tracker_Artifact_Changeset $changeset)
    {
        $async_emails = ForgeConfig::get('sys_async_emails');
        switch ($async_emails) {
            case 'all':
                return true;
            case false:
                return false;
            default:
                $project_ids = array_map(
                    function ($val) {
                        return (int) trim($val);
                    },
                    explode(',', $async_emails)
                );
                if (in_array($changeset->getTracker()->getProject()->getID(), $project_ids)) {
                    return true;
                }
        }
        return false;
    }

    private function queuePostCreationEvent(Tracker_Artifact_Changeset $changeset)
    {
        try {
            $this->actions_runner_dao->addNewPostCreationEvent($changeset->getId());
            $queue = $this->queue_factory->getPersistentQueue(Worker::EVENT_QUEUE_NAME, QueueFactory::REDIS);
            $queue->pushSinglePersistentMessage(
                AsynchronousActionsRunner::TOPIC,
                [
                    'artifact_id'  => (int) $changeset->getArtifact()->getId(),
                    'changeset_id' => (int) $changeset->getId(),
                ]
            );
        } catch (Exception $exception) {
            $this->logger->error("Unable to queue notification for {$changeset->getId()}, fallback to online notif");
            $this->processPostCreationActions($changeset);
            $this->actions_runner_dao->addEndDate($changeset->getId());
        }
    }

    private function processPostCreationActions(Tracker_Artifact_Changeset $changeset)
    {
        foreach ($this->post_creation_tasks as $notification_task) {
            $notification_task->execute($changeset);
        }
    }
}
