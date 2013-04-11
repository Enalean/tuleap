<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__).'/../../../bootstrap.php';

class Git_Driver_Gerrit_ProjectCreator_BaseTest extends TuleapTestCase {

    protected $contributors      = 'tuleap-localhost-mozilla/firefox-contributors';
    protected $integrators       = 'tuleap-localhost-mozilla/firefox-integrators';
    protected $supermen          = 'tuleap-localhost-mozilla/firefox-supermen';
    protected $owners            = 'tuleap-localhost-mozilla/firefox-owners';
    protected $replication       = 'tuleap.example.com-replication';

    protected $contributors_uuid = '8bd90045412f95ff348f41fa63606171f2328db3';
    protected $integrators_uuid  = '19b1241e78c8355c5c3d8a7e856ce3c55f555c22';
    protected $supermen_uuid     = '8a7e856ce3c55f555c228bd90045412f95ff348';
    protected $owners_uuid       = 'f9427648913e6ff14190d81b7b0abc60fa325d3a';
    protected $replication_uuid  = '2ce5c45e3b88415e51ce7e0d3a1ba0526dce6424';

    protected $project_members;
    protected $another_ugroup;
    protected $project_admins;
    protected $project_members_uuid = '8bd90045412f95ff348f41fa63606171f2328db3';
    protected $another_ugroup_uuid  = '19b1241e78c8355c5c3d8a7e856ce3c55f555c22';
    protected $project_admins_uuid  = '8a7e856ce3c55f555c228bd90045412f95ff348';
    protected $project_members_gerrit_name = 'mozilla/project_members';
    protected $another_ugroup_gerrit_name  = 'mozilla/another_ugroup';
    protected $project_admins_gerrit_name  = 'mozilla/project_admins';

    /** @var Git_RemoteServer_GerritServer */
    protected $server;

    /** @var Project */
    protected $project;
    protected $project_unix_name = 'mozilla';

    /** @var UGroupManager */
    protected $ugroup_manager;

    /** @var Git_Driver_Gerrit_MembershipManager */
    protected $membership_manager;

    protected $gerrit_project = 'tuleap-localhost-mozilla/firefox';
    protected $gerrit_git_url;
    protected $gerrit_admin_instance = 'admin-tuleap.example.com';
    protected $tuleap_instance       = 'tuleap.example.com';
    protected $gitolite_project = 'gitolite_firefox.git';

    public function setUp() {
        parent::setUp();
        Config::store();
        Config::set('sys_default_domain', $this->tuleap_instance);
        Config::set('tmp_dir', '/var/tmp');
        $this->fixtures = dirname(__FILE__) .'/_fixtures';
        $this->tmpdir   = Config::get('tmp_dir') .'/'. md5(uniqid(rand(), true));
        `unzip $this->fixtures/firefox.zip -d $this->tmpdir`;
        `tar -xzf $this->fixtures/gitolite_firefox.git.tgz --directory $this->tmpdir`;


        $host  = $this->tmpdir;
        $login = $this->gerrit_admin_instance;
        $id = $ssh_port = $http_port = $identity_file = 0;
        $replication_key = new Git_RemoteServer_Gerrit_ReplicationSSHKey();
        $this->server = partial_mock('Git_RemoteServer_GerritServer', array('getCloneSSHUrl'), 
                array($id, $host, $ssh_port, $http_port, $login, $identity_file, $replication_key));

        $this->gerrit_git_url = "$host/$this->gerrit_project";
        stub($this->server)->getCloneSSHUrl($this->gerrit_project)->returns($this->gerrit_git_url);

        $this->repository                      = mock('GitRepository');
        stub($this->repository)->getFullPath()->returns($this->tmpdir.'/'.$this->gitolite_project);
        $this->repository_in_a_private_project = mock('GitRepository');
        stub($this->repository_in_a_private_project)->getFullPath()->returns($this->tmpdir.'/'.$this->gitolite_project);
        $this->repository_without_registered   = mock('GitRepository');
        stub($this->repository_without_registered)->getFullPath()->returns($this->tmpdir.'/'.$this->gitolite_project);

        $this->driver = mock('Git_Driver_Gerrit');
        stub($this->driver)->createProject($this->server, $this->repository, $this->project_unix_name)->returns($this->gerrit_project);
        stub($this->driver)->createProject($this->server, $this->repository_in_a_private_project, $this->project_unix_name)->returns($this->gerrit_project);
        stub($this->driver)->createProject($this->server, $this->repository_without_registered, $this->project_unix_name)->returns($this->gerrit_project);
        stub($this->driver)->createParentProject($this->server, $this->repository, $this->project_admins_gerrit_name)->returns($this->project_unix_name);

        stub($this->driver)->getGroupUUID($this->server, $this->contributors)->returns($this->contributors_uuid);
        stub($this->driver)->getGroupUUID($this->server, $this->integrators)->returns($this->integrators_uuid);
        stub($this->driver)->getGroupUUID($this->server, $this->supermen)->returns($this->supermen_uuid);
        stub($this->driver)->getGroupUUID($this->server, $this->owners)->returns($this->owners_uuid);
        stub($this->driver)->getGroupUUID($this->server, $this->replication)->returns($this->replication_uuid);

        $this->userfinder = mock('Git_Driver_Gerrit_UserFinder');
        $this->ugroup_manager = mock('UGroupManager');

        $this->membership_manager = mock('Git_Driver_Gerrit_MembershipManager');

        $this->project_creator = new Git_Driver_Gerrit_ProjectCreator($this->tmpdir, $this->driver, $this->userfinder, $this->ugroup_manager, $this->membership_manager);

        $this->project = mock('Project');
        stub($this->project)->getUnixName()->returns($this->project_unix_name);
        stub($this->project)->isPublic()->returns(true);
        $private_project = stub('Project')->isPublic()->returns(false);
        stub($this->repository)->getProject()->returns($this->project);
        stub($this->repository_in_a_private_project)->getProject()->returns($private_project);
        stub($this->repository_without_registered)->getProject()->returns($this->project);

        stub($this->userfinder)->areRegisteredUsersAllowedTo(Git::PERM_READ, $this->repository)->returns(true);
        stub($this->userfinder)->areRegisteredUsersAllowedTo(Git::PERM_READ, $this->repository_in_a_private_project)->returns(true);
        stub($this->userfinder)->areRegisteredUsersAllowedTo(Git::PERM_READ, $this->repository_without_registered)->returns(false);
    }

