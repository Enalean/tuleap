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

use GitRepository;
use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\GitReference\GitReferenceNotFound;
use Tuleap\PullRequest\GitReference\UpdateGitPullRequestReference;
use Tuleap\PullRequest\PullRequest;

final class UpdateGitPullRequestReferenceStub implements UpdateGitPullRequestReference
{
    private int $update_pull_request_reference_call_count;
    private function __construct()
    {
        $this->update_pull_request_reference_call_count = 0;
    }

    /**
     * @throws \Git_Command_Exception
     * @throws GitReferenceNotFound
     */
    public function updatePullRequestReference(PullRequest $pull_request, GitExec $executor_repository_source, GitExec $executor_repository_destination, GitRepository $repository_destination,): void
    {
        $this->update_pull_request_reference_call_count++;
    }

    public static function build(): self
    {
        return new self();
    }

    public function getUpdatePullRequestReferenceCallCount(): int
    {
        return $this->update_pull_request_reference_call_count;
    }
}
