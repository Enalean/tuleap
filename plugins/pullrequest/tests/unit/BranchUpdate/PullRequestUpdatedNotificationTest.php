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

namespace Tuleap\PullRequest\BranchUpdate;

use PFUser;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\Git\GitPHP\Project;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\TemporaryTestDirectory;
use UserHelper;

final class PullRequestUpdatedNotificationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;

    public function testUpdateNotificationCanBeBuilt(): void
    {
        $change_user      = $this->buildUser(102);
        $user_103         = $this->buildUser(103);
        $owners           = [$change_user, $user_103];
        $pull_request     = $this->createMock(PullRequest::class);
        $user_helper      = $this->createMock(UserHelper::class);
        $html_url_builder = $this->createMock(HTMLURLBuilder::class);

        \ForgeConfig::set('codendi_cache_dir', $this->getTmpDir());

        $user_helper->method('getDisplayNameFromUser')->with($change_user)->willReturn('User A');
        $user_helper->method('getAbsoluteUserURL')->with($change_user)->willReturn('https://example.com/users/usera');
        $html_url_builder->method('getAbsolutePullRequestOverviewUrl')->with($pull_request)->willReturn('https://example.com/pr-link');
        $pull_request->method('getId')->willReturn(14);
        $pull_request->method('getTitle')->willReturn('A contribution');
        $pull_request->method('getBranchDest')->willReturn('master');

        $git_resource_accessor = $this->createMock(Project::class);
        $commit_a              = $this->createMock(Commit::class);
        $commit_a->method('GetTitle')->willReturn('Commit A');

        $commit_b = $this->createMock(Commit::class);
        $commit_b->method('GetTitle')->willReturn('Commit B');

        $git_resource_accessor->method('GetCommit')->willReturnMap([
            ['230549fc4be136fcae6ea6ed574c2f5c7b922346', $commit_a],
            ['fbe4dade4f744aa203ec35bf09f71475ecc3f9d6', $commit_b],
            ['a7d1692502252a5ec18bfcae4184498b1459810c', null],
        ]);

        $url_to_commit_builder = $this->createMock(RepositoryURLToCommitBuilder::class);
        $url_to_commit_builder->method('buildURLForReference')->willReturnMap([
            ['230549fc4be136fcae6ea6ed574c2f5c7b922346', 'https://example.com/230549'],
            ['fbe4dade4f744aa203ec35bf09f71475ecc3f9d6', 'https://example.com/fbe4da'],
            [],
        ]);

        $notification = PullRequestUpdatedNotification::fromOwnersAndReferences(
            $user_helper,
            $html_url_builder,
            new FilterUserFromCollection(),
            $pull_request,
            $change_user,
            $owners,
            $git_resource_accessor,
            $url_to_commit_builder,
            ['230549fc4be136fcae6ea6ed574c2f5c7b922346', 'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6', 'a7d1692502252a5ec18bfcae4184498b1459810c']
        );

        self::assertNotNull($notification);
        self::assertEqualsCanonicalizing([$user_103], $notification->getRecipients());
        self::assertSame($pull_request, $notification->getPullRequest());
        self::assertEquals(
            <<<EOF
            User A pushed 2 commits updating the pull request #14: A contribution.

            230549fc4be1  Commit A
            fbe4dade4f74  Commit B
            EOF,
            $notification->asPlaintext()
        );
        self::assertEquals(
            <<<EOF
            <p>
            <a href="https://example.com/users/usera">User A</a> pushed 2 commits updating the pull request <a href="https://example.com/pr-link">#14</a>: A contribution.</p>
            <ul>
                    <li><a href="https://example.com/230549">230549fc4be1</a> Commit A</li>
                    <li><a href="https://example.com/fbe4da">fbe4dade4f74</a> Commit B</li>
            </ul>

            EOF,
            $notification->asEnhancedContent()->toString()
        );
    }

    public function testCannotBuildWhenNoCommitAssociatedWithTheNotificationCanBeRetrieved(): void
    {
        $change_user = $this->buildUser(102);
        $owners      = [$change_user];

        $git_resource_accessor = $this->createMock(Project::class);
        $git_resource_accessor->method('GetCommit')->willReturn(null);

        $notification = PullRequestUpdatedNotification::fromOwnersAndReferences(
            $this->createMock(UserHelper::class),
            $this->createMock(HTMLURLBuilder::class),
            new FilterUserFromCollection(),
            $this->createMock(PullRequest::class),
            $change_user,
            $owners,
            $git_resource_accessor,
            $this->createMock(RepositoryURLToCommitBuilder::class),
            ['a7d1692502252a5ec18bfcae4184498b1459810c']
        );

        self::assertNull($notification);
    }

    private function buildUser(int $user_id): PFUser
    {
        return new PFUser(['user_id' => $user_id, 'language_id' => 'en']);
    }
}
