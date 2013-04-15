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

require_once dirname(__FILE__).'/../bootstrap.php';

class Git_UserAccountManager_SynchroniseSSHKeysTest extends TuleapTestCase {
    private $user;
    private $gerrit_driver;
    private $remote_gerrit_factory;
    private $original_keys;
    private $new_keys;

    /**
     * @var Git_UserAccountManager
     */
    private $user_account_manager;
    /**
     * @var Git_Driver_Gerrit_UserAccountManager
     */
    private $gerrit_user_account_manager;

    public function setUp() {
        parent::setUp();
        $this->user                  = aUser()->withLdapId("testUser")->build();
        $this->gerrit_driver         = mock('Git_Driver_Gerrit');
        $this->remote_gerrit_factory = mock('Git_RemoteServer_GerritServerFactory');
        $this->gerrit_user_account_manager  = mock('Git_Driver_Gerrit_UserAccountManager');

        $this->user_account_manager = new Git_UserAccountManager($this->gerrit_driver, $this->remote_gerrit_factory);
        $this->user_account_manager->setGerritUserAccountManager($this->gerrit_user_account_manager);

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
    }

    public function itThrowsAnExceptionIfGerritSynchFails() {
        expect($this->gerrit_user_account_manager)->synchroniseSSHKeys()->once();

        $this->gerrit_user_account_manager->throwOn('synchroniseSSHKeys', new Git_UserSynchronisationException());

        $this->expectException('Git_UserSynchronisationException');

        $this->user_account_manager->synchroniseSSHKeys(
            $this->original_keys,
            $this->new_keys,
            $this->user
        );
    }
}

class Git_UserAccountManager_PushSSHKeysTest extends TuleapTestCase {
    /** @var PFUser */
    private $user;
    private $gerrit_driver;
    private $remote_gerrit_factory;
    /**
     * @var Git_UserAccountManager
     */
    private $user_account_manager;
    /**
     * @var Git_Driver_Gerrit_UserAccountManager
     */
    private $gerrit_user_account_manager;

    public function setUp() {
        parent::setUp();

        $this->user = aUser()->withLdapId("testUser")->build();

        $this->gerrit_driver                = mock('Git_Driver_Gerrit');
        $this->remote_gerrit_factory        = mock('Git_RemoteServer_GerritServerFactory');
        $this->user_account_manager         = new Git_UserAccountManager($this->gerrit_driver, $this->remote_gerrit_factory);
        $this->gerrit_user_account_manager  = mock('Git_Driver_Gerrit_UserAccountManager');

        $this->user_account_manager->setGerritUserAccountManager($this->gerrit_user_account_manager);
    }

    public function itThrowsAnExceptionIfGerritPushFails() {
        expect($this->gerrit_user_account_manager)->pushSSHKeys()->once();

        $this->gerrit_user_account_manager->throwOn('pushSSHKeys', new Git_UserSynchronisationException());

        $this->expectException('Git_UserSynchronisationException');

        $this->user_account_manager->pushSSHKeys(
            $this->user
        );
    }
}
?>
