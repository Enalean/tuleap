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

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\ForgeConfigSandbox;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\PullRequest;

class GitPullRequestReferenceBulkConverterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private GitPullRequestReferenceDAO&MockObject $dao;
    private GitPullRequestReferenceUpdater&MockObject $pull_request_ref_updater;
    private Factory&MockObject $pull_request_factory;
    private \GitRepositoryFactory&MockObject $git_repository_factory;
    private \Psr\Log\LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->dao                      = $this->createMock(GitPullRequestReferenceDAO::class);
        $this->pull_request_ref_updater = $this->createMock(GitPullRequestReferenceUpdater::class);
        $this->pull_request_factory     = $this->createMock(Factory::class);
        $this->git_repository_factory   = $this->createMock(\GitRepositoryFactory::class);
        $this->logger                   = $this->createMock(\Psr\Log\LoggerInterface::class);

        \ForgeConfig::store();
        $tmp_dir = vfsStream::setup();
        \ForgeConfig::set('tmp_dir', $tmp_dir->url());
    }

    public function testAllPullRequestsWithoutRefsAreConverted(): void
    {
        $bulk_converter = new GitPullRequestReferenceBulkConverter(
            $this->dao,
            $this->pull_request_ref_updater,
            $this->pull_request_factory,
            $this->git_repository_factory,
            $this->logger
        );

        $this->dao->method('searchPullRequestsByReferenceStatus')->willReturn([['pr1'], ['pr2'], ['pr3']]);

        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(1);
        $pull_request->method('getRepositoryId')->willReturn(47);
        $pull_request->method('getRepoDestId')->willReturn(48);
        $this->pull_request_factory->method('getInstanceFromRow')->willReturn($pull_request);

        $git_repository = $this->createMock(\GitRepository::class);
        $git_repository->method('getId')->willReturn(47);
        $git_repository->method('getFullPath')->willReturn('');
        $this->git_repository_factory->method('getRepositoryById')->willReturn($git_repository);
        $this->logger->method('debug');

        $this->pull_request_ref_updater->expects(self::exactly(3))->method('updatePullRequestReference');

        $bulk_converter->convertAllPullRequestsWithoutAGitReference();
    }

    public function testPullRequestsWithoutValidGitRepositoryAreMarkedAsBroken(): void
    {
        $bulk_converter = new GitPullRequestReferenceBulkConverter(
            $this->dao,
            $this->pull_request_ref_updater,
            $this->pull_request_factory,
            $this->git_repository_factory,
            $this->logger
        );

        $this->dao->method('searchPullRequestsByReferenceStatus')->willReturn([['pr1']]);
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(1);
        $pull_request->method('getRepositoryId')->willReturn(1);
        $pull_request->method('getRepoDestId')->willReturn(1);
        $this->pull_request_factory->method('getInstanceFromRow')->willReturn($pull_request);
        $this->git_repository_factory->method('getRepositoryById')->willReturn(null);
        $this->logger->method('debug');

        $this->dao->method('updateStatusByPullRequestId')->with(
            $pull_request->getId(),
            GitPullRequestReference::STATUS_BROKEN
        );
        $this->logger->expects(self::once())->method('error');

        $bulk_converter->convertAllPullRequestsWithoutAGitReference();
    }

    public function testFailureToSetTheGitReferenceDoesNotInterruptTheWholeConversion(): void
    {
        $bulk_converter = new GitPullRequestReferenceBulkConverter(
            $this->dao,
            $this->pull_request_ref_updater,
            $this->pull_request_factory,
            $this->git_repository_factory,
            $this->logger
        );

        $this->dao->method('searchPullRequestsByReferenceStatus')->willReturn([['pr1'], ['pr2']]);
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(1);
        $pull_request->method('getRepositoryId')->willReturn(47);
        $pull_request->method('getRepoDestId')->willReturn(48);
        $this->pull_request_factory->method('getInstanceFromRow')->willReturn($pull_request);

        $git_repository = $this->createMock(\GitRepository::class);
        $git_repository->method('getId')->willReturn(47);
        $git_repository->method('getFullPath')->willReturn('');
        $this->git_repository_factory->method('getRepositoryById')->willReturn($git_repository);
        $this->logger->method('debug');

        $this->pull_request_ref_updater->expects(self::exactly(2))->method('updatePullRequestReference')->willThrowException(
            $this->createMock(\Git_Command_Exception::class)
        );
        $this->logger->expects(self::exactly(2))->method('error');

        $bulk_converter->convertAllPullRequestsWithoutAGitReference();
    }

    public function testBulkConversionIsStoppedWhenStopFileIsFound(): void
    {
        $bulk_converter = new GitPullRequestReferenceBulkConverter(
            $this->dao,
            $this->pull_request_ref_updater,
            $this->pull_request_factory,
            $this->git_repository_factory,
            $this->logger
        );

        $this->dao->method('searchPullRequestsByReferenceStatus')->willReturn([['pr1'], ['pr2']]);

        touch(\ForgeConfig::get('tmp_dir') . DIRECTORY_SEPARATOR . GitPullRequestReferenceBulkConverter::STOP_CONVERSION_FILE);

        $this->logger->expects(self::once())->method('info');

        $bulk_converter->convertAllPullRequestsWithoutAGitReference();
    }
}
