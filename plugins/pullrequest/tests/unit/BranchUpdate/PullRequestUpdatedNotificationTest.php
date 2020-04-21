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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\Git\GitPHP\Project;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\TemporaryTestDirectory;
use UserHelper;

final class PullRequestUpdatedNotificationTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;

    public function testUpdateNotificationCanBeBuilt(): void
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
        $pull_request->shouldReceive('getTitle')->andReturn('A contribution');
        $pull_request->shouldReceive('getBranchDest')->andReturn('master');

        $git_resource_accessor = \Mockery::mock(Project::class);
        $commit_a = \Mockery::mock(Commit::class);
        $commit_a->shouldReceive('GetTitle')->andReturn('Commit A');
        $git_resource_accessor->shouldReceive('GetCommit')
            ->with('230549fc4be136fcae6ea6ed574c2f5c7b922346')->andReturn($commit_a);
        $commit_b = \Mockery::mock(Commit::class);
        $commit_b->shouldReceive('GetTitle')->andReturn('Commit B');
        $git_resource_accessor->shouldReceive('GetCommit')
            ->with('fbe4dade4f744aa203ec35bf09f71475ecc3f9d6')->andReturn($commit_b);
        $git_resource_accessor->shouldReceive('GetCommit')
            ->with('a7d1692502252a5ec18bfcae4184498b1459810c')->andReturn(null);
        $url_to_commit_builder = \Mockery::mock(RepositoryURLToCommitBuilder::class);
        $url_to_commit_builder->shouldReceive('buildURLForReference')
            ->with('230549fc4be136fcae6ea6ed574c2f5c7b922346')->andReturn('https://example.com/230549');
        $url_to_commit_builder->shouldReceive('buildURLForReference')
            ->with('fbe4dade4f744aa203ec35bf09f71475ecc3f9d6')->andReturn('https://example.com/fbe4da');

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

        $this->assertEqualsCanonicalizing([$user_103], $notification->getRecipients());
        $this->assertSame($pull_request, $notification->getPullRequest());
        $this->assertEquals(
            <<<EOF
            User A pushed 2 commits updating the pull request #14: A contribution.

            230549fc4be1  Commit A
            fbe4dade4f74  Commit B
            EOF,
            $notification->asPlaintext()
        );
        $this->assertEquals(
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

        $git_resource_accessor = \Mockery::mock(Project::class);
        $git_resource_accessor->shouldReceive('GetCommit')->andReturn(null);

        $notification = PullRequestUpdatedNotification::fromOwnersAndReferences(
            \Mockery::mock(UserHelper::class),
            \Mockery::mock(HTMLURLBuilder::class),
            new FilterUserFromCollection(),
            \Mockery::mock(PullRequest::class),
            $change_user,
            $owners,
            $git_resource_accessor,
            \Mockery::mock(RepositoryURLToCommitBuilder::class),
            ['a7d1692502252a5ec18bfcae4184498b1459810c']
        );

        $this->assertNull($notification);
    }

    private function buildUser(int $user_id): PFUser
    {
        return new PFUser(['user_id' => $user_id, 'language_id' => 'en']);
    }
}
