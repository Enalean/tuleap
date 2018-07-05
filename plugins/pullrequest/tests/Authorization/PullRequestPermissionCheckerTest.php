<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

namespace Tuleap\PullRequest\Authorization;

use GitRepositoryFactory;
use Project_AccessPrivateException;
use Project_AccessProjectNotFoundException;
use Tuleap\PullRequest\PullRequest;

require_once __DIR__ . '/../bootstrap.php';

class PullRequestPermissionCheckerTest extends \TuleapTestCase
{
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

    public function setUp()
    {
        parent::setUp();
        $this->user                   = mock('PFUser');
        $this->pull_request           = mock('Tuleap\\PullRequest\\PullRequest');
        $this->repository             = mock('GitRepository');
        $this->git_repository_factory = mock('GitRepositoryFactory');
        $this->url_verification       = mock('URLVerification');
    }

    public function itThrowsWhenGitRepoIsNotFound()
    {
        stub($this->git_repository_factory)->getRepositoryById()->returns(null);

        $permission_checker = $this->instantiatePermissionChecker();

        $this->expectException('GitRepoNotFoundException');

        $permission_checker->checkPullRequestIsReadableByUser($this->pull_request, $this->user);
    }

    public function itLetsExceptionBubbleUpWhenUserHasNotAccessToProject()
    {
        stub($this->git_repository_factory)->getRepositoryById()->returns($this->repository);
        stub($this->repository)->getProject()->returns(\Mockery::mock(\Project::class));
        stub($this->url_verification)->userCanAccessProject()->throws(new Project_AccessPrivateException());

        $permission_checker = $this->instantiatePermissionChecker();

        $this->expectException('Project_AccessException');

        $permission_checker->checkPullRequestIsReadableByUser($this->pull_request, $this->user);
    }

    public function itLetsExceptionBubbleUpWhenProjectIsNotFound()
    {
        stub($this->git_repository_factory)->getRepositoryById()->returns($this->repository);
        stub($this->repository)->getProject()->returns(\Mockery::mock(\Project::class));
        stub($this->url_verification)->userCanAccessProject()->throws(new Project_AccessProjectNotFoundException());

        $permission_checker = $this->instantiatePermissionChecker();

        $this->expectException('Project_AccessProjectNotFoundException');

        $permission_checker->checkPullRequestIsReadableByUser($this->pull_request, $this->user);
    }

    public function itThrowsWhenUserCannotReadGitRepo()
    {
        stub($this->repository)->userCanRead($this->user)->returns(false);
        stub($this->repository)->getProject()->returns(\Mockery::mock(\Project::class));
        stub($this->git_repository_factory)->getRepositoryById()->returns($this->repository);

        $permission_checker = $this->instantiatePermissionChecker();

        $this->expectException('Tuleap\\PullRequest\\Exception\\UserCannotReadGitRepositoryException');

        $permission_checker->checkPullRequestIsReadableByUser($this->pull_request, $this->user);
    }

    private function instantiatePermissionChecker()
    {
        return new PullRequestPermissionChecker(
            $this->git_repository_factory,
            $this->url_verification
        );
    }
}
