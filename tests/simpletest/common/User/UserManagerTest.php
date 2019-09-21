<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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
 *
 */

Mock::generate('PFUser');
Mock::generate('UserDao');
Mock::generate('DataAccessResult');
Mock::generate('Response');
Mock::generate('BaseLanguage');

Mock::generatePartial(
    'UserManager',
    'UserManagerTestVersion',
    array('getUserInstanceFromRow',
                            'getCookieManager',
                            'getTokenManager',
                            'getSessionManager',
                            '_getPasswordLifetime',
                            '_getEventManager',
                            'getDao',
                            'destroySession',
                            'getForgeUserGroupPermissionsManager'
                      )
);
// Special mock for getUserByIdentifier test
Mock::generatePartial('UserManager', 'UserManager4GetByIdent', array('_getEventManager', 'getUserByUserName', 'getUserById', 'getUserByEmail'));

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class UserManagerTest extends TuleapTestCase
{
    public const PASSWORD = 'pwd';

    public function testCachingById()
    {
        $dao = mock('UserDao');
        stub($dao)->searchByUserId()->returnsDar(array('user_name' => 'user_123', 'user_id' => 123));

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

    public function testCachingByUserName()
    {
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

    public function testDoubleCaching()
    {
        $dao = mock('UserDao');
        stub($dao)->searchByUserId(123)->returnsDar(array('user_name' => 'user_123', 'user_id' => 123));
        stub($dao)->searchByUserName('user_456')->returnsDar(array('user_name' => 'user_456', 'user_id' => 456));

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

    public function testIsLoaded()
    {
        $dao = mock('UserDao');
        stub($dao)->searchByUserId(123)->returnsDar(array('user_name' => 'user_123', 'user_id' => 123));

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

    public function testGoodLogin()
    {
        $cm                  = Mockery::mock(\Tuleap\CookieManager::class);
        $session_manager     = mock('Tuleap\User\SessionManager');
        $dao                 = Mockery::mock(UserDao::class);
        $user123             = mock('PFUser');
        $user_manager        = new UserManagerTestVersion($this);
        $password_handler    = PasswordHandlerFactory::getPasswordHandler();
        $permissions_manager = mock(User_ForgeUserGroupPermissionsManager::class);

        $user_manager->setReturnValue('_getEventManager', \Mockery::spy(EventManager::class));
        $hash = 'valid_hash';

        $token_value   = 'token';
        $token         = stub('Rest_Token')->getTokenValue()->returns($token_value);
        $token_manager = stub('Rest_TokenManager')->generateTokenForUser()->returns($token);

        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        $user123->setReturnValue('getUserPw', $password_handler->computeHashPassword(self::PASSWORD));
        $user123->setReturnValue('getStatus', 'A');
        $user123->setReturnValue('isAnonymous', false);

        $cm->shouldReceive('setCookie')->with('session_hash', $hash, 0)->once();

        stub($session_manager)->createSession()->returns($hash);
        stub($permissions_manager)->doesUserHavePermission()->returns(false);

        $user_manager->setReturnReference('getCookieManager', $cm);
        stub($user_manager)->getSessionManager()->returns($session_manager);
        stub($user_manager)->getTokenManager()->returns($token_manager);
        stub($user_manager)->getForgeUserGroupPermissionsManager()->returns($permissions_manager);

        $dao->shouldReceive('searchByUserName')
            ->with('user_123')
            ->andReturn(TestHelper::arrayToDar(['user_name' => 'user_123', 'user_id' => 123]));
        $dao->shouldReceive('getUserAccessInfo')->andReturn(['nb_auth_failure' => 0, 'last_auth_success' => 0]);
        $user_manager->setReturnReference('getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));

        $dao->shouldNotReceive('storeLoginFailure');
        $dao->shouldReceive('storeLoginSuccess');

        $user_manager->setReturnReference('getDao', $dao);
        $this->assertEqual($user123, $user_manager->login('user_123', self::PASSWORD, 0));
    }

    public function testBadLogin()
    {
        $cm               = Mockery::mock(\Tuleap\CookieManager::class);
        $dao              = new MockUserDao($this);
        $dar              = new MockDataAccessResult($this);
        $user123          = mock('PFUser');
        $userAnonymous    = mock('PFUser');
        $um               = new UserManagerTestVersion($this);

        $um->setReturnValue('_getEventManager', \Mockery::spy(EventManager::class));

        $user123->setReturnValue('getId', 123);
        $user123->setReturnValue('getUserName', 'user_123');
        $user123->setReturnValue('getUserPw', md5('pwd'));
        $user123->setReturnValue('getLegacyUserPw', '');
        $user123->setReturnValue('isAnonymous', false);
        $user123->expectNever('setSessionHash');

        $userAnonymous->setReturnValue('getId', 0);
        $userAnonymous->setReturnValue('isAnonymous', true);

        $cm->shouldReceive('setCookie')->never();
        $um->setReturnReference('getCookieManager', $cm);

        $dao->setReturnReference('searchByUserName', $dar, array('user_123'));
        $dar->setReturnValue('getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $um->setReturnReference('getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        $um->setReturnReference('getUserInstanceFromRow', $userAnonymous, array(array('user_id' => 0)));

        $dao->expectOnce('storeLoginFailure');

        $um->setReturnReference('getDao', $dao);
        $this->assertEqual($userAnonymous, $um->login('user_123', 'bad_pwd', 0));
    }

    public function testSuspenedUserGetSession()
    {

        $cm               = Mockery::mock(\Tuleap\CookieManager::class);
        $session_manager  = mock('Tuleap\User\SessionManager');
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

        $cm->shouldReceive('getCookie')->andReturn('valid_hash');
        $dar_valid_hash->setReturnValue('getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $um->setReturnReference('getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        $um->setReturnReference('getUserInstanceFromRow', $userAnonymous, array(array('user_id' => 0)));
        stub($session_manager)->getUser()->returns($user123);

        $dao->expectNever('storeLastAccessDate');
        $session_manager->expectOnce('destroyAllSessions', array($user123));

        $um->setReturnReference('getDao', $dao);
        $um->setReturnReference('getCookieManager', $cm);
        $um->setReturnReference('getSessionManager', $session_manager);

        $user = $um->getCurrentUser();
        $this->assertTrue($user->isAnonymous(), 'A suspended user should not be able to use a valid session');
    }

    public function testDeletedUserGetSession()
    {
        $cm               = Mockery::mock(\Tuleap\CookieManager::class);
        $session_manager  = mock('Tuleap\User\SessionManager');
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

        $cm->shouldReceive('getCookie')->andReturn('valid_hash');
        $dar_valid_hash->setReturnValue('getRow', array('user_name' => 'user_123', 'user_id' => 123));
        $um->setReturnReference('getUserInstanceFromRow', $user123, array(array('user_name' => 'user_123', 'user_id' => 123)));
        $um->setReturnReference('getUserInstanceFromRow', $userAnonymous, array(array('user_id' => 0)));
        stub($session_manager)->getUser()->returns($user123);

        $dao->expectNever('storeLastAccessDate');
        stub($session_manager)->expectOnce('destroyAllSessions', array($user123));

        $um->setReturnReference('getDao', $dao);
        $um->setReturnReference('getCookieManager', $cm);
        $um->setReturnReference('getSessionManager', $session_manager);

        $user = $um->getCurrentUser();
        $this->assertTrue($user->isAnonymous(), 'A deleted user should not be able to use a valid session');
    }

    public function testGetUserByIdentifierPluginNoAnswerWithSimpleId()
    {
        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent');

        $um = new UserManager4GetByIdent($this);
        $um->setReturnReference('_getEventManager', $em);

        $um->expectOnce('getUserByUserName');
        $um->setReturnValue('getUserByUserName', null);

        $user = $um->getUserByIdentifier('test');
        $this->assertNull($user);
    }

    public function testGetUserByIdentifierPluginAnswerWithSimpleId()
    {
        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent');

        $um = new UserManager4GetByIdent($this);
        $um->setReturnReference('_getEventManager', $em);

        $um->expectOnce('getUserByUserName');
        $u1 = mock('PFUser');
        $um->setReturnReference('getUserByUserName', $u1);

        $user = $um->getUserByIdentifier('test');
        $this->assertIdentical($user, $u1);
    }

    public function testGetUserByIdentifierPluginNoAnswerWithComplexId()
    {
        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent');

        $um = new UserManager4GetByIdent($this);
        $um->setReturnReference('_getEventManager', $em);

        $um->expectNever('getUserByUserName');

        $user = $um->getUserByIdentifier('plugin:test');
        $this->assertNull($user);
    }

    public function testGetUserByIdentifierPluginAnswer()
    {
        $u1 = mock('PFUser');

        $em = new class($u1) extends EventManager {

            private $user;

            public function __construct(PFUser $user)
            {
                $this->user = $user;
            }

            public function processEvent($event, $params = array())
            {
                $params['tokenFound'] = true;
                $params['user'] = $this->user;
            }
        };

        $um = new UserManager4GetByIdent($this);
        $um->setReturnReference('_getEventManager', $em);

        $um->expectNever('getUserByUserName');

        $user = $um->getUserByIdentifier('test');
        $this->assertIdentical($user, $u1);
    }

    public function testGetUserByIdentifierPluginAnswerNotFound()
    {
        $u1 = mock('PFUser');

        $em = new class extends EventManager {
            public function processEvent($event, $params = array())
            {
                $params['tokenFound'] = false;
            }
        };

        $um = new UserManager4GetByIdent($this);
        $um->setReturnReference('_getEventManager', $em);

        $um->expectOnce('getUserByUserName');
        $um->setReturnValue('getUserByUserName', null);

        $user = $um->getUserByIdentifier('test');
        $this->assertNull($user);
    }

    public function testUpdateFailureWhenAnonymous()
    {
        $user = mock('PFUser');
        $user->setReturnValue('isAnonymous', true);

        $dao = new MockUserDao($this);
        $um = new UserManagerTestVersion($this);
        $um->setReturnReference('getDao', $dao);
        $this->assertFalse($um->updateDb($user));
    }

    public function testUpdateDaoResultPropagated()
    {
        $user = mock('PFUser');
        $user->setReturnValue('isAnonymous', false);
        $user->setReturnValue('isSuspended', false);
        $user->setReturnValue('isDeleted', false);
        $user->setReturnValue('toRow', array());


        // True
        $daotrue = Mockery::mock(UserDao::class);
        $daotrue->shouldReceive('updateByRow')->andReturns(true);
        $daotrue->shouldReceive('searchByUserId')->andReturns(new DataAccessResultEmpty());
        $session_manager_true = mock('Tuleap\User\SessionManager');
        $session_manager_true->expectNever('destroyAllSessions');
        $umtrue = new UserManagerTestVersion($this);
        stub($umtrue)->_getEventManager()->returns(\Mockery::spy(\EventManager::class));
        stub($umtrue)->getSessionManager()->returns($session_manager_true);
        $umtrue->setReturnReference('getDao', $daotrue);
        $this->assertTrue($umtrue->updateDb($user));

        // False
        $daofalse = Mockery::mock(UserDao::class);
        $daofalse->shouldReceive('updateByRow')->andReturns(false);
        $daofalse->shouldReceive('searchByUserId')->andReturns(new DataAccessResultEmpty());
        $session_manager_false = mock('Tuleap\User\SessionManager');
        $session_manager_false->expectNever('destroyAllSessions');
        $umfalse = new UserManagerTestVersion($this);
        stub($umfalse)->_getEventManager()->returns(\Mockery::mock(\EventManager::class));
        stub($umfalse)->getSessionManager()->returns($session_manager_false);
        $umfalse->setReturnReference('getDao', $daofalse);
        $this->assertFalse($umfalse->updateDb($user));
    }

    public function testUpdatePassword()
    {
        $user = mock('PFUser');
        $user->setReturnValue('isAnonymous', false);
        $user->setReturnValue('toRow', array());
        $user->setReturnValue('getPassword', self::PASSWORD);
        $user->setReturnValue('getUserPw', 'mustfail');

        $dao = Mockery::mock(UserDao::class);
        $dao->shouldReceive('updateByRow')->with(['clear_password' => self::PASSWORD, 'user_pw' => ''])->once()->andReturns(false);
        $dao->shouldReceive('searchByUserId')->andReturns(new DataAccessResultEmpty());

        $um = new UserManagerTestVersion($this);
        $um->setReturnReference('getDao', $dao);
        $um->updateDb($user);
    }

    public function testUpdateNoPasswordChange()
    {
        $password_handler = PasswordHandlerFactory::getPasswordHandler();
        $user             = mock('PFUser');
        $user->setReturnValue('isAnonymous', false);
        $user->setReturnValue('toRow', array());
        $user->setReturnValue('getPassword', self::PASSWORD);
        $user->setReturnValue('getUserPw', $password_handler->computeHashPassword(self::PASSWORD));

        $dao = Mockery::mock(UserDao::class);
        $dao->shouldReceive('updateByRow')->with(['user_pw' => ''])->once()->andReturns(false);
        $dao->shouldReceive('searchByUserId')->andReturns(new DataAccessResultEmpty());

        $um = new UserManagerTestVersion($this);
        $um->setReturnReference('getDao', $dao);
        $um->updateDb($user);
    }

    public function testUpdateToSuspendedDeleteSessions()
    {
        $user = mock('PFUser');
        $user->setReturnValue('getId', 123);
        $user->setReturnValue('isAnonymous', false);
        $user->setReturnValue('isSuspended', true);
        $user->setReturnValue('toRow', array());

        $dao = Mockery::mock(UserDao::class);
        $dao->shouldReceive('updateByRow')->andReturns(true);
        $dao->shouldReceive('searchByUserId')->andReturns(new DataAccessResultEmpty());

        $session_manager = mock('Tuleap\User\SessionManager');
        $session_manager->expectOnce('destroyAllSessions', array($user));

        $um = new UserManagerTestVersion($this);
        stub($um)->_getEventManager()->returns(\Mockery::spy(\EventManager::class));
        stub($um)->getSessionManager()->returns($session_manager);
        $um->setReturnReference('getDao', $dao);

        $this->assertTrue($um->updateDb($user));
    }

    public function testUpdateToDeletedDeleteSessions()
    {
        $user = mock('PFUser');
        $user->setReturnValue('getId', 123);
        $user->setReturnValue('isAnonymous', false);
        $user->setReturnValue('isDeleted', true);
        $user->setReturnValue('toRow', array());

        $dao = Mockery::mock(UserDao::class);
        $dao->shouldReceive('updateByRow')->andReturns(true);
        $dao->shouldReceive('searchByUserId')->andReturns(new DataAccessResultEmpty());

        $session_manager = mock('Tuleap\User\SessionManager');
        $session_manager->expectOnce('destroyAllSessions', array($user));

        $um = new UserManagerTestVersion($this);
        stub($um)->_getEventManager()->returns(\Mockery::spy(\EventManager::class));
        stub($um)->getSessionManager()->returns($session_manager);
        $um->setReturnReference('getDao', $dao);

        $this->assertTrue($um->updateDb($user));
    }

    public function testAssignNextUnixUidUpdateUser()
    {
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
    public function testLoginAsCallsGetCurrentUser()
    {
        $ordinaryUser = mock('PFUser');
        $ordinaryUser->setReturnValue('isSuperUser', false);
        $um = $this->aUserManagerWithCurrentUser($ordinaryUser);

        $um->expectOnce('getCurrentUser', array());

        $this->expectException('UserNotAuthorizedException');
        $um->loginAs(null);
    }

    public function testLoginAsReturnsAnExceptionWhenNotCallByTheSuperUser()
    {
        $hash_is_not_important = null;
        $ordinaryUser = mock('PFUser');
        $ordinaryUser->setReturnValue('isSuperUser', false);
        $um = $this->aUserManagerWithCurrentUser($ordinaryUser);

        $this->expectException('UserNotAuthorizedException');
        $um->loginAs('tlkjtj');
    }

    public function testLoginAsReturnsAnExceptionWhenAccountDoesNotExist()
    {
        $um = TestHelper::getPartialMock('UserManager', array('getCurrentUser', 'getUserByUserName'));
        $admin_user = $this->anAdminUser();
        $um->setReturnValue('getCurrentUser', $admin_user);

        $name = 'toto';
        $um->setReturnValue('getUserByUserName', null, array($name));

        $this->expectException('UserNotExistException');
        $um->loginAs($name);
    }

    public function testLoginAsReturnsAnExceptionWhenAccountIsNotInOrder()
    {
        $um = $this->aUserManagerWithCurrentUser($this->anAdminUser());
        $this->injectUser($um, 'Johnny', 'D');

        $this->expectException('UserNotActiveException');
        $um->loginAs('Johnny');
    }

    private function aUserWithStatusAndId($status, $id)
    {
        $userLoginAs = mock('PFUser');
        $userLoginAs->setReturnValue('getStatus', $status);
        $userLoginAs->setReturnValue('getId', $id);
        return $userLoginAs;
    }

    private function aUserManagerWithCurrentUser($user)
    {
        $um = TestHelper::getPartialMock('UserManager', array('getCurrentUser'));
        $um->setReturnValue('getCurrentUser', $user);
        return $um;
    }

    private function injectUser(UserManager $um, $name, $status)
    {
        $whatever = 999;
        $user = $this->aUserWithStatusAndId($status, $whatever);
        $um->_userid_bynames[$name] = $user->getId();
        $um->_users[$user->getId()] = $user;
        return $user;
    }

    private function anAdminUser()
    {
        $adminUser = mock('PFUser');
        $adminUser->setReturnValue('isSuperUser', true);
        return $adminUser;
    }

    public function itInsuresThatPluginsDoNotReceiveInvalidUsernameWhenFindingUser()
    {
        $user_manager  = TestHelper::getPartialMock('UserManager', array('_getEventManager'));
        $event_manager = \Mockery::mock(\EventManager::class);
        stub($user_manager)->_getEventManager()->returns($event_manager);

        $event_manager->shouldNotReceive('processEvent');
        $this->assertEqual($user_manager->findUser(null), null);
        $this->assertEqual($user_manager->findUser(false), null);
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses,PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class UserManager_GetUserWithSSHKeyTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->user_name = 'toto';

        $data_access = mock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        $data_array  = array('user_name' => $this->user_name);
        stub($data_access)->fetch()->returns($data_array);
        stub($data_access)->numRows()->returns(1);
        $result = new stdClass();
        $this->dar = new DataAccessResult($data_access, $result);
    }

    public function itReturnsTheListOfUsers()
    {
        $dao = stub('UserDao')->searchSSHKeys()->returns($this->dar);

        $user_manager = partial_mock('UserManager', array('getDao'));
        stub($user_manager)->getDao()->returns($dao);

        $users = $user_manager->getUsersWithSshKey();
        $this->assertEqual($users->getRow()->getUserName(), $this->user_name);
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,PSR1.Classes.ClassDeclaration.MultipleClasses,Squiz.Classes.ValidClassName.NotCamelCaps
class UserManager_GetInstanceFromRowEventsTest extends TuleapTestCase
{

    private $event_manager;
    private $user_manager;

    public function setUp()
    {
        parent::setUp();
        $this->event_manager = \Mockery::mock(\EventManager::class);
        $this->user_manager = partial_mock('UserManager', array());
        EventManager::setInstance($this->event_manager);
    }

    public function tearDown()
    {
        parent::tearDown();
        EventManager::clearInstance();
    }

    public function itDoesNotFailsWhenRowIsNull()
    {
        $this->assertEqual(
            $this->user_manager->getUserInstanceFromRow(null),
            new PFUser()
        );
    }

    public function itThrowsAnEventOnUserWithSpecialUserIds()
    {
        expect($this->event_manager)->processEvent(Event::USER_MANAGER_GET_USER_INSTANCE, '*')->once();

        $this->user_manager->getUserInstanceFromRow(array("user_id" => 90));
    }

    public function itDoesNotThrowsAnEventOnUserWithoutSpecialUserIds()
    {
        expect($this->event_manager)->processEvent()->never();

        $this->user_manager->getUserInstanceFromRow(array("user_id" => 200));
    }

    public function itThrowsAnEventWithUserRowAsParameter()
    {
        $user_row = array('user_id' => 90);

        $user = null;
        $expected_parameter = array(
            'row'  => $user_row,
            'user' => &$user,
        );
        expect($this->event_manager)->processEvent(Event::USER_MANAGER_GET_USER_INSTANCE, $expected_parameter)->once();

        $this->user_manager->getUserInstanceFromRow($user_row);
    }

    public function itReturnsAPFUserWhenNothingMatches()
    {
        $this->event_manager->shouldReceive('processEvent');
        $user_row = array('user_id' => 90);

        $this->assertEqual(
            $this->user_manager->getUserInstanceFromRow($user_row),
            new PFUser($user_row)
        );
    }

    public function itPassUserByReference()
    {
        $user_row = array('user_id' => 90);

        $event_manager = new EventManager();
        $event_manager->addListener(Event::USER_MANAGER_GET_USER_INSTANCE, $this, 'mockedMethodForEventTest', false);
        EventManager::setInstance($event_manager);
        $result_expected = 'thatValue';

        $result = $this->user_manager->getUserInstanceFromRow($user_row);
        $this->assertEqual($result, $result_expected);
    }

    public function mockedMethodForEventTest(array $params)
    {
        $params['user'] = 'thatValue';
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,PSR1.Classes.ClassDeclaration.MultipleClasses,Squiz.Classes.ValidClassName.NotCamelCaps
class UserManager_ManageSSHKeys extends TuleapTestCase
{

    /** @var UserManager */
    private $user_manager;

    /** @var PFUser */
    private $user;

    public function setUp()
    {
        parent::setUp();

        $dao = mock('UserDao');

        $this->user_manager = partial_mock(
            'UserManager',
            array('getDao', 'updateUserSSHKeys')
        );
        stub($this->user_manager)->getDao()->returns($dao);

        $this->user = aUser()->withId(101)->build();

        $this->an_ssh_key               = "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQC7pihW/WsGL8Pmk89ET/x1GTa646GWs/7DHujgxfP4ZH7mt6ta+KwH2tsEj5ESS19EIYG4hQYpckpd65fgihs7SrwLEVG3yO1gZSS+4bBfGaR/zQoFRNlJHiKh9vrr3AZZxCUUM4xpMi2wT4hBlr8lgYaxCQZpgXRqI6CSUSAVDM7e6Ct4zItmp7VqFLHTv7pljeIF+VTyoDWfMSaIBbDmmnZctR9hR3ywSmokvA9iN4a5bWjeXlIQdpjcjqapolvlo2XamN7HRTfxWefFceoVX3yVjTZ7DFkbHJdqwBMIQmAMbG633dx67dQLgeAKfWu/tGbCnalnzzeuMvU9b40f comment";
        $this->an_ssh_key_with_new_line = "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQC7pihW/WsGL8Pmk89ET/x1GTa646GWs/7DHujgxfP4ZH7mt6ta+KwH2tsEj5ESS19EIYG4hQYpckpd65fgihs7SrwLEVG3yO1gZSS+4bBfGaR/zQoFRNlJHiKh9vrr3AZZxCUUM4xpMi2wT4hBlr8lgYaxCQZpgXRqI6CSUSAVDM7e6Ct4zItmp7VqFLHTv7pljeIF+VTyoDWfMSaIBbDmmnZctR9hR3ywSmokvA9iN4a5bWjeXlIQdpjcjqapolvlo2XamN7HRTfxWefFceoVX3yVjTZ7DFkbHJdqwBMIQmAMbG633dx67dQLgeAKfWu/tGbCnalnzzeuMvU9b40f comment" . PHP_EOL;
        $this->a_second_ssh_key         = "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQC7pihW/WsGL8Pmk89ET/x1GTa646GWs/7DHujgxfP4ZH7mt6ta+KwH2tsEj5ESS19EIYG4hQYpckpd65fgihs7SrwLEVG3yO1gZSS+4bBfGaR/zQoFRNlJHiKh9vrr3AZZxCUUM4xpMi2wT4hBlr8lgYaxCQZpgXRqI6CSUSAVDM7e6Ct4zItmp7VqFLHTv7pljeIF+VTyoDWfMSaIBbDmmnZctR9hR3ywSmokvA9iN4a5bWjeXlIQdpjcjqapolvlo2XamN7HRTfxWefFceoVX3yVjTZ7DFkbHJdqwBMIQmAMbG633dx67dQLgeAKfWu/tGbCnalnzzeuMvU9b41y comment2";
    }

    public function itAddsANewSSHKey()
    {
        $user_ssh_keys   = 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQC7pihW/WsGL8Pmk89ET/x1GTa646GWs/7DHujgxfP4ZH7mt6ta+KwH2tsEj5ESS19EIYG4hQYpckpd65fgihs7SrwLEVG3yO1gZSS+4bBfGaR/zQoFRNlJHiKh9vrr3AZZxCUUM4xpMi2wT4hBlr8lgYaxCQZpgXRqI6CSUSAVDM7e6Ct4zItmp7VqFLHTv7pljeIF+VTyoDWfMSaIBbDmmnZctR9hR3ywSmokvA9iN4a5bWjeXlIQdpjcjqapolvlo2XamN7HRTfxWefFceoVX3yVjTZ7DFkbHJdqwBMIQmAMbG633dx67dQLgeAKfWu/tGbCnalnzzeuMvU9b4oF comment1';
        $expected_keys   = array(
            $user_ssh_keys,
            $this->an_ssh_key
        );

        $this->user->setAuthorizedKeys($user_ssh_keys);

        expect($this->user_manager)->updateUserSSHKeys($this->user, $expected_keys)->once();

        $this->user_manager->addSSHKeys($this->user, $this->an_ssh_key);
    }

    public function itOnlyAddsANewSSHKeyIfTheNewKeyEndedWithANewLineChar()
    {
        $user_ssh_keys   = 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQC7pihW/WsGL8Pmk89ET/x1GTa646GWs/7DHujgxfP4ZH7mt6ta+KwH2tsEj5ESS19EIYG4hQYpckpd65fgihs7SrwLEVG3yO1gZSS+4bBfGaR/zQoFRNlJHiKh9vrr3AZZxCUUM4xpMi2wT4hBlr8lgYaxCQZpgXRqI6CSUSAVDM7e6Ct4zItmp7VqFLHTv7pljeIF+VTyoDWfMSaIBbDmmnZctR9hR3ywSmokvA9iN4a5bWjeXlIQdpjcjqapolvlo2XamN7HRTfxWefFceoVX3yVjTZ7DFkbHJdqwBMIQmAMbG633dx67dQLgeAKfWu/tGbCnalnzzeuMvU9b4oF comment1';
        $expected_keys   = array(
           $user_ssh_keys,
           $this->an_ssh_key
        );

        $this->user->setAuthorizedKeys($user_ssh_keys);

        expect($this->user_manager)->updateUserSSHKeys($this->user, $expected_keys)->once();
        expect($GLOBALS['Response'])->addFeedback('warning', '*')->never();

        $this->user_manager->addSSHKeys($this->user, $this->an_ssh_key_with_new_line);
    }

    public function itAddsMultipleNewSSHKeys()
    {
        $user_ssh_keys   = 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQC7pihW/WsGL8Pmk89ET/x1GTa646GWs/7DHujgxfP4ZH7mt6ta+KwH2tsEj5ESS19EIYG4hQYpckpd65fgihs7SrwLEVG3yO1gZSS+4bBfGaR/zQoFRNlJHiKh9vrr3AZZxCUUM4xpMi2wT4hBlr8lgYaxCQZpgXRqI6CSUSAVDM7e6Ct4zItmp7VqFLHTv7pljeIF+VTyoDWfMSaIBbDmmnZctR9hR3ywSmokvA9iN4a5bWjeXlIQdpjcjqapolvlo2XamN7HRTfxWefFceoVX3yVjTZ7DFkbHJdqwBMIQmAMbG633dx67dQLgeAKfWu/tGbCnalnzzeuMvU9b4oF comment1';
        $expected_keys   = array(
            $user_ssh_keys,
            $this->an_ssh_key,
            $this->a_second_ssh_key,
        );

        $this->user->setAuthorizedKeys($user_ssh_keys);

        expect($this->user_manager)->updateUserSSHKeys($this->user, $expected_keys)->once();

        $this->user_manager->addSSHKeys($this->user, $this->an_ssh_key . PHP_EOL . $this->a_second_ssh_key);
    }

    public function itDoesNotAddAnExistingSSHKey()
    {
        $user_ssh_keys = "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQC7pihW/WsGL8Pmk89ET/x1GTa646GWs/7DHujgxfP4ZH7mt6ta+KwH2tsEj5ESS19EIYG4hQYpckpd65fgihs7SrwLEVG3yO1gZSS+4bBfGaR/zQoFRNlJHiKh9vrr3AZZxCUUM4xpMi2wT4hBlr8lgYaxCQZpgXRqI6CSUSAVDM7e6Ct4zItmp7VqFLHTv7pljeIF+VTyoDWfMSaIBbDmmnZctR9hR3ywSmokvA9iN4a5bWjeXlIQdpjcjqapolvlo2XamN7HRTfxWefFceoVX3yVjTZ7DFkbHJdqwBMIQmAMbG633dx67dQLgeAKfWu/tGbCnalnzzeuMvU9b4oF comment1 ###" . $this->an_ssh_key;

        $expected_keys = array(
            "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQC7pihW/WsGL8Pmk89ET/x1GTa646GWs/7DHujgxfP4ZH7mt6ta+KwH2tsEj5ESS19EIYG4hQYpckpd65fgihs7SrwLEVG3yO1gZSS+4bBfGaR/zQoFRNlJHiKh9vrr3AZZxCUUM4xpMi2wT4hBlr8lgYaxCQZpgXRqI6CSUSAVDM7e6Ct4zItmp7VqFLHTv7pljeIF+VTyoDWfMSaIBbDmmnZctR9hR3ywSmokvA9iN4a5bWjeXlIQdpjcjqapolvlo2XamN7HRTfxWefFceoVX3yVjTZ7DFkbHJdqwBMIQmAMbG633dx67dQLgeAKfWu/tGbCnalnzzeuMvU9b4oF comment1",
            $this->an_ssh_key
        );

        $this->user->setAuthorizedKeys($user_ssh_keys);

        expect($this->user_manager)->updateUserSSHKeys($this->user, $expected_keys)->once();

        $this->user_manager->addSSHKeys($this->user, $this->an_ssh_key);
    }

    public function itRemovesSelectedSSHKeys()
    {
        $user_ssh_keys =
           'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQC7pihW/WsGL8Pmk89ET/x1GTa646GWs/7DHujgxfP4ZH7mt6ta+KwH2tsEj5ESS19EIYG4hQYpckpd65fgihs7SrwLEVG3yO1gZSS+4bBfGaR/zQoFRNlJHiKh9vrr3AZZxCUUM4xpMi2wT4hBlr8lgYaxCQZpgXRqI6CSUSAVDM7e6Ct4zItmp7VqFLHTv7pljeIF+VTyoDWfMSaIBbDmmnZctR9hR3ywSmokvA9iN4a5bWjeXlIQdpjcjqapolvlo2XamN7HRTfxWefFceoVX3yVjTZ7DFkbHJdqwBMIQmAMbG633dx67dQLgeAKfWu/tGbCnalnzzeuMvU9b4oF comment1'
           . PFUser::SSH_KEY_SEPARATOR . $this->an_ssh_key;

        $ssh_keys_to_delete_index = array(0);

        $this->user->setAuthorizedKeys($user_ssh_keys);

        expect($this->user_manager)->updateUserSSHKeys($this->user, array($this->an_ssh_key))->once();

        $this->user_manager->deleteSSHKeys($this->user, $ssh_keys_to_delete_index);
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,PSR1.Classes.ClassDeclaration.MultipleClasses,Squiz.Classes.ValidClassName.NotCamelCaps
class UserManager_createAccountTest extends TuleapTestCase
{

    /** @var UserManager */
    private $manager;

    /** @var UserDao */
    private $dao;

    /** @var PFUser */
    private $user;

    /** @var User_PendingUserNotifier */
    private $pending_user_notifier;

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
        $this->user                  = aUser()->build();
        $this->dao                   = mock('UserDao');
        $this->pending_user_notifier = mock('User_PendingUserNotifier');

        $this->manager = partial_mock(
            'UserManager',
            array('getDefaultWidgetCreator'),
            array($this->pending_user_notifier)
        );
        $this->manager->setDao($this->dao);

        $default_widget_creator = mock('Tuleap\Dashboard\User\AtUserCreationDefaultWidgetsCreator');
        stub($this->manager)->getDefaultWidgetCreator()->returns($default_widget_creator);
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function itAsksToDaoToCreateTheAccount()
    {
        expect($this->dao)->create()->once();

        $this->manager->createAccount($this->user);
    }

    public function itSendsAnEmailToAdministratorIfUserIsPendingAndUserNeedsApproval()
    {
        stub($this->dao)->create()->returns(101);
        $this->user->setStatus(PFUser::STATUS_PENDING);
        ForgeConfig::set('sys_user_approval', 1);

        expect($this->pending_user_notifier)->notifyAdministrator($this->user)->once();

        $this->manager->createAccount($this->user);
    }

    public function itDoesNotSendAnEmailToAdministratorIfNoUserApproval()
    {
        stub($this->dao)->create()->returns(101);
        $this->user->setStatus(PFUser::STATUS_PENDING);
        ForgeConfig::set('sys_user_approval', 0);

        expect($this->pending_user_notifier)->notifyAdministrator()->never();

        $this->manager->createAccount($this->user);
    }

    public function itDoesNotSendAnEmailToAdministratorIfUserIsActive()
    {
        stub($this->dao)->create()->returns(101);
        $this->user->setStatus(PFUser::STATUS_ACTIVE);

        expect($this->pending_user_notifier)->notifyAdministrator()->never();

        $this->manager->createAccount($this->user);
    }
}
