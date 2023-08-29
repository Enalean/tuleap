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

use PFUser;
use Tuleap\ForgeConfigSandbox;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\TemporaryTestDirectory;
use UserHelper;

final class PullRequestNewInlineCommentNotificationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;

    public function testNewInlinceCommentNotificationCanBeBuilt(): void
    {
        $change_user            = $this->buildUser(102);
        $user_103               = $this->buildUser(103);
        $owners                 = [$change_user, $user_103];
        $pull_request           = $this->createMock(PullRequest::class);
        $user_helper            = $this->createMock(UserHelper::class);
        $html_url_builder       = $this->createMock(HTMLURLBuilder::class);
        $code_context_extractor = $this->createMock(InlineCommentCodeContextExtractor::class);

        \ForgeConfig::set('codendi_cache_dir', $this->getTmpDir());

        $user_helper->method('getDisplayNameFromUser')->with($change_user)->willReturn('User A');
        $user_helper->method('getAbsoluteUserURL')->with($change_user)->willReturn('https://example.com/users/usera');
        $html_url_builder->method('getAbsolutePullRequestOverviewUrl')->with($pull_request)->willReturn('https://example.com/pr-link');
        $pull_request->method('getId')->willReturn(14);
        $pull_request->method('getTitle')->willReturn('PR title');
        $code_context_extractor->method('getCodeContext')->willReturn('-Removed code');

        $notification = PullRequestNewInlineCommentNotification::fromOwnersAndInlineComment(
            $user_helper,
            $html_url_builder,
            new FilterUserFromCollection(),
            $pull_request,
            $change_user,
            $owners,
            new InlineComment(
                32,
                $pull_request->getId(),
                (int) $change_user->getId(),
                10,
                'path/to/file',
                2,
                'Foo comment',
                false,
                0,
                "right",
                "",
                Comment::FORMAT_TEXT
            ),
            $code_context_extractor
        );

        self::assertEqualsCanonicalizing([$user_103], $notification->getRecipients());
        self::assertSame($pull_request, $notification->getPullRequest());
        self::assertEquals(
            <<<EOF
            User A commented on #14: PR title in path/to/file:

            -Removed code

            Foo comment
            EOF,
            $notification->asPlaintext()
        );
        self::assertEquals(
            <<<EOF
            <p>
            <a href="https://example.com/users/usera">User A</a> commented on <a href="https://example.com/pr-link">#14</a>: PR title in path/to/file:</p>
            <pre style="color:#555"><code>-Removed code</code></pre>
            <p>
                Foo comment
            </p>

            EOF,
            $notification->asEnhancedContent()->toString()
        );
    }

    private function buildUser(int $user_id): PFUser
    {
        return new PFUser(['user_id' => $user_id, 'language_id' => 'en']);
    }
}
