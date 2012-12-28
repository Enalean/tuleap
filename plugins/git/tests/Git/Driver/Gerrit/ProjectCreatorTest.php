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

require_once dirname(__FILE__).'/../../../../include/constants.php';
require_once GIT_BASE_DIR . '/Git/Driver/Gerrit/ProjectCreator.class.php';

class Git_Driver_Gerrit_ProjectCreator_BaseTest extends TuleapTestCase {

    protected $contributors      = 'tuleap-localhost-mozilla/firefox-contributors';
    protected $integrators       = 'tuleap-localhost-mozilla/firefox-integrators';
    protected $supermen          = 'tuleap-localhost-mozilla/firefox-supermen';
    protected $owners            = 'tuleap-localhost-mozilla/firefox-owners';

    protected $contributors_uuid = '8bd90045412f95ff348f41fa63606171f2328db3';
    protected $integrators_uuid  = '19b1241e78c8355c5c3d8a7e856ce3c55f555c22';
    protected $supermen_uuid     = '8a7e856ce3c55f555c228bd90045412f95ff348';
    protected $owners_uuid       = 'f9427648913e6ff14190d81b7b0abc60fa325d3a';

    /** @var Git_RemoteServer_GerritServer */
    protected $server;

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
        $this->server = partial_mock('Git_RemoteServer_GerritServer', array('getCloneSSHUrl'), 
                array($id, $host, $ssh_port, $http_port, $login, $identity_file));

        $this->gerrit_git_url = "$host/$this->gerrit_project";
        stub($this->server)->getCloneSSHUrl($this->gerrit_project)->returns($this->gerrit_git_url);

        $this->repository                      = mock('GitRepository');
        stub($this->repository)->getFullPath()->returns($this->tmpdir.'/'.$this->gitolite_project);
        $this->repository_in_a_private_project = mock('GitRepository');
        stub($this->repository_in_a_private_project)->getFullPath()->returns($this->tmpdir.'/'.$this->gitolite_project);
        $this->repository_without_registered   = mock('GitRepository');
        stub($this->repository_without_registered)->getFullPath()->returns($this->tmpdir.'/'.$this->gitolite_project);
        
        $this->driver = mock('Git_Driver_Gerrit');
        stub($this->driver)->createProject($this->server, $this->repository)->returns($this->gerrit_project);
        stub($this->driver)->createProject($this->server, $this->repository_in_a_private_project)->returns($this->gerrit_project);
        stub($this->driver)->createProject($this->server, $this->repository_without_registered)->returns($this->gerrit_project);

        stub($this->driver)->getGroupUUID($this->server, $this->contributors)->returns($this->contributors_uuid);
        stub($this->driver)->getGroupUUID($this->server, $this->integrators)->returns($this->integrators_uuid);
        stub($this->driver)->getGroupUUID($this->server, $this->supermen)->returns($this->supermen_uuid);
        stub($this->driver)->getGroupUUID($this->server, $this->owners)->returns($this->owners_uuid);

        $this->userfinder = mock('Git_Driver_Gerrit_UserFinder');
        $this->project_creator = new Git_Driver_Gerrit_ProjectCreator($this->tmpdir, $this->driver, $this->userfinder);

        $public_project  = stub('Project')->isPublic()->returns(true);
        $private_project = stub('Project')->isPublic()->returns(false);
        stub($this->repository)->getProject()->returns($public_project);
        stub($this->repository_in_a_private_project)->getProject()->returns($private_project);
        stub($this->repository_without_registered)->getProject()->returns($public_project);

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

    public function itPushesTheUpdatedConfigToTheServer() {
        $this->project_creator->createProject($this->server, $this->repository);

        $this->assertItClonesTheDistantRepo();
        $this->assertCommitterIsConfigured();
        $this->assertTheRemoteOriginIsConfigured();
        $this->assertGroupsFileHasEverything();
        $this->assertPermissionsFileHasEverything();
        $this->assertEverythingIsCommitted();
        $this->assertEverythingIsPushedToTheServer();
    }

