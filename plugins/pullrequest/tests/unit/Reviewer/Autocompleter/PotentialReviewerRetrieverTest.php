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

namespace Tuleap\PullRequest\Reviewer\Autocompleter;

use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\PullRequest;
use UserDao;
use UserManager;

final class PotentialReviewerRetrieverTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserDao
     */
    private $user_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PullRequestPermissionChecker
     */
    private $pull_request_permission_checker;

    /**
     * @var PotentialReviewerRetriever
     */
    private $retriever;

    protected function setUp(): void
    {
        $this->user_manager                    = Mockery::mock(UserManager::class);
        $this->user_dao                        = Mockery::mock(UserDao::class);
        $this->pull_request_permission_checker = Mockery::mock(PullRequestPermissionChecker::class);

        $this->retriever = new PotentialReviewerRetriever(
            $this->user_manager,
            $this->user_dao,
            $this->pull_request_permission_checker
        );
    }

    public function testSearchUsersThatCanAccessThePullRequest(): void
    {
        $pull_request = Mockery::mock(PullRequest::class);

        $this->user_dao->shouldReceive('searchUserNameLike')->andReturn(\TestHelper::arrayToDar(
            ['user_id' => 101],
            ['user_id' => 102],
            ['user_id' => 103],
        ));
        $this->user_dao->shouldReceive('foundRows')->andReturn('10');

        $user_101 = Mockery::mock(PFUser::class);
        $user_102 = Mockery::mock(PFUser::class);
        $user_103 = Mockery::mock(PFUser::class);

        $this->user_manager->shouldReceive('getUserInstanceFromRow')
            ->andReturn($user_101, $user_102, $user_103);

        $this->pull_request_permission_checker->shouldReceive('checkPullRequestIsReadableByUser')
            ->with($pull_request, Mockery::not($user_102));
        $this->pull_request_permission_checker->shouldReceive('checkPullRequestIsReadableByUser')
            ->with($pull_request, $user_102)->andThrow(UserCannotReadGitRepositoryException::class);

        $potential_reviewers = $this->retriever->getPotentialReviewers(
            $pull_request,
            UsernameToSearch::fromString('user'),
            10
        );

        $this->assertEquals([$user_101, $user_103], $potential_reviewers);
    }

    public function testSearchPotentialReviewersUpToTheRequestedLimit(): void
    {
        $pull_request = Mockery::mock(PullRequest::class);

        $this->user_dao->shouldReceive('searchUserNameLike')->andReturn(\TestHelper::arrayToDar(
            ['user_id' => 101],
            ['user_id' => 102],
        ));

        $this->user_manager->shouldReceive('getUserInstanceFromRow')
            ->andReturn(Mockery::mock(PFUser::class));

        $this->pull_request_permission_checker->shouldReceive('checkPullRequestIsReadableByUser');

        $potential_reviewers = $this->retriever->getPotentialReviewers(
            $pull_request,
            UsernameToSearch::fromString('user'),
            1
        );

        $this->assertCount(1, $potential_reviewers);
    }

    public function testReturnsSearchEvenIfNoResultAreFound(): void
    {
        $pull_request = Mockery::mock(PullRequest::class);

        $this->user_dao->shouldReceive('searchUserNameLike')->andReturn(\TestHelper::emptyDar());
        $this->user_dao->shouldReceive('foundRows')->andReturn('0');

        $potential_reviewers = $this->retriever->getPotentialReviewers(
            $pull_request,
            UsernameToSearch::fromString('user'),
            20
        );

        $this->assertEmpty($potential_reviewers);
    }
}
