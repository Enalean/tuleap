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

use Tuleap\PullRequest\GitReference\GetReferenceByPullRequestId;
use Tuleap\PullRequest\GitReference\GitPullRequestReference;
use Tuleap\PullRequest\PullRequestWithGitReference;

final class GetReferenceByPullRequestIdStub implements GetReferenceByPullRequestId
{
    private function __construct(private readonly array $row)
    {
    }

    public function getReferenceByPullRequestId(int $pull_request_id): array
    {
        return $this->row;
    }

    public static function withoutRow(): self
    {
        return new self([]);
    }

    public static function withPullRequestWithReference(PullRequestWithGitReference $pull_request_with_git_reference): self
    {
        $reference = $pull_request_with_git_reference->getGitReference();
        $status    = GitPullRequestReference::STATUS_OK;
        if ($reference->isGitReferenceBroken()) {
            $status = GitPullRequestReference::STATUS_BROKEN;
        }
        if ($reference->isGitReferenceNeedToBeCreatedInRepository()) {
            $status = GitPullRequestReference::STATUS_NOT_YET_CREATED;
        }
        return new self(
            [
                'pr_id' => $pull_request_with_git_reference->getPullRequest()->getId(),
                'reference_id' => $reference->getGitReferenceId(),
                'repository_dest_id' => $pull_request_with_git_reference->getPullRequest()->getRepoDestId(),
                'status' => $status,
            ]
        );
    }
}
