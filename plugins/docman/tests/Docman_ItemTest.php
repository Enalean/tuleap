<?php
/*
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
 * 
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
require_once dirname(__FILE__).'/../include/Docman_Item.class.php';
require_once 'common/permission/PermissionsManager.class.php';
require_once 'common/project/ProjectManager.class.php';
require_once 'common/project/UGroup.class.php';

class Docman_ItemTest extends TuleapTestCase {
    protected $permissions_manager;
    protected $project_manager;
    protected $project;
    protected $docman_item;
    protected $item_id = 100;
    
    public function setUp() {
        parent::setUp();
        $this->docman_item         = new Docman_Item();
        $this->docman_item->setId($this->item_id);
        $this->permissions_manager = mock('PermissionsManager');
        $this->project_manager     = mock('ProjectManager');
        $this->project             = mock('Project');
        stub($this->project_manager)->getProject()->returns($this->project);
        stub($this->project)->getUnixName()->returns('gpig');
        PermissionsManager::setInstance($this->permissions_manager);
        ProjectManager::setInstance($this->project_manager);
    }
    
    public function tearDown() {
        parent::tearDown();
        $this->item_id++;
        PermissionsManager::clearInstance();
        ProjectManager::clearInstance();
    }
    
    
    public function itReturnsPermissionsThanksToPermissionsManager() {
        stub($this->permissions_manager)->getAuthorizedUgroupIds()->returns(array());
        $this->permissions_manager->expectOnce('getAuthorizedUgroupIds', array($this->item_id, "PLUGIN_DOCMAN_%"));
        
        $this->docman_item->getPermissions();
    }
    
    public function itReturnsPermissionsGivenByExternalPermissions_GetProjectObjectGroups() {
        $permissions = array(UGroup::REGISTERED, UGroup::PROJECT_MEMBERS, UGroup::PROJECT_ADMIN, 103);
        stub($this->permissions_manager)->getAuthorizedUgroupIds()->returns($permissions);
        
        $expected_permissions = ExternalPermissions::getProjectObjectGroups($this->project, $this->item_id, '');
        $permissions = $this->docman_item->getPermissions();
        $this->assertEqual($expected_permissions, $permissions);
    }
    
}

?>