    public function tearDown() {
        Config::restore();
        parent::tearDown();
//        is_dir("$this->tmpdir") && `rm -rf $this->tmpdir`;
        //remove the child repo
    }
}

class Git_Driver_Gerrit_ProjectCreator_InitiatePermissionsTest extends Git_Driver_Gerrit_ProjectCreator_BaseTest {

    public function setUp() {
        parent::setUp();
        $this->project_members = mock('UGroup');
        stub($this->project_members)->getNormalizedName()->returns('project_members');
        stub($this->project_members)->getId()->returns(UGroup::PROJECT_MEMBERS);

        $this->another_ugroup = mock('UGroup');
        stub($this->another_ugroup)->getNormalizedName()->returns('another_ugroup');
        stub($this->another_ugroup)->getId()->returns(120);

        $this->project_admins = mock('UGroup');
        stub($this->project_admins)->getNormalizedName()->returns('project_admins');
        stub($this->project_admins)->getId()->returns(UGroup::PROJECT_ADMIN);

        stub($this->ugroup_manager)->getUGroups()->returns(array($this->project_members, $this->another_ugroup, $this->project_admins));

        stub($this->driver)->getGroupUUID($this->server, $this->project_members_gerrit_name)->returns($this->project_members_uuid);
        stub($this->driver)->getGroupUUID($this->server, $this->another_ugroup_gerrit_name)->returns($this->another_ugroup_uuid);
        stub($this->driver)->getGroupUUID($this->server, $this->project_admins_gerrit_name)->returns($this->project_admins_uuid);

        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_READ)->returns(array(UGroup::REGISTERED));
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WRITE)->returns(array(UGroup::PROJECT_MEMBERS, 120));
        stub($this->userfinder)->getUgroups($this->repository->getId(), Git::PERM_WPLUS)->returns(array(UGroup::PROJECT_ADMIN));

        stub($this->membership_manager)->createGroupForServer()->returns('whatever');
    }

    public function tearDown() {
        parent::tearDown();
        $this->project_creator->removeTemporaryDirectory();
    }

    public function itPushesTheUpdatedConfigToTheServer() {
        $this->project_creator->createGerritProject($this->server, $this->repository);

        $this->assertItClonesTheDistantRepo();
        $this->assertCommitterIsConfigured();
        $this->assertTheRemoteOriginIsConfigured();
        $this->assertGroupsFileHasEverything();
        $this->assertPermissionsFileHasEverything();
        $this->assertEverythingIsCommitted();
        $this->assertEverythingIsPushedToTheServer();
    }

    public function itDoesNotSetPermsOnRegisteredUsersIfProjectIsPrivate() {
        $this->project_creator->createGerritProject($this->server, $this->repository_in_a_private_project);

        $this->assertNoPattern('/Registered Users/', file_get_contents("$this->tmpdir/project.config"));
    }

    public function itDoesNotSetPermsOnRegisteredUsersIfRepoHasNoReadForRegistered() {
        $this->project_creator->createGerritProject($this->server, $this->repository_without_registered);

        $this->assertNoPattern('/Registered Users/', file_get_contents("$this->tmpdir/project.config"));
    }

    private function assertItClonesTheDistantRepo() {
        $groups_file = "$this->tmpdir/groups";
        $config_file = "$this->tmpdir/project.config";
        $this->assertTrue(is_file($groups_file));
        $this->assertTrue(is_file($config_file));
    }

    private function assertCommitterIsConfigured() {
        $this->assertEqual(trim(`cd $this->tmpdir; git config --get user.name`), $this->gerrit_admin_instance);
        $this->assertEqual(trim(`cd $this->tmpdir; git config --get user.email`), 'codendiadm@'. $this->tuleap_instance);
    }

    private function assertTheRemoteOriginIsConfigured() {
        $cwd = getcwd();
        chdir("$this->tmpdir");
        exec('git remote -v', $output, $ret_val);
        chdir($cwd);
        
        $port          = $this->server->getSSHPort();
        $identity_file = $this->server->getIdentityFile();
        $host_login    = $this->server->getLogin() . '@' . $this->server->getHost();
        
        $this->assertEqual($output, array(
//            "gerrit\text:ssh -p $port -i $identity_file $host_login %S  (fetch)",
//            "gerrit\text:ssh -p $port -i $identity_file $host_login %S  (push)",
            "origin\t$this->gerrit_git_url (fetch)",
            "origin\t$this->gerrit_git_url (push)",
            )
        );
        $this->assertEqual($ret_val, 0);
    }

    private function assertEverythingIsPushedToTheServer() {
        $cwd = getcwd();
        chdir("$this->tmpdir");
        exec('git push origin HEAD:refs/meta/config --porcelain', $output, $ret_val);
        chdir($cwd);
        $this->assertEqual($output, array(
            "To $this->gerrit_git_url",
            "=\tHEAD:refs/meta/config\t[up to date]",
            "Done")
        );
        $this->assertEqual($ret_val, 0);
    }

    private function assertEverythingIsCommitted() {
        $cwd = getcwd();
        chdir("$this->tmpdir");
        exec('git status --porcelain', $output, $ret_val);
        chdir($cwd);
        $this->assertEqual($output, array(
            '?? gitolite_firefox.git/',
            '?? tuleap-localhost-mozilla/')
        );
        $this->assertEqual($ret_val, 0);
    }

    private function assertPermissionsFileHasEverything() {
        $config_file_contents = file_get_contents("$this->tmpdir/project.config");
        $expected_contents    = file_get_contents("$this->fixtures/expected_access_rights.config"); // TODO: To be completed

        $this->assertEqual($config_file_contents, $expected_contents);
    }

    private function assertGroupsFileHasEverything() {
        $groups_file = "$this->tmpdir/groups";
        $group_file_contents = file_get_contents($groups_file);

        $this->assertPattern("%$this->project_members_uuid\t$this->project_members_gerrit_name\n%", $group_file_contents);
        $this->assertPattern("%$this->another_ugroup_uuid\t$this->another_ugroup_gerrit_name\n%",   $group_file_contents);
        $this->assertPattern("%$this->replication_uuid\t$this->replication\n%",             $group_file_contents);
        $this->assertPattern("%global:Registered-Users\tRegistered Users\n%",     $group_file_contents);
    }

}

