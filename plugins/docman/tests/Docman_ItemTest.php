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
        stub($this->project)->getID()->returns($this->item_id + 1000);
        PermissionsManager::setInstance($this->permissions_manager);
        ProjectManager::setInstance($this->project_manager);
    }
    
    public function tearDown() {
        parent::tearDown();
        $this->item_id++;
        PermissionsManager::clearInstance();
        ProjectManager::clearInstance();
        Docman_ItemFactory::clearInstance($this->project->getID());
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
    
    Public function itReturnsIntersectionWithParentsPermissionsIfItHasParents() {
        Docman_ItemFactory::setInstance($this->project->getID(), mock('Docman_ItemFactory'));
        $parent_id          = $this->item_id + 200;
        $parent             = new Docman_Item();
        $parent->setId($parent_id);
        $permissions_type   = 'PLUGIN_DOCMAN_%';
        $this->docman_item->setParentId($parent_id);
        
        $parent_permissions = array(
                UGroup::PROJECT_MEMBERS,
                UGroup::PROJECT_ADMIN,
                203
        );
        $child_permissions  = array(
                UGroup::REGISTERED,
                UGroup::PROJECT_MEMBERS,
                UGroup::PROJECT_ADMIN,
                103
        );
        
        stub(Docman_ItemFactory::instance($this->project->getID()))->getItemFromDb($parent_id)->returns($parent);
        stub($this->permissions_manager)->getAuthorizedUgroupIds($parent_id,     $permissions_type)->returns($parent_permissions);
        stub($this->permissions_manager)->getAuthorizedUgroupIds($this->item_id, $permissions_type)->returns($child_permissions);
        
        $expected_permissions = array_intersect(
                ExternalPermissions::getProjectObjectGroups($this->project, $this->item_id, $permissions_type),
                ExternalPermissions::getProjectObjectGroups($this->project, $parent_id,     $permissions_type)
        );
        $permissions = $this->docman_item->getPermissions();
        $this->assertEqual($expected_permissions, $permissions);
        
        
    }
    
}

?>
