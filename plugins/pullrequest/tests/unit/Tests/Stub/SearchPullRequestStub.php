<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\PullRequest\Tests\Stub;

use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\SearchPullRequest;

final class SearchPullRequestStub implements SearchPullRequest
{
    private function __construct(private readonly array $pull_request_row)
    {
    }

    public function searchByPullRequestId(int $pull_request_id): array
    {
        return $this->pull_request_row;
    }

    public static function withNoRow(): self
    {
        return new self([]);
    }

    public static function withPullRequest(PullRequest $pull_request): self
    {
        return new self([
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
        ]);
    }
}
