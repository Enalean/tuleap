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
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\PullRequest;

class GitPullRequestReferenceBulkConverterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\MockInterface
     */
    private $dao;
    /**
     * @var \Mockery\MockInterface
     */
    private $pull_request_ref_updater;
    /**
     * @var \Mockery\MockInterface
     */
    private $pull_request_factory;
    /**
     * @var \Mockery\MockInterface
     */
    private $git_repository_factory;
    /**
     * @var \Mockery\MockInterface
     */
    private $logger;

    protected function setUp(): void
    {
        $this->dao                      = \Mockery::mock(GitPullRequestReferenceDAO::class);
        $this->pull_request_ref_updater = \Mockery::mock(GitPullRequestReferenceUpdater::class);
        $this->pull_request_factory     = \Mockery::mock(Factory::class);
        $this->git_repository_factory   = \Mockery::mock(\GitRepositoryFactory::class);
        $this->logger                   = \Mockery::mock(\Psr\Log\LoggerInterface::class);

        \ForgeConfig::store();
        $tmp_dir = vfsStream::setup();
        \ForgeConfig::set('tmp_dir', $tmp_dir->url());
    }

    protected function tearDown(): void
    {
        \ForgeConfig::restore();
    }

    public function testAllPullRequestsWithoutRefsAreConverted()
    {
        $bulk_converter = new GitPullRequestReferenceBulkConverter(
            $this->dao,
            $this->pull_request_ref_updater,
            $this->pull_request_factory,
            $this->git_repository_factory,
            $this->logger
        );

        $this->dao->shouldReceive('searchPullRequestsByReferenceStatus')->andReturns([['pr1'], ['pr2'], ['pr3']]);
        $this->pull_request_factory->shouldReceive('getInstanceFromRow')->andReturns(\Mockery::spy(PullRequest::class));
        $this->git_repository_factory->shouldReceive('getRepositoryById')->andReturns(\Mockery::spy(\GitRepository::class));
        $this->logger->shouldReceive('debug');

        $this->pull_request_ref_updater->shouldReceive('updatePullRequestReference')->times(3);

        $bulk_converter->convertAllPullRequestsWithoutAGitReference();
    }

    public function testPullRequestsWithoutValidGitRepositoryAreMarkedAsBroken()
    {
        $bulk_converter = new GitPullRequestReferenceBulkConverter(
            $this->dao,
            $this->pull_request_ref_updater,
            $this->pull_request_factory,
            $this->git_repository_factory,
            $this->logger
        );

        $this->dao->shouldReceive('searchPullRequestsByReferenceStatus')->andReturns([['pr1']]);
        $pull_request = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturns(1);
        $pull_request->shouldReceive('getRepositoryId')->andReturns(1);
        $pull_request->shouldReceive('getRepoDestId')->andReturns(1);
        $this->pull_request_factory->shouldReceive('getInstanceFromRow')->andReturns($pull_request);
        $this->git_repository_factory->shouldReceive('getRepositoryById')->andReturns(null);
        $this->logger->shouldReceive('debug');

        $this->dao->shouldReceive('updateStatusByPullRequestId')->with(
            $pull_request->getId(),
            GitPullRequestReference::STATUS_BROKEN
        );
        $this->logger->shouldReceive('error')->once();

        $bulk_converter->convertAllPullRequestsWithoutAGitReference();
    }


    public function testFailureToSetTheGitReferenceDoesNotInterruptTheWholeConversion()
    {
        $bulk_converter = new GitPullRequestReferenceBulkConverter(
            $this->dao,
            $this->pull_request_ref_updater,
            $this->pull_request_factory,
            $this->git_repository_factory,
            $this->logger
        );

        $this->dao->shouldReceive('searchPullRequestsByReferenceStatus')->andReturns([['pr1'], ['pr2']]);
        $this->pull_request_factory->shouldReceive('getInstanceFromRow')->andReturns(\Mockery::spy(PullRequest::class));
        $this->git_repository_factory->shouldReceive('getRepositoryById')->andReturns(\Mockery::spy(\GitRepository::class));
        $this->logger->shouldReceive('debug');

        $this->pull_request_ref_updater->shouldReceive('updatePullRequestReference')->times(2)->andThrow(
            \Mockery::mock(\Git_Command_Exception::class)
        );
        $this->logger->shouldReceive('error')->times(2);

        $bulk_converter->convertAllPullRequestsWithoutAGitReference();
    }

    public function testBulkConversionIsStoppedWhenStopFileIsFound()
    {
        $bulk_converter = new GitPullRequestReferenceBulkConverter(
            $this->dao,
            $this->pull_request_ref_updater,
            $this->pull_request_factory,
            $this->git_repository_factory,
            $this->logger
        );

        $this->dao->shouldReceive('searchPullRequestsByReferenceStatus')->andReturns([['pr1'], ['pr2']]);

        touch(\ForgeConfig::get('tmp_dir') . DIRECTORY_SEPARATOR . GitPullRequestReferenceBulkConverter::STOP_CONVERSION_FILE);

        $this->logger->shouldReceive('info')->once();

        $bulk_converter->convertAllPullRequestsWithoutAGitReference();
    }
}
