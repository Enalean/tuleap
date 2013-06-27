<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 */

require_once dirname(__FILE__).'/../../../bootstrap.php';
require_once dirname(__FILE__).'/../../../../../ldap/include/LDAP_User.class.php';
require_once dirname(__FILE__).'/../../../../../ldap/include/LDAPResult.class.php';

class Git_Driver_Gerrit_UserAccountManager_SynchroniseSSHKeysTest extends TuleapTestCase {
    private $user;
    private $gerrit_driver;
    private $remote_gerrit_factory;
    private $original_keys;
    private $new_keys;
    private $remote_server1;
    private $remote_server2;
    /**
     * @var Git_Driver_Gerrit_User
     */
    private $gerrit_user;
    /**
     * @var Git_Driver_Gerrit_UserAccountManager
     */
    private $user_account_manager;

    public function setUp() {
        parent::setUp();
        $this->user                  = aUser()->withLdapId("testUser")->build();
        $this->gerrit_driver         = mock('Git_Driver_Gerrit');
        $this->remote_gerrit_factory = mock('Git_RemoteServer_GerritServerFactory');
        $this->gerrit_user = mock('Git_Driver_Gerrit_User');

        $this->user_account_manager  = partial_mock(
            'Git_Driver_Gerrit_UserAccountManager',
            array('getGerritUser'),
            array($this->gerrit_driver, $this->remote_gerrit_factory)
        );
        stub($this->user_account_manager)->getGerritUser()->returns($this->gerrit_user);

        $this->original_keys = array(
            'Im a key',
            'Im a key',
            'Im another key',
            'Im an identical key',
            'Im an additional key',
        );

        $this->new_keys = array(
            'Im a new key',
            'Im another new key',
            'Im another new key',
            'Im an identical key',
        );

        $this->remote_server1 = mock('Git_RemoteServer_GerritServer');
        $this->remote_server2 = mock('Git_RemoteServer_GerritServer');

        stub($this->remote_gerrit_factory)->getRemoteServersForUser($this->user)->returns(array($this->remote_server1, $this->remote_server2));
    }

    public function itCallsRemoteServerFactory() {
        expect($this->remote_gerrit_factory)->getRemoteServersForUser($this->user)->once();
        $this->user_account_manager->synchroniseSSHKeys($this->original_keys, $this->new_keys, $this->user);
    }

    public function itDoesntSynchroniseIfUserHasNoRemoteServers() {
        $remote_gerrit_factory = stub('Git_RemoteServer_GerritServerFactory')->getRemoteServersForUser($this->user)->returns(array());

        expect($this->gerrit_driver)->addSSHKeyToAccount()->never();
        expect($this->gerrit_driver)->removeSSHKeyFromAccount()->never();

        $user_account_manager = new Git_Driver_Gerrit_UserAccountManager($this->gerrit_driver, $remote_gerrit_factory);

        $user_account_manager->synchroniseSSHKeys($this->original_keys, $this->new_keys, $this->user);
    }

    public function itDoesntSynchroniseIfKeysAreTheSame() {
        $original_keys = array();
        $new_keys      = array();

        expect($this->remote_gerrit_factory)->getRemoteServersForUser($this->user)->never();
        expect($this->gerrit_driver)->addSSHKeyToAccount()->never();
        expect($this->gerrit_driver)->removeSSHKeyFromAccount()->never();

        $this->user_account_manager->synchroniseSSHKeys($original_keys, $new_keys, $this->user);
    }

    public function itCallsTheDriverToAddAndRemoveKeysTheRightNumberOfTimes() {
        $added_keys = array(
            'Im a new key',
            'Im another new key',
        );

        $removed_keys = array(
            'Im a key',
            'Im another key',
            'Im an additional key',
        );
        
        expect($this->remote_gerrit_factory)->getRemoteServersForUser($this->user)->once();
        
        expect($this->gerrit_driver)->addSSHKeyToAccount($this->remote_server1, $this->user, $added_keys[1]);
        expect($this->gerrit_driver)->addSSHKeyToAccount($this->remote_server2, $this->user, $added_keys[1]);
        expect($this->gerrit_driver)->addSSHKeyToAccount($this->remote_server1, $this->user, $added_keys[0]);
        expect($this->gerrit_driver)->addSSHKeyToAccount($this->remote_server2, $this->user, $added_keys[0]);

        expect($this->gerrit_driver)->removeSSHKeyFromAccount($this->remote_server1, $this->user, $removed_keys[0]);
        expect($this->gerrit_driver)->removeSSHKeyFromAccount($this->remote_server2, $this->user, $removed_keys[0]);
        expect($this->gerrit_driver)->removeSSHKeyFromAccount($this->remote_server1, $this->user, $removed_keys[1]);
        expect($this->gerrit_driver)->removeSSHKeyFromAccount($this->remote_server2, $this->user, $removed_keys[1]);
        expect($this->gerrit_driver)->removeSSHKeyFromAccount($this->remote_server1, $this->user, $removed_keys[2]);
        expect($this->gerrit_driver)->removeSSHKeyFromAccount($this->remote_server2, $this->user, $removed_keys[2]);

        $this->user_account_manager->synchroniseSSHKeys($this->original_keys, $this->new_keys, $this->user);
    }

    public function itThrowsAnExceptionIfGerritDriverFails() {
        $this->gerrit_driver->throwOn('addSSHKeyToAccount', new Git_Driver_Gerrit_RemoteSSHCommandFailure());

        $this->expectException('Git_UserSynchronisationException');
        $this->user_account_manager->synchroniseSSHKeys($this->original_keys, $this->new_keys, $this->user);
    }
}

