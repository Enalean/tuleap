<?php

require_once('common/user/User.class.php');
Mock::generate('User');
require_once('common/dao/UserDao.class.php');
Mock::generate('UserDao');
require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');
require_once('common/event/EventManager.class.php');
Mock::generate('EventManager');

require_once('common/user/UserManager.class.php');
Mock::generatePartial('UserManager', 'UserManagerTestVersion', array('_getUserInstanceFromRow'));
// Special mock for getUserByIdentifier test
Mock::generatePartial('UserManager', 'UserManager4GetByIdent', array('_getEventManager', 'getUserByUserName', 'getUserById', 'getUserByEmail'));

Mock::generate('EventManager', 'BaseMockEventManager');

class MockEM4UserManager extends BaseMockEventManager {
   function processEvent($event, $params) {
       foreach(parent::processEvent($event, $params) as $key => $value) {
           $params[$key] = $value;
       }
   }
} 

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
        $user123->setReturnValue('getUserName', 'user_123');
        $user456 =& new MockUser($this);
        $user456->setReturnValue('getId', 456);
        $user456->setReturnValue('getUserName', 'user_456');
        
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
        $user123->setReturnValue('getUserName', 'user_123');
        $user456 =& new MockUser($this);
        $user456->setReturnValue('getId', 456);
        $user456->setReturnValue('getUserName', 'user_456');
        
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
    
    function testIsLoaded() {
        $dao =& new MockUserDao($this);
        $dar =& new MockDataAccessResult($this);
        $dao->setReturnReference('searchByUserId', $dar);
        $dar->setReturnValueAt(0, 'getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $dar->setReturnValueAt(1, 'getRow', false);
        
        $dao->expectOnce('searchByUserId', array(123));
        
        $user123 =& new MockUser($this);
        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        
        $um =& new UserManagerTestVersion($this);
        $um->setReturnReference('_getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        
        $um->UserManager($dao);
        $this->assertFalse($um->isUserLoadedById(123));
        $this->assertFalse($um->isUserLoadedByUserName('user_123'));
        $um->getUserById(123);
        $this->assertTrue($um->isUserLoadedById(123));
        $this->assertTrue($um->isUserLoadedByUserName('user_123'));
    }
    
    function testGetUserByIdentifierPluginNoAnswerWithSimpleId() {
        $em = new MockEventManager($this);
        $em->expectOnce('processEvent');   

        $um = new UserManager4GetByIdent($this);
        $um->setReturnReference('_getEventManager', $em);

        $um->expectOnce('getUserByUserName');
        $um->setReturnValue('getUserByUserName', null);

        $user = $um->getUserByIdentifier('test');
        $this->assertNull($user);
    }

    function testGetUserByIdentifierPluginAnswerWithSimpleId() {
        $em = new MockEventManager($this);
        $em->expectOnce('processEvent');   

        $um = new UserManager4GetByIdent($this);
        $um->setReturnReference('_getEventManager', $em);

        $um->expectOnce('getUserByUserName');
        $u1 = new MockUser($this);
        $um->setReturnReference('getUserByUserName', $u1);

        $user = $um->getUserByIdentifier('test');
        $this->assertIdentical($user, $u1);
    }    

    function testGetUserByIdentifierPluginNoAnswerWithComplexId() {
        $em = new MockEventManager($this);
        $em->expectOnce('processEvent');   

        $um = new UserManager4GetByIdent($this);
        $um->setReturnReference('_getEventManager', $em);

        $um->expectNever('getUserByUserName');

        $user = $um->getUserByIdentifier('plugin:test');
        $this->assertNull($user);
    }

    function testGetUserByIdentifierPluginAnswer() {
        $u1 = new MockUser($this);
        $em = new MockEM4UserManager($this);
        $em->setReturnValue('processEvent', array('tokenFound' => true, 'user' => &$u1));

        $um = new UserManager4GetByIdent($this);
        $um->setReturnReference('_getEventManager', $em);

        $um->expectNever('getUserByUserName');

        $user = $um->getUserByIdentifier('test');
        $this->assertIdentical($user, $u1);
    }

    function testGetUserByIdentifierPluginAnswerNotFound() {
        $u1 = new MockUser($this);
        $em = new MockEM4UserManager($this);
        $em->setReturnValue('processEvent', array('tokenFound' => false));

        $um = new UserManager4GetByIdent($this);
        $um->setReturnReference('_getEventManager', $em);

        $um->expectOnce('getUserByUserName');
        $um->setReturnValue('getUserByUserName', null);
        
        $user = $um->getUserByIdentifier('test');
        $this->assertNull($user);
    }
    
}
?>
