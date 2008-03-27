<?php

require_once('common/user/User.class.php');
Mock::generate('User');
require_once('common/dao/UserDao.class.php');
Mock::generate('UserDao');
require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');

require_once('common/user/UserManager.class.php');
Mock::generatePartial('UserManager', 'UserManagerTestVersion', array('_getUserInstanceFromRow'));


/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 *
 * Tests the class User
 */
class UserManagerTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function UserManagerTest($name = 'User Manager test') {
        $this->UnitTestCase($name);
    }
    
    function testCachingById() {
        $dao =& new MockUserDao($this);
        $dar =& new MockDataAccessResult($this);
        $dao->setReturnReference('searchByUserId', $dar);
        $dar->setReturnValueAt(0, 'getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $dar->setReturnValueAt(1, 'getRow', false);
        
        $dao->expectOnce('searchByUserId', array(123));
        
        $user123 =& new MockUser($this);
        $user123->setReturnValue('getId', 123);
        $user456 =& new MockUser($this);
        $user456->setReturnValue('getId', 456);
        
        $um =& new UserManagerTestVersion($this);
        $um->setReturnReference('_getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        $um->setReturnReference('_getUserInstanceFromRow', $user456, array(array('user_name' => 'user_456', 'user_id' => 456)));
        
        $um->UserManager($dao);
        $user_1 =& $um->getUserById(123);
        $user_2 =& $um->getuserById(123);
        $this->assertReference($user_1, $user_2);
        
    }
    
    function testCachingByUserName() {
        $dao =& new MockUserDao($this);
        $dar =& new MockDataAccessResult($this);
        $dao->setReturnReference('searchByUserName', $dar);
        $dar->setReturnValueAt(0, 'getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $dar->setReturnValueAt(1, 'getRow', false);
        
        $dao->expectOnce('searchByUserName', array('user_123'));
        
        $user123 =& new MockUser($this);
        $user123->setReturnValue('getId', 123);
        $user456 =& new MockUser($this);
        $user456->setReturnValue('getId', 456);
        
        $um =& new UserManagerTestVersion($this);
        $um->setReturnReference('_getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        $um->setReturnReference('_getUserInstanceFromRow', $user456, array(array('user_name' => 'user_456', 'user_id' => 456)));
        
        $um->UserManager($dao);
        $user_1 =& $um->getUserByUserName('user_123');
        $user_2 =& $um->getuserByUserName('user_123');
        $this->assertReference($user_1, $user_2);
        
    }
    
    function testDoubleCaching() {
        $dao =& new MockUserDao($this);
        $dar_123 =& new MockDataAccessResult($this);
        $dao->setReturnReference('searchByUserId', $dar_123, array(123));
        $dar_123->setReturnValueAt(0, 'getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $dar_123->setReturnValueAt(1, 'getRow', false);
        $dar_456 =& new MockDataAccessResult($this);
        $dao->setReturnReference('searchByUserName', $dar_456, array('user_456'));
        $dar_456->setReturnValueAt(0, 'getRow', array('user_name' => 'user_456', 'user_id' => 456));
        $dar_456->setReturnValueAt(1, 'getRow', false);
        
        $dao->expectOnce('searchByUserId', array(123));
        $dao->expectOnce('searchByUserName', array('user_456'));
        
        $user123 =& new MockUser($this);
        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        $user456 =& new MockUser($this);
        $user456->setReturnValue('getId', 456);
        $user456->setReturnValue('getUserName', 'user_456');
        
        $um =& new UserManagerTestVersion($this);
        $um->setReturnReference('_getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        $um->setReturnReference('_getUserInstanceFromRow', $user456, array(array('user_name' => 'user_456', 'user_id' => 456)));
        
        $um->UserManager($dao);
        $user_1 =& $um->getUserById(123);
        $user_2 =& $um->getUserByUserName('user_123');
        $this->assertReference($user_1, $user_2);
        $user_3 =& $um->getUserByUserName('user_456');
        $user_4 =& $um->getuserById(456);
        $this->assertReference($user_3, $user_4);
    }
}
?>
