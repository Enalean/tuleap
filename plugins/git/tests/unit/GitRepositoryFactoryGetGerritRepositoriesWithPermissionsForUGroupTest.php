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
class GitRepositoryFactoryGetGerritRepositoriesWithPermissionsForUGroupTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao = \Mockery::mock(GitDao::class);
        $this->project_manager = \Mockery::spy(\ProjectManager::class);

        $this->factory = \Mockery::mock(
            \GitRepositoryFactory::class,
            [$this->dao, $this->project_manager]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->project_id = 320;
        $this->ugroup_id = 115;

        $this->user_ugroups = [404, 416];
        $this->user         = \Mockery::spy(\PFUser::class)->shouldReceive('getUgroups')->with($this->project_id, null)->andReturns($this->user_ugroups)->getMock();

        $this->project = \Mockery::spy(\Project::class)->shouldReceive('getID')->andReturns($this->project_id)->getMock();
        $this->ugroup  = \Mockery::spy(\ProjectUGroup::class)->shouldReceive('getId')->andReturns($this->ugroup_id)->getMock();
    }

    public function testItCallsDaoWithArguments(): void
    {
        $ugroups = [404, 416, 115];
        $this->dao->shouldReceive('searchGerritRepositoriesWithPermissionsForUGroupAndProject')
            ->with($this->project_id, $ugroups)
            ->andReturn([])
            ->once();

        $this->factory->getGerritRepositoriesWithPermissionsForUGroupAndProject($this->project, $this->ugroup, $this->user);
    }

    public function testItHydratesTheRepositoriesWithFactory(): void
    {
        $db_row_for_repo_12 = ['repository_id' => 12, 'permission_type' => Git::PERM_READ, 'ugroup_id' => 115];
        $db_row_for_repo_23 = ['repository_id' => 23, 'permission_type' => Git::PERM_READ, 'ugroup_id' => 115];

        $this->dao->shouldReceive('searchGerritRepositoriesWithPermissionsForUGroupAndProject')->andReturns(\TestHelper::arrayToDar($db_row_for_repo_12, $db_row_for_repo_23));
        $this->factory->shouldReceive('instanciateFromRow')->with($db_row_for_repo_12)->andReturns(\Mockery::spy(\GitRepository::class));
        $this->factory->shouldReceive('instanciateFromRow')->with($db_row_for_repo_23)->andReturns(\Mockery::spy(\GitRepository::class));

        $this->factory->getGerritRepositoriesWithPermissionsForUGroupAndProject($this->project, $this->ugroup, $this->user);
    }

    public function testItReturnsOneRepositoryWithOnePermission(): void
    {
        $this->dao->shouldReceive('searchGerritRepositoriesWithPermissionsForUGroupAndProject')->andReturns(\TestHelper::arrayToDar([
            'repository_id'   => 12,
            'permission_type' => Git::PERM_READ,
            'ugroup_id'       => 115
        ]));

        $repository = \Mockery::spy(\GitRepository::class);

        $this->factory->shouldReceive('instanciateFromRow')->andReturns($repository);

        $git_with_permission = $this->factory->getGerritRepositoriesWithPermissionsForUGroupAndProject($this->project, $this->ugroup, $this->user);

        $this->assertEquals(
            [
                12 => new GitRepositoryWithPermissions(
                    $repository,
                    [
                        Git::PERM_READ          => [115],
                        Git::PERM_WRITE         => [],
                        Git::PERM_WPLUS         => [],
                        Git::SPECIAL_PERM_ADMIN => [],
                    ]
                )
            ],
            $git_with_permission
        );
    }

    public function testItReturnsOneRepositoryWithTwoPermissions(): void
    {
        $this->dao->shouldReceive('searchGerritRepositoriesWithPermissionsForUGroupAndProject')->andReturns(\TestHelper::arrayToDar([
            'repository_id'   => 12,
            'permission_type' => Git::PERM_READ,
            'ugroup_id'       => 115
        ], [
            'repository_id'   => 12,
            'permission_type' => Git::PERM_WRITE,
            'ugroup_id'       => 115
        ]));

        $repository = \Mockery::spy(\GitRepository::class);

        $this->factory->shouldReceive('instanciateFromRow')->andReturns($repository);

        $git_with_permission = $this->factory->getGerritRepositoriesWithPermissionsForUGroupAndProject($this->project, $this->ugroup, $this->user);

        $this->assertEquals(
            [
                12 => new GitRepositoryWithPermissions(
                    $repository,
                    [
                        Git::PERM_READ          => [115],
                        Git::PERM_WRITE         => [115],
                        Git::PERM_WPLUS         => [],
                        Git::SPECIAL_PERM_ADMIN => [],
                    ]
                )
            ],
            $git_with_permission
        );
    }

    public function testItReturnsOneRepositoryWithTwoGroupsForOnePermissionType(): void
    {
        $this->dao->shouldReceive('searchGerritRepositoriesWithPermissionsForUGroupAndProject')->andReturns(\TestHelper::arrayToDar([
            'repository_id'   => 12,
            'permission_type' => Git::PERM_READ,
            'ugroup_id'       => 115
        ], [
            'repository_id'   => 12,
            'permission_type' => Git::PERM_READ,
            'ugroup_id'       => 120
        ]));

        $repository = \Mockery::spy(\GitRepository::class);

        $this->factory->shouldReceive('instanciateFromRow')->andReturns($repository);

        $git_with_permission = $this->factory->getGerritRepositoriesWithPermissionsForUGroupAndProject($this->project, $this->ugroup, $this->user);

        $this->assertEquals(
            [
                12 => new GitRepositoryWithPermissions(
                    $repository,
                    [
                        Git::PERM_READ          => [115, 120],
                        Git::PERM_WRITE         => [],
                        Git::PERM_WPLUS         => [],
                        Git::SPECIAL_PERM_ADMIN => []
                    ]
                )
            ],
            $git_with_permission
        );

        $this->assertEquals(
            [
                Git::PERM_READ          => [115, 120],
                Git::PERM_WRITE         => [],
                Git::PERM_WPLUS         => [],
                Git::SPECIAL_PERM_ADMIN => []
            ],
            $git_with_permission[12]->getPermissions()
        );
    }
}
