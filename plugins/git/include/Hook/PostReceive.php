<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Hook;

use EventManager;
use Git_Ci_Launcher;
use Git_Exec;
use Git_SystemEventManager;
use GitRepository;
use GitRepositoryFactory;
use PFUser;
use Tuleap\Git\DefaultBranch\DefaultBranchPostReceiveUpdater;
use Tuleap\Git\MarkTechnicalReference;
use Tuleap\Git\Webhook\WebhookRequestSender;
use UserManager;

/**
 * Central access point for things that needs to happen when post-receive is
 * executed
 */
class PostReceive
{
    /** @var LogAnalyzer */
    private $log_analyzer;

    /** @var GitRepositoryFactory */
    private $repository_factory;

    /** @var UserManager */
    private $user_manager;

    /** @var Git_Ci_Launcher */
    private $ci_launcher;

    /** @var ParseLog */
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
    private DefaultBranchPostReceiveUpdater $default_branch_post_receive_updater;

    public function __construct(
        LogAnalyzer $log_analyzer,
        GitRepositoryFactory $repository_factory,
        UserManager $user_manager,
        Git_Ci_Launcher $ci_launcher,
        ParseLog $parse_log,
        Git_SystemEventManager $system_event_manager,
        EventManager $event_manager,
        WebhookRequestSender $webhook_request_sender,
        PostReceiveMailSender $mail_sender,
        DefaultBranchPostReceiveUpdater $default_branch_post_receive_updater,
    ) {
        $this->log_analyzer                        = $log_analyzer;
        $this->repository_factory                  = $repository_factory;
        $this->user_manager                        = $user_manager;
        $this->ci_launcher                         = $ci_launcher;
        $this->parse_log                           = $parse_log;
        $this->system_event_manager                = $system_event_manager;
        $this->event_manager                       = $event_manager;
        $this->webhook_request_sender              = $webhook_request_sender;
        $this->mail_sender                         = $mail_sender;
        $this->default_branch_post_receive_updater = $default_branch_post_receive_updater;
    }

    public function beforeParsingReferences($repository_path): void
    {
        $repository = $this->repository_factory->getFromFullPath($repository_path);
        if ($repository !== null) {
            $this->system_event_manager->queueGrokMirrorManifestFollowingAGitPush($repository);

            $this->default_branch_post_receive_updater->updateDefaultBranchWhenNeeded(
                Git_Exec::buildFromRepository($repository)
            );
        }
    }

    public function execute($repository_path, $user_name, $oldrev, $newrev, $refname)
    {
        $repository = $this->repository_factory->getFromFullPath($repository_path);
        if ($repository !== null) {
            $user = $this->user_manager->getUserByUserName($user_name);
            if ($user === null) {
                $user = new PFUser(['user_id' => 0]);
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
