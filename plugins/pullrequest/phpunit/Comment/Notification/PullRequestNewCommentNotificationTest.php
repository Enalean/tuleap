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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\TemporaryTestDirectory;
use UserHelper;

final class PullRequestNewCommentNotificationTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;

    public function testNewCommentNotificationCanBeBuiltFromTheOwnersOfThePullRequestAndTheAddedComment(): void
    {
        $change_user      = $this->buildUser(102);
        $user_103         = $this->buildUser(103);
        $owners           = [$change_user, $user_103];
        $pull_request     = \Mockery::mock(PullRequest::class);
        $user_helper      = \Mockery::mock(UserHelper::class);
        $html_url_builder = \Mockery::mock(HTMLURLBuilder::class);

        \ForgeConfig::set('codendi_cache_dir', $this->getTmpDir());

        $user_helper->shouldReceive('getDisplayNameFromUser')->with($change_user)->andReturn('User A');
        $user_helper->shouldReceive('getAbsoluteUserURL')->with($change_user)->andReturn('https://example.com/users/usera');
        $html_url_builder->shouldReceive('getAbsolutePullRequestOverviewUrl')->with($pull_request)->andReturn('https://example.com/pr-link');
        $pull_request->shouldReceive('getId')->andReturn(14);
        $pull_request->shouldReceive('getTitle')->andReturn('PR title');

        $notification = PullRequestNewCommentNotification::fromOwnersAndComment(
            $user_helper,
            $html_url_builder,
            new FilterUserFromCollection(),
            $pull_request,
            $change_user,
            $owners,
            'My comment'
        );

        $this->assertEqualsCanonicalizing([$user_103], $notification->getRecipients());
        $this->assertSame($pull_request, $notification->getPullRequest());
        $this->assertEquals(
            <<<EOF
            User A commented on #14: PR title.

            My comment
            EOF,
            $notification->asPlaintext()
        );
        $this->assertEquals(
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

    private function buildUser(int $user_id): PFUser
    {
        return new PFUser(['user_id' => $user_id, 'language_id' => 'en']);
    }
}
