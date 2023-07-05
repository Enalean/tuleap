<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

namespace Tuleap\SVN\Repository;

use BackendSVN;
use EventManager;
use HTTPRequest;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectManager;
use Psr\Log\NullLogger;
use System_Command;
use Tuleap\GlobalSVNPollution;
use Tuleap\SVN\AccessControl\AccessFileHistoryFactory;
use Tuleap\SVN\Dao;
use Tuleap\SVN\Repository\Exception\CannotFindRepositoryException;
use Tuleap\SVN\SvnAdmin;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class RepositoryManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalSVNPollution;
    use TemporaryTestDirectory;

    /**
     * @var MockObject&ProjectManager
     */
    private $project_manager;
    /**
     * @var MockObject&Dao
     */
    private $dao;
    /**
     * @var MockObject&HTTPRequest
     */
    private $request;
    /**
     * @var MockObject&\Project
     */
    private $project;
    private RepositoryManager $manager;

    protected function setUp(): void
    {
        $this->dao                   = $this->createMock(Dao::class);
        $this->project_manager       = $this->createMock(ProjectManager::class);
        $svn_admin                   = $this->createMock(SvnAdmin::class);
        $logger                      = new NullLogger();
        $system_command              = $this->createMock(System_Command::class);
        $destructor                  = $this->createMock(Destructor::class);
        $event_manager               = $this->createMock(EventManager::class);
        $backend                     = $this->createMock(BackendSVN::class);
        $access_file_history_factory = $this->createMock(AccessFileHistoryFactory::class);
        $this->manager               = new RepositoryManager(
            $this->dao,
            $this->project_manager,
            $svn_admin,
            $logger,
            $system_command,
            $destructor,
            $event_manager,
            $backend,
            $access_file_history_factory
        );

        $this->project = $this->createMock(\Project::class);
        $this->request = $this->createMock(HTTPRequest::class);
    }

    public function testItReturnsRepositoryFromAPublicPath(): void
    {
        $this->project->expects(self::once())->method('getUnixNameMixedCase')->willReturn('projectname');
        $this->request->expects(self::once())->method('get')->willReturn('projectname/repositoryname');
        $this->request->expects(self::once())->method('getProject')->willReturn($this->project);

        $this->dao->expects(self::once())
            ->method('searchRepositoryByName')
            ->with($this->project, 'repositoryname')
            ->willReturn(
                [
                    'id'                       => '1',
                    'name'                     => 'repositoryname',
                    'repository_deletion_date' => null,
                    'backup_path'              => null,
                    'is_core'                  => '0',
                ]
            );

        $this->project->expects(self::once())->method('getID')->willReturn(101);
        $this->project->expects(self::once())->method('isError')->willReturn(false);

        $repository = $this->manager->getRepositoryFromPublicPath($this->request);
        self::assertEquals($repository->getName(), 'repositoryname');
    }

    public function testItThrowsAnExceptionWhenRepositoryNameNotFound(): void
    {
        $this->project->expects(self::once())->method('getUnixNameMixedCase')->willReturn('projectname');
        $this->request->expects(self::once())->method('get')->willReturn('projectname/repositoryko');
        $this->request->expects(self::once())->method('getProject')->willReturn($this->project);

        $this->project->expects(self::once())->method('getID')->willReturn(101);
        $this->project->expects(self::once())->method('isError')->willReturn(false);

        $this->dao->expects(self::once())
            ->method('searchRepositoryByName')
            ->with($this->project, 'repositoryko')
            ->willReturn(false);

        $this->expectException(CannotFindRepositoryException::class);
        $this->manager->getRepositoryFromPublicPath($this->request);
    }

    public function testItThrowsAnExceptionWhenProjectNameNotFound(): void
    {
        $this->project->expects(self::once())->method('getUnixNameMixedCase')->willReturn('projectname');
        $this->request->expects(self::once())->method('get')->willReturn('falsyproject/repositoryname');
        $this->request->expects(self::once())->method('getProject')->willReturn($this->project);

        $this->expectException(CannotFindRepositoryException::class);
        $this->manager->getRepositoryFromPublicPath($this->request);
    }

    public function testItReturnsRepositoryFromAPublicPathWithLegacyAndNoMoreValidUnixName(): void
    {
        $this->project->expects(self::once())->method('getUnixNameMixedCase')->willReturn('0abcd');
        $this->request->expects(self::once())->method('get')->willReturn('0abcd/repositoryname');
        $this->request->expects(self::once())->method('getProject')->willReturn($this->project);

        $this->dao->expects(self::once())
            ->method('searchRepositoryByName')
            ->with($this->project, 'repositoryname')
            ->willReturn(
                [
                    'id'                       => '1',
                    'name'                     => 'repositoryname',
                    'repository_deletion_date' => null,
                    'backup_path'              => null,
                    'is_core'                  => '0',
                ]
            );

        $this->project->expects(self::once())->method('getID')->willReturn(101);
        $this->project->expects(self::once())->method('isError')->willReturn(false);

        $repository = $this->manager->getRepositoryFromPublicPath($this->request);
        self::assertEquals($repository->getName(), 'repositoryname');
    }

    public function testItReturnsTheCoreRepository(): void
    {
        $this->project->method('getUnixNameMixedCase')->willReturn('projectname');
        $this->request->method('get')->willReturn('projectname');
        $this->request->method('getProject')->willReturn($this->project);

        $this->dao->method('getCoreRepositoryId')->willReturn(15);

        $repository = $this->manager->getRepositoryFromPublicPath($this->request);
        self::assertEquals('projectname', $repository->getName());
        self::assertEquals(15, $repository->getId());
    }

    public function testItReturnsAnEmptyArrayWhenNoProjectHaveMultiSVNRepositories(): void
    {
        $this->dao->method('searchRepositoriesOfNonDeletedProjects')->willReturn([]);

        $collection          = $this->manager->getRepositoriesOfNonDeletedProjects();
        $expected_collection = [];

        self::assertEquals($expected_collection, $collection);
    }

    public function testItReturnsAnArrayOfRepositoryByProjectCollection(): void
    {
        $this->dao->method('searchRepositoriesOfNonDeletedProjects')->willReturn(
            [
                [
                    'project_id'               => '102',
                    'id'                       => '1',
                    'name'                     => 'repo A',
                    'backup_path'              => '/tmp/102',
                    'repository_deletion_date' => null,
                    'is_core'                  => '0',
                ],
                [
                    'project_id'               => '102',
                    'id'                       => '2',
                    'name'                     => 'repo B',
                    'backup_path'              => '/tmp/102',
                    'repository_deletion_date' => null,
                    'is_core'                  => '0',
                ],
                [
                    'project_id'               => '103',
                    'id'                       => '3',
                    'name'                     => 'repo D',
                    'backup_path'              => '/tmp/103',
                    'repository_deletion_date' => null,
                    'is_core'                  => '0',
                ],
            ]
        );

        $project_A = ProjectTestBuilder::aProject()->withId(102)->build();
        $project_B = ProjectTestBuilder::aProject()->withId(103)->build();

        $this->project_manager->method('getProject')->willReturnMap([
            ['102', $project_A],
            [102, $project_A],
            ['103', $project_B],
            [103, $project_B],
        ]);

        $collection          = $this->manager->getRepositoriesOfNonDeletedProjects();
        $expected_collection = [
            RepositoryByProjectCollection::build($project_A, [
                SvnRepository::buildFromDatabase(['id' => '1', 'name' => 'repo A', 'backup_path' => '/tmp/102', 'repository_deletion_date' => null, 'is_core' => '0'], $project_A),
                SvnRepository::buildFromDatabase(['id' => '2', 'name' => 'repo B', 'backup_path' => '/tmp/102', 'repository_deletion_date' => null, 'is_core' => '0'], $project_A),
            ]),
            RepositoryByProjectCollection::build($project_B, [SvnRepository::buildFromDatabase(['id' => '3', 'name' => 'repo D', 'backup_path' => '/tmp/103', 'repository_deletion_date' => null, 'is_core' => '0'], $project_B)]),
        ];

        self::assertEquals($expected_collection, $collection);
    }

    public function testGetRepositoryFromSystemPathWithCoreRepository(): void
    {
        $tmp_dir = $this->getTmpDir();
        mkdir($tmp_dir . '/svnroot/ProjectName', 0750, true);
        mkdir($tmp_dir . '/svnplugin/101/FooBar', 0750, true);
        \ForgeConfig::set('svn_prefix', $tmp_dir . '/svnroot');

        $this->project_manager->method('getProjectByCaseInsensitiveUnixName')->with('ProjectName')->willReturn(ProjectTestBuilder::aProject()->build());
        $this->dao->method('getCoreRepositoryId')->willReturn(67);

        $repository = $this->manager->getRepositoryFromSystemPath($tmp_dir . '/svnroot/ProjectName');
        self::assertInstanceOf(CoreRepository::class, $repository);
        self::assertEquals(67, $repository->getId());
    }

    public function testGetRepositoryFromSystemPathWithPluginRepository(): void
    {
        $tmp_dir = $this->getTmpDir();
        mkdir($tmp_dir . '/svnroot/ProjectName', 0750, true);
        mkdir($tmp_dir . '/svnplugin/101/FooBar', 0750, true);
        \ForgeConfig::set('svn_prefix', $tmp_dir . '/svnroot');

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager->method('getProject')->with('101')->willReturn($project);
        $this->dao->method('searchRepositoryByName')->with($project, 'FooBar')->willReturn(['id' => 670, 'name' => 'FooBar', 'is_core' => '0', 'backup_path' => null, 'repository_deletion_date' => null]);

        $repository = $this->manager->getRepositoryFromSystemPath($tmp_dir . '/svnplugin/101/FooBar');
        self::assertInstanceOf(SvnRepository::class, $repository);
        self::assertEquals(670, $repository->getId());
    }
}
