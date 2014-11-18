<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
            'whatever'
        );
    }

    public function tearDown() {
        PermissionsManager::clearInstance();
        parent::tearDown();
    }

    public function itReturnsEmptyStringForUnknownType() {
        $this->permissions_manager->setReturnValue('getAuthorizedUgroupIds', array());
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, '__none__');
        $this->assertIdentical('', $result);
    }

    public function itReturnsEmptyStringForAUserIdLowerOrEqualThan_100() {
        $this->permissions_manager->setReturnValue('getAuthorizedUgroupIds', array(100));
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $this->assertIdentical('', $result);
    }

    public function itReturnsStringWithUserIdIfIdGreaterThan_100() {
        $this->permissions_manager->setReturnValue('getAuthorizedUgroupIds', array(101));
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $this->assertPattern('/=\s@ug_101$/', $result);
    }

    public function itReturnsSiteActiveIfUserGroupIsRegistered() {
        $this->permissions_manager->setReturnValue('getAuthorizedUgroupIds', array(ProjectUGroup::REGISTERED));
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $this->assertPattern('/=\s@site_active @'. $this->project->getUnixName() .'_project_members$/', $result);
    }

    public function itReturnsProjectNameWithProjectMemberIfUserIsProjectMember() {
        $this->permissions_manager->setReturnValue('getAuthorizedUgroupIds', array(ProjectUGroup::PROJECT_MEMBERS));
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $project_name = 'project' . $this->project_id;
        $this->assertPattern('/=\s@'.$project_name.'_project_members$/', $result);
    }

    public function itReturnsProjectNameWithProjectAdminIfUserIsProjectAdmin() {
        $this->permissions_manager->setReturnValue('getAuthorizedUgroupIds', array(ProjectUGroup::PROJECT_ADMIN));
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $project_name = 'project' . $this->project_id;
        $this->assertPattern('/=\s@'.$project_name.'_project_admin$/', $result);
    }

    public function itPrefixesWithRForReaders() {
        $this->permissions_manager->setReturnValue('getAuthorizedUgroupIds', array(101));
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $this->assertPattern('/^\sR\s\s\s=/', $result);
    }

    public function itPrefixesWithRWForWriters() {
        $this->permissions_manager->setReturnValue('getAuthorizedUgroupIds', array(101));
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_WRITE);
        $this->assertPattern('/^\sRW\s\s=/', $result);
    }

    public function itPrefixesWithRWPlusForWritersPlus() {
        $this->permissions_manager->setReturnValue('getAuthorizedUgroupIds', array(101));
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_WPLUS);
        $this->assertPattern('/^\sRW\+\s=/', $result);
    }

    public function itReturnsAllGroupsSeparatedBySpaceIfItHasDifferentGroups() {
        $this->permissions_manager->setReturnValue('getAuthorizedUgroupIds', array(666, ProjectUGroup::REGISTERED));
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $this->assertIdentical(' R   = @ug_666 @site_active @'. $this->project->getUnixName() .'_project_members' . PHP_EOL, $result);
    }

    public function itReturnsAllGroupsSeparatedBySpaceIfItHasDifferentGroupsAndAddCodendiadmIfOnlineEditIsEnable() {
        $this->permissions_manager->setReturnValue('getAuthorizedUgroupIds', array(666, ProjectUGroup::REGISTERED));
        $this->repository->setReturnValue('hasOnlineEditEnabled', true);
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $this->assertIdentical(' R   = @ug_666 @site_active @'. $this->project->getUnixName() .'_project_members' . ' id_rsa_gl-adm' . PHP_EOL, $result);
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
        $this->serializer    = new Git_Gitolite_ConfigPermissionsSerializer($this->mirror_mapper, 'whatever');
        $this->project       = stub('Project')->getUnixName()->returns('foo');
        $this->repository    = aGitRepository()->withId(115)->withProject($this->project)->build();

        $user_mirror1        = aUser()->withUserName('git_mirror_1')->build();
        $this->mirror_1      = new Git_Mirror_Mirror($user_mirror1, 1, 'url', 'EUR');
        $user_mirror2        = aUser()->withUserName('git_mirror_2')->build();
        $this->mirror_2      = new Git_Mirror_Mirror($user_mirror2, 2, 'url', 'IND');

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
        stub($this->permissions_manager)->getAuthorizedUgroupIds()->returns(array(ProjectUGroup::REGISTERED));

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
        stub($this->permissions_manager)->getAuthorizedUgroupIds()->returns(array(ProjectUGroup::REGISTERED));

        $result = $this->serializer->getForRepository($this->repository);
        $this->assertPattern('/^ R   = git_mirror_1 git_mirror_2$/m', $result);
    }

    public function itHasNoMirrors() {
        stub($this->mirror_mapper)->fetchAllRepositoryMirrors($this->repository)->returns(
            array()
        );
        stub($this->permissions_manager)->getAuthorizedUgroupIds()->returns(array());

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
        $this->mirror_1      = new Git_Mirror_Mirror($user_mirror1, 1, 'url', 'CHN');
        $user_mirror2        = aUser()->withUserName('forge__gitmirror_2')->build();
        $this->mirror_2      = new Git_Mirror_Mirror($user_mirror2, 2, 'url', 'JPN');
    }

    public function itDumpsTheConf() {
        stub($this->mirror_mapper)->fetchAll()->returns(array());
        $serializer = new Git_Gitolite_ConfigPermissionsSerializer($this->mirror_mapper, 'whatever');
        $this->assertEqual(
            file_get_contents(dirname(__FILE__).'/_fixtures/default_gitolite.conf'),
            $serializer->getGitoliteDotConf(array('projecta', 'projectb'))
        );
    }

    public function itAllowsOverrideBySiteAdmin() {
        stub($this->mirror_mapper)->fetchAll()->returns(array());
        $serializer = new Git_Gitolite_ConfigPermissionsSerializer($this->mirror_mapper, dirname(__FILE__).'/_fixtures/etc_templates');
        $this->assertEqual(
            file_get_contents(dirname(__FILE__).'/_fixtures/override_gitolite.conf'),
            $serializer->getGitoliteDotConf(array('projecta', 'projectb'))
        );
    }

    public function __only__itGrantsReadAccessToGitoliteAdminForMirrorUsers() {
        stub($this->mirror_mapper)->fetchAll()->returns(array($this->mirror_1, $this->mirror_2));
        $serializer = new Git_Gitolite_ConfigPermissionsSerializer($this->mirror_mapper, 'whatever');
        $this->assertEqual(
            file_get_contents(dirname(__FILE__).'/_fixtures/mirrors_gitolite.conf'),
            $serializer->getGitoliteDotConf(array('projecta', 'projectb'))
        );
    }
}
