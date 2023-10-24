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

use GitRepository;
use GitRepositoryFactory;
use PFUser;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\PullRequest;

class PullRequestPermissionChecker implements CheckUserCanAccessPullRequest
{
    public function __construct(
        private readonly GitRepositoryFactory $git_repository_factory,
        private readonly ProjectAccessChecker $project_access_checker,
        private readonly AccessControlVerifier $access_control_verifier,
    ) {
    }

    /**
     * @throws \Project_AccessException
     * @throws UserCannotReadGitRepositoryException
     * @throws \GitRepoNotFoundException
     */
    public function checkPullRequestIsReadableByUser(PullRequest $pull_request, PFUser $user): void
    {
        $repository = $this->getRepository($pull_request->getRepoDestId());
        $this->checkUserCanReadRepository($user, $repository);
    }

    /**
     * @throws \GitRepoNotFoundException
     * @throws UserCannotMergePullRequestException
     */
    public function checkPullRequestIsMergeableByUser(PullRequest $pull_request, PFUser $user): void
    {
        $repository = $this->getRepository($pull_request->getRepoDestId());

        if (! $this->access_control_verifier->canWrite($user, $repository, $pull_request->getBranchDest())) {
            throw new UserCannotMergePullRequestException($pull_request, $user);
        }
    }

    /**
     * @throws \GitRepoNotFoundException
     */
    private function getRepository(int $repository_id): GitRepository
    {
        $repository = $this->git_repository_factory->getRepositoryById($repository_id);

        if (! $repository) {
            throw new \GitRepoNotFoundException();
        }

        return $repository;
    }

    /**
     * @throws \Project_AccessException
     * @throws UserCannotReadGitRepositoryException
     */
    private function checkUserCanReadRepository(PFUser $user, GitRepository $repository): void
    {
        $this->project_access_checker->checkUserCanAccessProject($user, $repository->getProject());

        if (! $repository->userCanRead($user)) {
            throw new UserCannotReadGitRepositoryException();
        }
    }
}
