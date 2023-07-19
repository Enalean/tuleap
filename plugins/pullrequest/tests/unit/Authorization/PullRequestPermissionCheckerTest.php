<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Authorization;

use GitRepoNotFoundException;
use GitRepositoryFactory;
use Project_AccessException;
use Project_AccessPrivateException;
use Project_AccessProjectNotFoundException;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class PullRequestPermissionCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitRepositoryFactory
     */
    private $git_repository_factory;
    private \PFUser $user;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PullRequest
     */
    private $pull_request;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProjectAccessChecker
     */
    private $project_access_checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AccessControlVerifier
     */
    private $access_control_verifier;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\GitRepository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user                    = UserTestBuilder::aUser()->build();
        $this->pull_request            = $this->createMock(\Tuleap\PullRequest\PullRequest::class);
        $this->repository              = $this->createMock(\GitRepository::class);
        $this->git_repository_factory  = $this->createMock(\GitRepositoryFactory::class);
        $this->project_access_checker  = $this->createMock(ProjectAccessChecker::class);
        $this->access_control_verifier = $this->createMock(AccessControlVerifier::class);
    }

    public function testItThrowsWhenGitRepoIsNotFound(): void
    {
        $this->pull_request->method('getRepoDestId')->willReturn(10);
        $this->git_repository_factory->method('getRepositoryById')->willReturn(null);

        $permission_checker = $this->instantiatePermissionChecker();

        $this->expectException(GitRepoNotFoundException::class);

        $permission_checker->checkPullRequestIsReadableByUser($this->pull_request, $this->user);
    }

    public function testItLetsExceptionBubbleUpWhenUserHasNotAccessToProject(): void
    {
        $this->pull_request->method('getRepoDestId')->willReturn(10);
        $this->git_repository_factory->method('getRepositoryById')->willReturn($this->repository);
        $this->repository->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());
        $this->project_access_checker->method('checkUserCanAccessProject')->willThrowException(new Project_AccessPrivateException());

        $permission_checker = $this->instantiatePermissionChecker();

        $this->expectException(Project_AccessException::class);

        $permission_checker->checkPullRequestIsReadableByUser($this->pull_request, $this->user);
    }

    public function testItLetsExceptionBubbleUpWhenProjectIsNotFound(): void
    {
        $this->pull_request->method('getRepoDestId')->willReturn(10);
        $this->git_repository_factory->method('getRepositoryById')->willReturn($this->repository);
        $this->repository->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());
        $this->project_access_checker->method('checkUserCanAccessProject')->willThrowException(new Project_AccessProjectNotFoundException());

        $permission_checker = $this->instantiatePermissionChecker();

        $this->expectException(Project_AccessProjectNotFoundException::class);

        $permission_checker->checkPullRequestIsReadableByUser($this->pull_request, $this->user);
    }

    public function testItThrowsWhenUserCannotReadTheDestinationGitRepo(): void
    {
        $this->pull_request->method('getRepoDestId')->willReturn(10);
        $this->repository->method('userCanRead')->with($this->user)->willReturn(false);
        $this->repository->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());
        $this->git_repository_factory->method('getRepositoryById')->willReturn($this->repository);

        $this->project_access_checker->method('checkUserCanAccessProject');

        $permission_checker = $this->instantiatePermissionChecker();

        $this->expectException(UserCannotReadGitRepositoryException::class);

        $permission_checker->checkPullRequestIsReadableByUser($this->pull_request, $this->user);
    }

    public function testChecksTheUserCanMergeAPullRequest(): void
    {
        $this->expectNotToPerformAssertions();
        $this->pull_request->method('getId')->willReturn(1);
        $this->pull_request->method('getRepoDestId')->willReturn(10);
        $this->pull_request->method('getBranchDest')->willReturn('main');
        $this->git_repository_factory->method('getRepositoryById')->willReturn($this->repository);
        $this->access_control_verifier->method('canWrite')->willReturn(true);

        $permission_checker = $this->instantiatePermissionChecker();

        $permission_checker->checkPullRequestIsMergeableByUser($this->pull_request, $this->user);
    }

    public function testRejectsUserThatCannotMergeAPullRequest(): void
    {
        $this->pull_request->method('getId')->willReturn(1);
        $this->pull_request->method('getRepoDestId')->willReturn(10);
        $this->pull_request->method('getBranchDest')->willReturn('main');
        $this->git_repository_factory->method('getRepositoryById')->willReturn($this->repository);
        $this->access_control_verifier->method('canWrite')->willReturn(false);

        $permission_checker = $this->instantiatePermissionChecker();

        $this->expectException(UserCannotMergePullRequestException::class);

        $permission_checker->checkPullRequestIsMergeableByUser($this->pull_request, $this->user);
    }

    private function instantiatePermissionChecker(): PullRequestPermissionChecker
    {
        return new PullRequestPermissionChecker(
            $this->git_repository_factory,
            $this->project_access_checker,
            $this->access_control_verifier
        );
    }
}
