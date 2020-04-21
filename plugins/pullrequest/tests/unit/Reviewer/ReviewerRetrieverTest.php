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

namespace Tuleap\PullRequest\Reviewer;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\PullRequest;

final class ReviewerRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ReviewerDAO
     */
    private $dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PullRequestPermissionChecker
     */
    private $permission_checker;

    /**
     * @var ReviewerRetriever
     */
    private $retriever;

    protected function setUp(): void
    {
        $this->dao                = Mockery::mock(ReviewerDAO::class);
        $this->user_manager       = Mockery::mock(\UserManager::class);
        $this->permission_checker = Mockery::mock(PullRequestPermissionChecker::class);

        $this->retriever = new ReviewerRetriever($this->user_manager, $this->dao, $this->permission_checker);
    }

    public function testCanRetrieveReviewerListForAPRWithoutReviewers(): void
    {
        $this->dao->shouldReceive('searchReviewers')->andReturn([]);
        $pr = Mockery::mock(PullRequest::class);
        $pr->shouldReceive('getId')->andReturn(12);
        $reviewers = $this->retriever->getReviewers($pr);

        $this->assertEmpty($reviewers);
    }


    public function testCanRetrieveReviewerList(): void
    {
        $user_row_147 = ['user_id' => 147];
        $user_row_148 = ['user_id' => 148];
        $this->dao->shouldReceive('searchReviewers')->andReturn([$user_row_147, $user_row_148]);

        $user_147 = Mockery::mock(\PFUser::class);
        $user_148 = Mockery::mock(\PFUser::class);
        $this->user_manager->shouldReceive('getUserInstanceFromRow')->with($user_row_147)->andReturn($user_147);
        $this->user_manager->shouldReceive('getUserInstanceFromRow')->with($user_row_148)->andReturn($user_148);

        $this->permission_checker->shouldReceive('checkPullRequestIsReadableByUser');

        $pr = Mockery::mock(PullRequest::class);
        $pr->shouldReceive('getId')->andReturn(13);
        $reviewers = $this->retriever->getReviewers($pr);

        $this->assertSame([$user_147, $user_148], $reviewers);
    }

    public function testUsersNotAbleToAccessThePullRequestAreNotAddedToTheReviewerList(): void
    {
        $user_row = ['user_id' => 149];
        $this->dao->shouldReceive('searchReviewers')->andReturn([$user_row]);

        $user = Mockery::mock(\PFUser::class);
        $this->user_manager->shouldReceive('getUserInstanceFromRow')->with($user_row)->andReturn($user);

        $this->permission_checker->shouldReceive('checkPullRequestIsReadableByUser')->andThrow(new UserCannotReadGitRepositoryException());

        $pr = Mockery::mock(PullRequest::class);
        $pr->shouldReceive('getId')->andReturn(13);
        $reviewers = $this->retriever->getReviewers($pr);

        $this->assertEmpty($reviewers);
    }
}
