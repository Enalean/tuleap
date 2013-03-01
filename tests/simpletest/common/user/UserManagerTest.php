<?php

require_once('common/user/User.class.php');
Mock::generate('PFUser');
require_once('common/dao/UserDao.class.php');
Mock::generate('UserDao');
require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');
require_once('common/include/Response.class.php');
Mock::generate('Response');
require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once('common/event/EventManager.class.php');
Mock::generate('EventManager');

require_once('common/user/UserManager.class.php');
Mock::generatePartial('UserManager', 
                      'UserManagerTestVersion', 
                      array('getUserInstanceFromRow', 
                            '_getCookieManager', 
                            '_getServerIp', 
                            'generateSessionHash',
                            '_getPasswordLifetime',
                            '_getEventManager',
                            'getDao',
                            'destroySession',
                      )
);
// Special mock for getUserByIdentifier test
Mock::generatePartial('UserManager', 'UserManager4GetByIdent', array('_getEventManager', 'getUserByUserName', 'getUserById', 'getUserByEmail'));

require_once('common/include/CookieManager.class.php');
Mock::generate('CookieManager');
Mock::generate('EventManager', 'BaseMockEventManager');

class MockEM4UserManager extends BaseMockEventManager {
   function processEvent($event, $params) {
       foreach(parent::processEvent($event, $params) as $key => $value) {
           $params[$key] = $value;
       }
   }
} 

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 *
 * Tests the class User
 */
class UserManagerTest extends UnitTestCase {
    
    function setUp() {
        $GLOBALS['Response'] = new MockResponse($this);
        $GLOBALS['Language'] = new MockBaseLanguage($this);
    }
    
    function tearDown() {
        unset($GLOBALS['Response']);
        unset($GLOBALS['Language']);
    }
    
