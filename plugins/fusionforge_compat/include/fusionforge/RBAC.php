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

class RBACEngine {
    function getInstance() {
        return new RBACEngine();
    }
    
    function getAvailableRolesForUser($user_obj) {
        return array();
    }
    
    public function getGlobalRoles() {
        return array();
    }
    
    public function getAvailableRoles() {
        return array(new Role());
    }
}

class Role {
    public function getName() {
        return 'forge_admin';
    }
	public function setName($name) {
            
        }
	public function getID() {
            
        }

	public function isPublic() {
            
        }
	public function setPublic($flag) {
            
        }

	public function getHomeProject() {
            return null;
        }
	public function getLinkedProjects() {
            
        }
	public function linkProject($project) {
            
        }
	public function unlinkProject($project) {
            
        }

	public function getUsers() {
            
        }
	public function hasUser($user) {
            
        }
	public function hasPermission($section, $reference, $action = NULL) {
            
        }
	public function hasGlobalPermission($section, $action = NULL) {
            return true;
        }
	public function normalizeData() {
            
        }
	public function getSettings() {
            
        }
	public function getSettingsForProject($project) {
            
        }
	public function setSettings($data) {
            
        }
}

