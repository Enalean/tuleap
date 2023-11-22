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

use Tuleap\Language\LocaleSwitcher;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\Notification\NotificationEnhancedContent;
use Tuleap\PullRequest\Notification\NotificationToProcess;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;

final class PullRequestNotificationSendMailTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \MailBuilder&\PHPUnit\Framework\MockObject\MockObject
     */
    private $mail_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PullRequestPermissionChecker
     */
    private $pull_request_permission_checker;
    /**
     * @var \GitRepositoryFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $repository_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&HTMLURLBuilder
     */
    private $html_url_builder;

    private PullRequestNotificationSendMail $notification_strategy;

    protected function setUp(): void
    {
        $this->mail_builder                    = $this->createMock(\MailBuilder::class);
        $this->pull_request_permission_checker = $this->createMock(PullRequestPermissionChecker::class);
        $this->repository_factory              = $this->createMock(\GitRepositoryFactory::class);
        $this->html_url_builder                = $this->createMock(HTMLURLBuilder::class);

        $this->notification_strategy = new PullRequestNotificationSendMail(
            $this->mail_builder,
            $this->createMock(\MailEnhancer::class),
            $this->pull_request_permission_checker,
            $this->repository_factory,
            $this->html_url_builder,
            new LocaleSwitcher(),
        );
    }

    public function testMailNotificationsAreSent(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getRepoDestId')->willReturn(12);
        $pull_request->method('getTitle')->willReturn('PR title');
        $recipient_en = $this->createMock(\PFUser::class);
        $recipient_en->method('getEmail')->willReturn('user_en@example.com');
        $recipient_en->method('getLocale')->willReturn('en_US');
        $recipient_fr = $this->createMock(\PFUser::class);
        $recipient_fr->method('getEmail')->willReturn('user_fr@example.com');
        $recipient_fr->method('getLocale')->willReturn('fr_FR');
        $recipient_with_same_email_but_different_locale = $this->createMock(\PFUser::class);
        $recipient_with_same_email_but_different_locale->method('getEmail')->willReturn('user_fr@example.com');
        $recipient_with_same_email_but_different_locale->method('getLocale')->willReturn('jp_JP');
        $notification = $this->buildNotificationToProcess($pull_request, $recipient_en, $recipient_fr);

        $destination_repository = $this->createMock(\GitRepository::class);
        $destination_repository->method('getFullName')->willReturn('Repository name');
        $destination_repository->method('getProject')->willReturn($this->createMock(\Project::class));
        $this->repository_factory->method('getRepositoryById')->willReturn($destination_repository);

        $this->pull_request_permission_checker->method('checkPullRequestIsReadableByUser');

        $this->html_url_builder->method('getAbsolutePullRequestOverviewUrl')->willReturn('/path/to/pr');

        $this->mail_builder->expects(self::exactly(2))->method('buildAndSendEmail');

        $this->notification_strategy->execute($notification);
    }

    public function testDoNotSendMailToRecipientThatCannotAccessThePullRequest(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getRepoDestId')->willReturn(12);
        $pull_request->method('getTitle')->willReturn('PR title');
        $recipient_with_access = $this->createMock(\PFUser::class);
        $recipient_with_access->method('getEmail')->willReturn('user@example.com');
        $recipient_with_access->method('getLocale')->willReturn('en_US');
        $recipient_without_access = $this->createMock(\PFUser::class);
        $notification             = $this->buildNotificationToProcess($pull_request, $recipient_with_access, $recipient_without_access);

        $destination_repository = $this->createMock(\GitRepository::class);
        $destination_repository->method('getFullName')->willReturn('Repository name');
        $destination_repository->method('getProject')->willReturn($this->createMock(\Project::class));
        $this->repository_factory->method('getRepositoryById')->willReturn($destination_repository);

        $this->pull_request_permission_checker->method('checkPullRequestIsReadableByUser')->willReturnCallback(
            function (PullRequest $pull_request_param, \PFUser $user_param) use ($pull_request, $recipient_without_access): void {
                if ($pull_request_param === $pull_request && $user_param === $recipient_without_access) {
                    throw new UserCannotReadGitRepositoryException();
                }
            }
        );

        $this->html_url_builder->method('getAbsolutePullRequestOverviewUrl')->willReturn('/path/to/pr');

        $this->mail_builder->expects(self::once())
            ->method('buildAndSendEmail')
            ->with(
                self::anything(),
                self::callback(
                    static function (\Tuleap\Notification\Notification $notification) use ($recipient_with_access): bool {
                        $emails = $notification->getEmails();
                        return count($emails) === 1 && $emails[0] === $recipient_with_access->getEmail();
                    }
                ),
                self::anything(),
            );

        $this->notification_strategy->execute($notification);
    }

    public function testDoNotSendMailIfTheDestinationRepositoryCannotBeFound(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getRepoDestId')->willReturn(404);

        $notification = $this->buildNotificationToProcess($pull_request);

        $this->repository_factory->method('getRepositoryById')->willReturn(null);

        $this->mail_builder->expects(self::never())->method('buildAndSendEmail');

        $this->notification_strategy->execute($notification);
    }

    private function buildNotificationToProcess(PullRequest $pull_request, \PFUser ...$recipients): NotificationToProcess
    {
        return new class ($pull_request, ...$recipients) implements NotificationToProcess
        {
            /**
             * @var PullRequest
             */
            private $pull_request;
            /**
             * @var \PFUser[]
             */
            private $recipients;

            public function __construct(PullRequest $pull_request, \PFUser ...$recipients)
            {
                $this->pull_request = $pull_request;
                $this->recipients   = $recipients;
            }

            /**
             * @psalm-mutation-free
             */
            public function getPullRequest(): PullRequest
            {
                return $this->pull_request;
            }

            /**
             * @psalm-mutation-free
             */
            public function getRecipients(): array
            {
                return $this->recipients;
            }

            /**
             * @psalm-mutation-free
             */
            public function asPlaintext(): string
            {
                return 'Plaintext mail body';
            }

            /**
             * @psalm-mutation-free
             */
            public function asEnhancedContent(): NotificationEnhancedContent
            {
                return new class implements NotificationEnhancedContent {
                    public function toString(): string
                    {
                        return 'Markup content mail body';
                    }
                };
            }
        };
    }
}
