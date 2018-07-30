<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use GitRepository;
use GitRepositoryFactory;
use PFUser;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\PullRequest;
use URLVerification;

class PullRequestPermissionChecker
{
    /**
     * @var GitRepositoryFactory
     */
    private $git_repository_factory;
    /**
     * @var URLVerification
     */
    private $url_verification;

    public function __construct(
        GitRepositoryFactory $git_repository_factory,
        URLVerification $URL_verification
    ) {
        $this->git_repository_factory = $git_repository_factory;
        $this->url_verification       = $URL_verification;
    }

    public function checkPullRequestIsReadableByUser(PullRequest $pull_request, PFUser $user)
    {
        $repository = $this->getRepository($pull_request->getRepositoryId());
        $this->checkUserCanReadRepository($user, $repository);
    }

    private function getRepository($repository_id)
    {
        $repository = $this->git_repository_factory->getRepositoryById($repository_id);

        if (! $repository) {
            throw new \GitRepoNotFoundException();
        }

        return $repository;
    }

    private function checkUserCanReadRepository(PFUser $user, GitRepository $repository)
    {
        $this->url_verification->userCanAccessProject($user, $repository->getProject());

        if (! $repository->userCanRead($user)) {
            throw new UserCannotReadGitRepositoryException();
        }
    }
}