class Git_Driver_Gerrit_UserAccountManager_PushSSHKeysTest extends TuleapTestCase {
    /** @var PFUser */
    private $user;
    private $gerrit_driver;
    private $remote_gerrit_factory;
    private $remote_server1;
    private $remote_server2;
    /**
     * @var Git_Driver_Gerrit_User
     */
    private $gerrit_user;
    /**
     * @var Git_Driver_Gerrit_UserAccountManager
     */
    private $user_account_manager;

    public function setUp() {
        parent::setUp();

        $this->user                  = aUser()->withLdapId("testUser")->build();
        $key1 = 'key1';
        $key2 = 'key2';

        $this->user->setAuthorizedKeys($key1.PFUser::SSH_KEY_SEPARATOR.$key2);

        $this->gerrit_driver         = mock('Git_Driver_Gerrit');
        $this->remote_gerrit_factory = mock('Git_RemoteServer_GerritServerFactory');
        $this->gerrit_user = mock('Git_Driver_Gerrit_User');

        $this->user_account_manager  = partial_mock(
            'Git_Driver_Gerrit_UserAccountManager',
            array('getGerritUser'),
            array($this->gerrit_driver, $this->remote_gerrit_factory)
        );
        stub($this->user_account_manager)->getGerritUser()->returns($this->gerrit_user);

        $this->remote_server1 = mock('Git_RemoteServer_GerritServer');
        $this->remote_server2 = mock('Git_RemoteServer_GerritServer');

        stub($this->remote_gerrit_factory)
            ->getRemoteServersForUser($this->user)
                ->returns(
                    array(
                        $this->remote_server1,
                        $this->remote_server2,
                    )
                );
    }

    public function itDoesntPushIfUserHasNoRemoteServers() {
        $remote_gerrit_factory = stub('Git_RemoteServer_GerritServerFactory')->getRemoteServersForUser($this->user)->returns(array());

        $user_account_manager = new Git_Driver_Gerrit_UserAccountManager($this->gerrit_driver, $remote_gerrit_factory);
        expect($this->gerrit_driver)->addSSHKeyToAccount()->never();
        expect($this->gerrit_driver)->removeSSHKeyFromAccount()->never();

        $user_account_manager->pushSSHKeys($this->user);
    }

    public function itDoesntPushIfUserHasNoKeys() {
        $this->user->setAuthorizedKeys('');

        expect($this->remote_gerrit_factory)->getRemoteServersForUser($this->user)->never();
        expect($this->gerrit_driver)->addSSHKeyToAccount()->never();
        expect($this->gerrit_driver)->removeSSHKeyFromAccount()->never();

        $this->user_account_manager->pushSSHKeys($this->user);
    }

    public function itCallsTheDriverToAddAndRemoveKeysTheRightNumberOfTimes() {
        $pushed_keys = array(
            'Im a new key',
            'Im another new key',
        );

        expect($this->remote_gerrit_factory)->getRemoteServersForUser($this->user)->once();

        expect($this->gerrit_driver)->addSSHKeyToAccount($this->remote_server1, $this->user, $pushed_keys[1]);
        expect($this->gerrit_driver)->addSSHKeyToAccount($this->remote_server2, $this->user, $pushed_keys[1]);
        expect($this->gerrit_driver)->addSSHKeyToAccount($this->remote_server1, $this->user, $pushed_keys[0]);
        expect($this->gerrit_driver)->addSSHKeyToAccount($this->remote_server2, $this->user, $pushed_keys[0]);

        expect($this->gerrit_driver)->removeSSHKeyFromAccount($this->remote_server1, $this->user, $pushed_keys[0]);
        expect($this->gerrit_driver)->removeSSHKeyFromAccount($this->remote_server2, $this->user, $pushed_keys[0]);
        expect($this->gerrit_driver)->removeSSHKeyFromAccount($this->remote_server1, $this->user, $pushed_keys[1]);
        expect($this->gerrit_driver)->removeSSHKeyFromAccount($this->remote_server2, $this->user, $pushed_keys[1]);

        //for each remote server
        expect($this->gerrit_driver)->addSSHKeyToAccount()->count(4);
        expect($this->gerrit_driver)->removeSSHKeyFromAccount()->count(4);

        $this->user_account_manager->pushSSHKeys($this->user);
    }

    public function itThrowsAnExceptionIfGerritDriverFails() {
        $this->gerrit_driver->throwOn('addSSHKeyToAccount', new Git_Driver_Gerrit_RemoteSSHCommandFailure());

        $this->expectException('Git_UserSynchronisationException');
        $this->user_account_manager->pushSSHKeys($this->user);
    }
}

class Git_Driver_Gerrit_UserAccountManager_GetGerritUserTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $event_manager = new EventManager();
        $event_manager->addListener(Event::GET_LDAP_LOGIN_NAME_FOR_USER, $this, 'hookReturnsLdapUser', false, 0);
        EventManager::setInstance($event_manager);

        $this->ldap_login  = 'bla blo';
        $this->ldap_result = stub('LDAPResult')->getLogin()->returns($this->ldap_login);
    }

    public function tearDown() {
        EventManager::clearInstance();
        parent::tearDown();
    }

    public function hookReturnsLdapUser($params) {
        $params['ldap_user'] = new LDAP_User($params['user'], $this->ldap_result);
    }

    public function itCreatesGerritUserFromLdapUser() {
        $user_manager = new Git_Driver_Gerrit_UserAccountManager(
            mock('Git_Driver_Gerrit'),
            mock('Git_RemoteServer_GerritServerFactory')
        );

        $gerrit_user = $user_manager->getGerritUser(mock('PFUser'));
        $this->assertEqual($gerrit_user->getWebUserName(), $this->ldap_login);
    }
}

?>
