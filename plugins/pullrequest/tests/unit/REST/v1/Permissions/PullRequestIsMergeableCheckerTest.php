<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1\Permissions;

use GitRepoNotFoundException;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Authorization\UserCannotMergePullRequestException;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class PullRequestIsMergeableCheckerTest extends TestCase
{
    private PullRequestIsMergeableChecker $pull_request_is_mergeable_checker;
    private \Tuleap\PullRequest\PullRequest $pullrequest;
    private \PFUser $user;
    /**
     * @var PullRequestPermissionChecker&\PHPUnit\Framework\MockObject\MockObject
     */
    private $permission_checker;

    protected function setUp(): void
    {
        $this->permission_checker = $this->createMock(PullRequestPermissionChecker::class);

        $this->pull_request_is_mergeable_checker = new PullRequestIsMergeableChecker($this->permission_checker);
        $this->pullrequest                       = PullRequestTestBuilder::aPullRequestInReview()->build();
        $this->user                              = UserTestBuilder::anActiveUser()->build();
    }

    public function testItThrowsA403ExceptionWhenUserCanNotMergePullRequest(): void
    {
        $this->permission_checker->method("checkPullRequestIsMergeableByUser")
            ->willThrowException(new UserCannotMergePullRequestException($this->pullrequest, $this->user));

        $this->expectExceptionCode(403);
        $this->pull_request_is_mergeable_checker->checkUserCanMerge($this->pullrequest, $this->user);
    }

    public function testItThrowsA403ExceptionWhenGitRepositoryIsNotFound(): void
    {
        $this->permission_checker->method("checkPullRequestIsMergeableByUser")
            ->willThrowException(new GitRepoNotFoundException());
        $this->expectExceptionCode(404);
        $this->pull_request_is_mergeable_checker->checkUserCanMerge($this->pullrequest, $this->user);
    }

    public function testItDoesNothingWHenUSerCanMergePullrequest(): void
    {
        $this->permission_checker->expects(self::once())->method("checkPullRequestIsMergeableByUser");
        $this->pull_request_is_mergeable_checker->checkUserCanMerge($this->pullrequest, $this->user);
    }
}
