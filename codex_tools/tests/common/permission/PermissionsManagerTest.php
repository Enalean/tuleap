<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}
require(getenv('SF_LOCAL_INC_PREFIX').'/etc/codex/conf/local.inc');

require_once('tests/simpletest/unit_tester.php');
require_once('tests/simpletest/mock_objects.php'); //uncomment to use Mocks
require_once('common/permission/PermissionsManager.class');
Mock::generatePartial('PermissionsManager', 'PermissionsManagerTestVersion', array('_getUgroupsForUser','_userHasFullPermission'));
require_once('common/dao/PermissionsDao.class');
Mock::generate('PermissionsDao');
require_once('common/dao/include/DataAccessResult.class');
Mock::generate('DataAccessResult');
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: PermissionsManagerTest.php 1901 2005-08-18 14:54:55Z nterray $
 *
 * Tests the class PermissionsManager
 */
class PermissionsManagerTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function PermissionsManagerTest($name = 'PermissionsManager test') {
        $this->UnitTestCase($name);
    }

    function testSingleton() { 
        $this->assertReference(
                PermissionsManager::instance(),
                PermissionsManager::instance());
        $this->assertIsA(PermissionsManager::instance(), 'PermissionsManager');
    }
    
    function testUserHasPermission() {
        
        $dar =& new MockDataAccessResult($this);
        $dar->setReturnValue('getRow', false);
        $dar->setReturnValueAt(0, 'getRow', array('object_id' => '123', 'ugroup_id' => 11, 'permission_type' => 'PERM_1'));
        
        $dar2 =& new MockDataAccessResult($this);
        $dar2->setReturnValue('getRow', false);
        $dar2->setReturnValueAt(0, 'getRow', array('object_id' => '125#2', 'ugroup_id' => 13, 'permission_type' => 'PERM_2'));
        $dar2->setReturnValueAt(1, 'getRow', array('object_id' => '125#3', 'ugroup_id' => 13, 'permission_type' => 'PERM_3'));
        
        $permissions_dao =& new MockPermissionsDao($this);
        $permissions_dao->setReturnValue('searchPermissionsByObjectIdAndUgroups', $dar);
        $permissions_dao->setReturnValue('searchPermissionsByArtifactFieldIdAndUgroups', $dar2);
        
        $pm =& new PermissionsManagerTestVersion($this);
        $pm->setReturnValue('_userHasFullPermission', true, array(1)); //Super user
        $pm->setReturnValue('_userHasFullPermission', false, array(2));
        $pm->setReturnValue('_getUgroupsForUser', array(11, 12), array(2));
        $pm->setReturnValue('_getUgroupsForUser', array(13), array(3));
        $pm->PermissionsManager($permissions_dao); //Only for test. You should use singleton instead
        $this->assertTrue($pm->userHasPermission(123, 'PERM_1', 1)); //Super user
        $this->assertTrue($pm->userHasPermission(123, 'PERM_1', 2));
        $this->assertTrue($pm->userHasPermission(123, 'PERM_1', 2));
        $this->assertFalse($pm->userHasPermission(123, 'PERM_1', 3));
        $this->assertTrue($pm->userHasPermission('125#3', 'PERM_3', 3));
        
        $permissions_dao->tally();
    }
    
    
}

if (CODEX_RUNNER === __FILE__) {
    $test = &new PermissionsManagerTest();
    $test->run(new CodexReporter());
 }
?>
