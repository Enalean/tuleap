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

use Tuleap\PullRequest\Criterion\PullRequestSortOrder;
use Tuleap\PullRequest\Criterion\SearchCriteria;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestsPage;
use Tuleap\PullRequest\SearchPaginatedPullRequests;

final class SearchPaginatedPullRequestsStub implements SearchPaginatedPullRequests
{
    private function __construct(private readonly array $all_rows)
    {
    }

    public static function withAtLeastOnePullRequest(PullRequest $pull_request, PullRequest ...$other_pull_requests): self
    {
        $all_rows = [];

        $all_rows[] = self::convertPullRequestToRow($pull_request);

        foreach ($other_pull_requests as $other_pull_request) {
            $all_rows[] = self::convertPullRequestToRow($other_pull_request);
        }
        return new self($all_rows);
    }

    private static function convertPullRequestToRow(PullRequest $pull_request): array
    {
        return [
            'id'                 => $pull_request->getId(),
            'title'              => $pull_request->getTitle(),
            'description'        => $pull_request->getDescription(),
            'repository_id'      => $pull_request->getRepositoryId(),
            'user_id'            => $pull_request->getUserId(),
            'creation_date'      => $pull_request->getCreationDate(),
            'branch_src'         => $pull_request->getBranchSrc(),
            'sha1_src'           => $pull_request->getSha1Src(),
            'repo_dest_id'       => $pull_request->getRepoDestId(),
            'branch_dest'        => $pull_request->getBranchDest(),
            'sha1_dest'          => $pull_request->getSha1Dest(),
            'description_format' => $pull_request->getDescriptionFormat(),
            'status'             => $pull_request->getStatus(),
            'merge_status'       => $pull_request->getMergeStatus(),
        ];
    }

    public function getPaginatedPullRequests(int $repository_id, SearchCriteria $criteria, PullRequestSortOrder $order, int $limit, int $offset): PullRequestsPage
    {
        return new PullRequestsPage(
            count($this->all_rows),
            $this->all_rows
        );
    }
}
