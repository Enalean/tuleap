<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Notification;

use ForgeConfig;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Notification\Mention\MentionedUserCollection;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ContentInterpretorStub;
use UserHelper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PullRequestDescriptionUpdatedNotificationTest extends TestCase
{
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;

    public function testItBuildsANotificationToProcess(): void
    {
        ForgeConfig::set('codendi_cache_dir', $this->getTmpDir());

        $updater     = UserTestBuilder::anActiveUser()->withId(101)->withUserName('alice')->build();
        $user_helper = $this->createMock(UserHelper::class);
        $user_helper->method('getDisplayNameFromUser')->with($updater)->willReturn('alice');
        $user_helper->method('getAbsoluteUserURL')->with($updater)->willReturn('https://example.com/alice');
        $pull_request     = PullRequestTestBuilder::aPullRequestInReview()
            ->withId(25)
            ->withTitle('feat: Add some features')
            ->withDescription(TimelineComment::FORMAT_TEXT, 'Hello @hector and @alice')
            ->build();
        $html_url_builder = $this->createMock(HTMLURLBuilder::class);
        $html_url_builder->method('getAbsolutePullRequestOverviewUrl')->with($pull_request)->willReturn('https://example.com/pr25');
        $hector = UserTestBuilder::anActiveUser()->withId(102)->withUserName('hector')->build();

        $notification = PullRequestDescriptionUpdatedNotification::fromPullRequest(
            $pull_request,
            GitRepositoryTestBuilder::aProjectRepository()->build(),
            new MentionedUserCollection([$hector, $updater]),
            new FilterUserFromCollection(),
            $user_helper,
            $updater,
            $html_url_builder,
            ContentInterpretorStub::withInterpretedText('Hello @hector and @alice'),
        );

        self::assertSame($pull_request, $notification->getPullRequest());
        self::assertSame([$hector], $notification->getRecipients());
        self::assertSame('alice updated the description of the pull request #25: feat: Add some features', $notification->asPlaintext());
        self::assertSame(
            <<<EOF
            <p>
            <a href="https://example.com/alice">alice</a> updated the description of the pull request <a href="https://example.com/pr25">#25</a>: feat: Add some features</p>
            <p>
                Hello @hector and @alice
            </p>

            EOF,
            $notification->asEnhancedContent()->toString()
        );
    }
}
