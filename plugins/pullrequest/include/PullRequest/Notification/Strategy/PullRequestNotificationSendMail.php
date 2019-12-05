<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Notification\Strategy;

use Codendi_HTMLPurifier;
use GitRepository;
use GitRepositoryFactory;
use MailBuilder;
use MailEnhancer;
use Notification;
use Project_AccessException;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\Notification\NotificationToProcess;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;

final class PullRequestNotificationSendMail implements PullRequestNotificationStrategy
{
    /**
     * @var MailBuilder
     */
    private $mail_builder;
    /**
     * @var MailEnhancer
     */
    private $mail_enhancer;
    /**
     * @var PullRequestPermissionChecker
     */
    private $pull_request_permission_checker;
    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;
    /**
     * @var HTMLURLBuilder
     */
    private $url_builder;

    public function __construct(
        MailBuilder $mail_builder,
        MailEnhancer $mail_enhancer,
        PullRequestPermissionChecker $pull_request_permission_checker,
        GitRepositoryFactory $repository_factory,
        HTMLURLBuilder $url_builder
    ) {
        $this->mail_builder                    = $mail_builder;
        $this->mail_enhancer                   = $mail_enhancer;
        $this->pull_request_permission_checker = $pull_request_permission_checker;
        $this->repository_factory              = $repository_factory;
        $this->url_builder                     = $url_builder;
    }

    public function execute(NotificationToProcess $notification) : void
    {
        $pull_request           = $notification->getPullRequest();
        $destination_repository = $this->repository_factory->getRepositoryById($pull_request->getRepoDestId());

        if ($destination_repository === null) {
            return;
        }

        $this->mail_builder->buildAndSendEmail(
            $destination_repository->getProject(),
            new Notification(
                $this->getRecipientEmails($notification),
                $this->getSubject($pull_request, $destination_repository),
                $notification->asEnhancedContent()->toString(),
                $notification->asPlaintext(),
                $this->url_builder->getAbsolutePullRequestOverviewUrl($pull_request),
                dgettext('tuleap-pullrequest', 'Pull request')
            ),
            $this->mail_enhancer
        );
    }

    /**
     * @return string[]
     */
    private function getRecipientEmails(NotificationToProcess $notification): array
    {
        $recipients = [];

        foreach ($notification->getRecipients() as $recipient) {
            try {
                $this->pull_request_permission_checker->checkPullRequestIsReadableByUser($notification->getPullRequest(), $recipient);
            } catch (\GitRepoNotFoundException|Project_AccessException|UserCannotReadGitRepositoryException $e) {
                continue;
            }
            $recipients[] = $recipient->getEmail();
        }

        return $recipients;
    }

    private function getSubject(PullRequest $pull_request, GitRepository $destination_repository): string
    {
        return sprintf(
            '[%s] %s',
            $destination_repository->getFullName(),
            $pull_request->getTitle()
        );
    }
}
