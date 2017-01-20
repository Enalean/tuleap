<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2008.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'bootstrap.php';

Mock::generatePartial('LDAP_UserManager', 'LDAP_UserManagerGenerateLogin', array('getLoginFromString', 'userNameIsAvailable'));
Mock::generate('LDAP');
Mock::generate('LDAP_UserDao');
Mock::generate('PFUser');
Mock::generate('BackendSVN');
Mock::generate('SystemEventManager');


class LDAP_UserManagerTest extends TuleapTestCase {

    function testGetLoginFromString() {
        $ldap = new MockLDAP($this);
        $lum = new LDAP_UserManager($ldap, mock('LDAP_UserSync'));
        
        $this->assertEqual($lum->getLoginFromString('coincoin'), 'coincoin');
        
        $this->assertEqual($lum->getLoginFromString('coin coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin.coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin:coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin;coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin,coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin?coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin%coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin^coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin*coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin(coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin)coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin{coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin}coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin[coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin]coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin<coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin>coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin+coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin=coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin$coin'), 'coin_coin');
        $this->assertEqual($lum->getLoginFromString('coin\ coin'), 'coin_coin');
        
        $this->assertEqual($lum->getLoginFromString("coincoin'"), 'coincoin');
        $this->assertEqual($lum->getLoginFromString('coincoin"'), 'coincoin');
        $this->assertEqual($lum->getLoginFromString('coin/coin'), 'coincoin');
        
        // Accent test
        $this->assertEqual($lum->getLoginFromString('coiné'), 'coine');

        // getLoginFromString only accept utf8 strings.
        //$this->assertEqual($lum->getLoginFromString(utf8_decode('coiné')), 'coine');
    }
    
    function testGenerateLoginNotAlreadyUsed() {
        $lum = new LDAP_UserManagerGenerateLogin($this);
        
        $lum->setReturnValue('getLoginFromString', 'john');
        $lum->setReturnValue('userNameIsAvailable', true);
        
        $this->assertEqual($lum->generateLogin('john'), 'john');
    }
    
    function testGenerateLoginAlreadyUsed() {
        $lum = new LDAP_UserManagerGenerateLogin($this);
        
        $lum->setReturnValue('getLoginFromString', 'john');
        $lum->setReturnValueAt(0, 'userNameIsAvailable', false);
        $lum->setReturnValueAt(1, 'userNameIsAvailable', true);
        
        $this->assertEqual($lum->generateLogin('john'), 'john2');
    }
    
    function testUpdateLdapUidShouldPrepareRenameOfUserInTheWholePlatform() {
        // Parameters
        $user = mock('PFUser');
        $user->setReturnValue('getId', 105);
        $ldap_uid = 'johndoe';
        
        $lum  = TestHelper::getPartialMock('LDAP_UserManager', array('getDao', 'getBackendSVN'));
        
        $dao = new MockLDAP_UserDao();
        $dao->expectOnce('updateLdapUid', array(105, $ldap_uid));
        $dao->setReturnValue('updateLdapUid', true);
        $lum->setReturnValue('getDao', $dao);
        
        $this->assertTrue($lum->updateLdapUid($user, $ldap_uid));
        $this->assertEqual($lum->getUsersToRename(), array($user));
    }
    
    function testTriggerRenameOfUsersShouldUpdateSVNAccessFileOfProjectWhereTheUserIsMember() {
        // Parameters
        $user = mock('PFUser');
        $user->setReturnValue('getId', 105);
        
        $lum  = TestHelper::getPartialMock('LDAP_UserManager', array('getSystemEventManager'));
        
        $sem = new MockSystemEventManager();
        $sem->expectOnce('createEvent', array('PLUGIN_LDAP_UPDATE_LOGIN',
                                              '105',
                                              SystemEvent::PRIORITY_MEDIUM));
        $lum->setReturnValue('getSystemEventManager', $sem);
        
        $lum->addUserToRename($user);
        
        $lum->triggerRenameOfUsers();
    }
    
