<?php
/**
 * Copyright Enalean (c) 2013-2020. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Tuleap\Git\Hook\PostReceiveExecuteEvent;
use Tuleap\Git\Hook\PostReceiveMailSender;
use Tuleap\Git\MarkTechnicalReference;
use Tuleap\Git\Webhook\WebhookRequestSender;

/**
 * Central access point for things that needs to happen when post-receive is
 * executed
 */
class Git_Hook_PostReceive
{
    /** @var Git_Hook_LogAnalyzer */
    private $log_analyzer;

    /** @var GitRepositoryFactory  */
    private $repository_factory;

    /** @var UserManager */
    private $user_manager;

    /** @var Git_Ci_Launcher */
    private $ci_launcher;

    /** @var Git_Hook_ParseLog */
    private $parse_log;

    /** @var Git_SystemEventManager */
    private $system_event_manager;

    /** @var EventManager */
    private $event_manager;

    /**
     * @var WebhookRequestSender
     */
    private $webhook_request_sender;

    /**
     * @var PostReceiveMailSender
     */
    private $mail_sender;

    public function __construct(
        Git_Hook_LogAnalyzer $log_analyzer,
        GitRepositoryFactory $repository_factory,
        UserManager $user_manager,
        Git_Ci_Launcher $ci_launcher,
        Git_Hook_ParseLog $parse_log,
        Git_SystemEventManager $system_event_manager,
        EventManager $event_manager,
        WebhookRequestSender $webhook_request_sender,
        PostReceiveMailSender $mail_sender
    ) {
        $this->log_analyzer           = $log_analyzer;
        $this->repository_factory     = $repository_factory;
        $this->user_manager           = $user_manager;
        $this->ci_launcher            = $ci_launcher;
        $this->parse_log              = $parse_log;
        $this->system_event_manager   = $system_event_manager;
        $this->event_manager          = $event_manager;
        $this->webhook_request_sender = $webhook_request_sender;
        $this->mail_sender            = $mail_sender;
    }

    public function beforeParsingReferences($repository_path)
    {
        $repository = $this->repository_factory->getFromFullPath($repository_path);
        if ($repository !== null) {
            $this->system_event_manager->queueGrokMirrorManifestFollowingAGitPush($repository);
        }
    }

    public function execute($repository_path, $user_name, $oldrev, $newrev, $refname)
    {
        $repository = $this->repository_factory->getFromFullPath($repository_path);
        if ($repository !== null) {
            $user = $this->user_manager->getUserByUserName($user_name);
            if ($user === null) {
                $user = new PFUser(array('user_id' => 0));
            }

            $technical_reference_event = new MarkTechnicalReference($refname);
            $this->event_manager->processEvent($technical_reference_event);
            if (! $technical_reference_event->isATechnicalReference()) {
                $this->mail_sender->sendMail($repository, $oldrev, $newrev, $refname);
                $this->executeForRepositoryAndUser($repository, $user, $oldrev, $newrev, $refname);
            }
            $this->processGitWebhooks($repository, $user, $oldrev, $newrev, $refname);

            $event = new PostReceiveExecuteEvent(
                $repository,
                $user,
                $oldrev,
                $newrev,
                $refname,
                $technical_reference_event->isATechnicalReference()
            );
            $this->event_manager->processEvent($event);
        }
    }

    private function executeForRepositoryAndUser(GitRepository $repository, PFUser $user, $oldrev, $newrev, $refname)
    {
        $this->ci_launcher->executeForRepository($repository);

        $push_details = $this->log_analyzer->getPushDetails($repository, $user, $oldrev, $newrev, $refname);
        $this->parse_log->execute($push_details);
    }

    private function processGitWebhooks(GitRepository $repository, PFUser $user, $oldrev, $newrev, $refname)
    {
        $this->webhook_request_sender->sendRequests($repository, $user, $oldrev, $newrev, $refname);
    }
}
