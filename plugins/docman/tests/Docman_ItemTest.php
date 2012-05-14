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
Mock::generate('PermissionsManager');

class Docman_ItemTest extends TuleapTestCase {
    protected $permissions_manager;
    protected $docman_item;
    
    public function setUp() {
        parent::setUp();
        $this->docman_item = new Docman_Item();
        $this->permissions_manager = new MockPermissionsManager();
        PermissionsManager::setInstance($this->permissions_manager);
    }
    
    public function tearDown() {
        parent::tearDown();
        PermissionsManager::clearInstance();
    }
    
    
    public function itReturnsPermissionsThanksToPermissionsManager() {
        $item_id = 10;
        $this->docman_item->setId($item_id);
        
        $this->permissions_manager->setReturnValue('getPermissionsAndUgroupsByObjectid', array());
        $this->permissions_manager->expectOnce('getPermissionsAndUgroupsByObjectid', array($item_id, array()));
        
        $this->docman_item->getPermissions();
    }
    
    public function itReturnsPermissionsAfterReducingArrayToValues() {
        $permissions = array(
                'PLUGIN_DOCMAN_READ' =>  array(3, 102),
                'PLUGIN_DOCMAN_WRITE' =>  array(103, 2),
        );
        $this->permissions_manager->setReturnValue('getPermissionsAndUgroupsByObjectid', $permissions);
        
        $expected_permissions = array(3, 102, 103, 2);
        $permissions = $this->docman_item->getPermissions();
        $this->assertEqual($expected_permissions, $permissions);
    }
    
}

?>
