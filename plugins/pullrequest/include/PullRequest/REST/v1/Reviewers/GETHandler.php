<?php
/**
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1\Reviewers;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\PullRequest\PullRequest\REST\v1\UserNotFoundFault;
use Tuleap\PullRequest\PullRequest\Reviewer\SearchRepositoryReviewers;
use Tuleap\User\REST\MinimalUserRepresentation;
use Tuleap\User\RetrieveUserById;

final class GETHandler
{
    public function __construct(
        private readonly RetrieveUserById $retrieve_user_by_id,
        private readonly SearchRepositoryReviewers $search_repository_reviewers,
    ) {
    }

    /**
     * @return Ok<RepositoryPullRequestsReviewersRepresentation> | Err<Fault>
     */
    public function handle(\GitRepository $repository, int $limit, int $offset): Ok | Err
    {
        $result          = $this->search_repository_reviewers->searchRepositoryPullRequestsReviewersIds($repository->getId(), $limit, $offset);
        $representations = [];

        foreach ($result->reviewers_ids as $reviewer_id) {
            $reviewer = $this->retrieve_user_by_id->getUserById($reviewer_id);
            if (! $reviewer) {
                return Result::err(UserNotFoundFault::fromUserId($reviewer_id));
            }

            $representations[] = MinimalUserRepresentation::build($reviewer);
        }

        return Result::ok(new RepositoryPullRequestsReviewersRepresentation($representations, $result->total_size));
    }
}
