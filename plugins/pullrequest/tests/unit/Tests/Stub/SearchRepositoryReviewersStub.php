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


namespace Tuleap\PullRequest\Tests\Stub;

use Tuleap\PullRequest\PullRequest\Reviewer\RepositoryPullRequestsReviewersIdsPage;
use Tuleap\PullRequest\PullRequest\Reviewer\SearchRepositoryReviewers;

final class SearchRepositoryReviewersStub implements SearchRepositoryReviewers
{
    /**
     * @param \PFUser[] $reviewers
     */
    private function __construct(private readonly array $reviewers)
    {
    }

    public static function withReviewers(\PFUser $first_reviewer, \PFUser ...$other_reviewers): self
    {
        return new self([$first_reviewer, ...$other_reviewers]);
    }

    public function searchRepositoryPullRequestsReviewersIds(int $repository_id, int $limit, int $offset): RepositoryPullRequestsReviewersIdsPage
    {
        $page = array_slice($this->reviewers, $offset, $limit);

        return new RepositoryPullRequestsReviewersIdsPage(
            count($this->reviewers),
            array_map(static fn (\PFUser $reviewer) => (int) $reviewer->getId(), $page)
        );
    }
}
