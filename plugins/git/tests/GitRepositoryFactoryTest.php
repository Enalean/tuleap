<?php
/**
 * Copyright (c) Enalean, 2011-2019. All Rights Reserved.
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

require_once 'bootstrap.php';

class GitRepositoryFactoryTest extends TuleapTestCase
{
    private $dao;
    private $project_manager;
    private $project;
    private $factory;

    public function setUp()
    {
        parent::setUp();

        $this->dao             = safe_mock(GitDao::class);
        $this->project_manager = mock('ProjectManager');
        $this->project         = mock('Project');

        stub($this->project)->getID()->returns(101);
        stub($this->project)->getUnixName()->returns('garden');

        stub($this->project_manager)->getProjectByUnixName('garden')->returns($this->project);

        $this->factory        = new GitRepositoryFactory($this->dao, $this->project_manager);
    }

    function testGetRepositoryFromFullPath()
    {
        expect($this->dao)->searchProjectRepositoryByPath(101, 'garden/u/manuel/grou/ping/diskinstaller.git')->once();
        stub($this->dao)->searchProjectRepositoryByPath()->returns([]);

        $this->factory->getFromFullPath('/data/tuleap/gitolite/repositories/garden/u/manuel/grou/ping/diskinstaller.git');
    }

    function testGetRepositoryFromFullPathAndGitRoot()
    {
        expect($this->dao)->searchProjectRepositoryByPath(101, 'garden/diskinstaller.git')->once();
        stub($this->dao)->searchProjectRepositoryByPath()->returns([]);

        $this->factory->getFromFullPath('/data/tuleap/gitroot/garden/diskinstaller.git');
    }

    public function itReturnsSpecialRepositoryWhenIdMatches()
    {
        $this->assertIsA(
            $this->factory->getRepositoryById(GitRepositoryGitoliteAdmin::ID),
            'GitRepositoryGitoliteAdmin'
        );
    }

    public function itCanonicalizesRepositoryName()
    {
        $user    = mock('PFUser');
        $project = mock('Project');
        $backend = mock('Git_Backend_Interface');

        $repository = $this->factory->buildRepository($project, 'a', $user, $backend);
        $this->assertEqual($repository->getName(), 'a');

        $repository = $this->factory->buildRepository($project, 'a/b', $user, $backend);
        $this->assertEqual($repository->getName(), 'a/b');

        $repository = $this->factory->buildRepository($project, 'a//b', $user, $backend);
        $this->assertEqual($repository->getName(), 'a/b');

        $repository = $this->factory->buildRepository($project, 'a///b', $user, $backend);
        $this->assertEqual($repository->getName(), 'a/b');
    }
}

class GitRepositoryFactory_getGerritRepositoriesWithPermissionsForUGroupTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->dao = \Mockery::mock(GitDao::class);
        $this->project_manager = mock('ProjectManager');

        $this->factory = partial_mock('GitRepositoryFactory', array('instanciateFromRow'), array($this->dao, $this->project_manager));

        $this->project_id = 320;
        $this->ugroup_id = 115;

        $this->user_ugroups = array(404, 416);
        $this->user         = stub('PFUser')->getUgroups($this->project_id, null)->returns($this->user_ugroups);

        $this->project = stub('Project')->getID()->returns($this->project_id);
        $this->ugroup  = stub('ProjectUGroup')->getId()->returns($this->ugroup_id);
    }

    public function itCallsDaoWithArguments()
    {
        $ugroups = array(404, 416, 115);
        $this->dao->shouldReceive('searchGerritRepositoriesWithPermissionsForUGroupAndProject')
            ->with($this->project_id, $ugroups)->andReturn([])
            ->once();
        $this->factory->getGerritRepositoriesWithPermissionsForUGroupAndProject($this->project, $this->ugroup, $this->user);
    }

    public function itHydratesTheRepositoriesWithFactory()
    {
        $db_row_for_repo_12 = array('repository_id' => 12, 'permission_type' => Git::PERM_READ, 'ugroup_id' => 115);
        $db_row_for_repo_23 = array('repository_id' => 23, 'permission_type' => Git::PERM_READ, 'ugroup_id' => 115);

        stub($this->dao)->searchGerritRepositoriesWithPermissionsForUGroupAndProject()->returnsDar(
            $db_row_for_repo_12,
            $db_row_for_repo_23
        );
        expect($this->factory)->instanciateFromRow()->count(2);
        expect($this->factory)->instanciateFromRow($db_row_for_repo_12)->at(0);
        expect($this->factory)->instanciateFromRow($db_row_for_repo_23)->at(1);
        stub($this->factory)->instanciateFromRow()->returns(mock('GitRepository'));

        $this->factory->getGerritRepositoriesWithPermissionsForUGroupAndProject($this->project, $this->ugroup, $this->user);
    }

    public function itReturnsOneRepositoryWithOnePermission()
    {
        stub($this->dao)->searchGerritRepositoriesWithPermissionsForUGroupAndProject()->returnsDar(
            array(
                'repository_id'   => 12,
                'permission_type' => Git::PERM_READ,
                'ugroup_id'       => 115
            )
        );

        $repository = mock('GitRepository');

        stub($this->factory)->instanciateFromRow()->returns($repository);

        $git_with_permission = $this->factory->getGerritRepositoriesWithPermissionsForUGroupAndProject($this->project, $this->ugroup, $this->user);

        $this->assertEqual(
            $git_with_permission,
            array(
                12 => new GitRepositoryWithPermissions(
                    $repository,
                    array(
                        Git::PERM_READ          => array(115),
                        Git::PERM_WRITE         => array(),
                        Git::PERM_WPLUS         => array(),
                        Git::SPECIAL_PERM_ADMIN => array(),
                    )
                )
            )
        );
    }

    public function itReturnsOneRepositoryWithTwoPermissions()
    {
        stub($this->dao)->searchGerritRepositoriesWithPermissionsForUGroupAndProject()->returnsDar(
            array(
                'repository_id'   => 12,
                'permission_type' => Git::PERM_READ,
                'ugroup_id'       => 115
            ),
            array(
                'repository_id'   => 12,
                'permission_type' => Git::PERM_WRITE,
                'ugroup_id'       => 115
            )
        );

        $repository = mock('GitRepository');

        stub($this->factory)->instanciateFromRow()->returns($repository);

        $git_with_permission = $this->factory->getGerritRepositoriesWithPermissionsForUGroupAndProject($this->project, $this->ugroup, $this->user);

        $this->assertEqual(
            $git_with_permission,
            array(
                12 => new GitRepositoryWithPermissions(
                    $repository,
                    array(
                        Git::PERM_READ          => array(115),
                        Git::PERM_WRITE         => array(115),
                        Git::PERM_WPLUS         => array(),
                        Git::SPECIAL_PERM_ADMIN => array(),
                    )
                )
            )
        );
    }

    public function itReturnsOneRepositoryWithTwoGroupsForOnePermissionType()
    {
        stub($this->dao)->searchGerritRepositoriesWithPermissionsForUGroupAndProject()->returnsDar(
            array(
                'repository_id'   => 12,
                'permission_type' => Git::PERM_READ,
                'ugroup_id'       => 115
            ),
            array(
                'repository_id'   => 12,
                'permission_type' => Git::PERM_READ,
                'ugroup_id'       => 120
            )
        );

        $repository = mock('GitRepository');

        stub($this->factory)->instanciateFromRow()->returns($repository);

        $git_with_permission = $this->factory->getGerritRepositoriesWithPermissionsForUGroupAndProject($this->project, $this->ugroup, $this->user);

        $this->assertEqual(
            $git_with_permission,
            array(
                12 => new GitRepositoryWithPermissions(
                    $repository,
                    array(
                        Git::PERM_READ          => array(115, 120),
                        Git::PERM_WRITE         => array(),
                        Git::PERM_WPLUS         => array(),
                        Git::SPECIAL_PERM_ADMIN => array()
                    )
                )
            )
        );

        $this->assertEqual(
            $git_with_permission[12]->getPermissions(),
            array(
                Git::PERM_READ          => array(115, 120),
                Git::PERM_WRITE         => array(),
                Git::PERM_WPLUS         => array(),
                Git::SPECIAL_PERM_ADMIN => array()
            )
        );
    }
}

class GitRepositoryFactory_getAllGerritRepositoriesFromProjectTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->dao = \Mockery::mock(GitDao::class);
        $this->project_manager = mock('ProjectManager');

        $this->factory = partial_mock(
            'GitRepositoryFactory',
            array('instanciateFromRow', 'getGerritRepositoriesWithPermissionsForUGroupAndProject'),
            array(
                $this->dao,
                $this->project_manager
            )
        );

        $this->repository = mock('GitRepository');

        $this->project_id = 320;
        $this->ugroup_id = 115;

        $this->user_ugroups = array(404, 416);
        $this->user         = stub('PFUser')->getUgroups($this->project_id, null)->returns($this->user_ugroups);

        $this->project = stub('Project')->getID()->returns($this->project_id);
        $this->ugroup  = stub('ProjectUGroup')->getId()->returns($this->ugroup_id);
    }

    public function itFetchAllGerritRepositoriesFromDao()
    {
        $this->dao->shouldReceive('searchAllGerritRepositoriesOfProject')->with($this->project_id)->andReturn([])->once();
        $this->factory->getAllGerritRepositoriesFromProject($this->project, $this->user);
    }

    public function itInstanciateGitRepositoriesObjects()
    {
        stub($this->dao)->searchAllGerritRepositoriesOfProject()->returnsDar(
            array('repository_id' => 12),
            array('repository_id' => 23)
        );
        expect($this->factory)->instanciateFromRow()->count(2);
        expect($this->factory)->instanciateFromRow(array('repository_id' => 12))->at(0);
        expect($this->factory)->instanciateFromRow(array('repository_id' => 23))->at(1);
        stub($this->factory)->instanciateFromRow()->returns(mock('GitRepository'));

        stub($this->factory)->getGerritRepositoriesWithPermissionsForUGroupAndProject()->returns(array());

        $this->factory->getAllGerritRepositoriesFromProject($this->project, $this->user);
    }

    public function itMergesPermissions()
    {
        stub($this->dao)->searchAllGerritRepositoriesOfProject()->returnsDar(
            array('repository_id' => 12)
        );
        stub($this->factory)->instanciateFromRow()->returns($this->repository);

        stub($this->factory)->getGerritRepositoriesWithPermissionsForUGroupAndProject()->returns(
            array(
                12 => new GitRepositoryWithPermissions(
                    $this->repository,
                    array(
                        Git::PERM_READ          => array(),
                        Git::PERM_WRITE         => array(ProjectUGroup::PROJECT_ADMIN, 404),
                        Git::PERM_WPLUS         => array(),
                        Git::SPECIAL_PERM_ADMIN => array()
                    )
                )
            )
        );

        $repositories_with_permissions = $this->factory->getAllGerritRepositoriesFromProject($this->project, $this->user);

        $this->assertEqual(
            $repositories_with_permissions,
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
            )
        );
    }
}
