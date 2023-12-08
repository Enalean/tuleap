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

use org\bovigo\vfs\vfsStream;
use Tuleap\SVN\Dao;
use Tuleap\SVN\Repository\Exception\CannotDeleteRepositoryException;
use Tuleap\SVNCore\Repository;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class RepositoryDeleterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \SystemEventManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $system_event_manager;
    /**
     * @var RepositoryManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $repository_manager;
    /**
     * @var Dao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $dao;
    private \Project $project;
    /**
     * @var Repository&\PHPUnit\Framework\MockObject\MockObject
     */
    private $repository;
    private RepositoryDeleter $repository_deleter;
    /**
     * @var \System_Command&\PHPUnit\Framework\MockObject\MockObject
     */
    private $system_command;
    private string $fixtures_dir;
    private \ProjectHistoryDao&\PHPUnit\Framework\MockObject\MockObject $project_history_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->system_command       = $this->createMock(\System_Command::class);
        $this->project_history_dao  = $this->createMock(\ProjectHistoryDao::class);
        $this->dao                  = $this->createMock(\Tuleap\SVN\Dao::class);
        $this->system_event_manager = $this->createMock(\SystemEventManager::class);
        $this->repository_manager   = $this->createMock(\Tuleap\SVN\Repository\RepositoryManager::class);

        $this->repository_deleter = new RepositoryDeleter(
            $this->system_command,
            $this->project_history_dao,
            $this->dao,
            $this->system_event_manager,
            $this->repository_manager,
        );

        $this->repository = $this->createMock(\Tuleap\SVNCore\Repository::class);
        $this->project    = ProjectTestBuilder::aProject()->build();

        $this->fixtures_dir = __DIR__ . '/../_fixtures';
    }

    public function testItReturnFalseWhenRepositoryIsNotFoundOnFileSystem(): void
    {
        $this->repository->method('getProject')->willReturn($this->project);
        $this->repository->method('getSystemPath')->willReturn('/a/non/existing/path');

        self::assertFalse($this->repository_deleter->delete($this->repository));
    }

    public function testItDeleteTheRepository(): void
    {
        $this->repository->method('getProject')->willReturn($this->project);
        $this->repository->method('getSystemPath')->willReturn($this->fixtures_dir);
        $this->system_command->method('exec')->willReturn(true);

        self::assertFalse($this->repository_deleter->delete($this->repository));
    }

    public function testItThrowsAnExceptionWhenRepositoryCantBeMarkedAsDeleted(): void
    {
        $this->repository->method('canBeDeleted')->willReturn(false);
        $this->expectException(CannotDeleteRepositoryException::class);

        $this->repository_deleter->markAsDeleted($this->repository);
    }

    public function testItMarkTheRepositoryAsDeleted(): void
    {
        $this->repository->method('canBeDeleted')->willReturn(true);
        $this->repository->method('getId')->willReturn(1);
        $this->repository->method('getSystemBackupPath')->willReturn('');
        $this->repository->method('getBackupFileName')->willReturn('');
        $this->repository->expects(self::once())->method('setDeletionDate');
        $this->dao->expects(self::once())->method('markAsDeleted');

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
                    ],
                ],
            ],
            vfsStream::setup()
        );

        \ForgeConfig::set('sys_data_dir', $directory->url());
        $this->repository_manager->method('getRepositoriesInProject')->willReturn(
            [
                SvnRepository::buildActiveRepository(1, 'repo01', $this->project),
                SvnRepository::buildActiveRepository(2, 'repo02', $this->project),
                SvnRepository::buildActiveRepository(3, 'repo03', $this->project),
            ]
        );

        $this->project_history_dao->method('groupAddHistory');

        $this->system_event_manager->expects(self::exactly(3))->method('createEvent');
        $this->repository_deleter->deleteProjectRepositories($this->project);
    }
}
