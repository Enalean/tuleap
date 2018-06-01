<?php
/**
 * Copyright (c) Enalean, 2011-2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Git\Permissions\FineGrainedPermission;

require_once dirname(__FILE__).'/../../bootstrap.php';

Mock::generate('Project');
Mock::generate('GitRepository');
Mock::generate('PermissionsManager');

class Git_Gitolite_ConfigPermissionsSerializerTest extends TuleapTestCase {
    private $serializer;
    private $project;
    private $project_id    = 100;

    private $repository;
    private $repository_id = 200;

    public function setUp() {
        parent::setUp();

        $this->project_id++;
        $this->repository_id++;

        $this->project    = new MockProject();
        $this->project->setReturnValue('getId', $this->project_id);
        $this->project->setReturnValue('getUnixName', 'project' . $this->project_id);

        $this->repository = new MockGitRepository();
        $this->repository->setReturnValue('getId', $this->repository);
        stub($this->repository)->getProject()->returns($this->project);

        PermissionsManager::setInstance(new MockPermissionsManager());
        $this->permissions_manager = PermissionsManager::instance();

        $this->serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            stub('Git_Mirror_MirrorDataMapper')->fetchAllRepositoryMirrors()->returns(array()),
            mock('Git_Driver_Gerrit_ProjectCreatorStatus'),
            'whatever',
            mock('Tuleap\Git\Permissions\FineGrainedRetriever'),
            mock('Tuleap\Git\Permissions\FineGrainedPermissionFactory'),
            mock('Tuleap\Git\Permissions\RegexpFineGrainedRetriever'),
            mock(EventManager::class)
        );
    }

    public function tearDown() {
        PermissionsManager::clearInstance();
        parent::tearDown();
    }

    public function itReturnsEmptyStringForUnknownType() {
        $this->permissions_manager->setReturnValue('getAuthorizedUGroupIdsForProject', array());
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, '__none__');
        $this->assertIdentical('', $result);
    }

    public function itReturnsEmptyStringForAUserIdLowerOrEqualThan_100() {
        $this->permissions_manager->setReturnValue('getAuthorizedUGroupIdsForProject', array(100));
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $this->assertIdentical('', $result);
    }

    public function itReturnsStringWithUserIdIfIdGreaterThan_100() {
        $this->permissions_manager->setReturnValue('getAuthorizedUGroupIdsForProject', array(101));
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $this->assertPattern('/=\s@ug_101$/', $result);
    }

    public function itReturnsSiteActiveIfUserGroupIsRegistered() {
        $this->permissions_manager->setReturnValue('getAuthorizedUGroupIdsForProject', array(ProjectUGroup::REGISTERED));
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $this->assertPattern('/=\s@site_active @'. $this->project->getUnixName() .'_project_members$/', $result);
    }

    public function itReturnsProjectNameWithProjectMemberIfUserIsProjectMember() {
        $this->permissions_manager->setReturnValue('getAuthorizedUGroupIdsForProject', array(ProjectUGroup::PROJECT_MEMBERS));
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $project_name = 'project' . $this->project_id;
        $this->assertPattern('/=\s@'.$project_name.'_project_members$/', $result);
    }

    public function itReturnsProjectNameWithProjectAdminIfUserIsProjectAdmin() {
        $this->permissions_manager->setReturnValue('getAuthorizedUGroupIdsForProject', array(ProjectUGroup::PROJECT_ADMIN));
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $project_name = 'project' . $this->project_id;
        $this->assertPattern('/=\s@'.$project_name.'_project_admin$/', $result);
    }

    public function itPrefixesWithRForReaders() {
        $this->permissions_manager->setReturnValue('getAuthorizedUGroupIdsForProject', array(101));
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $this->assertPattern('/^\sR\s\s\s=/', $result);
    }

    public function itPrefixesWithRWForWriters() {
        $this->permissions_manager->setReturnValue('getAuthorizedUGroupIdsForProject', array(101));
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_WRITE);
        $this->assertPattern('/^\sRW\s\s=/', $result);
    }

    public function itPrefixesWithRWPlusForWritersPlus() {
        $this->permissions_manager->setReturnValue('getAuthorizedUGroupIdsForProject', array(101));
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_WPLUS);
        $this->assertPattern('/^\sRW\+\s=/', $result);
    }

    public function itReturnsAllGroupsSeparatedBySpaceIfItHasDifferentGroups() {
        $this->permissions_manager->setReturnValue('getAuthorizedUGroupIdsForProject', array(666, ProjectUGroup::REGISTERED));
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $this->assertIdentical(' R   = @ug_666 @site_active @'. $this->project->getUnixName() .'_project_members' . PHP_EOL, $result);
    }
}

class Git_Gitolite_ConfigPermissionsSerializer_MirrorsTest extends TuleapTestCase {

    private $serializer;
    private $mirror_mapper;
    private $repository;
    private $mirror_1;
    private $mirror_2;
    private $permissions_manager;
    private $project;

    public function setUp() {
        parent::setUp();
        $this->mirror_mapper = mock('Git_Mirror_MirrorDataMapper');
        $this->serializer    = new Git_Gitolite_ConfigPermissionsSerializer(
            $this->mirror_mapper,
            mock('Git_Driver_Gerrit_ProjectCreatorStatus'),
            'whatever',
            mock('Tuleap\Git\Permissions\FineGrainedRetriever'),
            mock('Tuleap\Git\Permissions\FineGrainedPermissionFactory'),
            mock('Tuleap\Git\Permissions\RegexpFineGrainedRetriever'),
            mock(EventManager::class)
        );

        $this->project    = stub('Project')->getUnixName()->returns('foo');
        $this->repository = aGitRepository()->withId(115)->withProject($this->project)->build();

        $user_mirror1     = aUser()->withUserName('git_mirror_1')->build();
        $this->mirror_1   = new Git_Mirror_Mirror($user_mirror1, 1, 'url', 'hostname', 'EUR');
        $user_mirror2     = aUser()->withUserName('git_mirror_2')->build();
        $this->mirror_2   = new Git_Mirror_Mirror($user_mirror2, 2, 'url', 'hostname', 'IND');

        $this->permissions_manager = mock('PermissionsManager');
        PermissionsManager::setInstance($this->permissions_manager);
    }

    public function tearDown() {
        PermissionsManager::clearInstance();
        parent::tearDown();
    }

    public function itGrantsReadPermissionToOneMirror() {
        stub($this->mirror_mapper)->fetchAllRepositoryMirrors($this->repository)->returns(
            array(
                $this->mirror_1
            )
        );
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject()->returns(array(ProjectUGroup::REGISTERED));

        $result = $this->serializer->getForRepository($this->repository);
        $this->assertPattern('/^ R   = git_mirror_1$/m', $result);
    }

    public function itGrantsReadPermissionToTwoMirrors() {
        stub($this->mirror_mapper)->fetchAllRepositoryMirrors($this->repository)->returns(
            array(
                $this->mirror_1,
                $this->mirror_2,
            )
        );
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject()->returns(array(ProjectUGroup::REGISTERED));

        $result = $this->serializer->getForRepository($this->repository);
        $this->assertPattern('/^ R   = git_mirror_1 git_mirror_2$/m', $result);
    }

    public function itHasNoMirrors() {
        stub($this->mirror_mapper)->fetchAllRepositoryMirrors($this->repository)->returns(
            array()
        );
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject()->returns(array());

        $result = $this->serializer->getForRepository($this->repository);
        $this->assertEqual('', $result);
    }
}

class Git_Gitolite_ConfigPermissionsSerializer_GitoliteConfTest extends TuleapTestCase {

    private $mirror_mapper;
    private $mirror_1;
    private $mirror_2;

    public function setUp() {
        parent::setUp();
        $this->mirror_mapper = mock('Git_Mirror_MirrorDataMapper');

        $user_mirror1        = aUser()->withUserName('forge__gitmirror_1')->build();
        $this->mirror_1      = new Git_Mirror_Mirror($user_mirror1, 1, 'url', 'hostname', 'CHN');
        $user_mirror2        = aUser()->withUserName('forge__gitmirror_2')->build();
        $this->mirror_2      = new Git_Mirror_Mirror($user_mirror2, 2, 'url', 'hostname', 'JPN');
    }

    public function itDumpsTheConf() {
        stub($this->mirror_mapper)->fetchAll()->returns(array());
        $serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            $this->mirror_mapper,
            mock('Git_Driver_Gerrit_ProjectCreatorStatus'),
            'whatever',
            mock('Tuleap\Git\Permissions\FineGrainedRetriever'),
            mock('Tuleap\Git\Permissions\FineGrainedPermissionFactory'),
            mock('Tuleap\Git\Permissions\RegexpFineGrainedRetriever'),
            mock(EventManager::class)
        );

        $this->assertEqual(
            file_get_contents(dirname(__FILE__).'/_fixtures/default_gitolite.conf'),
            $serializer->getGitoliteDotConf(array('projecta', 'projectb'))
        );
    }

    public function itAllowsOverrideBySiteAdmin() {
        stub($this->mirror_mapper)->fetchAll()->returns(array());
        $serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            $this->mirror_mapper,
            mock('Git_Driver_Gerrit_ProjectCreatorStatus'),
            dirname(__FILE__).'/_fixtures/etc_templates',
            mock('Tuleap\Git\Permissions\FineGrainedRetriever'),
            mock('Tuleap\Git\Permissions\FineGrainedPermissionFactory'),
            mock('Tuleap\Git\Permissions\RegexpFineGrainedRetriever'),
            mock(EventManager::class)
        );

        $this->assertEqual(
            file_get_contents(dirname(__FILE__).'/_fixtures/override_gitolite.conf'),
            $serializer->getGitoliteDotConf(array('projecta', 'projectb'))
        );
    }

    public function itGrantsReadAccessToGitoliteAdminForMirrorUsers() {
        stub($this->mirror_mapper)->fetchAll()->returns(array($this->mirror_1, $this->mirror_2));
        $serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            $this->mirror_mapper,
            mock('Git_Driver_Gerrit_ProjectCreatorStatus'),
            'whatever',
            mock('Tuleap\Git\Permissions\FineGrainedRetriever'),
            mock('Tuleap\Git\Permissions\FineGrainedPermissionFactory'),
            mock('Tuleap\Git\Permissions\RegexpFineGrainedRetriever'),
            mock(EventManager::class)
        );
        $this->assertEqual(
            file_get_contents(dirname(__FILE__).'/_fixtures/mirrors_gitolite.conf'),
            $serializer->getGitoliteDotConf(array('projecta', 'projectb'))
        );
    }
}

class Git_Gitolite_ConfigPermissionsSerializer_GerritTest extends TuleapTestCase {

    private $serializer;
    private $repository;
    private $gerrit_status;

    public function setUp() {
        parent::setUp();

        $project = mock('Project');
        stub($project)->getId()->returns(102);
        stub($project)->getUnixName()->returns('gpig');

        $this->repository = mock('GitRepository');
        stub($this->repository)->getId()->returns(1001);
        stub($this->repository)->getProject()->returns($project);

        PermissionsManager::setInstance(mock('PermissionsManager'));
        $this->permissions_manager = PermissionsManager::instance();

        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject('*', '*', Git::PERM_READ)->returns(array(ProjectUGroup::REGISTERED));
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject('*', '*', Git::PERM_WRITE)->returns(array(ProjectUGroup::PROJECT_MEMBERS));
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject('*', '*', Git::PERM_WPLUS)->returns(array());

        $this->gerrit_status = mock('Git_Driver_Gerrit_ProjectCreatorStatus');

        $this->serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            stub('Git_Mirror_MirrorDataMapper')->fetchAllRepositoryMirrors()->returns(array()),
            $this->gerrit_status,
            'whatever',
            mock('Tuleap\Git\Permissions\FineGrainedRetriever'),
            mock('Tuleap\Git\Permissions\FineGrainedPermissionFactory'),
            mock('Tuleap\Git\Permissions\RegexpFineGrainedRetriever'),
            mock(EventManager::class)
        );
    }

    public function tearDown() {
        PermissionsManager::clearInstance();
        parent::tearDown();
    }

    public function itGeneratesTheDefaultConfiguration() {
        $this->assertEqual(
            " R   = @site_active @gpig_project_members\n".
            " RW  = @gpig_project_members\n",
            $this->serializer->getForRepository($this->repository)
        );
    }

    public function itGrantsEverythingToGerritUserAfterMigrationIsDoneWithSuccess() {
        stub($this->repository)->isMigratedToGerrit()->returns(true);
        stub($this->repository)->getRemoteServerId()->returns(2);

        stub($this->gerrit_status)->getStatus($this->repository)->returns(
            Git_Driver_Gerrit_ProjectCreatorStatus::DONE
        );

        $this->assertEqual(
            " R   = @site_active @gpig_project_members\n".
            " RW+ = forge__gerrit_2\n",
            $this->serializer->getForRepository($this->repository)
        );
    }

    public function itDoesntGrantAllPermissionsToGerritIfMigrationIsWaitingForExecution() {
        stub($this->repository)->isMigratedToGerrit()->returns(true);
        stub($this->repository)->getRemoteServerId()->returns(2);

        stub($this->gerrit_status)->getStatus($this->repository)->returns(
            Git_Driver_Gerrit_ProjectCreatorStatus::QUEUE
        );

        $this->assertEqual(
            " R   = @site_active @gpig_project_members\n".
            " RW  = @gpig_project_members\n",
            $this->serializer->getForRepository($this->repository)
        );
    }

    public function itDoesntGrantAllPermissionsToGerritIfMigrationIsError() {
        stub($this->repository)->isMigratedToGerrit()->returns(true);
        stub($this->repository)->getRemoteServerId()->returns(2);

        stub($this->gerrit_status)->getStatus($this->repository)->returns(
            Git_Driver_Gerrit_ProjectCreatorStatus::ERROR
        );

        $this->assertEqual(
            " R   = @site_active @gpig_project_members\n".
            " RW  = @gpig_project_members\n",
            $this->serializer->getForRepository($this->repository)
        );
    }
}

class Git_Gitolite_ConfigPermissionsSerializer_GerritAndMirrorsTest extends TuleapTestCase {

    private $serializer;
    private $repository;
    private $gerrit_status;

    public function setUp() {
        parent::setUp();

        $project = mock('Project');
        stub($project)->getId()->returns(102);
        stub($project)->getUnixName()->returns('gpig');

        $this->repository = mock('GitRepository');
        stub($this->repository)->getId()->returns(1001);
        stub($this->repository)->getProject()->returns($project);

        PermissionsManager::setInstance(mock('PermissionsManager'));
        $this->permissions_manager = PermissionsManager::instance();

        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject('*', '*', Git::PERM_READ)->returns(array(ProjectUGroup::REGISTERED));
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject('*', '*', Git::PERM_WRITE)->returns(array(ProjectUGroup::PROJECT_MEMBERS));
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject('*', '*', Git::PERM_WPLUS)->returns(array());

        $this->gerrit_status = mock('Git_Driver_Gerrit_ProjectCreatorStatus');

        $user_mirror1        = aUser()->withUserName('git_mirror_1')->build();
        $this->mirror_1      = new Git_Mirror_Mirror($user_mirror1, 1, 'url', 'hostname', 'EUR');

        $this->serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            stub('Git_Mirror_MirrorDataMapper')->fetchAllRepositoryMirrors()->returns(array()),
            $this->gerrit_status,
            'whatever',
            mock('Tuleap\Git\Permissions\FineGrainedRetriever'),
            mock('Tuleap\Git\Permissions\FineGrainedPermissionFactory'),
            mock('Tuleap\Git\Permissions\RegexpFineGrainedRetriever'),
            mock(EventManager::class)
        );
    }

    public function tearDown() {
        PermissionsManager::clearInstance();
        parent::tearDown();
    }
}

class Git_Gitolite_ConfigPermissionsSerializer_FineGrainedPermissionsTest extends TuleapTestCase {

    /**
     * @var Tuleap\Git\Permissions\FineGrainedRetriever
     */
    private $retriever;

    /**
     * @var Tuleap\Git\Permissions\FineGrainedPermissionFactory
     */
    private $factory;

    /**
     * @var Git_Gitolite_ConfigPermissionsSerializer
     */
    private $serializer;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var GitRepository
     */
    private $repository;

    /**
     * @var PermissionsManager
     */
    private $permissions_manager;

    /**
     * @var ProjectUGroup
     */
    private $ugroup_01;

    /**
     * @var ProjectUGroup
     */
    private $ugroup_02;

    /**
     * @var ProjectUGroup
     */
    private $ugroup_03;

    /**
     * @var ProjectUGroup
     */
    private $ugroup_nobody;

    /**
     * @var FineGrainedPermission
     */
    private $permission_01;

    /**
     * @var FineGrainedPermission
     */
    private $permission_02;

    /**
     * @var FineGrainedPermission
     */
    private $permission_03;

    /**
     * @var Tuleap\Git\Permissions\RegexpFineGrainedRetriever
     */
    private $regexp_retriever;

    public function setUp()
    {
        parent::setUp();

        $this->retriever = mock('Tuleap\Git\Permissions\FineGrainedRetriever');
        $this->factory   = mock('Tuleap\Git\Permissions\FineGrainedPermissionFactory');
        $this->regexp_retriever = mock('Tuleap\Git\Permissions\RegexpFineGrainedRetriever');

        $this->serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            stub('Git_Mirror_MirrorDataMapper')->fetchAllRepositoryMirrors()->returns(array()),
            mock('Git_Driver_Gerrit_ProjectCreatorStatus'),
            'whatever',
            $this->retriever,
            $this->factory,
            $this->regexp_retriever,
            mock(EventManager::class)
        );

        $this->project    = mock('Project');
        $this->repository = stub('GitRepository')->getId()->returns(1);
        stub($this->repository)->getProject()->returns($this->project);

        $this->permissions_manager = mock('PermissionsManager');
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject('*', '*', Git::PERM_READ)->returns(
            array(ProjectUGroup::REGISTERED)
        );

        $this->ugroup_01     = stub('ProjectUGroup')->getId()->returns('101');
        $this->ugroup_02     = stub('ProjectUGroup')->getId()->returns('102');
        $this->ugroup_03     = stub('ProjectUGroup')->getId()->returns('103');
        $this->ugroup_nobody = stub('ProjectUGroup')->getId()->returns('100');

        $this->permission_01 = new FineGrainedPermission(
            1,
            1,
            'refs/heads/master',
            array(),
            array()
        );

        $this->permission_02 = new FineGrainedPermission(
            2,
            1,
            'refs/tags/v1',
            array(),
            array()
        );

        $this->permission_03 = new FineGrainedPermission(
            3,
            1,
            'refs/heads/dev/*',
            array(),
            array()
        );

        PermissionsManager::setInstance($this->permissions_manager);
    }

    public function tearDown() {
        PermissionsManager::clearInstance();
        parent::tearDown();
    }

    public function itMustFollowTheExpectedOrderForPermission()
    {
        stub($this->factory)->getBranchesFineGrainedPermissionsForRepository()->returns(array(1 => $this->permission_01));
        stub($this->factory)->getTagsFineGrainedPermissionsForRepository()->returns(array());

        $writers   = array($this->ugroup_01, $this->ugroup_02);
        $rewinders = array($this->ugroup_03);

        $this->permission_01->setWriters($writers);
        $this->permission_01->setRewinders($rewinders);

        stub($this->retriever)->doesRepositoryUseFineGrainedPermissions($this->repository)->returns(true);

        $config = $this->serializer->getForRepository($this->repository);

        $expected = <<<EOS
 R   = @site_active @_project_members
 RW+ refs/heads/master$ = @ug_103
 RW refs/heads/master$ = @ug_101 @ug_102
 - refs/heads/master$ = @all

EOS;

        $this->assertEqual($config, $expected);
    }

    public function itFetchesFineGrainedPermissions()
    {
        stub($this->factory)->getBranchesFineGrainedPermissionsForRepository()->returns(array(1 => $this->permission_01));
        stub($this->factory)->getTagsFineGrainedPermissionsForRepository()->returns(array(2 => $this->permission_02));

        $writers   = array($this->ugroup_01, $this->ugroup_02);
        $rewinders = array($this->ugroup_03);

        $this->permission_01->setWriters($writers);
        $this->permission_01->setRewinders($rewinders);

        $this->permission_02->setWriters($writers);
        $this->permission_02->setRewinders($rewinders);

        stub($this->retriever)->doesRepositoryUseFineGrainedPermissions($this->repository)->returns(true);

        $config = $this->serializer->getForRepository($this->repository);

        $expected = <<<EOS
 R   = @site_active @_project_members
 RW+ refs/heads/master$ = @ug_103
 RW refs/heads/master$ = @ug_101 @ug_102
 - refs/heads/master$ = @all
 RW+ refs/tags/v1$ = @ug_103
 RW refs/tags/v1$ = @ug_101 @ug_102
 - refs/tags/v1$ = @all

EOS;

        $this->assertEqual($config, $expected);
    }

    public function itDealsWithNobody()
    {
        stub($this->factory)->getBranchesFineGrainedPermissionsForRepository()->returns(array(1 => $this->permission_01));
        stub($this->factory)->getTagsFineGrainedPermissionsForRepository()->returns(array(2 => $this->permission_02));

        $writers          = array($this->ugroup_01, $this->ugroup_02);
        $rewinders        = array($this->ugroup_03);
        $rewinders_nobody = array($this->ugroup_nobody);

        $this->permission_01->setWriters($writers);
        $this->permission_01->setRewinders($rewinders);

        $this->permission_02->setWriters($writers);
        $this->permission_02->setRewinders($rewinders_nobody);

        stub($this->retriever)->doesRepositoryUseFineGrainedPermissions($this->repository)->returns(true);

        $config = $this->serializer->getForRepository($this->repository);

        $expected = <<<EOS
 R   = @site_active @_project_members
 RW+ refs/heads/master$ = @ug_103
 RW refs/heads/master$ = @ug_101 @ug_102
 - refs/heads/master$ = @all
 RW refs/tags/v1$ = @ug_101 @ug_102
 - refs/tags/v1$ = @all

EOS;

        $this->assertEqual($config, $expected);
    }

    public function itDealsWithStarPath()
    {
        stub($this->factory)->getBranchesFineGrainedPermissionsForRepository()->returns(array(3 => $this->permission_03));
        stub($this->factory)->getTagsFineGrainedPermissionsForRepository()->returns(array(2 => $this->permission_02));

        $writers          = array($this->ugroup_01, $this->ugroup_02);
        $rewinders        = array($this->ugroup_03);

        $this->permission_03->setWriters($writers);
        $this->permission_03->setRewinders($rewinders);

        $this->permission_02->setWriters($writers);
        $this->permission_02->setRewinders($rewinders);

        stub($this->retriever)->doesRepositoryUseFineGrainedPermissions($this->repository)->returns(true);

        $config = $this->serializer->getForRepository($this->repository);

        $expected = <<<EOS
 R   = @site_active @_project_members
 RW+ refs/heads/dev/.*$ = @ug_103
 RW refs/heads/dev/.*$ = @ug_101 @ug_102
 - refs/heads/dev/.*$ = @all
 RW+ refs/tags/v1$ = @ug_103
 RW refs/tags/v1$ = @ug_101 @ug_102
 - refs/tags/v1$ = @all

EOS;

        $this->assertEqual($config, $expected);
    }

    public function itDealsWithNoUgroupSelected()
    {
        stub($this->factory)->getBranchesFineGrainedPermissionsForRepository()->returns(array(1 => $this->permission_01));
        stub($this->factory)->getTagsFineGrainedPermissionsForRepository()->returns(array(2 => $this->permission_02));

        $writers          = array($this->ugroup_01, $this->ugroup_02);
        $rewinders        = array();

        $this->permission_01->setWriters($writers);
        $this->permission_01->setRewinders($rewinders);

        $this->permission_02->setWriters($writers);
        $this->permission_02->setRewinders($rewinders);

        stub($this->retriever)->doesRepositoryUseFineGrainedPermissions($this->repository)->returns(true);

        $config = $this->serializer->getForRepository($this->repository);

        $expected = <<<EOS
 R   = @site_active @_project_members
 RW refs/heads/master$ = @ug_101 @ug_102
 - refs/heads/master$ = @all
 RW refs/tags/v1$ = @ug_101 @ug_102
 - refs/tags/v1$ = @all

EOS;

        $this->assertEqual($config, $expected);
    }

    public function itDeniesPatternIfNobodyCanWriteAndRewind()
    {
        stub($this->factory)->getBranchesFineGrainedPermissionsForRepository()->returns(array(1 => $this->permission_01));
        stub($this->factory)->getTagsFineGrainedPermissionsForRepository()->returns(array(2 => $this->permission_02));

        $writers          = array($this->ugroup_nobody);
        $rewinders        = array();

        $this->permission_01->setWriters($writers);
        $this->permission_01->setRewinders($rewinders);

        $this->permission_02->setWriters($writers);
        $this->permission_02->setRewinders($rewinders);

        stub($this->retriever)->doesRepositoryUseFineGrainedPermissions($this->repository)->returns(true);

        $config = $this->serializer->getForRepository($this->repository);

        $expected = <<<EOS
 R   = @site_active @_project_members
 - refs/heads/master$ = @all
 - refs/tags/v1$ = @all

EOS;

        $this->assertEqual($config, $expected);
    }

    public function itAddEndCharacterAtPatternEndWhenRegexpAreDisabled()
    {
        stub($this->factory)->getBranchesFineGrainedPermissionsForRepository()->returns(array(1 => $this->permission_01));
        stub($this->factory)->getTagsFineGrainedPermissionsForRepository()->returns(array(2 => $this->permission_02));

        $writers          = array($this->ugroup_01, $this->ugroup_02);
        $rewinders        = array($this->ugroup_03);

        $this->permission_01->setWriters($writers);
        $this->permission_01->setRewinders($rewinders);

        $this->permission_02->setWriters($writers);
        $this->permission_02->setRewinders($rewinders);

        stub($this->retriever)->doesRepositoryUseFineGrainedPermissions($this->repository)->returns(true);
        stub($this->regexp_retriever)->areRegexpActivatedForRepository($this->repository)->returns(false);

        $config = $this->serializer->getForRepository($this->repository);


        $expected = <<<EOS
 R   = @site_active @_project_members
 RW+ refs/heads/master$ = @ug_103
 RW refs/heads/master$ = @ug_101 @ug_102
 - refs/heads/master$ = @all
 RW+ refs/tags/v1$ = @ug_103
 RW refs/tags/v1$ = @ug_101 @ug_102
 - refs/tags/v1$ = @all

EOS;

        $this->assertEqual($config, $expected);
    }

    public function itDoesntUpdatePatternWhenRegexpAreEnabled()
    {
        stub($this->factory)->getBranchesFineGrainedPermissionsForRepository()->returns(array(1 => $this->permission_01));
        stub($this->factory)->getTagsFineGrainedPermissionsForRepository()->returns(array(2 => $this->permission_02));

        $writers          = array($this->ugroup_01, $this->ugroup_02);
        $rewinders        = array($this->ugroup_03);

        $this->permission_01->setWriters($writers);
        $this->permission_01->setRewinders($rewinders);

        $this->permission_02->setWriters($writers);
        $this->permission_02->setRewinders($rewinders);

        stub($this->retriever)->doesRepositoryUseFineGrainedPermissions($this->repository)->returns(true);
        stub($this->regexp_retriever)->areRegexpActivatedForRepository()->returns(true);

        $config = $this->serializer->getForRepository($this->repository);


        $expected = <<<EOS
 R   = @site_active @_project_members
 RW+ refs/heads/master = @ug_103
 RW refs/heads/master = @ug_101 @ug_102
 - refs/heads/master = @all
 RW+ refs/tags/v1 = @ug_103
 RW refs/tags/v1 = @ug_101 @ug_102
 - refs/tags/v1 = @all

EOS;

        $this->assertEqual($config, $expected);
    }
}
