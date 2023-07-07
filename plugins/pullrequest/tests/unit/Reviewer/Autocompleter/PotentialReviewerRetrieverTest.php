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

use PFUser;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\PullRequest;
use Tuleap\Test\Builders\UserTestBuilder;
use UserDao;
use UserManager;

final class PotentialReviewerRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserDao
     */
    private $user_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PullRequestPermissionChecker
     */
    private $pull_request_permission_checker;
    private PotentialReviewerRetriever $retriever;

    protected function setUp(): void
    {
        $this->user_manager                    = $this->createMock(UserManager::class);
        $this->user_dao                        = $this->createMock(UserDao::class);
        $this->pull_request_permission_checker = $this->createMock(PullRequestPermissionChecker::class);

        $this->retriever = new PotentialReviewerRetriever(
            $this->user_manager,
            $this->user_dao,
            $this->pull_request_permission_checker
        );
    }

    public function testSearchUsersThatCanAccessThePullRequest(): void
    {
        $pull_request = $this->createMock(PullRequest::class);

        $this->user_dao->method('searchUserNameLike')->willReturn([
            ['user_id' => 101],
            ['user_id' => 102],
            ['user_id' => 103],
        ]);
        $this->user_dao->method('foundRows')->willReturn(10);

        $user_101 = UserTestBuilder::aUser()->withId(101)->build();
        $user_102 = UserTestBuilder::aUser()->withId(102)->build();
        $user_103 = UserTestBuilder::aUser()->withId(103)->build();

        $this->user_manager->method('getUserInstanceFromRow')
            ->willReturn($user_101, $user_102, $user_103);

        $this->pull_request_permission_checker
            ->method('checkPullRequestIsReadableByUser')
            ->willReturnCallback(
                function (PullRequest $pull_request_param, PFUser $user_param) use ($pull_request, $user_102): void {
                    if ($pull_request_param === $pull_request && $user_param === $user_102) {
                        throw new UserCannotReadGitRepositoryException();
                    }
                }
            );

        $potential_reviewers = $this->retriever->getPotentialReviewers(
            $pull_request,
            UsernameToSearch::fromString('user'),
            10
        );

        self::assertEquals([$user_101, $user_103], $potential_reviewers);
    }

    public function testSearchPotentialReviewersUpToTheRequestedLimit(): void
    {
        $pull_request = $this->createMock(PullRequest::class);

        $this->user_dao->method('searchUserNameLike')->willReturn([
            ['user_id' => 101],
            ['user_id' => 102],
        ]);

        $this->user_manager->method('getUserInstanceFromRow')
            ->willReturn(UserTestBuilder::aUser()->build());

        $this->pull_request_permission_checker->method('checkPullRequestIsReadableByUser');

        $potential_reviewers = $this->retriever->getPotentialReviewers(
            $pull_request,
            UsernameToSearch::fromString('user'),
            1
        );

        self::assertCount(1, $potential_reviewers);
    }

    public function testReturnsSearchEvenIfNoResultAreFound(): void
    {
        $pull_request = $this->createMock(PullRequest::class);

        $this->user_dao->method('searchUserNameLike')->willReturn([]);
        $this->user_dao->method('foundRows')->willReturn(0);

        $potential_reviewers = $this->retriever->getPotentialReviewers(
            $pull_request,
            UsernameToSearch::fromString('user'),
            20
        );

        self::assertEmpty($potential_reviewers);
    }
}
