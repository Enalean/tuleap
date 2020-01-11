<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once 'bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitRepositoryFactoryGetAllGerritRepositoriesFromProjectTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao = \Mockery::mock(GitDao::class);
        $this->project_manager = \Mockery::spy(\ProjectManager::class);

        $this->factory = \Mockery::mock(
            \GitRepositoryFactory::class,
            [
                $this->dao,
                $this->project_manager
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->repository = \Mockery::spy(\GitRepository::class);

        $this->project_id = 320;
        $this->ugroup_id = 115;

        $this->user_ugroups = array(404, 416);
        $this->user         = \Mockery::spy(\PFUser::class)->shouldReceive('getUgroups')->with($this->project_id, null)->andReturns($this->user_ugroups)->getMock();

        $this->project = \Mockery::spy(\Project::class)->shouldReceive('getID')->andReturns($this->project_id)->getMock();
        $this->ugroup  = \Mockery::spy(\ProjectUGroup::class)->shouldReceive('getId')->andReturns($this->ugroup_id)->getMock();
    }

    public function testItFetchAllGerritRepositoriesFromDao()
    {
        $this->dao->shouldReceive('searchAllGerritRepositoriesOfProject')->with($this->project_id)->andReturn([])->once();
        $this->factory->getAllGerritRepositoriesFromProject($this->project, $this->user);
    }

    public function testItInstanciateGitRepositoriesObjects()
    {
        $this->dao->shouldReceive('searchAllGerritRepositoriesOfProject')->andReturns(\TestHelper::arrayToDar(array('repository_id' => 12), array('repository_id' => 23)));
        $this->factory->shouldReceive('instanciateFromRow')->with(array('repository_id' => 12))->andReturns(\Mockery::spy(\GitRepository::class));
        $this->factory->shouldReceive('instanciateFromRow')->with(array('repository_id' => 23))->andReturns(\Mockery::spy(\GitRepository::class));

        $this->factory->shouldReceive('getGerritRepositoriesWithPermissionsForUGroupAndProject')->andReturns(array());

        $this->factory->getAllGerritRepositoriesFromProject($this->project, $this->user);
    }

    public function testItMergesPermissions()
    {
        $this->dao->shouldReceive('searchAllGerritRepositoriesOfProject')->andReturns(\TestHelper::arrayToDar(array('repository_id' => 12)));
        $this->factory->shouldReceive('instanciateFromRow')->andReturns($this->repository);

        $this->factory->shouldReceive('getGerritRepositoriesWithPermissionsForUGroupAndProject')->andReturns(array(
            12 => new GitRepositoryWithPermissions(
                $this->repository,
                array(
                    Git::PERM_READ          => array(),
                    Git::PERM_WRITE         => array(ProjectUGroup::PROJECT_ADMIN, 404),
                    Git::PERM_WPLUS         => array(),
                    Git::SPECIAL_PERM_ADMIN => array()
                )
            )
        ));

        $repositories_with_permissions = $this->factory->getAllGerritRepositoriesFromProject($this->project, $this->user);

        $this->assertEquals(
            array(
                12 => new GitRepositoryWithPermissions(
                    $this->repository,
                    array(
                        Git::PERM_READ          => array(),
                        Git::PERM_WRITE         => array(ProjectUGroup::PROJECT_ADMIN, 404),
                        Git::PERM_WPLUS         => array(),
                        Git::SPECIAL_PERM_ADMIN => array(ProjectUGroup::PROJECT_ADMIN)
                    )
                )
            ),
            $repositories_with_permissions
        );
    }
}
