<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\InlineComment\Notification;

use ForgeConfig;
use PFUser;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Notification\Mention\MentionedUserCollection;
use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\Notification\FormatNotificationContent;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\PullRequest\Tests\Builders\InlineCommentTestBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\FormatNotificationContentStub;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UserHelper;

final class UpdatedInlineCommentNotificationTest extends TestCase
{
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;

    private PFUser $owner_user;
    private PFUser $mentioned_user;
    private PullRequest $pull_request;
    private PFUser $comment_author;

    protected function setUp(): void
    {
        $this->owner_user     = UserTestBuilder::buildWithId(103);
        $this->mentioned_user = UserTestBuilder::anActiveUser()->withId(105)->build();
        $this->comment_author = UserTestBuilder::anActiveUser()->withId(102)->withUserName('user_a')->build();
        $this->pull_request   = PullRequestTestBuilder::aPullRequestInReview()->withId(14)->withTitle('PR title')->build();
    }

    public function testUpdatedInlineCommentNotificationCanBeBuilt(): void
    {
        $notification = $this->buildNotification(
            InlineCommentTestBuilder::aTextComment('Foo comment @peralta')->onFile('path/to/file')->build(),
            FormatNotificationContentStub::withDefault(),
            [$this->mentioned_user]
        );

        self::assertEqualsCanonicalizing([$this->owner_user, $this->mentioned_user], $notification->getRecipients());
        self::assertSame($this->pull_request, $notification->getPullRequest());
        self::assertEquals(
            <<<EOF
            User A updated their comment on #14: PR title in path/to/file:

            -Removed code

            Foo comment @peralta
            EOF,
            $notification->asPlaintext()
        );
        self::assertEquals(
            <<<EOF
            <p>
            <a href="https://example.com/users/usera">User A</a> updated their comment on <a href="https://example.com/pr-link">#14</a>: PR title in path/to/file:</p>
            <pre style="color:#555"><code>-Removed code</code></pre>
            <p>
                Foo comment @peralta
            </p>

            EOF,
            $notification->asEnhancedContent()->toString()
        );
    }

    public function testUpdatedInlineCommentInMarkdownNotificationCanBeBuilt(): void
    {
        $notification = $this->buildNotification(
            InlineCommentTestBuilder::aMarkdownComment('**Foo comment**')->onFile('path/to/file')->build(),
            FormatNotificationContentStub::withFormattedContent('<em>Foo comment</em>'),
            []
        );

        self::assertEqualsCanonicalizing([$this->owner_user], $notification->getRecipients());
        self::assertSame($this->pull_request, $notification->getPullRequest());
        self::assertEquals(
            <<<EOF
            User A updated their comment on #14: PR title in path/to/file:

            -Removed code

            **Foo comment**
            EOF,
            $notification->asPlaintext()
        );
        self::assertEquals(
            <<<EOF
            <p>
            <a href="https://example.com/users/usera">User A</a> updated their comment on <a href="https://example.com/pr-link">#14</a>: PR title in path/to/file:</p>
            <pre style="color:#555"><code>-Removed code</code></pre>
            <p>
                <em>Foo comment</em>
            </p>

            EOF,
            $notification->asEnhancedContent()->toString()
        );
    }

    public function testNewInlineCommentNotificationDoesNotNotifyTheCommentAuthorWhoMentionedHimself(): void
    {
        $notification = $this->buildNotification(
            InlineCommentTestBuilder::aMarkdownComment('**Foo comment @user_a**')->onFile('path/to/file')->build(),
            FormatNotificationContentStub::withFormattedContent('<em>Foo comment @user_a</em>'),
            [$this->comment_author]
        );
        self::assertEqualsCanonicalizing([$this->owner_user], $notification->getRecipients());
        self::assertSame($this->pull_request, $notification->getPullRequest());
        self::assertEquals(
            <<<EOF
            User A updated their comment on #14: PR title in path/to/file:

            -Removed code

            **Foo comment @user_a**
            EOF,
            $notification->asPlaintext()
        );
        self::assertEquals(
            <<<EOF
            <p>
            <a href="https://example.com/users/usera">User A</a> updated their comment on <a href="https://example.com/pr-link">#14</a>: PR title in path/to/file:</p>
            <pre style="color:#555"><code>-Removed code</code></pre>
            <p>
                <em>Foo comment @user_a</em>
            </p>

            EOF,
            $notification->asEnhancedContent()->toString()
        );
    }

    /**
     * @param list<PFUser> $mentioned_users
     */
    private function buildNotification(InlineComment $inline_comment, FormatNotificationContent $format_notification_content, array $mentioned_users): UpdatedInlineCommentNotification
    {
        $owners                 = [$this->comment_author, $this->owner_user];
        $user_helper            = $this->createMock(UserHelper::class);
        $html_url_builder       = $this->createMock(HTMLURLBuilder::class);
        $code_context_extractor = $this->createMock(InlineCommentCodeContextExtractor::class);

        ForgeConfig::set('codendi_cache_dir', $this->getTmpDir());

        $user_helper->method('getDisplayNameFromUser')->with($this->comment_author)->willReturn('User A');
        $user_helper->method('getAbsoluteUserURL')->with($this->comment_author)->willReturn('https://example.com/users/usera');
        $html_url_builder->method('getAbsolutePullRequestOverviewUrl')->with($this->pull_request)->willReturn('https://example.com/pr-link');
        $code_context_extractor->method('getCodeContext')->willReturn('-Removed code');

        return UpdatedInlineCommentNotification::fromOwnersAndUpdatedInlineComment(
            $user_helper,
            $html_url_builder,
            new FilterUserFromCollection(),
            $this->pull_request,
            $this->comment_author,
            $owners,
            $inline_comment,
            $code_context_extractor,
            $format_notification_content,
            new MentionedUserCollection($mentioned_users),
        );
    }
}