    function testCachingById() {
        $dao = new MockUserDao($this);
        $dar = new MockDataAccessResult($this);
        $dao->setReturnReference('searchByUserId', $dar);
        $dar->setReturnValueAt(0, 'getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $dar->setReturnValueAt(1, 'getRow', false);
        
        $dao->expectOnce('searchByUserId', array(123));
        
        $user123 = mock('PFUser');
        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        $user456 = mock('PFUser');
        $user456->setReturnValue('getId', 456);
        $user456->setReturnValue('getUserName', 'user_456');
        
        $um = new UserManagerTestVersion($this);
        $um->setReturnReference('getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        $um->setReturnReference('getUserInstanceFromRow', $user456, array(array('user_name' => 'user_456', 'user_id' => 456)));
        
        $um->setReturnReference('getDao', $dao);
        $user_1 = $um->getUserById(123);
        $user_2 = $um->getuserById(123);
        $this->assertReference($user_1, $user_2);
    }
    
    function testCachingByUserName() {
        $dao = new MockUserDao($this);
        $dar = new MockDataAccessResult($this);
        $dao->setReturnReference('searchByUserName', $dar);
        $dar->setReturnValueAt(0, 'getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $dar->setReturnValueAt(1, 'getRow', false);
        
        $dao->expectOnce('searchByUserName', array('user_123'));
        
        $user123 = mock('PFUser');
        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        $user456 = mock('PFUser');
        $user456->setReturnValue('getId', 456);
        $user456->setReturnValue('getUserName', 'user_456');
        
        $um = new UserManagerTestVersion($this);
        $um->setReturnReference('getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        $um->setReturnReference('getUserInstanceFromRow', $user456, array(array('user_name' => 'user_456', 'user_id' => 456)));
        
        $um->setReturnReference('getDao', $dao);
        $user_1 = $um->getUserByUserName('user_123');
        $user_2 = $um->getuserByUserName('user_123');
        $this->assertReference($user_1, $user_2);
    }
    
    function testDoubleCaching() {
        $dao = new MockUserDao($this);
        $dar_123 = new MockDataAccessResult($this);
        $dao->setReturnReference('searchByUserId', $dar_123, array(123));
        $dar_123->setReturnValueAt(0, 'getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $dar_123->setReturnValueAt(1, 'getRow', false);
        $dar_456 = new MockDataAccessResult($this);
        $dao->setReturnReference('searchByUserName', $dar_456, array('user_456'));
        $dar_456->setReturnValueAt(0, 'getRow', array('user_name' => 'user_456', 'user_id' => 456));
        $dar_456->setReturnValueAt(1, 'getRow', false);
        
        $dao->expectOnce('searchByUserId', array(123));
        $dao->expectOnce('searchByUserName', array('user_456'));
        
        $user123 = mock('PFUser');
        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        $user456 = mock('PFUser');
        $user456->setReturnValue('getId', 456);
        $user456->setReturnValue('getUserName', 'user_456');
        
        $um = new UserManagerTestVersion($this);
        $um->setReturnReference('getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        $um->setReturnReference('getUserInstanceFromRow', $user456, array(array('user_name' => 'user_456', 'user_id' => 456)));
        
        $um->setReturnReference('getDao', $dao);
        $user_1 = $um->getUserById(123);
        $user_2 = $um->getUserByUserName('user_123');
        $this->assertReference($user_1, $user_2);
        $user_3 = $um->getUserByUserName('user_456');
        $user_4 = $um->getuserById(456);
        $this->assertReference($user_3, $user_4);
    }
    
    function testIsLoaded() {
        $dao = new MockUserDao($this);
        $dar = new MockDataAccessResult($this);
        $dao->setReturnReference('searchByUserId', $dar);
        $dar->setReturnValueAt(0, 'getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $dar->setReturnValueAt(1, 'getRow', false);
        
        $dao->expectOnce('searchByUserId', array(123));
        
        $user123 = mock('PFUser');
        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        
        $um = new UserManagerTestVersion($this);
        $um->setReturnReference('getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        
        $um->setReturnReference('getDao', $dao);
        $this->assertFalse($um->isUserLoadedById(123));
        $this->assertFalse($um->isUserLoadedByUserName('user_123'));
        $um->getUserById(123);
        $this->assertTrue($um->isUserLoadedById(123));
        $this->assertTrue($um->isUserLoadedByUserName('user_123'));
    }
    
    function testEmptySessionHash() {
        $cm               = new MockCookieManager($this);
        $userAnonymous    = mock('PFUser');
        $dao              = new MockUserDao($this);
        $um               = new UserManagerTestVersion($this);
        
        $userAnonymous->setReturnValue('getId', 0);
        $userAnonymous->setReturnValue('isAnonymous', true);
        
        $cm->setReturnValue('getCookie', '');
        
        $um->setReturnReference('getDao', $dao);
        $um->setReturnReference('_getCookieManager', $cm);
        $um->setReturnReference('getUserInstanceFromRow', $userAnonymous, array(array('user_id' => 0)));
        
        //expect that the user is cached
        $um->expectOnce('getUserInstanceFromRow');
        
        $user = $um->getCurrentUser();
        $this->assertTrue($user->isAnonymous(), 'An empty session hash gives an anonymous user');
        
        $this->assertReference($user, $um->getUserById($user->getId()));
    }
    
    function testValidSessionHash() {
        $cm               = new MockCookieManager($this);
        $dar_valid_hash   = new MockDataAccessResult($this);
        $user123          = mock('PFUser');
        $dao              = new MockUserDao($this);
        $um               = new UserManagerTestVersion($this);
        
        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        $user123->setReturnValue('isAnonymous', false);
        $user123->setReturnValue('isSuspended', false);
        $user123->setReturnValue('isDeleted',   false);

        $cm->setReturnValue('getCookie', 'valid_hash');
        $um->setReturnValue('_getServerIp', '212.212.123.12');
        $dar_valid_hash->setReturnValue('getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $dao->setReturnReference('searchBySessionHashAndIp', $dar_valid_hash, array('valid_hash', '212.212.123.12'));
        $um->setReturnReference('getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        
        $dao->expectOnce('storeLastAccessDate', array(123, '*'));
        
        $um->setReturnReference('getDao', $dao);
        $um->setReturnReference('_getCookieManager', $cm);
        
        $user = $um->getCurrentUser();
        $this->assertFalse($user->isAnonymous(), 'An valid session hash gives a registered user');
    }
    
    function testInvalidSessionHash() {
        $cm               = new MockCookieManager($this);
        $dar_invalid_hash = new MockDataAccessResult($this);
        $userAnonymous    = mock('PFUser');
        $dao              = new MockUserDao($this);
        $um               = new UserManagerTestVersion($this);
        
        $userAnonymous->setReturnValue('getId', 0);
        $userAnonymous->setReturnValue('isAnonymous', true);

        $cm->setReturnValue('getCookie', 'invalid_hash');
        $um->setReturnValue('_getServerIp', '212.212.123.12');
        $dar_invalid_hash->setReturnValue('getRow', false);
        $dao->setReturnReference('searchBySessionHashAndIp', $dar_invalid_hash, array('invalid_hash', '212.212.123.12'));
        $um->setReturnReference('getUserInstanceFromRow', $userAnonymous, array(array('user_id' => 0)));
        
        $um->setReturnReference('getDao', $dao);
        $um->setReturnReference('_getCookieManager', $cm);
        
        $user = $um->getCurrentUser();
        $this->assertTrue($user->isAnonymous(), 'An invalid session hash gives an anonymous user');
    }
    
    function testInvalidIp() {
        $cm               = new MockCookieManager($this);
        $dar_invalid      = new MockDataAccessResult($this);
        $userAnonymous    = mock('PFUser');
        $dao              = new MockUserDao($this);
        $um               = new UserManagerTestVersion($this);
        
        $userAnonymous->setReturnValue('getId', 0);
        $userAnonymous->setReturnValue('isAnonymous', true);
        
        $cm->setReturnValue('getCookie', 'valid_hash');
        $um->setReturnValue('_getServerIp', 'in.val.id.ip');
        $dar_invalid->setReturnValue('getRow', false);
        $dao->setReturnReference('searchBySessionHashAndIp', $dar_invalid, array('valid_hash', 'in.val.id.ip'));
        $um->setReturnReference('getUserInstanceFromRow', $userAnonymous, array(array('user_id' => 0)));
        
        $um->setReturnReference('getDao', $dao);
        $um->setReturnReference('_getCookieManager', $cm);
        
        $user = $um->getCurrentUser();
        $this->assertTrue($user->isAnonymous(), 'An invalid ip gives an anonymous user');
    }
    
    function testSessionContinue() {
        $cm               = new MockCookieManager($this);
        $dar_invalid_hash = new MockDataAccessResult($this);
        $dar_valid_hash   = new MockDataAccessResult($this);
        $user123          = mock('PFUser');
        $userAnonymous    = mock('PFUser');
        $dao              = new MockUserDao($this);
        $um               = new UserManagerTestVersion($this);
        
        $userAnonymous->setReturnValue('getId', 0);
        $userAnonymous->setReturnValue('isAnonymous', true);
        
        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        $user123->setReturnValue('isAnonymous', false);
        $user123->expectOnce('setSessionHash', array('valid_hash'));

        $cm->setReturnValue('getCookie', 'empty_hash');
        $um->setReturnValue('_getServerIp', '212.212.35.25');
        $dao->setReturnReference('searchBySessionHashAndIp', $dar_invalid_hash, array('empty_hash', ''));
        $dar_invalid_hash->setReturnValue('getRow', false);
        $um->setReturnReference('getUserInstanceFromRow', $userAnonymous, array(array('user_id' => 0)));
        
        $dao->setReturnReference('searchBySessionHashAndIp', $dar_valid_hash, array('valid_hash', '212.212.35.25'));
        $dar_valid_hash->setReturnValue('getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $um->setReturnReference('getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        
        $um->setReturnReference('getDao', $dao);
        $um->setReturnReference('_getCookieManager', $cm);
        
        $user1 = $um->getCurrentUser();
        $this->assertTrue($user1->isAnonymous(), 'An invalid ip gives an anonymous user');
        
        //The user is cached
        $user2 = $um->getCurrentUser();
        $this->assertTrue($user2->isAnonymous(), 'An invalid ip gives an anonymous user');
        
        //Force refresh by providing a session_hash.
        //This will continue the session for the protocols 
        //which don't handle cookies
        $user3 = $um->getCurrentUser('valid_hash');
        $this->assertFalse($user3->isAnonymous(), 'The session can be continued');
    }
    
    function testLogout() {
        $cm               = new MockCookieManager($this);
        $dar_valid_hash   = new MockDataAccessResult($this);
        $user123          = mock('PFUser');
        $dao              = new MockUserDao($this);
        $um               = new UserManagerTestVersion($this);
        
        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        $user123->setReturnValue('isAnonymous', false);
        $user123->setReturnValue('getSessionHash', 'valid_hash');
        $user123->expectAt(0, 'setSessionHash', array('valid_hash'));
        $user123->expectAt(1, 'setSessionHash', array(false));

        $cm->setReturnValue('getCookie', 'valid_hash');
        $cm->expectOnce('removeCookie', array('session_hash'));
        $um->setReturnValue('_getServerIp', '212.212.123.12');
        $dao->expectOnce('deleteSession', array('valid_hash'));
        $dao->setReturnReference('searchBySessionHashAndIp', $dar_valid_hash, array('valid_hash', '212.212.123.12'));
        $dar_valid_hash->setReturnValue('getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $um->setReturnReference('getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        
        $um->setReturnReference('getDao', $dao);
        $um->setReturnReference('_getCookieManager', $cm);
        $um->expectOnce('destroySession');
        
        $user = $um->getCurrentUser();
        $um->logout();
    }
    
    function testGoodLogin() {
        $cm               = new MockCookieManager($this);
        $dao              = new MockUserDao($this);
        $dar              = new MockDataAccessResult($this);
        $user123          = mock('PFUser');
        $um               = new UserManagerTestVersion($this);
        $em               = new MockEventManager($this);
        
        $um->setReturnReference('_getEventManager', $em);
        
        $hash = 'valid_hash';
        $dao->setReturnValue('createSession', $hash);
        
        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        $user123->setReturnValue('getUserPw', md5('pwd'));
        $user123->setReturnValue('getStatus', 'A');
        $user123->setReturnValue('isAnonymous', false);
        $user123->expectOnce('setSessionHash', array($hash));
        
        $cm->expectOnce('setCookie', array('session_hash', $hash, 0));
        $um->setReturnReference('_getCookieManager', $cm);
        
        $dao->setReturnReference('searchByUserName', $dar, array('user_123'));
        $dar->setReturnValue('getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $um->setReturnReference('getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        
        $dao->expectNever('storeLoginFailure');
        
        $um->setReturnReference('getDao', $dao);
        $this->assertReference($user123, $um->login('user_123', 'pwd', 0));
    }
    
    function testBadLogin() {
        $cm               = new MockCookieManager($this);
        $dao              = new MockUserDao($this);
        $dar              = new MockDataAccessResult($this);
        $user123          = mock('PFUser');
        $userAnonymous    = mock('PFUser');
        $um               = new UserManagerTestVersion($this);
        $em               = new MockEventManager($this);
        
        $um->setReturnReference('_getEventManager', $em);
        
        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        $user123->setReturnValue('getUserPw', md5('pwd'));
        $user123->setReturnValue('isAnonymous', false);
        $user123->expectNever('setSessionHash');
        
        $userAnonymous->setReturnValue('getId', 0);
        $userAnonymous->setReturnValue('isAnonymous', true);
        
        $cm->expectNever('setCookie');
        $um->setReturnReference('_getCookieManager', $cm);
        
        $dao->setReturnReference('searchByUserName', $dar, array('user_123'));
        $dar->setReturnValue('getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $um->setReturnReference('getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        $um->setReturnReference('getUserInstanceFromRow', $userAnonymous, array(array('user_id' => 0)));
        
        $dao->expectNever('createSession');
        $dao->expectOnce('storeLoginFailure');
        
        $um->setReturnReference('getDao', $dao);
        $this->assertReference($userAnonymous, $um->login('user_123', 'bad_pwd', 0));
    }
    
    function testSuspenedUserGetSession() {
        
        $cm               = new MockCookieManager($this);
        $dar_valid_hash   = new MockDataAccessResult($this);
        $user123          = mock('PFUser');
        $userAnonymous    = mock('PFUser');
        $dao              = new MockUserDao($this);
        $um               = new UserManagerTestVersion($this);
        
        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        $user123->setReturnValue('isAnonymous', false);
        $user123->setReturnValue('isSuspended', true);
        
        $userAnonymous->setReturnValue('isAnonymous', true);
        
        $cm->setReturnValue('getCookie', 'valid_hash');
        $um->setReturnValue('_getServerIp', '212.212.123.12');
        $dar_valid_hash->setReturnValue('getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $dao->setReturnReference('searchBySessionHashAndIp', $dar_valid_hash, array('valid_hash', '212.212.123.12'));
        $um->setReturnReference('getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        $um->setReturnReference('getUserInstanceFromRow', $userAnonymous, array(array('user_id' => 0)));
        
        $dao->expectNever('storeLastAccessDate');
        $dao->expectOnce('deleteAllUserSessions', array(123));
        
        $um->setReturnReference('getDao', $dao);
        $um->setReturnReference('_getCookieManager', $cm);
        
        $user = $um->getCurrentUser();
        $this->assertTrue($user->isAnonymous(), 'A suspended user should not be able to use a valid session');
    }

    function testDeletedUserGetSession() {
        $cm               = new MockCookieManager($this);
        $dar_valid_hash   = new MockDataAccessResult($this);
        $user123          = mock('PFUser');
        $userAnonymous    = mock('PFUser');
        $dao              = new MockUserDao($this);
        $um               = new UserManagerTestVersion($this);
        
        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        $user123->setReturnValue('isAnonymous', false);
        $user123->setReturnValue('isDeleted', true);
        
        $userAnonymous->setReturnValue('isAnonymous', true);
        
        $cm->setReturnValue('getCookie', 'valid_hash');
        $um->setReturnValue('_getServerIp', '212.212.123.12');
        $dar_valid_hash->setReturnValue('getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $dao->setReturnReference('searchBySessionHashAndIp', $dar_valid_hash, array('valid_hash', '212.212.123.12'));
        $um->setReturnReference('getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        $um->setReturnReference('getUserInstanceFromRow', $userAnonymous, array(array('user_id' => 0)));
        
        $dao->expectNever('storeLastAccessDate');
        $dao->expectOnce('deleteAllUserSessions', array(123));
        
        $um->setReturnReference('getDao', $dao);
        $um->setReturnReference('_getCookieManager', $cm);
        
        $user = $um->getCurrentUser();
        $this->assertTrue($user->isAnonymous(), 'A deleted user should not be able to use a valid session');
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
        $u1 = mock('PFUser');
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
        $u1 = mock('PFUser');
        $em = new MockEM4UserManager($this);
        $em->setReturnValue('processEvent', array('tokenFound' => true, 'user' => &$u1));

        $um = new UserManager4GetByIdent($this);
        $um->setReturnReference('_getEventManager', $em);

        $um->expectNever('getUserByUserName');

        $user = $um->getUserByIdentifier('test');
        $this->assertIdentical($user, $u1);
    }

    function testGetUserByIdentifierPluginAnswerNotFound() {
        $u1 = mock('PFUser');
        $em = new MockEM4UserManager($this);
        $em->setReturnValue('processEvent', array('tokenFound' => false));

        $um = new UserManager4GetByIdent($this);
        $um->setReturnReference('_getEventManager', $em);

        $um->expectOnce('getUserByUserName');
        $um->setReturnValue('getUserByUserName', null);
        
        $user = $um->getUserByIdentifier('test');
        $this->assertNull($user);
    }
    
    function testUpdateFailureWhenAnonymous() {
    	$user = mock('PFUser');
        $user->setReturnValue('isAnonymous', true);
        
        $dao = new MockUserDao($this);
        $um = new UserManagerTestVersion($this);
        $um->setReturnReference('getDao', $dao);
        $this->assertFalse($um->updateDb($user));
    }
    
    function testUpdateDaoResultPropagated() {
    	$user = mock('PFUser');
    	$user->setReturnValue('isAnonymous', false);
    	$user->setReturnValue('isSuspended', false);
    	$user->setReturnValue('isDeleted',   false);
        $user->setReturnValue('toRow',       array());
    	
    	// True
    	$daotrue = new MockUserDao($this);
        $daotrue->setReturnValue('updateByRow', true);
        $daotrue->expectNever('deleteAllUserSessions');
    	$umtrue = new UserManagerTestVersion($this);
        $umtrue->setReturnReference('getDao', $daotrue);
        $this->assertTrue($umtrue->updateDb($user));
        
        // False
        $daofalse = new MockUserDao($this);
        $daofalse->setReturnValue('updateByRow', false);
        $daofalse->expectNever('deleteAllUserSessions');
        $umfalse = new UserManagerTestVersion($this);
        $umfalse->setReturnReference('getDao', $daofalse);
        $this->assertFalse($umfalse->updateDb($user));
    }
    
    function testUpdatePassword() {
    	$password = "coco l'asticot";
    	
    	$user = mock('PFUser');
        $user->setReturnValue('isAnonymous', false);
        $user->setReturnValue('toRow', array());
        $user->setReturnValue('getPassword', $password);
        $user->setReturnValue('getUserPw', md5("j'ai faim"));
        
        $dao = new MockUserDao($this);
        $dao->expect('updateByRow', array(array('password' => $password)));
        
        $um = new UserManagerTestVersion($this);
        $um->setReturnReference('getDao', $dao);
        $um->updateDb($user);
    }

    function testUpdateNoPasswordChange() {
        $password = "coco l'asticot";
        
        $user = mock('PFUser');
        $user->setReturnValue('isAnonymous', false);
        $user->setReturnValue('toRow', array());
        $user->setReturnValue('getPassword', $password);
        $user->setReturnValue('getUserPw', md5($password));
        
        $dao = new MockUserDao($this);
        $dao->expect('updateByRow', array(array()));
        
        $um = new UserManagerTestVersion($this);
        $um->setReturnReference('getDao', $dao);
        $um->updateDb($user);
    }

    function testUpdateToSuspendedDeleteSessions() {
        $user = mock('PFUser');
        $user->setReturnValue('getId', 123);
        $user->setReturnValue('isAnonymous', false);
        $user->setReturnValue('isSuspended', true);
        $user->setReturnValue('toRow',       array());

        $dao = new MockUserDao($this);
        $dao->setReturnValue('updateByRow', true);
        $dao->expectOnce('deleteAllUserSessions', array(123));

        $um = new UserManagerTestVersion($this);
        $um->setReturnReference('getDao', $dao);

        $this->assertTrue($um->updateDb($user));
    }

    function testUpdateToDeletedDeleteSessions() {
        $user = mock('PFUser');
        $user->setReturnValue('getId', 123);
        $user->setReturnValue('isAnonymous', false);
        $user->setReturnValue('isDeleted', true);
        $user->setReturnValue('toRow',       array());

        $dao = new MockUserDao($this);
        $dao->setReturnValue('updateByRow', true);
        $dao->expectOnce('deleteAllUserSessions', array(123));

        $um = new UserManagerTestVersion($this);
        $um->setReturnReference('getDao', $dao);

        $this->assertTrue($um->updateDb($user));
    }

    function testAssignNextUnixUidUpdateUser() {
        $user = mock('PFUser');
        $user->expectOnce('setUnixUid', array(1789));
        
        $dao = new MockUserDao($this);
        $dao->setReturnValue('assignNextUnixUid', 1789);
        
        $um = new UserManagerTestVersion($this);
        $um->setReturnReference('getDao', $dao);
        $um->assignNextUnixUid($user);
        
        // Instead of $user->expectOnce('setUnixUid', array(1789)); with
        // Codendi 4.0 and new User, we should use this assertion:
        //$this->assertEqual(1789, $user->getUnixUid());
    }
    function testLoginAsCallsGetCurrentUser() {
        $ordinaryUser = mock('PFUser');
        $ordinaryUser->setReturnValue('isSuperUser', false);
        $um = $this->aUserManagerWithCurrentUser($ordinaryUser);

        $um->expectOnce('getCurrentUser', array());
        
        $this->expectException('UserNotAuthorizedException');
        $um->loginAs(null);
    }
    
    function testLoginAsReturnsAnExceptionWhenNotCallByTheSuperUser() {
        $hash_is_not_important = null;
        $ordinaryUser = mock('PFUser');
        $ordinaryUser->setReturnValue('isSuperUser', false);
        $um = $this->aUserManagerWithCurrentUser($ordinaryUser);
        
        $this->expectException('UserNotAuthorizedException');
        $um->loginAs('tlkjtj');
    }
    
    function testLoginAsReturnsAnExceptionWhenAccountDoesNotExist() {
        $um = TestHelper::getPartialMock('UserManager', array('getCurrentUser', 'getUserByUserName'));
        $admin_user = $this->anAdminUser();
        $um->setReturnValue('getCurrentUser', $admin_user);
        
        $name = 'toto';
        $um->setReturnValue('getUserByUserName', null, array($name));

        $this->expectException('UserNotExistException');
        $um->loginAs($name);
    }
    
    function testLoginAsReturnsAnExceptionWhenAccountIsNotInOrder() {
        $um = $this->aUserManagerWithCurrentUser($this->anAdminUser());
        $this->injectUser($um, 'Johnny', 'D');

        $this->expectException('UserNotActiveException');
        $um->loginAs('Johnny');
    }
    
    function testLoginAsReturnsAnExceptionWhenSessionIsNotCreated() {
        $um = $this->aUserManagerWithCurrentUser($this->anAdminUser());

        $this->injectUser($um, 'Clooney', 'A');
        
        $user_dao = new MockUserDao($this);
        $user_dao->setReturnValue('createSession', false);
        $um->_userdao = $user_dao;

        $this->expectException('SessionNotCreatedException');
        $um->loginAs('Clooney');
    }
    
    function testLoginAsCreatesASessionAndReturnsASessionHash() {
        $um = $this->aUserManagerWithCurrentUser($this->anAdminUser());
        
        $userLoginAs = $this->injectUser($um, 'Clooney', 'A');

        $user_dao = new MockUserDao($this);
        $user_dao->setReturnValue('createSession', 'session_hash', array($userLoginAs->getId(), $_SERVER['REQUEST_TIME']));
        $um->_userdao = $user_dao;
        
        $session_hash = $um->loginAs('Clooney');
        $this->assertEqual($session_hash, 'session_hash');
        
    }
    
    private function aUserWithStatusAndId($status, $id) {
        $userLoginAs = mock('PFUser');
        $userLoginAs->setReturnValue('getStatus', $status);
        $userLoginAs->setReturnValue('getId', $id);
        return $userLoginAs;
    }

    private function aUserManagerWithCurrentUser($user) {
        $um = TestHelper::getPartialMock('UserManager', array('getCurrentUser'));
        $um->setReturnValue('getCurrentUser', $user);
        return $um;
    }
    
    function injectUser(UserManager $um, $name, $status) {
        $whatever = 999;
        $user = $this->aUserWithStatusAndId($status, $whatever);
        $um->_userid_bynames[$name] = $user->getId();
        $um->_users[$user->getId()] = $user;
        return $user;
    }

    private function anAdminUser() {
        $adminUser = mock('PFUser');
        $adminUser->setReturnValue('isSuperUser', true);
        return $adminUser;
    }
}

class UserManager_GetUserWithSSHKeyTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $this->user_name = 'toto';

        $data_access = mock('DataAccess');
        $data_array  = array('user_name' => $this->user_name);
        stub($data_access)->fetch()->returns($data_array);
        stub($data_access)->numRows()->returns(1);
        $result = new stdClass();
        $this->dar = new DataAccessResult($data_access, $result);
    }

    public function itReturnsTheListOfUsers() {
        $dao = stub('UserDao')->searchSSHKeys()->returns($this->dar);

        $user_manager = partial_mock('UserManager', array('getDao'));
        stub($user_manager)->getDao()->returns($dao);

        $users = $user_manager->getUsersWithSshKey();
        $this->assertEqual($users->getRow()->getUserName(), $this->user_name);
    }
}

?>
