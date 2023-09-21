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

namespace Tuleap\PullRequest\Comment\Notification;

use PFUser;
use Tuleap\ForgeConfigSandbox;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\Notification\FormatNotificationContent;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\PullRequest\Tests\Builders\CommentTestBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\FormatNotificationContentStub;
use Tuleap\TemporaryTestDirectory;
use UserHelper;

final class PullRequestNewCommentNotificationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;

    private PFUser $user_103;
    private PullRequest $pull_request;

    protected function setUp(): void
    {
        $this->user_103     = $this->buildUser(103);
        $this->pull_request = PullRequestTestBuilder::aPullRequestInReview()->withId(14)->withTitle("PR title")->build();
    }

    public function testNewCommentNotificationCanBeBuiltFromTheOwnersOfThePullRequestAndTheAddedComment(): void
    {
        $notification = $this->buildNotification(
            CommentTestBuilder::aMarkdownComment('My comment')->build(),
            FormatNotificationContentStub::withDefault(),
        );

        self::assertEqualsCanonicalizing([$this->user_103], $notification->getRecipients());
        self::assertSame($this->pull_request, $notification->getPullRequest());
        self::assertEquals(
            <<<EOF
            User A commented on #14: PR title.

            My comment
            EOF,
            $notification->asPlaintext()
        );
        self::assertEquals(
            <<<EOF
            <p>
            <a href="https://example.com/users/usera">User A</a> commented on <a href="https://example.com/pr-link">#14</a>: PR title.</p>
            <p>
                My comment
            </p>

            EOF,
            $notification->asEnhancedContent()->toString()
        );
    }

    public function testNewCommentNotificationCanBeBuiltFromTheOwnersOfThePullRequestAndTheAddedCommentInMarkdown(): void
    {
        $notification = $this->buildNotification(
            CommentTestBuilder::aMarkdownComment('**My comment**')->build(),
            FormatNotificationContentStub::withFormattedContent("<em>My comment</em>"),
        );

        self::assertEqualsCanonicalizing([$this->user_103], $notification->getRecipients());
        self::assertSame($this->pull_request, $notification->getPullRequest());
        self::assertEquals(
            <<<EOF
            User A commented on #14: PR title.

            **My comment**
            EOF,
            $notification->asPlaintext()
        );
        self::assertEquals(
            <<<EOF
            <p>
            <a href="https://example.com/users/usera">User A</a> commented on <a href="https://example.com/pr-link">#14</a>: PR title.</p>
            <p>
                <em>My comment</em>
            </p>

            EOF,
            $notification->asEnhancedContent()->toString()
        );
    }

    private function buildUser(int $user_id): PFUser
    {
        return new PFUser(['user_id' => $user_id, 'language_id' => 'en']);
    }

    private function buildNotification(Comment $comment, FormatNotificationContent $format_notification_content): PullRequestNewCommentNotification
    {
        $change_user      = $this->buildUser(102);
        $owners           = [$change_user, $this->user_103];
        $user_helper      = $this->createMock(UserHelper::class);
        $html_url_builder = $this->createMock(HTMLURLBuilder::class);

        \ForgeConfig::set('codendi_cache_dir', $this->getTmpDir());

        $user_helper->method('getDisplayNameFromUser')->with($change_user)->willReturn('User A');
        $user_helper->method('getAbsoluteUserURL')->with($change_user)->willReturn('https://example.com/users/usera');
        $html_url_builder->method('getAbsolutePullRequestOverviewUrl')->with($this->pull_request)->willReturn('https://example.com/pr-link');

        return PullRequestNewCommentNotification::fromOwnersAndComment(
            $user_helper,
            $html_url_builder,
            new FilterUserFromCollection(),
            $format_notification_content,
            $this->pull_request,
            $change_user,
            $owners,
            $comment,
        );
    }
}
