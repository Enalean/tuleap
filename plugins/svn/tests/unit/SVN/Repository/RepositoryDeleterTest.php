<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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

namespace Tuleap\SVN\Repository;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tuleap\SVN\Dao;
use Tuleap\SVN\Repository\Exception\CannotDeleteRepositoryException;
use Tuleap\Test\Builders\ProjectTestBuilder;

class RepositoryDeleterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \SystemEventManager
     */
    private $system_event_manager;
    /**
     * @var RepositoryManager
     */
    private $repository_manager;
    /**
     * @var Dao
     */
    private $dao;
    /**
     * @var \Project
     */
    private $project;
    /**
     * @var Repository
     */
    private $repository;
    /**
     * @var RepositoryDeleter
     */
    private $repository_deleter;
    /**
     * @var \System_Command
     */
    private $system_command;

    private $fixtures_dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->system_command       = \Mockery::spy(\System_Command::class);
        $project_history_dao        = \Mockery::spy(\ProjectHistoryDao::class);
        $this->dao                  = \Mockery::spy(\Tuleap\SVN\Dao::class);
        $this->system_event_manager = \Mockery::spy(\SystemEventManager::class);
        $this->repository_manager   = \Mockery::spy(\Tuleap\SVN\Repository\RepositoryManager::class);

        $this->repository_deleter = new RepositoryDeleter(
            $this->system_command,
            $project_history_dao,
            $this->dao,
            $this->system_event_manager,
            $this->repository_manager
        );

        $this->repository = \Mockery::spy(\Tuleap\SVN\Repository\Repository::class);
        $this->project    = ProjectTestBuilder::aProject()->build();

        $this->fixtures_dir = __DIR__ . '/../_fixtures';
    }

    public function testItReturnFalseWhenRepositoryIsNotFoundOnFileSystem(): void
    {
        $this->repository->shouldReceive('getProject')->andReturn($this->project);
        $this->repository->shouldReceive('getSystemPath')->andReturn('/a/non/existing/path');

        $this->assertFalse($this->repository_deleter->delete($this->repository));
    }

    public function testItDeleteTheRepository(): void
    {
        $this->repository->shouldReceive('getProject')->andReturn($this->project);
        $this->repository->shouldReceive('getSystemPath')->andReturn($this->fixtures_dir);
        $this->system_command->shouldReceive('exec')->andReturn(true);

        $this->assertFalse($this->repository_deleter->delete($this->repository));
    }

    public function testItThrowsAnExceptionWhenRepositoryCantBeMarkedAsDeleted(): void
    {
        $this->repository->shouldReceive('canBeDeleted')->andReturn(false);
        $this->expectException(CannotDeleteRepositoryException::class);

        $this->repository_deleter->markAsDeleted($this->repository);
    }

    public function testItMarkTheRepositoryAsDeleted(): void
    {
        $this->repository->shouldReceive('canBeDeleted')->andReturn(true);
        $this->dao->shouldReceive('markAsDeleted')->once();

        $this->repository_deleter->markAsDeleted($this->repository);
    }

    public function testItShouldRemoveAllRepositoryOfAProject(): void
    {
        $directory = vfsStream::create(
            [
                'svn_plugin' => [
                    '101' => [
                        'repo01' => [],
                        'repo02' => [],
                        'repo03' => [],
                    ]
                ]
            ],
            vfsStream::setup()
        );

        \ForgeConfig::set('sys_data_dir', $directory->url());
        $this->repository_manager->shouldReceive('getRepositoriesInProject')->andReturn(
            [
                SvnRepository::buildActiveRepository(1, 'repo01', $this->project),
                SvnRepository::buildActiveRepository(2, 'repo02', $this->project),
                SvnRepository::buildActiveRepository(3, 'repo03', $this->project),
            ]
        );

        $this->system_event_manager->shouldReceive('createEvent')->times(3);
        $this->repository_deleter->deleteProjectRepositories($this->project);
    }
}
