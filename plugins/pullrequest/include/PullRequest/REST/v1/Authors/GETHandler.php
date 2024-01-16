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

namespace Tuleap\PullRequest\REST\v1\Authors;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\PullRequest\PullRequest\REST\v1\UserNotFoundFault;
use Tuleap\PullRequest\SearchPaginatedPullRequestsAuthors;
use Tuleap\User\REST\MinimalUserRepresentation;
use Tuleap\User\RetrieveUserById;

final class GETHandler
{
    public function __construct(
        private readonly RetrieveUserById $retrieve_user_by_id,
        private readonly SearchPaginatedPullRequestsAuthors $search_paginated_pull_requests_authors,
    ) {
    }

    /**
     * @return Ok<RepositoryPullRequestsAuthorsRepresentation> | Err<Fault>
     */
    public function handle(\GitRepository $repository, int $limit, int $offset): Ok | Err
    {
        $result = $this->search_paginated_pull_requests_authors->getPaginatedPullRequestsAuthorsIds($repository->getId(), $limit, $offset);

        $authors_representations = [];

        foreach ($result->authors_ids as $author_id) {
            $author = $this->retrieve_user_by_id->getUserById($author_id);
            if (! $author) {
                return Result::err(UserNotFoundFault::fromUserId($author_id));
            }

            $authors_representations[] = MinimalUserRepresentation::build($author);
        }

        return Result::ok(new RepositoryPullRequestsAuthorsRepresentation($authors_representations, $result->total_size));
    }
}
