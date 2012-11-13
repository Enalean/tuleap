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

class IntitiatePermissionsIntegrationTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        Config::store();
        Config::set('tmp_dir', '/var/tmp');
        $this->current_dir = dirname(__FILE__);
        $this->dir = Config::get('tmp_dir');
        $this->tmpdir = uniqid("$this->dir/");
        `unzip $this->current_dir/firefox.zip -d $this->tmpdir`;
        
    }
    
    public function tearDown() {
        Config::restore();
        parent::tearDown();
//        is_dir("$this->tmpdir") && `rm -rf $this->tmpdir`;
        
        //remove the child repo
        
    }
    
    public function itClonesTheDistantRepo() {

        $initiator = new Git_Gerrit_Driver_ProjectCreator($this->tmpdir, mock('Git_Driver_Gerrit'));
        $initiator->cloneGerritProjectConfig("$this->tmpdir/firefox.git");

        $groups_file = "$this->tmpdir/firefox/groups";
        $config_file = "$this->tmpdir/firefox/project.config";
        $this->assertTrue(is_file($groups_file));
        $this->assertTrue(is_file($config_file));
    }
    public function itPushesTheUpdatedConfigToTheServer() {
        $remote_ssh = mock('Git_Driver_Gerrit_RemoteSSHCommand');
        $remote_ssh->setReturnValueAt(3, 'execute', $this->contributors_uuid);
        $remote_ssh->setReturnValueAt(4, 'execute', $this->integrators_uuid);
        $remote_ssh->setReturnValueAt(3, 'execute', $this->supermen_uuid);
        $driver = new Git_Driver_Gerrit($remote_ssh, mock('Logger'));
        
        $initiator = new Git_Gerrit_Driver_ProjectCreator($this->tmpdir, $driver);
        $initiator->cloneGerritProjectConfig();

        $initiator->initiatePermissison("$this->tmpdir/firefox.git");
        
        $this->assertGroupsFileHasEverything();
        $this->assertPermissionsFileHasEverything();
        
        $this->assertEverythingIsPushedToTheServer();
    }
    
    
    private function assertEverythingIsPushedToTheServer() {
        $cwd = getcwd();
        chdir("$this->tmpdir/firefox");
        exec('git status --porcelain', $output, $ret_val);
        chdir($cwd);
        $this->assertEqual($output, array());
        $this->assertEqual($ret_val, 0);
    }
    
    private function assertPermissionsFileHasEverything() {
        $config_file_contents = file_get_contents("$this->tmpdir/firefox/project.config");
        $expected_contents    = <<<EOF
[access]
	inheritFrom = tuleap-localhost-AlmAcl
[project]
	state = active
[access "refs/heads/*"]
	Read = group Registered Users
	Read = group tuleap-localhost-mozilla/firefox-contributor
	create = group tuleap-localhost-mozilla/firefox-integrators
	Read = group Registered Users
	Read = group tuleap-localhost-mozilla/firefox-contributor
	create = group tuleap-localhost-mozilla/firefox-integrators   
EOF;
        $this->assertEqual($config_file_contents, $expected_contents);
        
    }
    
    
    
    private function assertGroupsFileHasEverything() {
        $groups_file = "$this->tmpdir/firefox/groups";
        $group_file_contents = file_get_contents($groups_file);

        $this->contributors_uuid = '8bd90045412f95ff348f41fa63606171f2328db3';
        $this->contributors_name = 'tuleap-localhost-mozilla/firefox-contributor';
        $this->assertPattern("%$this->contributors_uuid\t$this->contributors_name%", $group_file_contents);

        $this->integrators_uuid = '19b1241e78c8355c5c3d8a7e856ce3c55f555c22';
        $this->integrators_name = 'tuleap-localhost-mozilla/firefox-integrators';
        $this->assertPattern("%$this->integrators_uuid\t$this->integrators_name%", $group_file_contents);
        
        $this->supermen_uuid = '8a7e856ce3c55f555c228bd90045412f95ff348';
        $this->supermen_name = 'tuleap-localhost-mozilla/firefox-supermen';
        $this->assertPattern("%$this->supermen_uuid\t$this->supermen_name%", $group_file_contents);

    }
    
}
?>
