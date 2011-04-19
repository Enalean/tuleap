<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2011. All rights reserved
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
require_once('www/project/admin/ugroup_utils.php');

Mock::generate('UserManager');
Mock::generate('User');

require_once(dirname(__FILE__) .'/../../../../include/simpletest/mock_functions.php');

class UgroupUtilsTest extends UnitTestCase {

    function setUp() {
        MockFunction::generate('db_query');
        MockFunction::generate('db_fetch_array');
        MockFunction::generate('ugroup_get_user_manager');
    }

    function tearDown() {
        MockFunction::restore('db_query');
        MockFunction::restore('db_fetch_array');
        MockFunction::restore('ugroup_get_user_manager');
    }

    function testUgroupContainProjectAdminsNoUsers() {
        MockFunction::setReturnValue('db_fetch_array', null);
        MockFunction::expectOnce('db_fetch_array');
        $this->assertFalse(ugroup_contain_project_admins(1, '', $containNonAdmin));
        $this->assertFalse($containNonAdmin);
    }

    function testUgroupContainProjectAdminsOnlyAdmins() {
        MockFunction::setReturnValueAt(0 ,'db_fetch_array', array('user_id' => 1));
        MockFunction::setReturnValueAt(1 ,'db_fetch_array', array('user_id' => 2));
        MockFunction::setReturnValueAt(2 ,'db_fetch_array', null);
        MockFunction::expectCallCount('db_fetch_array', 3);
        $user = new MockUser();
        $user->setReturnValue('isMember', true);
        $user->expectCallCount('isMeMber', 2);
        $um = new MockUserManager();
        $um->setReturnValue('getUserById', $user);
        MockFunction::setReturnValue('ugroup_get_user_manager', $um);
        $this->assertTrue(ugroup_contain_project_admins(1, '', $containNonAdmin));
        $this->assertFalse($containNonAdmin);
    }

    function testUgroupContainProjectAdminsOnlyNonAdmins() {
        MockFunction::setReturnValueAt(0 ,'db_fetch_array', array('user_id' => 1));
        MockFunction::setReturnValueAt(1 ,'db_fetch_array', array('user_id' => 2));
        MockFunction::setReturnValueAt(2 ,'db_fetch_array', null);
        MockFunction::expectCallCount('db_fetch_array', 3);
        $user = new MockUser();
        $user->setReturnValue('isMember', false);
        $user->expectCallCount('isMeMber', 2);
        $um = new MockUserManager();
        $um->setReturnValue('getUserById', $user);
        MockFunction::setReturnValue('ugroup_get_user_manager', $um);
        $this->assertFalse(ugroup_contain_project_admins(1, '', $containNonAdmin));
        $this->assertTrue($containNonAdmin);
    }

    function testUgroupContainProjectAdminsMixed() {
        MockFunction::setReturnValueAt(0 ,'db_fetch_array', array('user_id' => 1));
        MockFunction::setReturnValueAt(1 ,'db_fetch_array', array('user_id' => 2));
        MockFunction::setReturnValueAt(2 ,'db_fetch_array', null);
        MockFunction::expectCallCount('db_fetch_array', 3);
        $user = new MockUser();
        $user->setReturnValueAt(0, 'isMember', true);
        $user->setReturnValueAt(1, 'isMember', false);
        $user->expectCallCount('isMeMber', 2);
        $um = new MockUserManager();
        $um->setReturnValue('getUserById', $user);
        MockFunction::setReturnValue('ugroup_get_user_manager', $um);
        $this->assertTrue(ugroup_contain_project_admins(1, '', $containNonAdmin));
        $this->assertTrue($containNonAdmin);
    }

    function testUgroupValidateStaticAdminUgroupsNoGroups() {
        MockFunction::setReturnValue('db_fetch_array', null);
        MockFunction::expectOnce('db_fetch_array');
        MockFunction::expectNever('ugroup_contain_project_admins');
        $validUGroups = array(1, 2);
        $this->assertFalse(ugroup_validate_static_admin_ugroups(1, array(3, 4), $validUGroups));
        $this->assertEqual(array(1, 2), $validUGroups);
    }