    function testTriggerRenameOfUsersWithSeveralUsers() {
        // Parameters
        $user1 = mock('PFUser');
        $user1->setReturnValue('getId', 101);
        $user2 = mock('PFUser');
        $user2->setReturnValue('getId', 102);
        $user3 = mock('PFUser');
        $user3->setReturnValue('getId', 103);
        
        $lum  = TestHelper::getPartialMock('LDAP_UserManager', array('getSystemEventManager'));
        
        $sem = new MockSystemEventManager();
        $sem->expectOnce('createEvent', array('PLUGIN_LDAP_UPDATE_LOGIN',
                                              '101'.SystemEvent::PARAMETER_SEPARATOR.'102'.SystemEvent::PARAMETER_SEPARATOR.'103',
                                              SystemEvent::PRIORITY_MEDIUM));
        $lum->setReturnValue('getSystemEventManager', $sem);
        
        $lum->addUserToRename($user1);
        $lum->addUserToRename($user2);
        $lum->addUserToRename($user3);
        
        $lum->triggerRenameOfUsers();
    }
    
    function testTriggerRenameOfUsersWithoutUser() {
        $lum = TestHelper::getPartialMock('LDAP_UserManager', array('getSystemEventManager'));
        
        $sem = new MockSystemEventManager();
        $sem->expectNever('createEvent');
        $lum->setReturnValue('getSystemEventManager', $sem);
        
        $lum->triggerRenameOfUsers();
    }
}

class LDAP_UserManager_AuthenticatTest extends TuleapTestCase {

    private $username    = 'toto';
    private $password    = 'welcome0';
    private $ldap_params = array(
        'dn'          => 'dc=tuleap,dc=local',
        'mail'        => 'mail',
        'cn'          => 'cn',
        'uid'         => 'uid',
        'eduid'       => 'uuid',
        'search_user' => '(|(uid=%words%)(cn=%words%)(mail=%words%))',
    );

    private $ldap;
    private $user_sync;
    private $ldap_user_manager;
    private $empty_ldap_result_iterator;
    private $john_mc_lane_result_iterator;

    public function setUp() {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('sys_logger_level', 'debug');
        $this->empty_ldap_result_iterator   = aLDAPResultIterator()->build();
        $this->john_mc_lane_result_iterator = aLDAPResultIterator()
            ->withParams($this->ldap_params)
            ->withInfo(
                array(
                    array(
                        'cn'   => 'John Mac Lane',
                        'uid'  => 'john_lane',
                        'mail' => 'john.mc.lane@nypd.gov',
                        'uuid' => 'ed1234',
                        'dn'   => 'uid=john_lane,ou=people,dc=tuleap,dc=local'
                    )
                )
            )
            ->build();
        $this->ldap = partial_mock(
            'LDAP',
            array('search', 'authenticate', 'searchLogin'),
            array($this->ldap_params, mock('TruncateLevelLogger'), new LdapQueryEscaper())
        );
        $this->user_sync         = mock('LDAP_UserSync');
        $this->user_manager      = mock('UserManager');
        $this->ldap_user_manager = partial_mock(
            'LDAP_UserManager',
            array('getUserManager', 'createAccountFromLdap', 'synchronizeUser'),
            array($this->ldap, $this->user_sync)
        );
        stub($this->ldap_user_manager)->getUserManager()->returns($this->user_manager);
    }

