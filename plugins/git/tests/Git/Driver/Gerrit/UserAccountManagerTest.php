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

require_once dirname(__FILE__) .'/../../../../include/constants.php';
require_once GIT_BASE_DIR.'/Git/Driver/Gerrit/UserAccountManager.class.php';

class Git_Driver_Gerrit_UserAccountManagerTest extends TuleapTestCase {

    public function itThrowsAnExceptionIfUserIsNotLDAP() {
        $user = mock('PFUser');
        $gerrit_driver = mock('Git_Driver_Gerrit');
        stub($user)->isLDAP()->returns(false);

        $this->expectException('Git_Driver_Gerrit_InvalidLDAPUserException');
        new Git_Driver_Gerrit_UserAccountManager($user, $gerrit_driver);
    }

    public function itInstanciateAUserAccountManager() {
        $user = aUser()->withLdapId('testUser')->build();
        $gerrit_driver = mock('Git_Driver_Gerrit');

        $user_account_manager = new Git_Driver_Gerrit_UserAccountManager($user, $gerrit_driver);
        $this->assertIsA($user_account_manager, 'Git_Driver_Gerrit_UserAccountManager');
    }
}

class Git_Driver_Gerrit_UserAccountManager_SynchroniseSSHKeysTest extends TuleapTestCase {
    private $user;
    private $gerrit_driver;
    private $remote_gerrit_factory;
    private $original_keys;
    private $new_keys;
    private $remote_server1;
    private $remote_server2;
    /**
     * @var Git_Driver_Gerrit_UserAccountManager
     */
    private $user_account_manager;

    public function setUp() {
        parent::setUp();
        $this->user                  = aUser()->withLdapId("testUser")->build();
        $this->gerrit_driver         = mock('Git_Driver_Gerrit');
        $this->remote_gerrit_factory = mock('Git_RemoteServer_GerritServerFactory');
        $this->user_account_manager  = new Git_Driver_Gerrit_UserAccountManager($this->user, $this->gerrit_driver);

        $this->original_keys = array(
            'Im a key',
            'Im another key',
            'Im an identical key',
            'Im an additional key',
        );

        $this->new_keys = array(
            'Im a new key',
            'Im another new key',
            'Im an identical key',
        );

        $this->remote_server1 = mock('Git_RemoteServer_GerritServer');
        $this->remote_server2 = mock('Git_RemoteServer_GerritServer');

        stub($this->remote_gerrit_factory)->getRemoteServersForUser($this->user)->returns(array($this->remote_server1, $this->remote_server2));

    }

    public function itCallsRemoteServerFactory() {
        expect($this->remote_gerrit_factory)->getRemoteServersForUser($this->user)->once();
        $this->user_account_manager->synchroniseSSHKeys($this->original_keys, $this->new_keys, $this->remote_gerrit_factory);
    }

    public function itDoesntSynchroniseIfUserHasNoRemoteServers() {
        $remote_gerrit_factory = stub('Git_RemoteServer_GerritServerFactory')->getRemoteServersForUser($this->user)->returns(array());

        expect($this->gerrit_driver)->addSSHKeyToAccount()->never();
        expect($this->gerrit_driver)->removeSSHKeyFromAccount()->never();

        $this->user_account_manager->synchroniseSSHKeys($this->original_keys, $this->new_keys, $remote_gerrit_factory);
    }

    public function itDoesntSynchroniseIfKeysAreTheSame() {
        $original_keys = array();
        $new_keys      = array();

        expect($this->remote_gerrit_factory)->getRemoteServersForUser($this->user)->never();
        expect($this->gerrit_driver)->addSSHKeyToAccount()->never();
        expect($this->gerrit_driver)->removeSSHKeyFromAccount()->never();

        $this->user_account_manager->synchroniseSSHKeys($original_keys, $new_keys, $this->remote_gerrit_factory);
    }

    public function itCallsTheDriverToAddAndRemoveKeysTheRightNumberOfTimes() {
        expect($this->remote_gerrit_factory)->getRemoteServersForUser($this->user)->once();
        expect($this->gerrit_driver)->addSSHKeyToAccount()->count(4);
        expect($this->gerrit_driver)->removeSSHKeyFromAccount()->count(6);

        $this->user_account_manager->synchroniseSSHKeys($this->original_keys, $this->new_keys, $this->remote_gerrit_factory);
    }
}
?>