class Git_Driver_Gerrit_ProjectCreator_CallsToGerritTest extends Git_Driver_Gerrit_ProjectCreator_BaseTest {

    public function setUp() {
        parent::setUp();
        stub($this->userfinder)->getUgroups()->returns(array());
    }

    public function tearDown() {
        parent::tearDown();
        $this->project_creator->removeTemporaryDirectory();
    }

    public function itCreatesAProjectAndExportGitBranchesAndTagsWithoutCreateParentProject() {
        //ssh gerrit gerrit create tuleap.net-Firefox/all/mobile

        $this->project_admins = mock('UGroup');
        stub($this->project_admins)->getNormalizedName()->returns('project_admins');
        stub($this->project_admins)->getId()->returns(UGroup::PROJECT_ADMIN);

        stub($this->ugroup_manager)->getUGroups()->returns(array($this->project_admins));
        stub($this->driver)->DoesTheParentProjectExist()->returns(true);

        expect($this->driver)->DoesTheParentProjectExist($this->server, $this->repository->getProject()->getUnixName())->once();
        expect($this->driver)->createProject($this->server, $this->repository, $this->project_unix_name)->once();
        expect($this->driver)->createParentProject($this->server, $this->repository, $this->project_admins_gerrit_name)->never();

        $project_name = $this->project_creator->createGerritProject($this->server, $this->repository);
        $this->assertEqual($this->gerrit_project, $project_name);

        $this->assertAllGitBranchesPushedToTheServer();
        $this->assertAllGitTagsPushedToTheServer();
    }

