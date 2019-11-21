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

final class ReviewerUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ReviewerDAO
     */
    private $dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PullRequestPermissionChecker
     */
    private $permissions_checker;

    /**
     * @var ReviewerUpdater
     */
    private $reviewer_updater;

    protected function setUp() : void
    {
        $this->dao                 = Mockery::mock(ReviewerDAO::class);
        $this->permissions_checker = Mockery::mock(PullRequestPermissionChecker::class);

        $this->reviewer_updater = new ReviewerUpdater($this->dao, $this->permissions_checker);
    }

    public function testListOfReviewersCanBeCleared(): void
    {
        $pull_request = Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(85);

        $this->dao->shouldReceive('setReviewers')->with(85);

        $this->reviewer_updater->updatePullRequestReviewers($pull_request);
    }

    public function testSetListOfReviewers(): void
    {
        $pull_request = Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(85);

        $user_1 = Mockery::mock(\PFUser::class);
        $user_1->shouldReceive('getId')->andReturn('101');
        $user_2 = Mockery::mock(\PFUser::class);
        $user_2->shouldReceive('getId')->andReturn('102');

        $this->dao->shouldReceive('setReviewers')->with(85, 101, 102);
        $this->permissions_checker->shouldReceive('checkPullRequestIsReadableByUser')->twice();

        $this->reviewer_updater->updatePullRequestReviewers($pull_request, $user_1, $user_2);
    }

    public function testUpdateTheListOfReviewersIsRejectedIfOneOfTheNewReviewerCanNotAccessThePullRequest(): void
    {
        $pull_request = Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(85);

        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn('101');

        $this->permissions_checker->shouldReceive('checkPullRequestIsReadableByUser')
            ->andThrow(UserCannotReadGitRepositoryException::class);

        $this->expectException(UserCannotBeAddedAsReviewerException::class);
        $this->reviewer_updater->updatePullRequestReviewers($pull_request, $user);
    }
}
