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

        $initiator = new InitiatePermissions();
        $initiator->cloneGerritProjectConfig($this->tmpdir);

        $groups_file = "$this->tmpdir/firefox/groups";
        $config_file = "$this->tmpdir/firefox/project.config";
        $this->assertTrue(is_file($groups_file));
        $this->assertTrue(is_file($config_file));
    }
    public function itPushesTheUpdatedConfigToTheServer() {
        $initiator = new InitiatePermissions();
        $initiator->cloneGerritProjectConfig($this->tmpdir);

        $initiator->initiatePermissison();
        
        $this->assertGroupsFileHasEverything();
        $this->assertPermissionsFileHasEverything();
        
        $this->assertEverythingIsPushedToTheServer();
    }
    
    private function assertEverythingIsPushedToTheServer() {
        
    }
    
    private function assertPermissionsFileHasEverything() {
        $config_file = "$this->tmpdir/firefox/project.config";
        
        
    }
    
    private function assertGroupsFileHasEverything() {
        $groups_file = "$this->tmpdir/firefox/groups";
        $group_file_contents = file_get_contents($groups_file);

        $contributors_uuid = '8bd90045412f95ff348f41fa63606171f2328db3';
        $contributors_name = 'tuleap-localhost-mozilla/firefox-contributor';
        $this->assertPattern("%$contributors_uuid\t$contributors_name%", $group_file_contents);

        $integrators_uuid = '19b1241e78c8355c5c3d8a7e856ce3c55f555c22';
        $integrators_name = 'tuleap-localhost-mozilla/firefox-integrators';
        $this->assertPattern("%$integrators_uuid\t$integrators_name%", $group_file_contents);
        
        $supermen_uuid = '8a7e856ce3c55f555c228bd90045412f95ff348';
        $supermen_name = 'tuleap-localhost-mozilla/firefox-supermen';
        $this->assertPattern("%$supermen_uuid\t$supermen_name%", $group_file_contents);

    }
    
}

class InitiatePermissions {

    public function cloneGerritProjectConfig($dir) {
        `mkdir $dir/firefox; cd $dir/firefox`;
        `cd $dir/firefox; git init`;
        `cd $dir/firefox; git pull $dir/firefox.git refs/meta/config`;
        `cd $dir/firefox; git checkout FETCH_HEAD`;
    }

    public function initiatePermissison() {
        
    }
}
?>
