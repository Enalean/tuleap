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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project_AccessException;
use Project_AccessPrivateException;
use Project_AccessProjectNotFoundException;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;

class PullRequestPermissionCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var GitRepositoryFactory
     */
    private $git_repository_factory;
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var PullRequest
     */
    private $pull_request;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectAccessChecker
     */
    private $project_access_checker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AccessControlVerifier
     */
    private $access_control_verifier;
    /**
     * @var \GitRepository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user                    = \Mockery::spy(\PFUser::class);
        $this->pull_request            = \Mockery::spy(\Tuleap\PullRequest\PullRequest::class);
        $this->repository              = \Mockery::spy(\GitRepository::class);
        $this->git_repository_factory  = \Mockery::spy(\GitRepositoryFactory::class);
        $this->project_access_checker  = \Mockery::spy(ProjectAccessChecker::class);
        $this->access_control_verifier = \Mockery::mock(AccessControlVerifier::class);
    }

    public function testItThrowsWhenGitRepoIsNotFound(): void
    {
        $this->pull_request->shouldReceive('getRepositoryId')->andReturn(10);
        $this->git_repository_factory->shouldReceive('getRepositoryById')->andReturns(null);

        $permission_checker = $this->instantiatePermissionChecker();

        $this->expectException(GitRepoNotFoundException::class);

        $permission_checker->checkPullRequestIsReadableByUser($this->pull_request, $this->user);
    }

    public function testItLetsExceptionBubbleUpWhenUserHasNotAccessToProject(): void
    {
        $this->pull_request->shouldReceive('getRepositoryId')->andReturn(10);
        $this->git_repository_factory->shouldReceive('getRepositoryById')->andReturns($this->repository);
        $this->repository->shouldReceive('getProject')->andReturns(\Mockery::mock(\Project::class));
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andThrows(new Project_AccessPrivateException());

        $permission_checker = $this->instantiatePermissionChecker();

        $this->expectException(Project_AccessException::class);

        $permission_checker->checkPullRequestIsReadableByUser($this->pull_request, $this->user);
    }

    public function testItLetsExceptionBubbleUpWhenProjectIsNotFound(): void
    {
        $this->pull_request->shouldReceive('getRepositoryId')->andReturn(10);
        $this->git_repository_factory->shouldReceive('getRepositoryById')->andReturns($this->repository);
        $this->repository->shouldReceive('getProject')->andReturns(\Mockery::mock(\Project::class));
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->andThrows(new Project_AccessProjectNotFoundException());

        $permission_checker = $this->instantiatePermissionChecker();

        $this->expectException(Project_AccessProjectNotFoundException::class);

        $permission_checker->checkPullRequestIsReadableByUser($this->pull_request, $this->user);
    }

    public function testItThrowsWhenUserCannotReadGitRepo(): void
    {
        $this->pull_request->shouldReceive('getRepositoryId')->andReturn(10);
        $this->repository->shouldReceive('userCanRead')->with($this->user)->andReturns(false);
        $this->repository->shouldReceive('getProject')->andReturns(\Mockery::mock(\Project::class));
        $this->git_repository_factory->shouldReceive('getRepositoryById')->andReturns($this->repository);

        $permission_checker = $this->instantiatePermissionChecker();

        $this->expectException(UserCannotReadGitRepositoryException::class);

        $permission_checker->checkPullRequestIsReadableByUser($this->pull_request, $this->user);
    }

    public function testChecksTheUserCanMergeAPullRequest(): void
    {
        $this->pull_request->shouldReceive('getRepoDestId')->andReturn(10);
        $this->git_repository_factory->shouldReceive('getRepositoryById')->andReturns($this->repository);
        $this->repository->shouldReceive('userCanAccessProject')->andReturn(\Mockery::mock(\Project::class));
        $this->access_control_verifier->shouldReceive('canWrite')->andReturn(true);

        $permission_checker = $this->instantiatePermissionChecker();

        $permission_checker->checkPullRequestIsMergeableByUser($this->pull_request, $this->user);
    }

    public function testRejectsUserThatCannotMergeAPullRequest(): void
    {
        $this->pull_request->shouldReceive('getRepoDestId')->andReturn(10);
        $this->git_repository_factory->shouldReceive('getRepositoryById')->andReturns($this->repository);
        $this->repository->shouldReceive('userCanAccessProject')->andReturn(\Mockery::mock(\Project::class));
        $this->access_control_verifier->shouldReceive('canWrite')->andReturn(false);

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
