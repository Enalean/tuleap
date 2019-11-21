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

use GitRepositoryFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project_AccessPrivateException;
use Project_AccessProjectNotFoundException;
use Tuleap\PullRequest\PullRequest;

require_once __DIR__ . '/../bootstrap.php';

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
     * @var \URLVerification
     */
    private $url_verification;
    /**
     * @var \GitRepository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user                   = \Mockery::spy(\PFUser::class);
        $this->pull_request           = \Mockery::spy(\Tuleap\PullRequest\PullRequest::class);
        $this->repository             = \Mockery::spy(\GitRepository::class);
        $this->git_repository_factory = \Mockery::spy(\GitRepositoryFactory::class);
        $this->url_verification       = \Mockery::spy(\URLVerification::class);
    }

    public function testItThrowsWhenGitRepoIsNotFound(): void
    {
        $this->git_repository_factory->shouldReceive('getRepositoryById')->andReturns(null);

        $permission_checker = $this->instantiatePermissionChecker();

        $this->expectException('GitRepoNotFoundException');

        $permission_checker->checkPullRequestIsReadableByUser($this->pull_request, $this->user);
    }

    public function testItLetsExceptionBubbleUpWhenUserHasNotAccessToProject(): void
    {
        $this->git_repository_factory->shouldReceive('getRepositoryById')->andReturns($this->repository);
        $this->repository->shouldReceive('getProject')->andReturns(\Mockery::mock(\Project::class));
        $this->url_verification->shouldReceive('userCanAccessProject')->andThrows(new Project_AccessPrivateException());

        $permission_checker = $this->instantiatePermissionChecker();

        $this->expectException('Project_AccessException');

        $permission_checker->checkPullRequestIsReadableByUser($this->pull_request, $this->user);
    }

    public function testItLetsExceptionBubbleUpWhenProjectIsNotFound(): void
    {
        $this->git_repository_factory->shouldReceive('getRepositoryById')->andReturns($this->repository);
        $this->repository->shouldReceive('getProject')->andReturns(\Mockery::mock(\Project::class));
        $this->url_verification->shouldReceive('userCanAccessProject')->andThrows(new Project_AccessProjectNotFoundException());

        $permission_checker = $this->instantiatePermissionChecker();

        $this->expectException('Project_AccessProjectNotFoundException');

        $permission_checker->checkPullRequestIsReadableByUser($this->pull_request, $this->user);
    }

    public function testItThrowsWhenUserCannotReadGitRepo(): void
    {
        $this->repository->shouldReceive('userCanRead')->with($this->user)->andReturns(false);
        $this->repository->shouldReceive('getProject')->andReturns(\Mockery::mock(\Project::class));
        $this->git_repository_factory->shouldReceive('getRepositoryById')->andReturns($this->repository);

        $permission_checker = $this->instantiatePermissionChecker();

        $this->expectException('Tuleap\\PullRequest\\Exception\\UserCannotReadGitRepositoryException');

        $permission_checker->checkPullRequestIsReadableByUser($this->pull_request, $this->user);
    }

    private function instantiatePermissionChecker(): PullRequestPermissionChecker
    {
        return new PullRequestPermissionChecker(
            $this->git_repository_factory,
            $this->url_verification
        );
    }
}