    public function tearDown() {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function itDelegatesAutenticateToLDAP() {
        stub($this->user_sync)->getSyncAttributes()->returns(array());
        stub($this->ldap)->authenticate()->returns(true);
        stub($this->ldap)->searchLogin()->returns($this->john_mc_lane_result_iterator);

        expect($this->ldap)->authenticate($this->username, $this->password)->once();

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function itRaisesAnExceptionIfAuthenticationFailed() {
        $this->expectException('LDAP_AuthenticationFailedException');

        stub($this->ldap)->authenticate()->returns(false);

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function itFetchLDAPUserInfoBasedOnLogin() {
        stub($this->user_sync)->getSyncAttributes()->returns(array());
        stub($this->ldap)->authenticate()->returns(true);
        stub($this->ldap)->searchLogin()->returns($this->john_mc_lane_result_iterator);

        expect($this->ldap)->searchLogin($this->username, '*')->once();

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function itFetchesLDAPUserInfoWithExtendedAttributesDefinedInUserSync() {
        $attributes = array('mail', 'cn', 'uid', 'uuid', 'dn', 'employeeType');
        stub($this->user_sync)->getSyncAttributes()->returns($attributes);
        stub($this->ldap)->authenticate()->returns(true);
        stub($this->ldap)->searchLogin()->returns($this->john_mc_lane_result_iterator);

        expect($this->ldap)->searchLogin('*', $attributes)->once();

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function itFetchesStandardLDAPInfosEvenWhenNotSpecifiedInSyncAttributes() {
        $attributes = array('employeeType');
        stub($this->user_sync)->getSyncAttributes()->returns($attributes);
        stub($this->ldap)->authenticate()->returns(true);
        stub($this->ldap)->searchLogin()->returns($this->john_mc_lane_result_iterator);

        expect($this->ldap)->searchLogin('*', array('mail', 'cn', 'uid', 'uuid', 'dn', 'employeeType'))->once();

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function itTriesToFindTheTuleapUserBasedOnLdapId() {
        stub($this->user_sync)->getSyncAttributes()->returns(array());
        stub($this->ldap)->authenticate()->returns(true);
        stub($this->ldap)->searchLogin()->returns($this->john_mc_lane_result_iterator);

        expect($this->user_manager)->getUserByLdapId('ed1234')->once();

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function itRaisesAnExceptionWhenLDAPUserIsNotFound() {
        stub($this->user_sync)->getSyncAttributes()->returns(array());
        stub($this->ldap)->authenticate()->returns(true);
        stub($this->ldap)->searchLogin()->returns($this->empty_ldap_result_iterator);

        $this->expectException('LDAP_UserNotFoundException');

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function itRaisesAnExceptionWhenSeveralLDAPUsersAreFound() {
        stub($this->user_sync)->getSyncAttributes()->returns(array());
        stub($this->ldap)->authenticate()->returns(true);
        stub($this->ldap)->searchLogin()->returns(
            aLDAPResultIterator()
            ->withParams($this->ldap_params)
            ->withInfo(
                array(
                    array(
                        'cn'   => 'John Mac Lane',
                        'uid'  => 'john_lane',
                        'mail' => 'john.mc.lane@nypd.gov',
                        'uuid' => 'ed1234',
                        'dn'   => 'uid=john_lane,ou=people,dc=tuleap,dc=local'
                    ),
                    array(
                        'cn'   => 'William Wallas',
                        'uid'  => 'will_wall',
                        'mail' => 'will_wall@edimburgh.co.uk',
                        'uuid' => 'ed5432',
                        'dn'   => 'uid=will_wall,ou=people,dc=tuleap,dc=local'
                    )
                )
            )
            ->build()
        );

        $this->expectException('LDAP_UserNotFoundException');

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function itCreatesUserAccountWhenUserDoesntExist() {
        stub($this->user_sync)->getSyncAttributes()->returns(array());
        stub($this->ldap)->authenticate()->returns(true);
        stub($this->ldap)->searchLogin()->returns($this->john_mc_lane_result_iterator);
        stub($this->user_manager)->getUserByLdapId()->returns(null);

        expect($this->ldap_user_manager)->createAccountFromLdap('*')->once();

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function itReturnsUserOnAccountCreation() {
        $expected_user = aUser()->withId(123)->build();
        stub($this->user_sync)->getSyncAttributes()->returns(array());
        stub($this->ldap)->authenticate()->returns(true);
        stub($this->ldap)->searchLogin()->returns($this->john_mc_lane_result_iterator);
        stub($this->user_manager)->getUserByLdapId()->returns(null);

        stub($this->ldap_user_manager)->createAccountFromLdap()->returns($expected_user);

        $user = $this->ldap_user_manager->authenticate($this->username, $this->password);
        $this->assertIdentical($user, $expected_user);
    }

    public function itUpdateUserAccountsIfAlreadyExists() {
        $expected_user = aUser()->withId(123)->build();
        stub($this->user_sync)->getSyncAttributes()->returns(array());
        stub($this->ldap)->authenticate()->returns(true);
        stub($this->ldap)->searchLogin()->returns($this->john_mc_lane_result_iterator);
        stub($this->user_manager)->getUserByLdapId()->returns($expected_user);

        expect($this->ldap_user_manager)->synchronizeUser(
            $expected_user,
            new LDAPResultExpectation('John Mac Lane'),
            $this->password
        )->once();

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function itReturnsUserOnAccountUpdate() {
        $expected_user = aUser()->withId(123)->build();
        stub($this->user_sync)->getSyncAttributes()->returns(array());
        stub($this->ldap)->authenticate()->returns(true);
        stub($this->ldap)->searchLogin()->returns($this->john_mc_lane_result_iterator);
        stub($this->user_manager)->getUserByLdapId()->returns($expected_user);

        stub($this->ldap_user_manager)->synchronizeUser()->returns(true);

        $user = $this->ldap_user_manager->authenticate($this->username, $this->password);
        $this->assertIdentical($user, $expected_user);
    }
}
