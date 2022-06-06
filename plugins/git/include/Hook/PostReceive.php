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

use Git_Ci_Launcher;
use Git_Exec;
use Git_SystemEventManager;
use GitRepository;
use GitRepositoryFactory;
use PFUser;
use Tuleap\Git\DefaultBranch\DefaultBranchPostReceiveUpdater;
use Tuleap\Git\MarkTechnicalReference;
use Tuleap\Git\Webhook\WebhookRequestSender;

/**
 * Central access point for things that needs to happen when post-receive is
 * executed
 */
class PostReceive
{
    public function __construct(
        private LogAnalyzer $log_analyzer,
        private GitRepositoryFactory $repository_factory,
        private \UserManager $user_manager,
        private Git_Ci_Launcher $ci_launcher,
        private ParseLog $parse_log,
        private Git_SystemEventManager $system_event_manager,
        private \EventManager $event_manager,
        private WebhookRequestSender $webhook_request_sender,
        private PostReceiveMailSender $mail_sender,
        private DefaultBranchPostReceiveUpdater $default_branch_post_receive_updater,
    ) {
    }

    public function beforeParsingReferences(string $repository_path): void
    {
        $repository = $this->repository_factory->getFromFullPath($repository_path);
        if ($repository !== null) {
            $this->system_event_manager->queueGrokMirrorManifestFollowingAGitPush($repository);

            $this->default_branch_post_receive_updater->updateDefaultBranchWhenNeeded(
                Git_Exec::buildFromRepository($repository)
            );
        }
    }

    /**
     * @throws \Git_Command_UnknownObjectTypeException
     */
    public function execute(string $repository_path, string $user_name, string $oldrev, string $newrev, string $refname): void
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

    /**
     * @throws \Git_Command_UnknownObjectTypeException
     */
    private function executeForRepositoryAndUser(GitRepository $repository, PFUser $user, string $oldrev, string $newrev, string $refname): void
    {
        $this->ci_launcher->executeForRepository($repository);

        $push_details = $this->log_analyzer->getPushDetails($repository, $user, $oldrev, $newrev, $refname);
        $this->parse_log->execute($push_details);
    }

    private function processGitWebhooks(GitRepository $repository, PFUser $user, string $oldrev, string $newrev, string $refname): void
    {
        $this->webhook_request_sender->sendRequests($repository, $user, $oldrev, $newrev, $refname);
    }
}
