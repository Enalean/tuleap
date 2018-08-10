<?php
/**
 * Copyright (c) Enalean, 2013-2018. All Rights Reserved.
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

class GitPlugin_GetRemoteServersForUserTest extends TuleapTestCase {

    /**
     *
     * @var GitPlugin
     */
    private $plugin;
    private $user_account_manager;
    private $gerrit_server_factory;
    private $logger;
    private $user;

    public function setUp() {
        parent::setUp();

        $id = 456;
        $mocked_methods = array(
            'getGerritServerFactory'
        );
        $this->plugin = partial_mock('GitPlugin', $mocked_methods, array($id));

        $this->user_account_manager = mock('Git_UserAccountManager');
        $this->plugin->setUserAccountManager($this->user_account_manager);

        $this->gerrit_server_factory = mock('Git_RemoteServer_GerritServerFactory');
        stub($this->plugin)->getGerritServerFactory()->returns($this->gerrit_server_factory);

        $this->logger = mock('BackendLogger');
        $this->plugin->setLogger($this->logger);

        $this->user = mock('PFUser');

        $_POST['ssh_key_push'] = true;
    }

    public function testItDoesNotPushKeysIfNoUserIsPassed() {
        $params = array(
            'html' => '',
        );

        expect($this->user_account_manager)->pushSSHKeys()->never();
        $this->plugin->getRemoteServersForUser($params);
    }

    public function tesItDoesNotPushKeysIfUserIsInvalid() {
        $params = array(
            'user' => 'me',
            'html' => '',
        );

        expect($this->user_account_manager)->pushSSHKeys()->never();
        $this->plugin->getRemoteServersForUser($params);
    }

    public function itLogsAnErrorIfSSHKeyPushFails() {
        $params = array(
            'user' => $this->user,
            'html' => '',
        );
        
        $this->user_account_manager->throwOn('pushSSHKeys', new Git_UserSynchronisationException());

        expect($this->logger)->error()->once();

        stub($this->gerrit_server_factory)->getRemoteServersForUser()->returns([]);

        $this->plugin->getRemoteServersForUser($params);
    }

    public function itAddsResponseFeedbackIfSSHKeyPushFails() {
        $params = array(
            'user' => $this->user,
            'html' => '',
        );

        $this->user_account_manager->throwOn('pushSSHKeys', new Git_UserSynchronisationException());

        $response = mock('Response');
        $GLOBALS['Response'] = $response;
        expect($response)->addFeedback()->once();

        stub($this->gerrit_server_factory)->getRemoteServersForUser()->returns([]);

        $this->plugin->getRemoteServersForUser($params);
    }
}

class GitPlugin_Post_System_Events extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $id = 456;
        $mocked_methods = array(
            'getGitSystemEventManager',
            'getGitoliteDriver',
            'getLogger',
        );
        $this->plugin               = partial_mock('GitPlugin', $mocked_methods, array($id));
        $this->system_event_manager = mock('Git_SystemEventManager');
        $this->gitolite_driver      = mock('Git_GitoliteDriver');

        stub($this->plugin)->getGitSystemEventManager()->returns($this->system_event_manager);
        stub($this->plugin)->getGitoliteDriver()->returns($this->gitolite_driver);
        stub($this->plugin)->getLogger()->returns(mock('TruncateLevelLogger'));
    }

    public function itProcessGrokmirrorManifestUpdateInPostSystemEventsActions() {
        expect($this->gitolite_driver)
            ->commit()
            ->once();

        expect($this->gitolite_driver)
            ->push()
            ->once();

        $params = array(
            'executed_events_ids' => array(125),
            'queue_name' => 'git'
        );

        $this->plugin->post_system_events_actions($params);
    }

    public function itDoesNotProcessPostSystemEventsActionsIfNotGitRelated() {
        expect($this->gitolite_driver)
        ->commit()
        ->never();

        expect($this->gitolite_driver)
        ->push()
        ->never();

        $params = array(
            'executed_events_ids' => array(54156),
            'queue_name'          => 'owner'
        );

        $this->plugin->post_system_events_actions($params);
    }
}