    function testUgroupValidateStaticAdminUgroupsNotProjectGroups() {
        MockFunction::setReturnValueAt(0 ,'db_fetch_array', array('ugroup_id' => 3));
        MockFunction::setReturnValueAt(1 ,'db_fetch_array', array('ugroup_id' => 4));
        MockFunction::setReturnValueAt(2 ,'db_fetch_array', null);
        MockFunction::expectCallCount('db_fetch_array', 3);
        MockFunction::expectCallCount('ugroup_contain_project_admins', 2);
        $validUGroups = array(1, 2);
        $this->assertFalse(ugroup_validate_static_admin_ugroups(1, array(5, 6), $validUGroups));
        $this->assertEqual(array(1, 2), $validUGroups);
    }

    function testUgroupValidateStaticAdminUgroupsContainAdmins() {
        MockFunction::setReturnValueAt(0 ,'db_fetch_array', array('ugroup_id' => 3));
        MockFunction::setReturnValueAt(1 ,'db_fetch_array', array('ugroup_id' => 4));
        MockFunction::setReturnValueAt(2 ,'db_fetch_array', null);
        MockFunction::expectCallCount('db_fetch_array', 3);
        MockFunction::generate('ugroup_contain_project_admins');
        MockFunction::setReturnValue('ugroup_contain_project_admins', true);
        MockFunction::expectCallCount('ugroup_contain_project_admins', 2);
        $validUGroups = array(1, 2);
        $this->assertFalse(ugroup_validate_static_admin_ugroups(1, array(3, 4), $validUGroups));
        $this->assertEqual(array(1, 2, 3, 4), $validUGroups);
        MockFunction::restore('ugroup_contain_project_admins');
    }

    function testUgroupValidateStaticAdminUgroupsContainNoAdmins() {
        MockFunction::setReturnValueAt(0 ,'db_fetch_array', array('ugroup_id' => 3));
        MockFunction::setReturnValueAt(1 ,'db_fetch_array', array('ugroup_id' => 4));
        MockFunction::setReturnValueAt(2 ,'db_fetch_array', null);
        MockFunction::expectCallCount('db_fetch_array', 3);
        MockFunction::generate('ugroup_contain_project_admins');
        MockFunction::setReturnValue('ugroup_contain_project_admins', false);
        MockFunction::expectCallCount('ugroup_contain_project_admins', 2);
        $validUGroups = array(1, 2);
        $this->assertFalse(ugroup_validate_static_admin_ugroups(1, array(3, 4), $validUGroups));
        $this->assertEqual(array(1, 2), $validUGroups);
        MockFunction::restore('ugroup_contain_project_admins');
    }

    function testUgroupValidateStaticAdminUgroupsMixed() {
        MockFunction::setReturnValueAt(0 ,'db_fetch_array', array('ugroup_id' => 3));
        MockFunction::setReturnValueAt(1 ,'db_fetch_array', array('ugroup_id' => 4));
        MockFunction::setReturnValueAt(2 ,'db_fetch_array', null);
        MockFunction::expectCallCount('db_fetch_array', 3);
        MockFunction::generate('ugroup_contain_project_admins');
        MockFunction::setReturnValueAt(0, 'ugroup_contain_project_admins', false);
        MockFunction::setReturnValueAt(1, 'ugroup_contain_project_admins', true);
        MockFunction::expectCallCount('ugroup_contain_project_admins', 2);
        $validUGroups = array(1, 2);
        $this->assertFalse(ugroup_validate_static_admin_ugroups(1, array(3, 4), $validUGroups));
        $this->assertEqual(array(1, 2, 4), $validUGroups);
        MockFunction::restore('ugroup_contain_project_admins');
    }

    function testUgroupValidateDynamicAdminUgroupsContainAdmins() {
        MockFunction::generate('ugroup_contain_project_admins');
        MockFunction::setReturnValue('ugroup_contain_project_admins', true);
        MockFunction::expectCallCount('ugroup_contain_project_admins', 2);
        $validUGroups = array(1, 2);
        $this->assertFalse(ugroup_validate_dynamic_admin_ugroups(1, array(3, 4), $validUGroups));
        $this->assertEqual(array(1, 2, 3, 4), $validUGroups);
        MockFunction::restore('ugroup_contain_project_admins');
    }

    function testUgroupValidateDynamicAdminUgroupsContainNoAdmins() {
        MockFunction::generate('ugroup_contain_project_admins');
        MockFunction::setReturnValue('ugroup_contain_project_admins', false);
        MockFunction::expectCallCount('ugroup_contain_project_admins', 2);
        $validUGroups = array(1, 2);
        $this->assertFalse(ugroup_validate_dynamic_admin_ugroups(1, array(3, 4), $validUGroups));
        $this->assertEqual(array(1, 2), $validUGroups);
        MockFunction::restore('ugroup_contain_project_admins');
    }

