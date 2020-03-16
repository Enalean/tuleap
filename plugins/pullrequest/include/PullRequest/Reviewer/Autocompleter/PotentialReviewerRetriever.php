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

use GitRepoNotFoundException;
use PFUser;
use Project_AccessException;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\PullRequest;
use UserDao;
use UserManager;

class PotentialReviewerRetriever
{
    /**
     * @var UserDao
     */
    private $dao;
    /**
     * @var PullRequestPermissionChecker
     */
    private $pull_request_permission_checker;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(
        UserManager $user_manager,
        UserDao $dao,
        PullRequestPermissionChecker $pull_request_permission_checker
    ) {
        $this->user_manager                    = $user_manager;
        $this->dao                             = $dao;
        $this->pull_request_permission_checker = $pull_request_permission_checker;
    }

    /**
     * @return PFUser[]
     * @psalm-return list<PFUser>
     */
    public function getPotentialReviewers(
        PullRequest $pull_request,
        UsernameToSearch $username_to_search,
        int $max_number_reviewers
    ): array {
        $potential_reviewers = [];

        $offset     = 0;

        while (count($potential_reviewers) < $max_number_reviewers) {
            $dar = $this->dao->searchUserNameLike($username_to_search->toString(), $max_number_reviewers, $offset);

            if ($dar === false) {
                throw new \DataAccessQueryException('Cannot access data while searching for users');
            }

            foreach ($dar as $row) {
                $user = $this->user_manager->getUserInstanceFromRow($row);

                try {
                    $this->pull_request_permission_checker->checkPullRequestIsReadableByUser($pull_request, $user);
                } catch (GitRepoNotFoundException | Project_AccessException | UserCannotReadGitRepositoryException $e) {
                    continue;
                }

                $potential_reviewers[] = $user;

                if (count($potential_reviewers) >= $max_number_reviewers) {
                    return $potential_reviewers;
                }
            }

            $offset    += $max_number_reviewers;
            $found_rows = (int) $this->dao->foundRows();

            if ($offset >= $found_rows) {
                return $potential_reviewers;
            }
        }

        return $potential_reviewers;
    }
}
