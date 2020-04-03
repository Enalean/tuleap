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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Language\LocaleSwitcher;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\Notification\NotificationEnhancedContent;
use Tuleap\PullRequest\Notification\NotificationToProcess;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;

final class PullRequestNotificationSendMailTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \MailBuilder|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $mail_builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PullRequestPermissionChecker
     */
    private $pull_request_permission_checker;
    /**
     * @var \GitRepositoryFactory|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $repository_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|HTMLURLBuilder
     */
    private $html_url_builder;

    /**
     * @var PullRequestNotificationSendMail
     */
    private $notification_strategy;

    protected function setUp(): void
    {
        $this->mail_builder                    = \Mockery::mock(\MailBuilder::class);
        $this->pull_request_permission_checker = \Mockery::mock(PullRequestPermissionChecker::class);
        $this->repository_factory              = \Mockery::mock(\GitRepositoryFactory::class);
        $this->html_url_builder                = \Mockery::mock(HTMLURLBuilder::class);

        $this->notification_strategy = new PullRequestNotificationSendMail(
            $this->mail_builder,
            \Mockery::mock(\MailEnhancer::class),
            $this->pull_request_permission_checker,
            $this->repository_factory,
            $this->html_url_builder,
            new LocaleSwitcher(),
        );
    }

    public function testMailNotificationsAreSent(): void
    {
        $pull_request = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getRepoDestId')->andReturn(12);
        $pull_request->shouldReceive('getTitle')->andReturn('PR title');
        $recipient_en = \Mockery::mock(\PFUser::class);
        $recipient_en->shouldReceive('getEmail')->andReturn('user_en@example.com');
        $recipient_en->shouldReceive('getLocale')->andReturn('en_US');
        $recipient_fr = \Mockery::mock(\PFUser::class);
        $recipient_fr->shouldReceive('getEmail')->andReturn('user_fr@example.com');
        $recipient_fr->shouldReceive('getLocale')->andReturn('fr_FR');
        $recipient_with_same_email_but_different_locale = \Mockery::mock(\PFUser::class);
        $recipient_with_same_email_but_different_locale->shouldReceive('getEmail')->andReturn('user_fr@example.com');
        $recipient_with_same_email_but_different_locale->shouldReceive('getLocale')->andReturn('jp_JP');
        $notification = $this->buildNotificationToProcess($pull_request, $recipient_en, $recipient_fr);

        $destination_repository = \Mockery::mock(\GitRepository::class);
        $destination_repository->shouldReceive('getFullName')->andReturn('Repository name');
        $destination_repository->shouldReceive('getProject')->andReturn(\Mockery::mock(\Project::class));
        $this->repository_factory->shouldReceive('getRepositoryById')->andReturn($destination_repository);

        $this->pull_request_permission_checker->shouldReceive('checkPullRequestIsReadableByUser');

        $this->html_url_builder->shouldReceive('getAbsolutePullRequestOverviewUrl')->andReturn('/path/to/pr');

        $this->mail_builder->shouldReceive('buildAndSendEmail')->twice();

        $this->notification_strategy->execute($notification);
    }

    public function testDoNotSendMailToRecipientThatCannotAccessThePullRequest(): void
    {
        $pull_request = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getRepoDestId')->andReturn(12);
        $pull_request->shouldReceive('getTitle')->andReturn('PR title');
        $recipient_with_access = \Mockery::mock(\PFUser::class);
        $recipient_with_access->shouldReceive('getEmail')->andReturn('user@example.com');
        $recipient_with_access->shouldReceive('getLocale')->andReturn('en_US');
        $recipient_without_access = \Mockery::mock(\PFUser::class);
        $notification = $this->buildNotificationToProcess($pull_request, $recipient_with_access, $recipient_without_access);

        $destination_repository = \Mockery::mock(\GitRepository::class);
        $destination_repository->shouldReceive('getFullName')->andReturn('Repository name');
        $destination_repository->shouldReceive('getProject')->andReturn(\Mockery::mock(\Project::class));
        $this->repository_factory->shouldReceive('getRepositoryById')->andReturn($destination_repository);

        $this->pull_request_permission_checker->shouldReceive('checkPullRequestIsReadableByUser')
            ->with($pull_request, $recipient_with_access);
        $this->pull_request_permission_checker->shouldReceive('checkPullRequestIsReadableByUser')
            ->with($pull_request, $recipient_without_access)->andThrow(UserCannotReadGitRepositoryException::class);

        $this->html_url_builder->shouldReceive('getAbsolutePullRequestOverviewUrl')->andReturn('/path/to/pr');

        $this->mail_builder->shouldReceive('buildAndSendEmail')->with(
            \Mockery::any(),
            \Mockery::on(
                static function (\Notification $notification) use ($recipient_with_access): bool {
                    $emails = $notification->getEmails();
                    return count($emails) === 1 && $emails[0] === $recipient_with_access->getEmail();
                }
            ),
            \Mockery::any()
        )->once();

        $this->notification_strategy->execute($notification);
    }

    public function testDoNotSendMailIfTheDestinationRepositoryCannotBeFound(): void
    {
        $pull_request = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getRepoDestId')->andReturn(404);

        $notification = $this->buildNotificationToProcess($pull_request);

        $this->repository_factory->shouldReceive('getRepositoryById')->andReturn(null);

        $this->mail_builder->shouldNotReceive('buildAndSendEmail');

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

            public function getPullRequest(): PullRequest
            {
                return $this->pull_request;
            }

            public function getRecipients(): array
            {
                return $this->recipients;
            }

            public function asPlaintext(): string
            {
                return 'Plaintext mail body';
            }

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
