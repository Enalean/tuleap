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

use Tuleap\PullRequest\PullRequestsAuthorsIdsPage;
use Tuleap\PullRequest\SearchPaginatedPullRequestsAuthors;

final class SearchPaginatedPullRequestsAuthorsStub implements SearchPaginatedPullRequestsAuthors
{
    /**
     * @param \PFUser[] $authors
     */
    private function __construct(private readonly array $authors)
    {
    }

    public static function withAuthors(\PFUser $first_author, \PFUser ...$other_authors): self
    {
        return new self([$first_author, ...$other_authors]);
    }

    public function getPaginatedPullRequestsAuthorsIds(int $repository_id, int $limit, int $offset): PullRequestsAuthorsIdsPage
    {
        $page = array_slice($this->authors, $offset, $limit);

        return new PullRequestsAuthorsIdsPage(
            count($this->authors),
            array_map(static fn(\PFUser $author) => (int) $author->getId(), $page)
        );
    }
}
