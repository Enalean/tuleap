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

use Mockery;
use PHPUnit\Framework\TestCase;
use Project;
use Psr\Log\LoggerInterface;
use Statistics_DiskUsageDao;
use Statistics_DiskUsageManager;
use Tuleap\SVN\Repository\Repository;
use Tuleap\SVN\Repository\RepositoryManager;

class DiskUsageRetrieverTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|RepositoryManager
     */
    private $repository_manager;

    /**
     * @var Mockery\MockInterface|Statistics_DiskUsageManager
     */
    private $disk_usage_manager;

    /**
     * @var Mockery\MockInterface|DiskUsageDao
     */
    private $disk_usage_dao;

    /**
     * @var Mockery\MockInterface|Statistics_DiskUsageDao
     */
    private $dao;

    /**
     * @var Mockery\MockInterface|LoggerInterface
     */
    private $logger;

    /**
     * @var DiskUsageRetriever
     */
    private $disk_usage_retriever;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;

    protected function setUp(): void
    {
        $this->project            = Mockery::mock(Project::class);
        $this->repository_manager = Mockery::mock(RepositoryManager::class);
        $this->disk_usage_manager = Mockery::mock(Statistics_DiskUsageManager::class);
        $this->disk_usage_dao     = Mockery::mock(DiskUsageDao::class);
        $this->dao                = Mockery::mock(Statistics_DiskUsageDao::class);
        $this->logger             = Mockery::mock(LoggerInterface::class);

        $this->disk_usage_retriever = new DiskUsageRetriever(
            $this->repository_manager,
            $this->disk_usage_manager,
            $this->disk_usage_dao,
            $this->dao,
            $this->logger
        );

        $this->project->shouldReceive('getId')->andReturn(111);
        $this->project->shouldReceive('getUnixName')->andReturn('projet');
    }

    public function testGetDiskUsageForProject()
    {
        $this->logger->shouldReceive('info')->with('Collecting statistics for project projet')->once();

        $this->disk_usage_dao->shouldReceive('hasRepositoriesUpdatedAfterGivenDate')->andReturn(true);
        $this->logger->shouldReceive('info')->with('Project has new commit, collecting disk size data.')->once();

        $repository = Mockery::mock(Repository::class);
        $repository->shouldReceive('getSystemPath')->andReturn('path/to/repo');

        $this->disk_usage_manager->shouldReceive('getDirSize')
            ->withArgs(['path/to/repo'])
            ->andReturn(11);

        $this->repository_manager->shouldReceive('getRepositoriesInProject')
            ->withArgs([$this->project])
            ->andReturn([$repository]);

        $this->assertEquals(11, $this->disk_usage_retriever->getDiskUsageForProject($this->project));
    }

    public function testGetDiskUsageForProjectWhenNoNewCommit()
    {
        $this->logger->shouldReceive('info')->with('Collecting statistics for project projet')->once();

        $this->disk_usage_dao->shouldReceive('hasRepositoriesUpdatedAfterGivenDate')->andReturn(false);
        $this->disk_usage_dao->shouldReceive('hasRepositories')->andReturn(true);
        $this->logger->shouldReceive('info')
                     ->with("No new commit made on this project since yesterday, duplicate value from DB.")
                     ->once();

        $this->dao->shouldReceive('getLastSizeForService')->andReturn(['size' => 11]);

        $this->assertEquals(11, $this->disk_usage_retriever->getDiskUsageForProject($this->project));
    }

    public function testGetDiskUsageForProjectWhenNoRepositories()
    {
        $this->logger->shouldReceive('info')->with('Collecting statistics for project projet')->once();

        $this->disk_usage_dao->shouldReceive('hasRepositoriesUpdatedAfterGivenDate')->andReturn(true);
        $this->logger->shouldReceive('info')->with('Project has new commit, collecting disk size data.')->once();

        $this->repository_manager->shouldReceive('getRepositoriesInProject')
            ->withArgs([$this->project])
            ->andReturn([]);

        $this->assertEquals(0, $this->disk_usage_retriever->getDiskUsageForProject($this->project));
    }
}
