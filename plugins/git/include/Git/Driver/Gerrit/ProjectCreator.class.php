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

require_once GIT_BASE_DIR . '/Git/Driver/Gerrit/RemoteSSHCommand.class.php';
require_once GIT_BASE_DIR . '/Git/Driver/Gerrit.class.php';

class Git_Gerrit_Driver_ProjectCreator {
    
    /** @var Git_Gerrit_Driver_Gerrit */
    private $driver;
    
    private $working_dir;
    
    public function __construct($dir, Git_Gerrit_Driver_Gerrit $driver) {
        $this->driver       = $driver;
        $this->working_dir  = $dir;
    }
    
    public function cloneGerritProjectConfig($gerrit_project_url) {
        $dir = $this->working_dir;
        `mkdir $dir/firefox; cd $dir/firefox`;
        `cd $dir/firefox; git init`;
        `cd $dir/firefox; git pull $gerrit_project_url refs/meta/config`;
        `cd $dir/firefox; git checkout FETCH_HEAD`;
    }

    public function initiatePermissison($gerrit_project_url) {
        $this->cloneGerritProjectConfig($gerrit_project_url);
        $this->addGroupsToGroupFile();
        $this->addPermissionsToProjectConf();
        $this->pushToServer();
        
    }
    
    private function addGroupsToGroupFile() {
        $groups = array('contributors', 'integrators', 'supermen');
        foreach ($groups as $group) {
//            $group_uuid = $this->driver-$dir/firefox.git>getGroupId()
        }
        
    }
    private function addPermissionsToProjectConf() {
        
    }
    private function pushToServer() {
        
    }
}

?>