    function testUgroupValidateDynamicAdminUgroupsMixed() {
        MockFunction::generate('ugroup_contain_project_admins');
        MockFunction::setReturnValueAt(0, 'ugroup_contain_project_admins', false);
        MockFunction::setReturnValueAt(1, 'ugroup_contain_project_admins', true);
        MockFunction::expectCallCount('ugroup_contain_project_admins', 2);
        $validUGroups = array(1, 2);
        $this->assertFalse(ugroup_validate_dynamic_admin_ugroups(1, array(3, 4), $validUGroups));
        $this->assertEqual(array(1, 2, 4), $validUGroups);
        MockFunction::restore('ugroup_contain_project_admins');
    }

    function testUgroupValidateAdminUgroupsNoUgroups() {
        MockFunction::setReturnValue('db_fetch_array', null);
        MockFunction::expectOnce('db_fetch_array');
        MockFunction::generate('ugroup_contain_project_admins');
        MockFunction::setReturnValue('ugroup_contain_project_admins', false);
        MockFunction::expectCallCount('ugroup_contain_project_admins', 2);
        $ugroups = array(1, 2);
        $this->assertFalse(ugroup_validate_admin_ugroups(1, $ugroups, $containNonAdmin));
        $this->assertEqual(array(), $ugroups);
        $this->assertFalse($containNonAdmin);
        MockFunction::restore('ugroup_contain_project_admins');
    }

    function testUgroupValidateAdminUgroupsStaticAllAdmins() {
        MockFunction::setReturnValueAt(0 ,'db_fetch_array', array('ugroup_id' => 1));
        MockFunction::setReturnValueAt(1 ,'db_fetch_array', null);
        MockFunction::expectCallCount('db_fetch_array', 2);
        MockFunction::generate('ugroup_contain_project_admins');
        MockFunction::setReturnValueAt(0, 'ugroup_contain_project_admins', true);
        MockFunction::setReturnValueAt(1, 'ugroup_contain_project_admins', false);
        MockFunction::setReturnValueAt(2, 'ugroup_contain_project_admins', false);
        MockFunction::expectCallCount('ugroup_contain_project_admins', 3);
        $ugroups = array(1, 2);
        $this->assertFalse(ugroup_validate_admin_ugroups(1, $ugroups, $containNonAdmin));
        $this->assertEqual(array(1), $ugroups);
        $this->assertFalse($containNonAdmin);
        MockFunction::restore('ugroup_contain_project_admins');
    }

    function testUgroupValidateAdminUgroupsStaticMixed() {
        MockFunction::setReturnValueAt(0 ,'db_fetch_array', array('ugroup_id' => 1));
        MockFunction::setReturnValueAt(1 ,'db_fetch_array', array('ugroup_id' => 2));
        MockFunction::setReturnValueAt(2 ,'db_fetch_array', null);
        MockFunction::expectCallCount('db_fetch_array', 3);
        MockFunction::generate('ugroup_contain_project_admins');
        MockFunction::setReturnValueAt(0, 'ugroup_contain_project_admins', false);
        MockFunction::setReturnValueAt(1, 'ugroup_contain_project_admins', true);
        MockFunction::setReturnValueAt(2, 'ugroup_contain_project_admins', false);
        MockFunction::setReturnValueAt(3, 'ugroup_contain_project_admins', false);
        MockFunction::expectCallCount('ugroup_contain_project_admins', 4);
        $ugroups = array(1, 2);
        $this->assertFalse(ugroup_validate_admin_ugroups(1, $ugroups, $containNonAdmin));
        $this->assertEqual(array(2), $ugroups);
        $this->assertFalse($containNonAdmin);
        MockFunction::restore('ugroup_contain_project_admins');
    }

    function testUgroupValidateAdminUgroupsStatic() {
        MockFunction::setReturnValueAt(0 ,'db_fetch_array', array('ugroup_id' => 1));
        MockFunction::setReturnValueAt(1 ,'db_fetch_array', array('ugroup_id' => 2));
        MockFunction::setReturnValueAt(2 ,'db_fetch_array', null);
        MockFunction::expectCallCount('db_fetch_array', 3);
        MockFunction::generate('ugroup_contain_project_admins');
        MockFunction::setReturnValueAt(0, 'ugroup_contain_project_admins', true);
        MockFunction::setReturnValueAt(1, 'ugroup_contain_project_admins', true);
        MockFunction::setReturnValueAt(2, 'ugroup_contain_project_admins', false);
        MockFunction::setReturnValueAt(3, 'ugroup_contain_project_admins', false);
        MockFunction::expectCallCount('ugroup_contain_project_admins', 4);
        $ugroups = array(1, 2);
        $this->assertTrue(ugroup_validate_admin_ugroups(1, $ugroups, $containNonAdmin));
        $this->assertEqual(array(1, 2), $ugroups);
        $this->assertFalse($containNonAdmin);
        MockFunction::restore('ugroup_contain_project_admins');
    }

