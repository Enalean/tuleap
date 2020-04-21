<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;
use Tuleap\PullRequest\PullRequest;

class GitPullRequestReferenceRetrieverTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testGitReferenceIsRetrieved()
    {
        $dao = \Mockery::mock(GitPullRequestReferenceDAO::class);
        $dao->shouldReceive('getReferenceByPullRequestId')->andReturns(
            ['pr_id' => 1, 'reference_id' => 1, 'repository_dest_id' => 1, 'status' => GitPullRequestReference::STATUS_OK]
        );
        $pull_request  = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturns(1);

        $reference_retriever = new GitPullRequestReferenceRetriever($dao);
        $git_reference       = $reference_retriever->getGitReferenceFromPullRequest($pull_request);

        $this->assertInstanceOf(GitPullRequestReference::class, $git_reference);
    }

    public function testNotFoundExceptionIsThrownWhenGitReferenceIsNotReservedForThePullRequest()
    {
        $dao = \Mockery::mock(GitPullRequestReferenceDAO::class);
        $dao->shouldReceive('getReferenceByPullRequestId')->andReturns([]);
        $pull_request  = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturns(1);

        $reference_retriever = new GitPullRequestReferenceRetriever($dao);

        $this->expectException(GitPullRequestReferenceNotFoundException::class);

        $reference_retriever->getGitReferenceFromPullRequest($pull_request);
    }
}
