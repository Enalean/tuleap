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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\PullRequest;

class GitPullRequestReferenceBulkConverterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testAllPullRequestsWithoutRefsAreConverted()
    {
        $dao                      = \Mockery::mock(GitPullRequestReferenceDAO::class);
        $pull_request_ref_updater = \Mockery::mock(GitPullRequestReferenceUpdater::class);
        $pull_request_factory     = \Mockery::mock(Factory::class);
        $git_repository_factory   = \Mockery::mock(\GitRepositoryFactory::class);
        $logger                   = \Mockery::mock(\Logger::class);

        $bulk_converter = new GitPullRequestReferenceBulkConverter(
            $dao,
            $pull_request_ref_updater,
            $pull_request_factory,
            $git_repository_factory,
            $logger
        );

        $dao->shouldReceive('searchPullRequestsByReferenceStatus')->andReturns([['pr1'], ['pr2'], ['pr3']]);
        $pull_request_factory->shouldReceive('getInstanceFromRow')->andReturns(\Mockery::spy(PullRequest::class));
        $git_repository_factory->shouldReceive('getRepositoryById')->andReturns(\Mockery::spy(\GitRepository::class));
        $logger->shouldReceive('debug');

        $pull_request_ref_updater->shouldReceive('updatePullRequestReference')->times(3);

        $bulk_converter->convertAllPullRequestsWithoutAGitReference();
    }

    public function testPullRequestsWithoutValidGitRepositoryAreMarkedAsBroken()
    {
        $dao                      = \Mockery::mock(GitPullRequestReferenceDAO::class);
        $pull_request_ref_updater = \Mockery::mock(GitPullRequestReferenceUpdater::class);
        $pull_request_factory     = \Mockery::mock(Factory::class);
        $git_repository_factory   = \Mockery::mock(\GitRepositoryFactory::class);
        $logger                   = \Mockery::mock(\Logger::class);

        $bulk_converter = new GitPullRequestReferenceBulkConverter(
            $dao,
            $pull_request_ref_updater,
            $pull_request_factory,
            $git_repository_factory,
            $logger
        );

        $dao->shouldReceive('searchPullRequestsByReferenceStatus')->andReturns([['pr1']]);
        $pull_request = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturns(1);
        $pull_request->shouldReceive('getRepositoryId')->andReturns(1);
        $pull_request->shouldReceive('getRepoDestId')->andReturns(1);
        $pull_request_factory->shouldReceive('getInstanceFromRow')->andReturns($pull_request);
        $git_repository_factory->shouldReceive('getRepositoryById')->andReturns(null);
        $logger->shouldReceive('debug');

        $dao->shouldReceive('updateStatusByPullRequestId')->with(
            $pull_request->getId(),
            GitPullRequestReference::STATUS_BROKEN
        );
        $logger->shouldReceive('error')->once();

        $bulk_converter->convertAllPullRequestsWithoutAGitReference();
    }


    public function testFailureToSetTheGitReferenceDoesNotInterruptTheWholeConvertion()
    {
        $dao                      = \Mockery::mock(GitPullRequestReferenceDAO::class);
        $pull_request_ref_updater = \Mockery::mock(GitPullRequestReferenceUpdater::class);
        $pull_request_factory     = \Mockery::mock(Factory::class);
        $git_repository_factory   = \Mockery::mock(\GitRepositoryFactory::class);
        $logger                   = \Mockery::mock(\Logger::class);

        $bulk_converter = new GitPullRequestReferenceBulkConverter(
            $dao,
            $pull_request_ref_updater,
            $pull_request_factory,
            $git_repository_factory,
            $logger
        );

        $dao->shouldReceive('searchPullRequestsByReferenceStatus')->andReturns([['pr1'], ['pr2']]);
        $pull_request_factory->shouldReceive('getInstanceFromRow')->andReturns(\Mockery::spy(PullRequest::class));
        $git_repository_factory->shouldReceive('getRepositoryById')->andReturns(\Mockery::spy(\GitRepository::class));
        $logger->shouldReceive('debug');

        $pull_request_ref_updater->shouldReceive('updatePullRequestReference')->times(2)->andThrow(
            \Mockery::mock(\Git_Command_Exception::class)
        );
        $logger->shouldReceive('error')->times(2);

        $bulk_converter->convertAllPullRequestsWithoutAGitReference();
    }
}
