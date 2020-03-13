<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Git\DiskUsage;

use ForgeConfig;
use Git_LogDao;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Statistics_DiskUsageManager;

class CollectorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Statistics_DiskUsageManager
     */
    private $disk_usage_manager;
    /**
     * @var Git_LogDao|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $git_log_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Retriever
     */
    private $retriever;
    /**
     * @var Collector
     */
    private $collector;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;

    protected function setUp(): void
    {
        $this->disk_usage_manager = Mockery::mock(Statistics_DiskUsageManager::class);
        $this->git_log_dao        = Mockery::mock(Git_LogDao::class);
        $this->retriever          = Mockery::mock(Retriever::class);

        $this->collector          = new Collector($this->disk_usage_manager, $this->git_log_dao, $this->retriever);

        $this->project = Mockery::mock(Project::class);
        $this->project->shouldReceive('getId')->andReturn(111);
        $this->project->shouldReceive('getUnixNameLowerCase')->andReturn('leprojet');
    }

    public function testCollectSizeForGitoliteRepositories() : void
    {
        $this->git_log_dao->shouldReceive('hasRepositoriesUpdatedAfterGivenDate')->andReturn(true);
        $this->git_log_dao->shouldReceive('hasRepositories')->andReturn(true);

        $this->disk_usage_manager->shouldReceive('getDirSize')->with(ForgeConfig::get('sys_data_dir') . '/gitolite/repositories/leprojet')->andReturn(11);
        $this->disk_usage_manager->shouldReceive('getDirSize')->with(ForgeConfig::get('sys_data_dir') . '/gitroot/leprojet')->andReturn(0);

        $this->assertEquals(11, $this->collector->collectForGitoliteRepositories($this->project));
    }

    public function testCollectSizeWhenNoRepositories() : void
    {
        $this->git_log_dao->shouldReceive('hasRepositoriesUpdatedAfterGivenDate')->andReturn(false);
        $this->git_log_dao->shouldReceive('hasRepositories')->andReturn(false);

        $this->disk_usage_manager->shouldReceive('getDirSize')->with(ForgeConfig::get('sys_data_dir') . '/gitolite/repositories/leprojet')->andReturn(11);
        $this->disk_usage_manager->shouldReceive('getDirSize')->with(ForgeConfig::get('sys_data_dir') . '/gitroot/leprojet')->andReturn(0);

        $this->assertEquals(11, $this->collector->collectForGitoliteRepositories($this->project));
    }

    public function testCollectLastSizeWhenNoNewCommit() : void
    {
        $this->git_log_dao->shouldReceive('hasRepositoriesUpdatedAfterGivenDate')->andReturn(false);
        $this->git_log_dao->shouldReceive('hasRepositories')->andReturn(true);

        $this->retriever->shouldReceive('getLastSizeForProject')->with($this->project)->andReturn(90);

        $this->assertEquals(90, $this->collector->collectForGitoliteRepositories($this->project));
    }
}
