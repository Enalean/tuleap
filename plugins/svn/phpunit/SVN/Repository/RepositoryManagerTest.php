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

use Backend;
use EventManager;
use HTTPRequest;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectManager;
use System_Command;
use Tuleap\GlobalSVNPollution;
use Tuleap\SVN\AccessControl\AccessFileHistoryFactory;
use Tuleap\SVN\Dao;
use Tuleap\SVN\Repository\Exception\CannotFindRepositoryException;
use Tuleap\SVN\SvnAdmin;

require_once __DIR__ . '/../../bootstrap.php';

class RepositoryManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalSVNPollution;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Dao
     */
    private $dao;
    /**
     * @var HTTPRequest|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $request;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Project
     */
    private $project;

    /**
     * @var RepositoryManager
     */
    private $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao                   = Mockery::mock(Dao::class);
        $this->project_manager       = Mockery::mock(ProjectManager::class);
        $svn_admin                   = Mockery::mock(SvnAdmin::class);
        $logger                      = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $system_command              = Mockery::mock(System_Command::class);
        $destructor                  = Mockery::mock(Destructor::class);
        $event_manager               = Mockery::mock(EventManager::class);
        $backend                     = Mockery::mock(Backend::class);
        $access_file_history_factory = Mockery::mock(AccessFileHistoryFactory::class);
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

        $this->project = Mockery::mock(\Project::class);
        $this->request = Mockery::mock(HTTPRequest::class);
    }

    public function testItReturnsRepositoryFromAPublicPath(): void
    {
        $this->project->shouldReceive('getUnixNameMixedCase')->once()->andReturn('projectname');
        $this->request->shouldReceive('get')->once()->andReturn('projectname/repositoryname');
        $this->request->shouldReceive('getProject')->once()->andReturn($this->project);

        $this->dao->shouldReceive('searchRepositoryByName')->once()->withArgs(
            [$this->project, 'repositoryname']
        )->andReturn(
            [
                'id'                       => 1,
                'name'                     => 'repositoryname',
                'repository_deletion_date' => '0000-00-00 00:00:00',
                'backup_path'              => ''
            ]
        );

        $this->project->shouldReceive('getID')->once()->andReturn(101);
        $this->project->shouldReceive('isError')->once()->andReturnFalse();

        $repository = $this->manager->getRepositoryFromPublicPath($this->request);
        $this->assertEquals($repository->getName(), 'repositoryname');
    }

    public function testItThrowsAnExceptionWhenRepositoryNameNotFound(): void
    {
        $this->project->shouldReceive('getUnixNameMixedCase')->once()->andReturn('projectname');
        $this->request->shouldReceive('get')->once()->andReturn('projectname/repositoryko');
        $this->request->shouldReceive('getProject')->once()->andReturn($this->project);

        $this->project->shouldReceive('getID')->once()->andReturn(101);
        $this->project->shouldReceive('isError')->once()->andReturnFalse();

        $this->dao->shouldReceive('searchRepositoryByName')->once()->withArgs(
            [$this->project, 'repositoryko']
        )->andReturn(false);

        $this->expectException(CannotFindRepositoryException::class);
        $this->manager->getRepositoryFromPublicPath($this->request);
    }

    public function testItThrowsAnExceptionWhenProjectNameNotFound(): void
    {
        $this->project->shouldReceive('getUnixNameMixedCase')->once()->andReturn('projectname');
        $this->request->shouldReceive('get')->once()->andReturn('falsyproject/repositoryname');
        $this->request->shouldReceive('getProject')->once()->andReturn($this->project);

        $this->expectException(CannotFindRepositoryException::class);
        $this->manager->getRepositoryFromPublicPath($this->request);
    }

    public function testItReturnsRepositoryFromAPublicPathWithLegacyAndNoMoreValidUnixName(): void
    {
        $this->project->shouldReceive('getUnixNameMixedCase')->once()->andReturn('0abcd');
        $this->request->shouldReceive('get')->once()->andReturn('0abcd/repositoryname');
        $this->request->shouldReceive('getProject')->once()->andReturn($this->project);

        $this->dao->shouldReceive('searchRepositoryByName')->once()->withArgs([$this->project, 'repositoryname'])->once(
        )->andReturn(
            [
                'id'                       => 1,
                'name'                     => 'repositoryname',
                'repository_deletion_date' => '0000-00-00 00:00:00',
                'backup_path'              => ''
            ]
        );

        $this->project->shouldReceive('getID')->once()->andReturn(101);
        $this->project->shouldReceive('isError')->once()->andReturnFalse();

        $repository = $this->manager->getRepositoryFromPublicPath($this->request);
        $this->assertEquals($repository->getName(), 'repositoryname');
    }

    public function testItReturnsAnEmptyArrayWhenNoProjectHaveMultiSVNRepositories(): void
    {
        $this->dao->shouldReceive('searchRepositoriesOfNonDeletedProjects')->andReturn([]);

        $collection          = $this->manager->getRepositoriesOfNonDeletedProjects();
        $expected_collection = [];

        $this->assertEquals($expected_collection, $collection);
    }

    public function testItReturnsAnEArrayOfRepositoryByProjectCollection(): void
    {
        $this->dao->shouldReceive('searchRepositoriesOfNonDeletedProjects')->andReturn(
            [
                [
                    'project_id'               => 102,
                    'id'                       => 1,
                    'name'                     => 'repo A',
                    'backup_path'              => '/tmp/102',
                    'repository_deletion_date' => null
                ],
                [
                    'project_id'               => 102,
                    'id'                       => 2,
                    'name'                     => 'repo B',
                    'backup_path'              => '/tmp/102',
                    'repository_deletion_date' => null
                ],
                [
                    'project_id'               => 103,
                    'id'                       => 1,
                    'name'                     => 'repo D',
                    'backup_path'              => '/tmp/103',
                    'repository_deletion_date' => null
                ]
            ]
        );

        $project_A = Mockery::mock(Project::class);
        $this->project_manager->shouldReceive('getProject')->withArgs([102])->andReturn($project_A);
        $project_B = Mockery::mock(Project::class);
        $this->project_manager->shouldReceive('getProject')->withArgs([103])->andReturn($project_B);

        $collection          = $this->manager->getRepositoriesOfNonDeletedProjects();
        $expected_collection = [
            RepositoryByProjectCollection::build($project_A, [
                new Repository(1, 'repo A', '/tmp/102', null, $project_A),
                new Repository(2, 'repo B', '/tmp/102', null, $project_A)
            ]),
            RepositoryByProjectCollection::build($project_B, [new Repository(1, 'repo D', '/tmp/103', null, $project_A)])
        ];

        $this->assertEquals($expected_collection, $collection);
    }
}