    function testUgroupValidateAdminUgroupsDynamicMixed() {
        MockFunction::setReturnValueAt(0 ,'db_fetch_array', array('ugroup_id' => 1));
        MockFunction::setReturnValueAt(1 ,'db_fetch_array', array('ugroup_id' => 2));
        MockFunction::setReturnValueAt(2 ,'db_fetch_array', null);
        MockFunction::expectCallCount('db_fetch_array', 3);
        MockFunction::generate('ugroup_contain_project_admins');
        MockFunction::setReturnValueAt(0, 'ugroup_contain_project_admins', false);
        MockFunction::setReturnValueAt(1, 'ugroup_contain_project_admins', false);
        MockFunction::setReturnValueAt(2, 'ugroup_contain_project_admins', false);
        MockFunction::setReturnValueAt(3, 'ugroup_contain_project_admins', true);
        MockFunction::expectCallCount('ugroup_contain_project_admins', 4);
        $ugroups = array(1, 2);
        $this->assertFalse(ugroup_validate_admin_ugroups(1, $ugroups, $containNonAdmin));
        $this->assertEqual(array(2), $ugroups);
        $this->assertFalse($containNonAdmin);
        MockFunction::restore('ugroup_contain_project_admins');
    }

    function testUgroupValidateAdminUgroupsDynamicAllAdmins() {
        MockFunction::setReturnValueAt(0 ,'db_fetch_array', array('ugroup_id' => 1));
        MockFunction::setReturnValueAt(1 ,'db_fetch_array', array('ugroup_id' => 2));
        MockFunction::setReturnValueAt(2 ,'db_fetch_array', null);
        MockFunction::expectCallCount('db_fetch_array', 3);
        MockFunction::generate('ugroup_contain_project_admins');
        MockFunction::setReturnValueAt(0, 'ugroup_contain_project_admins', false);
        MockFunction::setReturnValueAt(1, 'ugroup_contain_project_admins', false);
        MockFunction::setReturnValueAt(2, 'ugroup_contain_project_admins', true);
        MockFunction::setReturnValueAt(3, 'ugroup_contain_project_admins', true);
        MockFunction::expectCallCount('ugroup_contain_project_admins', 4);
        $ugroups = array(1, 2);
        $this->assertTrue(ugroup_validate_admin_ugroups(1, $ugroups, $containNonAdmin));
        $this->assertEqual(array(1, 2), $ugroups);
        $this->assertFalse($containNonAdmin);
        MockFunction::restore('ugroup_contain_project_admins');
    }

    function testUgroupValidateAdminUgroupsBothAllAdmins() {
        MockFunction::setReturnValueAt(0 ,'db_fetch_array', array('ugroup_id' => 1));
        MockFunction::setReturnValueAt(1 ,'db_fetch_array', array('ugroup_id' => 2));
        MockFunction::setReturnValueAt(2 ,'db_fetch_array', null);
        MockFunction::expectCallCount('db_fetch_array', 3);
        MockFunction::generate('ugroup_contain_project_admins');
        MockFunction::setReturnValueAt(0, 'ugroup_contain_project_admins', true);
        MockFunction::setReturnValueAt(1, 'ugroup_contain_project_admins', true);
        MockFunction::setReturnValueAt(2, 'ugroup_contain_project_admins', false);
        MockFunction::setReturnValueAt(3, 'ugroup_contain_project_admins', false);
        MockFunction::setReturnValueAt(4, 'ugroup_contain_project_admins', true);
        MockFunction::setReturnValueAt(5, 'ugroup_contain_project_admins', true);
        MockFunction::expectCallCount('ugroup_contain_project_admins', 6);
        $ugroups = array(1, 2, 3, 4);
        $this->assertTrue(ugroup_validate_admin_ugroups(1, $ugroups, $containNonAdmin));
        $this->assertEqual(array(1, 2, 3, 4), $ugroups);
        $this->assertFalse($containNonAdmin);
        MockFunction::restore('ugroup_contain_project_admins');
    }
}
?>