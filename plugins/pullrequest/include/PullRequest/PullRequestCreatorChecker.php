<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use GitRepository;
use PFUser;
use Tuleap\PullRequest\Exception\PullRequestAlreadyExistsException;
use Tuleap\PullRequest\Exception\PullRequestAnonymousUserException;
use Tuleap\PullRequest\Exception\PullRequestCannotBeCreatedException;
use Tuleap\PullRequest\Exception\PullRequestRepositoryMigratedOnGerritException;
use Tuleap\PullRequest\Exception\PullRequestTargetException;

final class PullRequestCreatorChecker
{
    public function __construct(private Dao $pull_request_dao)
    {
    }

    /**
     * @throws PullRequestAnonymousUserException
     * @throws PullRequestCannotBeCreatedException
     * @throws PullRequestRepositoryMigratedOnGerritException
     * @throws PullRequestTargetException
     * @throws PullRequestAlreadyExistsException
     */
    public function checkIfPullRequestCanBeCreated(
        PFUser $creator,
        GitRepository $repository_src,
        string $branch_src,
        GitRepository $repository_dest,
        string $branch_dest,
    ): void {
        if ($creator->isAnonymous()) {
            throw new PullRequestAnonymousUserException();
        }

        if (
            $repository_src->getId() !== $repository_dest->getId() &&
            ! $this->isSourceRepositoryAForkOfDestinationRepository($repository_src, $repository_dest)
        ) {
            throw new PullRequestTargetException();
        }

        if ($repository_dest->isMigratedToGerrit()) {
            throw new PullRequestRepositoryMigratedOnGerritException();
        }

        if ($this->isPullRequestBeCreatedOnTheSameBranch($repository_src, $repository_dest, $branch_src, $branch_dest)) {
            throw new PullRequestCannotBeCreatedException();
        }

        $this->checkIfPullRequestAlreadyExists(
            $repository_src,
            $repository_dest,
            $branch_src,
            $branch_dest
        );
    }

    private function isSourceRepositoryAForkOfDestinationRepository(
        GitRepository $repository_src,
        GitRepository $repository_dest,
    ): bool {
        return $repository_src->getParentId() === $repository_dest->getId();
    }

    private function isPullRequestBeCreatedOnTheSameBranch(
        GitRepository $repository_src,
        GitRepository $repository_dest,
        string $branch_src,
        string $branch_dest,
    ): bool {
        return $branch_src === $branch_dest && $repository_src->getId() === $repository_dest->getId();
    }

    /**
     * @throws PullRequestAlreadyExistsException
     */
    private function checkIfPullRequestAlreadyExists(
        GitRepository $repository_src,
        GitRepository $repository_dest,
        string $branch_src,
        string $branch_dest,
    ): void {
        $is_pull_request_already_existing = $this->pull_request_dao->isPullRequestAlreadyExisting(
            $repository_src->getId(),
            $branch_src,
            $repository_dest->getId(),
            $branch_dest
        );

        if ($is_pull_request_already_existing) {
            throw new PullRequestAlreadyExistsException();
        }
    }
}
