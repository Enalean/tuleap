<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\SVN\DiskUsage;

use Project;
use Psr\Log\LoggerInterface;
use Statistics_DiskUsageDao;
use Statistics_DiskUsageManager;
use Tuleap\SVNCore\Repository;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class DiskUsageRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&RepositoryManager
     */
    private $repository_manager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Statistics_DiskUsageManager
     */
    private $disk_usage_manager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&DiskUsageDao
     */
    private $disk_usage_dao;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Statistics_DiskUsageDao
     */
    private $dao;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&LoggerInterface
     */
    private $logger;

    private DiskUsageRetriever $disk_usage_retriever;
    private Project $project;

    protected function setUp(): void
    {
        $this->project            = ProjectTestBuilder::aProject()->withId(111)->withUnixName('projet')->build();
        $this->repository_manager = $this->createMock(RepositoryManager::class);
        $this->disk_usage_manager = $this->createMock(Statistics_DiskUsageManager::class);
        $this->disk_usage_dao     = $this->createMock(DiskUsageDao::class);
        $this->dao                = $this->createMock(Statistics_DiskUsageDao::class);
        $this->logger             = $this->createMock(LoggerInterface::class);

        $this->disk_usage_retriever = new DiskUsageRetriever(
            $this->repository_manager,
            $this->disk_usage_manager,
            $this->disk_usage_dao,
            $this->dao,
            $this->logger
        );
    }

    public function testGetDiskUsageForProject(): void
    {
        $this->logger->method('info')->withConsecutive(
            ['Collecting statistics for project projet'],
            ['Project has new commit, collecting disk size data.'],
        );

        $this->disk_usage_dao->method('hasRepositoriesUpdatedAfterGivenDate')->willReturn(true);

        $repository = $this->createMock(Repository::class);
        $repository->method('getSystemPath')->willReturn('path/to/repo');

        $this->disk_usage_manager->method('getDirSize')
            ->with('path/to/repo')
            ->willReturn(11);

        $this->repository_manager->method('getRepositoriesInProject')
            ->with($this->project)
            ->willReturn([$repository]);

        self::assertEquals(11, $this->disk_usage_retriever->getDiskUsageForProject($this->project));
    }

    public function testGetDiskUsageForProjectWhenNoNewCommit(): void
    {
        $this->logger->method('info')->withConsecutive(
            ['Collecting statistics for project projet'],
            ["No new commit made on this project since yesterday, duplicate value from DB."],
        );

        $this->disk_usage_dao->method('hasRepositoriesUpdatedAfterGivenDate')->willReturn(false);
        $this->disk_usage_dao->method('hasRepositories')->willReturn(true);

        $this->dao->method('getLastSizeForService')->willReturn(['size' => 11]);

        self::assertEquals(11, $this->disk_usage_retriever->getDiskUsageForProject($this->project));
    }

    public function testGetDiskUsageForProjectWhenNoRepositories(): void
    {
        $this->logger->method('info')->withConsecutive(
            ['Collecting statistics for project projet'],
            ['Project has new commit, collecting disk size data.'],
        );

        $this->disk_usage_dao->method('hasRepositoriesUpdatedAfterGivenDate')->willReturn(true);

        $this->repository_manager->method('getRepositoriesInProject')
            ->with($this->project)
            ->willReturn([]);

        self::assertEquals(0, $this->disk_usage_retriever->getDiskUsageForProject($this->project));
    }

    public function testLastSizeForProjectIs0WhenNoDataIsAvailable(): void
    {
        $this->dao->method('getLastSizeForService')->willReturn(false);
        self::assertEquals(0, $this->disk_usage_retriever->getLastSizeForProject($this->project));
    }
}
