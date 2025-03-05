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
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Statistics_DiskUsageManager;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CollectorTest extends TestCase
{
    private Statistics_DiskUsageManager&MockObject $disk_usage_manager;
    private Git_LogDao&MockObject $git_log_dao;
    private Retriever&MockObject $retriever;
    private Collector $collector;
    private Project $project;

    protected function setUp(): void
    {
        $this->disk_usage_manager = $this->createMock(Statistics_DiskUsageManager::class);
        $this->git_log_dao        = $this->createMock(Git_LogDao::class);
        $this->retriever          = $this->createMock(Retriever::class);

        $this->collector = new Collector($this->disk_usage_manager, $this->git_log_dao, $this->retriever);

        $this->project = ProjectTestBuilder::aProject()->withId(111)->withUnixName('leprojet')->build();
    }

    public function testCollectSizeForGitoliteRepositories(): void
    {
        $this->git_log_dao->method('hasRepositoriesUpdatedAfterGivenDate')->willReturn(true);
        $this->git_log_dao->method('hasRepositories')->willReturn(true);

        $this->disk_usage_manager->method('getDirSize')
            ->willReturnCallback(static fn(string $dir) => match ($dir) {
                ForgeConfig::get('sys_data_dir') . '/gitolite/repositories/leprojet' => 11,
                ForgeConfig::get('sys_data_dir') . '/gitroot/leprojet'               => 0,
            });

        self::assertEquals(11, $this->collector->collectForGitoliteRepositories($this->project));
    }

    public function testCollectSizeWhenNoRepositories(): void
    {
        $this->git_log_dao->method('hasRepositoriesUpdatedAfterGivenDate')->willReturn(false);
        $this->git_log_dao->method('hasRepositories')->willReturn(false);

        $this->disk_usage_manager->method('getDirSize')
            ->willReturnCallback(static fn(string $dir) => match ($dir) {
                ForgeConfig::get('sys_data_dir') . '/gitolite/repositories/leprojet' => 11,
                ForgeConfig::get('sys_data_dir') . '/gitroot/leprojet'               => 0,
            });

        self::assertEquals(11, $this->collector->collectForGitoliteRepositories($this->project));
    }

    public function testCollectLastSizeWhenNoNewCommit(): void
    {
        $this->git_log_dao->method('hasRepositoriesUpdatedAfterGivenDate')->willReturn(false);
        $this->git_log_dao->method('hasRepositories')->willReturn(true);

        $this->retriever->method('getLastSizeForProject')->with($this->project)->willReturn(90);

        self::assertEquals(90, $this->collector->collectForGitoliteRepositories($this->project));
    }
}
