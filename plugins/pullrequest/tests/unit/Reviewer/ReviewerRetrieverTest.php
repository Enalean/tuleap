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

use GitRepoNotFoundException;
use Project_AccessException;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\PullRequest;
use Tuleap\Test\Builders\UserTestBuilder;

final class ReviewerRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ReviewerDAO
     */
    private $dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PullRequestPermissionChecker
     */
    private $permission_checker;
    private ReviewerRetriever $retriever;

    protected function setUp(): void
    {
        $this->dao                = $this->createMock(ReviewerDAO::class);
        $this->user_manager       = $this->createMock(\UserManager::class);
        $this->permission_checker = $this->createMock(PullRequestPermissionChecker::class);

        $this->retriever = new ReviewerRetriever($this->user_manager, $this->dao, $this->permission_checker);
    }

    public function testCanRetrieveReviewerListForAPRWithoutReviewers(): void
    {
        $this->dao->method('searchReviewers')->willReturn([]);
        $pr = $this->createMock(PullRequest::class);
        $pr->method('getId')->willReturn(12);
        $reviewers = $this->retriever->getReviewers($pr);

        self::assertEmpty($reviewers);
    }

    public function testCanRetrieveReviewerList(): void
    {
        $user_row_147 = ['user_id' => 147];
        $user_row_148 = ['user_id' => 148];
        $this->dao->method('searchReviewers')->willReturn([$user_row_147, $user_row_148]);

        $user_147 = UserTestBuilder::aUser()->build();
        $user_148 = UserTestBuilder::aUser()->build();

        $this->user_manager->method('getUserInstanceFromRow')->willReturnMap([
            [$user_row_147, $user_147],
            [$user_row_148, $user_148],
        ]);

        $this->permission_checker->method('checkPullRequestIsReadableByUser');

        $pr = $this->createMock(PullRequest::class);
        $pr->method('getId')->willReturn(13);
        $reviewers = $this->retriever->getReviewers($pr);

        self::assertSame([$user_147, $user_148], $reviewers);
    }

    public function testUsersNotAbleToAccessThePullRequestAreNotAddedToTheReviewerList(): void
    {
        $user_row = ['user_id' => 149];
        $this->dao->method('searchReviewers')->willReturn([$user_row]);

        $user = UserTestBuilder::aUser()->build();
        $this->user_manager->method('getUserInstanceFromRow')->with($user_row)->willReturn($user);

        $this->permission_checker->method('checkPullRequestIsReadableByUser')->willThrowException(new UserCannotReadGitRepositoryException());

        $pr = $this->createMock(PullRequest::class);
        $pr->method('getId')->willReturn(13);
        $reviewers = $this->retriever->getReviewers($pr);

        self::assertEmpty($reviewers);
    }

    public function testUsersNotAbleToAccessTheProjectAreNotAddedToTheReviewerList(): void
    {
        $user_row = ['user_id' => 149];
        $this->dao->method('searchReviewers')->willReturn([$user_row]);

        $user = UserTestBuilder::aUser()->build();
        $this->user_manager->method('getUserInstanceFromRow')->with($user_row)->willReturn($user);

        $this->permission_checker->method('checkPullRequestIsReadableByUser')
            ->willThrowException(
                new class extends Project_AccessException
                {
                }
            );

        $pr = $this->createMock(PullRequest::class);
        $pr->method('getId')->willReturn(13);
        $reviewers = $this->retriever->getReviewers($pr);

        self::assertEmpty($reviewers);
    }

    public function testUsersNotAbleToAccessTheGitRepositoryAreNotAddedToTheReviewerList(): void
    {
        $user_row = ['user_id' => 149];
        $this->dao->method('searchReviewers')->willReturn([$user_row]);

        $user = UserTestBuilder::aUser()->build();
        $this->user_manager->method('getUserInstanceFromRow')->with($user_row)->willReturn($user);

        $this->permission_checker->method('checkPullRequestIsReadableByUser')
            ->willThrowException(new GitRepoNotFoundException());

        $pr = $this->createMock(PullRequest::class);
        $pr->method('getId')->willReturn(13);
        $reviewers = $this->retriever->getReviewers($pr);

        self::assertEmpty($reviewers);
    }
}
