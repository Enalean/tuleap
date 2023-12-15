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

namespace Tuleap\PullRequest\Reviewer;

use Project_AccessException;
use Tuleap\PullRequest\Authorization\CheckUserCanAccessPullRequest;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequest\Reviewer\RetrieveReviewers;
use Tuleap\User\ProvideUserFromRow;

class ReviewerRetriever
{
    public function __construct(
        private readonly ProvideUserFromRow $user_manager,
        private readonly RetrieveReviewers $reviewer_dao,
        private readonly CheckUserCanAccessPullRequest $pull_request_permission_checker,
    ) {
    }

    /**
     * @return \PFUser[]
     */
    public function getReviewers(PullRequest $pull_request): array
    {
        $user_rows = $this->reviewer_dao->searchReviewers($pull_request->getId());

        $users = [];

        foreach ($user_rows as $user_row) {
            $reviewer = $this->user_manager->getUserInstanceFromRow($user_row);

            try {
                $this->pull_request_permission_checker->checkPullRequestIsReadableByUser($pull_request, $reviewer);
                $users[] = $reviewer;
            } catch (\GitRepoNotFoundException | Project_AccessException | UserCannotReadGitRepositoryException $exception) {
            }
        }

        return $users;
    }
}
