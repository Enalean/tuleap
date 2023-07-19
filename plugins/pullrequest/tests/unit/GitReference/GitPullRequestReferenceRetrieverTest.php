<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\GitReference;

use Tuleap\PullRequest\PullRequest;

final class GitPullRequestReferenceRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testGitReferenceIsRetrieved(): void
    {
        $dao = $this->createMock(GitPullRequestReferenceDAO::class);
        $dao->method('getReferenceByPullRequestId')->willReturn(
            ['pr_id' => 1, 'reference_id' => 1, 'repository_dest_id' => 1, 'status' => GitPullRequestReference::STATUS_OK]
        );
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(1);

        $reference_retriever = new GitPullRequestReferenceRetriever($dao);
        $git_reference       = $reference_retriever->getGitReferenceFromPullRequest($pull_request);

        self::assertInstanceOf(GitPullRequestReference::class, $git_reference);
    }

    public function testNotFoundExceptionIsThrownWhenGitReferenceIsNotReservedForThePullRequest(): void
    {
        $dao = $this->createMock(GitPullRequestReferenceDAO::class);
        $dao->method('getReferenceByPullRequestId')->willReturn([]);
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(1);

        $reference_retriever = new GitPullRequestReferenceRetriever($dao);

        $this->expectException(GitPullRequestReferenceNotFoundException::class);

        $reference_retriever->getGitReferenceFromPullRequest($pull_request);
    }
}
