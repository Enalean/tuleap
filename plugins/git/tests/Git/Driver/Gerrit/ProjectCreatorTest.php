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

    protected $contributors_uuid = '8bd90045412f95ff348f41fa63606171f2328db3';
    protected $integrators_uuid  = '19b1241e78c8355c5c3d8a7e856ce3c55f555c22';
    protected $supermen_uuid     = '8a7e856ce3c55f555c228bd90045412f95ff348';

    /** @var Git_RemoteServer_GerritServer */
    protected $server;

    public function setUp() {
        parent::setUp();
        Config::store();
        Config::set('tmp_dir', '/var/tmp');
        $this->fixtures = dirname(__FILE__) .'/_fixtures';
        $this->tmpdir   = Config::get('tmp_dir') .'/'. md5(uniqid(rand(), true));
        `unzip $this->fixtures/firefox.zip -d $this->tmpdir`;

        $host = $this->tmpdir;
        $id = $port = $login = $identity_file = 0;
        $this->server = partial_mock('Git_RemoteServer_GerritServer', array('getCloneSSHUrl'), array($id, $host, $port, $login, $identity_file));
        stub($this->server)->getCloneSSHUrl()->returns($host);

        $this->repository = mock('GitRepository');
        $this->driver = mock('Git_Driver_Gerrit');
        stub($this->driver)->createProject($this->server, $this->repository)->returns('tuleap-localhost-mozilla/firefox');

        stub($this->driver)->getGroupUUID($this->server, $this->contributors)->returns($this->contributors_uuid);
        stub($this->driver)->getGroupUUID($this->server, $this->integrators)->returns($this->integrators_uuid);
        stub($this->driver)->getGroupUUID($this->server, $this->supermen)->returns($this->supermen_uuid);

        $this->userfinder = mock('Git_Driver_Gerrit_UserFinder');
        $this->project_creator = new Git_Driver_Gerrit_ProjectCreator($this->tmpdir, $this->driver, $this->userfinder);

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
        $this->assertGroupsFileHasEverything();
        $this->assertPermissionsFileHasEverything();
        $this->assertEverythingIsPushedToTheServer();
    }

    private function assertItClonesTheDistantRepo() {
        $groups_file = "$this->tmpdir/groups";
        $config_file = "$this->tmpdir/project.config";
        $this->assertTrue(is_file($groups_file));
        $this->assertTrue(is_file($config_file));
    }

    private function assertEverythingIsPushedToTheServer() {
        $cwd = getcwd();
        chdir("$this->tmpdir");
        exec('git status --porcelain', $output, $ret_val);
        chdir($cwd);
        $this->assertEqual($output, array('?? tuleap-localhost-mozilla/'));
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

        $this->assertPattern("%$this->contributors_uuid\t$this->contributors%", $group_file_contents);
        $this->assertPattern("%$this->integrators_uuid\t$this->integrators%",   $group_file_contents);
        $this->assertPattern("%$this->supermen_uuid\t$this->supermen%",         $group_file_contents);
    }

    private function itThrowsAnExceptionWhenSomethingGoneBad() {
        // remote git repo doesn't exist
        // add permissions doesn't work
        // cannot commit
        // ...
    }

    private function itLogsEachMethodsCall() {
    }
}

class Git_Driver_Gerrit_ProjectCreator_CallsToGerritTest extends Git_Driver_Gerrit_ProjectCreator_BaseTest {

    public function itCreatesAProject() {
        //ssh gerrit gerrit create tuleap.net-Firefox/all/mobile
        expect($this->driver)->createProject($this->server, $this->repository)->once()->returns('tuleap-localhost-mozilla/firefox');
        $project_name = $this->project_creator->createProject($this->server, $this->repository);
        $this->assertEqual('tuleap-localhost-mozilla/firefox', $project_name);
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

    public function expectGroupCreation($group_name, $permissions_level, $call_order) {
        $user_list = array(aUser()->withUserName('goyotm')->build(),  aUser()->withUserName('martissonj')->build());
        stub($this->userfinder)->getUsersForPermission($permissions_level, $this->repository)->returns($user_list);

        expect($this->driver)->createGroup($this->server, $this->repository, $group_name, $user_list)->at($call_order);
        $this->driver->expectCallCount('createGroup', 3);

        $this->project_creator->createProject($this->server, $this->repository);
    }
}
?>