    public function itCreatesAProjectAndExportGitBranchesAndTagsAndCreateParentProject() {
        //ssh gerrit gerrit create tuleap.net-Firefox/all/mobile

        $this->project_admins = mock('UGroup');
        stub($this->project_admins)->getNormalizedName()->returns('project_admins');
        stub($this->project_admins)->getId()->returns(UGroup::PROJECT_ADMIN);

        stub($this->ugroup_manager)->getUGroups()->returns(array($this->project_admins));
        stub($this->driver)->DoesTheParentProjectExist()->returns(false);

        expect($this->driver)->DoesTheParentProjectExist($this->server, $this->repository->getProject()->getUnixName())->once();
        expect($this->driver)->createParentProject($this->server, $this->repository, $this->project_admins_gerrit_name)->once();
        expect($this->driver)->createProject($this->server, $this->repository, $this->project_unix_name)->once();

        $project_name = $this->project_creator->createGerritProject($this->server, $this->repository);
        $this->assertEqual($this->gerrit_project, $project_name);

        $this->assertAllGitBranchesPushedToTheServer();
        $this->assertAllGitTagsPushedToTheServer();
    }

    public function itCreatesProjectMembersGroup() {
        $user_list = array(aUser()->withUserName('goyotm')->build(),  aUser()->withUserName('martissonj')->build());
        $ugroup = mock('UGroup');
        stub($ugroup)->getLdapMembersIds()->returns($user_list);
        stub($ugroup)->getNormalizedName()->returns('project_members');
        stub($ugroup)->getId()->returns(Ugroup::PROJECT_MEMBERS);

        expect($this->ugroup_manager)->getUGroups($this->project)->once();
        stub($this->ugroup_manager)->getUGroups()->returns(array($ugroup));

        expect($this->membership_manager)->createGroupForServer($this->server, $ugroup)->once();
        $this->project_creator->createGerritProject($this->server, $this->repository);
    }

    public function itCreatesAllGroups() {

        $user1 = aUser()->withUserName('goyotm')->withLdapId('goyotm')->build();
        $user2 = aUser()->withUserName('martissonj')->withLdapId('martissonj')->build();

        $project_members_list = array($user1->getLdapId(), $user2->getLdapId());
        $ugroup_project_members = mock('UGroup');
        stub($ugroup_project_members)->getLdapMembersIds()->returns($project_members_list);
        stub($ugroup_project_members)->getNormalizedName()->returns('project_members');
        stub($ugroup_project_members)->getId()->returns(UGroup::PROJECT_MEMBERS);

        $another_group_list = array($user1->getLdapId());
        $ugroup_another_group = mock('UGroup');
        stub($ugroup_another_group)->getLdapMembersIds()->returns($another_group_list);
        stub($ugroup_another_group)->getNormalizedName()->returns('another_group');
        stub($ugroup_another_group)->getId()->returns(120);

        stub($this->ugroup_manager)->getUGroups()->returns(array($ugroup_project_members, $ugroup_another_group));

        expect($this->membership_manager)->createGroupForServer()->count(2);
        expect($this->membership_manager)->createGroupForServer($this->server, $ugroup_project_members)->at(0);
        expect($this->membership_manager)->createGroupForServer($this->server, $ugroup_another_group)->at(1);

        $this->project_creator->createGerritProject($this->server, $this->repository);
    }

    private function assertAllGitBranchesPushedToTheServer() {
        $cwd = getcwd();
        chdir("$this->tmpdir/$this->gitolite_project");

        exec("git show-ref --heads", $refs_cmd, $ret_val);

        $expected_result = array("To $this->gerrit_git_url");

        foreach ($refs_cmd as $ref) {
            $ref               = substr($ref, strpos($ref, ' ') + 1);
            $expected_result[] = "=\t$ref:$ref\t[up to date]";
        }

        $expected_result[] = "Done";

        exec("git push $this->gerrit_git_url refs/heads/*:refs/heads/* --porcelain", $output, $ret_val);
        chdir($cwd);

        $this->assertEqual($output, $expected_result);
        $this->assertEqual($ret_val, 0);
    }

    private function assertAllGitTagsPushedToTheServer() {
        $cwd = getcwd();
        chdir("$this->tmpdir/$this->gitolite_project");

        exec("git show-ref --tags", $refs_cmd, $ret_val);
        $expected_result = array("To $this->gerrit_git_url");

        foreach ($refs_cmd as $ref) {
            $ref               = substr($ref, strpos($ref, ' ') + 1);
            $expected_result[] = "=\t$ref:$ref\t[up to date]";
        }

        $expected_result[] = "Done";

        exec("git push $this->gerrit_git_url refs/tags/*:refs/tags/* --porcelain", $output, $ret_val);
        chdir($cwd);

        $this->assertEqual($output, $expected_result);
        $this->assertEqual($ret_val, 0);
    }
}
?>
