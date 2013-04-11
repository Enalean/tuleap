<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once 'bootstrap.php';

class GitPlugin_PropagateUserKeysToGerritTest extends TuleapTestCase {
    
    private $plugin;
    private $user_account_manager;
    private $gerrit_server_factory;
    private $logger;
    private $user;

    public function setUp() {
        parent::setUp();

        $id = 456;
        $mocked_methods = array(
            'getUserAccountManager',
            'getGerritServerFactory'
        );
        $this->plugin = partial_mock('GitPlugin', $mocked_methods, array($id));

        $this->user_account_manager = mock('Git_UserAccountManager');
        stub($this->plugin)->getUserAccountManager()->returns($this->user_account_manager);

        $this->gerrit_server_factory = mock('Git_RemoteServer_GerritServerFactory');
        stub($this->plugin)->getGerritServerFactory()->returns($this->gerrit_server_factory);

        $this->logger = mock('BackendLogger');
        $this->plugin->setLogger($this->logger);

        $this->user = mock('PFUser');
    }

    public function testItLogsAnErrorIfNoUserIsPassed() {
        $params = array(
            'original_keys' => '',
        );
        
        expect($this->logger)->error()->once();
        $this->plugin->propagateUserKeysToGerrit($params);
    }

    public function testItLogsAnErrorIfUserIsInvalid() {
        $params = array(
            'user' => 'me',
            'original_keys' => '',
        );

        expect($this->logger)->error()->once();
        $this->plugin->propagateUserKeysToGerrit($params);
    }

    public function itTransformsEmptyKeyStringIntoArrayBeforeSendingToGitUserManager() {
        $original_keys = array();
        $new_keys = array();

        $params = array(
            'user'          => $this->user,
            'original_keys' => '',
        );

        stub($this->user)->getAuthorizedKeysArray()->returns($new_keys);

        expect($this->logger)->error()->never();
        expect($this->user_account_manager)->synchroniseSSHKeys(
                $original_keys,
                $new_keys,
                $this->user
            )->once();

        $this->plugin->propagateUserKeysToGerrit($params);
    }

    public function itTransformsNonEmptyKeyStringIntoArrayBeforeSendingToGitUserManager() {
        $new_keys      = array();
        $original_keys = array(
            'abcdefg',
            'wxyz',
        );

        $params = array(
            'user'          => $this->user,
            'original_keys' => 'abcdefg'.PFUser::SSH_KEY_SEPARATOR.'wxyz',
        );

        stub($this->user)->getAuthorizedKeysArray()->returns($new_keys);

        expect($this->logger)->error()->never();
        expect($this->user_account_manager)->synchroniseSSHKeys(
                $original_keys,
                $new_keys,
                $this->user
            )->once();

        $this->plugin->propagateUserKeysToGerrit($params);
    }

    public function itLogsAnErrorIfSSHKeySynchFails() {
        $params = array(
            'user'          => $this->user,
            'original_keys' => '',
        );

        $this->user_account_manager->throwOn('synchroniseSSHKeys', new Git_UserSynchronisationException());
        
        expect($this->logger)->error()->once();

        $this->plugin->propagateUserKeysToGerrit($params);
    }
}


class GitPlugin_PushUserSSHKeysToRemoteServersTest extends TuleapTestCase {

    private $plugin;
    private $user_account_manager;
    private $gerrit_server_factory;
    private $logger;
    private $user;

    public function setUp() {
        parent::setUp();

        $id = 456;
        $mocked_methods = array(
            'getUserAccountManager',
            'getGerritServerFactory'
        );
        $this->plugin = partial_mock('GitPlugin', $mocked_methods, array($id));

        $this->user_account_manager = mock('Git_UserAccountManager');
        stub($this->plugin)->getUserAccountManager()->returns($this->user_account_manager);

        $this->gerrit_server_factory = mock('Git_RemoteServer_GerritServerFactory');
        stub($this->plugin)->getGerritServerFactory()->returns($this->gerrit_server_factory);

        $this->logger = mock('BackendLogger');
        $this->plugin->setLogger($this->logger);

        $this->user = mock('PFUser');
    }

    public function testItLogsAnErrorIfNoUserIsPassed() {
        $params = array();

        expect($this->logger)->error()->once();
        $this->plugin->pushUserSSHKeysToRemoteServers($params);
    }

    public function testItLogsAnErrorIfUserIsInvalid() {
        $params = array(
            'user' => 'me',
        );

        expect($this->logger)->error()->once();
        $this->plugin->pushUserSSHKeysToRemoteServers($params);
    }

    public function itLogsAnErrorIfSSHKeyPushFails() {
        $params = array(
            'user' => $this->user,
        );

        $this->user_account_manager->throwOn('pushSSHKeys', new Git_UserSynchronisationException());

        expect($this->logger)->error()->once();

        $this->plugin->pushUserSSHKeysToRemoteServers($params);
    }

    public function itAddsResponseFeedbackIfSSHKeyPushFails() {
        $params = array(
            'user' => $this->user,
        );

        $this->user_account_manager->throwOn('pushSSHKeys', new Git_UserSynchronisationException());

        $response = mock('Response');
        $GLOBALS['Response'] = $response;
        expect($response)->addFeedback()->once();

        $this->plugin->pushUserSSHKeysToRemoteServers($params);
    }
}

?>