    public function itDoesNotSetPermsOnRegisteredUsersIfProjectIsPrivate() {
        $this->project_creator->createProject($this->server, $this->repository_in_a_private_project);

        $this->assertNoPattern('/Registered Users/', file_get_contents("$this->tmpdir/project.config"));
    }

    public function itDoesNotSetPermsOnRegisteredUsersIfRepoHasNoReadForRegistered() {
        $this->project_creator->createProject($this->server, $this->repository_without_registered);

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
            "gerrit\text:ssh -p $port -i $identity_file $host_login %S  (fetch)",
            "gerrit\text:ssh -p $port -i $identity_file $host_login %S  (push)",
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

        $this->assertPattern("%$this->contributors_uuid\t$this->contributors\n%", $group_file_contents);
        $this->assertPattern("%$this->integrators_uuid\t$this->integrators\n%",   $group_file_contents);
        $this->assertPattern("%$this->supermen_uuid\t$this->supermen\n%",         $group_file_contents);
        $this->assertPattern("%$this->owners_uuid\t$this->owners\n%",             $group_file_contents);
        $this->assertPattern("%global:Registered-Users\tRegistered Users\n%",     $group_file_contents);
    }

}

class Git_Driver_Gerrit_ProjectCreator_CallsToGerritTest extends Git_Driver_Gerrit_ProjectCreator_BaseTest {

    public function itCreatesAProjectAndExportGitBranchesAndTags() {
        //ssh gerrit gerrit create tuleap.net-Firefox/all/mobile
        expect($this->driver)->createProject($this->server, $this->repository)->once();
        $project_name = $this->project_creator->createProject($this->server, $this->repository);
        $this->assertEqual($this->gerrit_project, $project_name);

        $this->assertAllGitBranchesPushedToTheServer();
        $this->assertAllGitTagsPushedToTheServer();
    }

    public function itCreatesContributorsGroup() {
        $group_name = 'contributors';
        $permissions_level = Git::PERM_READ;
        $call_order = 0;

        $this->expectGroupCreation($group_name, $permissions_level, $call_order);
    }

    //do not get members if dynamic group is registered or all users
    //what if Userfinder returns read OR write users when I ask for write

    public function itCreatesIntegratorsGroup() {
        $group_name        = 'integrators';
        $permissions_level = Git::PERM_WRITE;
        $call_order        = 1;

        $this->expectGroupCreation($group_name, $permissions_level, $call_order);
    }

    public function itCreatesSupermenGroup() {
        $group_name        = 'supermen';
        $permissions_level = Git::PERM_WPLUS;
        $call_order        = 2;

        $this->expectGroupCreation($group_name, $permissions_level, $call_order);
    }

    public function itCreatesOwnerGroup() {
        $group_name        = 'owners';
        $permissions_level = Git::SPECIAL_PERM_ADMIN;
        $call_order        = 3;

        $this->expectGroupCreation($group_name, $permissions_level, $call_order);
    }

    private function expectGroupCreation($group_name, $permissions_level, $call_order) {
        $user_list = array(aUser()->withUserName('goyotm')->build(),  aUser()->withUserName('martissonj')->build());
        stub($this->userfinder)->getUsersForPermission($permissions_level, $this->repository)->returns($user_list);

        expect($this->driver)->createGroup($this->server, $this->repository, $group_name, $user_list)->at($call_order);
        $this->driver->expectCallCount('createGroup', 4);

        $this->project_creator->createProject($this->server, $this->repository);
    }

    public function itDoesNotStopIfAGroupCannotBeCreated() {
        stub($this->driver)->createGroup()->throwsAt(1, new Exception());
        $this->driver->expectCallCount('createGroup', 4);

        $this->project_creator->createProject($this->server, $this->repository);
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
