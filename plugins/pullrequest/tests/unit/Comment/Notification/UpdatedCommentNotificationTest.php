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
use Tuleap\Notification\Mention\MentionedUserInTextRetriever;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\Notification\FormatNotificationContent;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\PullRequest\Tests\Builders\CommentTestBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\FormatNotificationContentStub;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\ProvideAndRetrieveUserStub;
use Tuleap\User\RetrieveUserByUserName;
use UserHelper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UpdatedCommentNotificationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use TemporaryTestDirectory;
    use ForgeConfigSandbox;

    private PFUser $owner_user;
    private PFUser $comment_author;
    private PullRequest $pull_request;

    protected function setUp(): void
    {
        $this->owner_user     = UserTestBuilder::buildWithId(103);
        $this->comment_author = UserTestBuilder::anActiveUser()->withId(102)->withUserName('UserA')->build();
        $this->pull_request   = PullRequestTestBuilder::aPullRequestInReview()->withId(14)->withTitle('PR title')->build();
    }

    public function testUpdatedCommentNotificationCanBeBuiltFromTheOwnersOfThePullRequestAndTheUpdatedComment(): void
    {
        $notification = $this->buildNotification(
            CommentTestBuilder::aMarkdownComment('My comment')->build(),
            FormatNotificationContentStub::withDefault(),
            ProvideAndRetrieveUserStub::build(UserTestBuilder::buildWithDefaults()),
        );

        self::assertEqualsCanonicalizing([$this->owner_user], $notification->getRecipients());
        self::assertSame($this->pull_request, $notification->getPullRequest());
        self::assertEquals(
            <<<EOF
            User A updated their comment on #14: PR title.

            My comment
            EOF,
            $notification->asPlaintext()
        );
        self::assertEquals(
            <<<EOF
            <p>
            <a href="https://example.com/users/usera">User A</a> updated their comment on <a href="https://example.com/pr-link">#14</a>: PR title.</p>
            <p>
                My comment
            </p>

            EOF,
            $notification->asEnhancedContent()->toString()
        );
    }

    public function testUpdatedCommentNotificationCanBeBuiltFromTheOwnersOfThePullRequestAndTheUpdatedCommentInMarkdown(): void
    {
        $notification = $this->buildNotification(
            CommentTestBuilder::aMarkdownComment('**My comment**')->build(),
            FormatNotificationContentStub::withFormattedContent('<em>My comment</em>'),
            ProvideAndRetrieveUserStub::build(UserTestBuilder::buildWithDefaults()),
        );

        self::assertEqualsCanonicalizing([$this->owner_user], $notification->getRecipients());
        self::assertSame($this->pull_request, $notification->getPullRequest());
        self::assertEquals(
            <<<EOF
            User A updated their comment on #14: PR title.

            **My comment**
            EOF,
            $notification->asPlaintext()
        );
        self::assertEquals(
            <<<EOF
            <p>
            <a href="https://example.com/users/usera">User A</a> updated their comment on <a href="https://example.com/pr-link">#14</a>: PR title.</p>
            <p>
                <em>My comment</em>
            </p>

            EOF,
            $notification->asEnhancedContent()->toString()
        );
    }

    public function testNewCommentNotificationCanBeBuiltWithMentionedUsers(): void
    {
        $user_bob     = UserTestBuilder::anActiveUser()->withUserName('bob')->build();
        $notification = $this->buildNotification(
            CommentTestBuilder::aMarkdownComment('Hello @bob')->build(),
            FormatNotificationContentStub::withFormattedContent('Hello <a href="https://example.com/users/bob">@bob</a>'),
            ProvideAndRetrieveUserStub::build(UserTestBuilder::buildWithDefaults())->withUsers([$user_bob]),
        );

        self::assertEqualsCanonicalizing([$this->owner_user, $user_bob], $notification->getRecipients());
    }

    public function testCommentUpdaterIsRemovedFromRecipients(): void
    {
        $notification = $this->buildNotification(
            CommentTestBuilder::aMarkdownComment('Hello @UserA')->build(),
            FormatNotificationContentStub::withFormattedContent('Hello <a href="https://example.com/users/UserA">@UserA</a>'),
            ProvideAndRetrieveUserStub::build(UserTestBuilder::buildWithDefaults())->withUsers([$this->comment_author]),
        );

        self::assertEqualsCanonicalizing([$this->owner_user], $notification->getRecipients());
    }

    private function buildNotification(
        Comment $comment,
        FormatNotificationContent $format_notification_content,
        RetrieveUserByUserName $user_retriever,
    ): UpdatedCommentNotification {
        $owners           = [$this->comment_author, $this->owner_user];
        $user_helper      = $this->createMock(UserHelper::class);
        $html_url_builder = $this->createMock(HTMLURLBuilder::class);

        \ForgeConfig::set('codendi_cache_dir', $this->getTmpDir());

        $user_helper->method('getDisplayNameFromUser')->with($this->comment_author)->willReturn('User A');
        $user_helper->method('getAbsoluteUserURL')->with($this->comment_author)->willReturn('https://example.com/users/usera');
        $html_url_builder->method('getAbsolutePullRequestOverviewUrl')->with($this->pull_request)->willReturn('https://example.com/pr-link');

        $mentioned_user_retriever = new MentionedUserInTextRetriever($user_retriever);

        return UpdatedCommentNotification::fromOwnersAndUpdatedComment(
            $user_helper,
            $html_url_builder,
            new FilterUserFromCollection(),
            $format_notification_content,
            $this->pull_request,
            $this->comment_author,
            $owners,
            $comment,
            $mentioned_user_retriever->getMentionedUsers($comment->getContent()),
        );
    }
}
