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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PullRequestNewInlineCommentNotificationTest extends TestCase
{
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;

    private PFUser $user_103;
    private PullRequest $pull_request;
    private PFUser $mentioned_user;
    private PFUser $change_user;

    protected function setUp(): void
    {
        $this->user_103       = UserTestBuilder::anActiveUser()->withId(103)->build();
        $this->mentioned_user = UserTestBuilder::anActiveUser()->withId(105)->build();
        $this->change_user    = UserTestBuilder::anActiveUser()->withId(102)->withUserName('user_a')->build();
        $this->pull_request   = PullRequestTestBuilder::aPullRequestInReview()->withId(14)->withTitle('PR title')->build();
    }

    public function testNewInlineCommentNotificationCanBeBuilt(): void
    {
        $notification = $this->buildNotification(
            InlineCommentTestBuilder::aTextComment('Foo comment @peralta')->onFile('path/to/file')->build(),
            FormatNotificationContentStub::withDefault(),
            [$this->mentioned_user]
        );

        self::assertEqualsCanonicalizing([$this->user_103, $this->mentioned_user], $notification->getRecipients());
        self::assertSame($this->pull_request, $notification->getPullRequest());
        self::assertEquals(
            <<<EOF
            User A commented on #14: PR title in path/to/file:

            -Removed code

            Foo comment @peralta
            EOF,
            $notification->asPlaintext()
        );
        self::assertEquals(
            <<<EOF
            <p>
            <a href="https://example.com/users/usera">User A</a> commented on <a href="https://example.com/pr-link">#14</a>: PR title in path/to/file:</p>
            <pre style="color:#555"><code>-Removed code</code></pre>
            <p>
                Foo comment @peralta
            </p>

            EOF,
            $notification->asEnhancedContent()->toString()
        );
    }

    public function testNewInlineCommentNotificationDoesNotNotifyTheChangeUserWhoMentionedHimself(): void
    {
        $notification = $this->buildNotification(
            InlineCommentTestBuilder::aMarkdownComment('**Foo comment @user_a**')->onFile('path/to/file')->build(),
            FormatNotificationContentStub::withFormattedContent('<em>Foo comment @user_a</em>'),
            [$this->change_user]
        );

        self::assertEqualsCanonicalizing([$this->user_103], $notification->getRecipients());
        self::assertSame($this->pull_request, $notification->getPullRequest());
        self::assertEquals(
            <<<EOF
            User A commented on #14: PR title in path/to/file:

            -Removed code

            **Foo comment @user_a**
            EOF,
            $notification->asPlaintext()
        );
        self::assertEquals(
            <<<EOF
            <p>
            <a href="https://example.com/users/usera">User A</a> commented on <a href="https://example.com/pr-link">#14</a>: PR title in path/to/file:</p>
            <pre style="color:#555"><code>-Removed code</code></pre>
            <p>
                <em>Foo comment @user_a</em>
            </p>

            EOF,
            $notification->asEnhancedContent()->toString()
        );
    }

    public function testNewInlineCommentInMarkdownNotificationCanBeBuilt(): void
    {
        $notification = $this->buildNotification(
            InlineCommentTestBuilder::aMarkdownComment('**Foo comment**')->onFile('path/to/file')->build(),
            FormatNotificationContentStub::withFormattedContent('<em>Foo comment</em>'),
            []
        );

        self::assertEqualsCanonicalizing([$this->user_103], $notification->getRecipients());
        self::assertSame($this->pull_request, $notification->getPullRequest());
        self::assertEquals(
            <<<EOF
            User A commented on #14: PR title in path/to/file:

            -Removed code

            **Foo comment**
            EOF,
            $notification->asPlaintext()
        );
        self::assertEquals(
            <<<EOF
            <p>
            <a href="https://example.com/users/usera">User A</a> commented on <a href="https://example.com/pr-link">#14</a>: PR title in path/to/file:</p>
            <pre style="color:#555"><code>-Removed code</code></pre>
            <p>
                <em>Foo comment</em>
            </p>

            EOF,
            $notification->asEnhancedContent()->toString()
        );
    }

    /**
     * @param list<PFUser> $mentioned_users
     */
    private function buildNotification(InlineComment $inline_comment, FormatNotificationContent $format_notification_content, array $mentioned_users): PullRequestNewInlineCommentNotification
    {
        $owners                 = [$this->change_user, $this->user_103];
        $user_helper            = $this->createMock(UserHelper::class);
        $html_url_builder       = $this->createMock(HTMLURLBuilder::class);
        $code_context_extractor = $this->createMock(InlineCommentCodeContextExtractor::class);

        ForgeConfig::set('codendi_cache_dir', $this->getTmpDir());

        $user_helper->method('getDisplayNameFromUser')->with($this->change_user)->willReturn('User A');
        $user_helper->method('getAbsoluteUserURL')->with($this->change_user)->willReturn('https://example.com/users/usera');
        $html_url_builder->method('getAbsolutePullRequestOverviewUrl')->with($this->pull_request)->willReturn('https://example.com/pr-link');
        $code_context_extractor->method('getCodeContext')->willReturn('-Removed code');

        return PullRequestNewInlineCommentNotification::fromOwnersAndInlineComment(
            $user_helper,
            $html_url_builder,
            new FilterUserFromCollection(),
            $this->pull_request,
            $this->change_user,
            $owners,
            $inline_comment,
            $code_context_extractor,
            $format_notification_content,
            new MentionedUserCollection($mentioned_users)
        );
    }
}
