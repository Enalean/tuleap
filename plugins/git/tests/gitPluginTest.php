<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

class GitPlugin_GetRemoteServersForUserTest extends TuleapTestCase
{

    /**
     *
     * @var GitPlugin
     */
    private $plugin;
    private $user_account_manager;
    private $gerrit_server_factory;
    private $logger;
    private $user;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $id = 456;
        $mocked_methods = array(
            'getGerritServerFactory'
        );
        $this->plugin = \Mockery::mock(\GitPlugin::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $this->user_account_manager = \Mockery::spy(\Git_UserAccountManager::class);
        $this->plugin->setUserAccountManager($this->user_account_manager);

        $this->gerrit_server_factory = \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class);
        $this->plugin->shouldReceive('getGerritServerFactory')->andReturns($this->gerrit_server_factory);

        $this->logger = \Mockery::spy(\BackendLogger::class);
        $this->plugin->setLogger($this->logger);

        $this->user = \Mockery::spy(\PFUser::class);

        $_POST['ssh_key_push'] = true;
    }

    public function testItDoesNotPushKeysIfNoUserIsPassed()
    {
        $params = array(
            'html' => '',
        );

        $this->user_account_manager->shouldReceive('pushSSHKeys')->never();
        $this->plugin->getRemoteServersForUser($params);
    }

    public function tesItDoesNotPushKeysIfUserIsInvalid()
    {
        $params = array(
            'user' => 'me',
            'html' => '',
        );

        $this->user_account_manager->shouldReceive('pushSSHKeys')->never();
        $this->plugin->getRemoteServersForUser($params);
    }

    public function itLogsAnErrorIfSSHKeyPushFails()
    {
        $params = array(
            'user' => $this->user,
            'html' => '',
        );

        $this->user_account_manager->shouldReceive('pushSSHKeys')->andThrows(new Git_UserSynchronisationException());

        $this->logger->shouldReceive('error')->once();

        $this->gerrit_server_factory->shouldReceive('getRemoteServersForUser')->andReturns([]);

        $this->plugin->getRemoteServersForUser($params);
    }

    public function itAddsResponseFeedbackIfSSHKeyPushFails()
    {
        $params = array(
            'user' => $this->user,
            'html' => '',
        );

        $this->user_account_manager->shouldReceive('pushSSHKeys')->andThrows(new Git_UserSynchronisationException());

        $response = \Mockery::spy(\Response::class);
        $GLOBALS['Response'] = $response;
        $response->shouldReceive('addFeedback')->once();

        $this->gerrit_server_factory->shouldReceive('getRemoteServersForUser')->andReturns([]);

        $this->plugin->getRemoteServersForUser($params);
    }
}

class GitPlugin_Post_System_Events extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $id = 456;
        $mocked_methods = array(
            'getGitSystemEventManager',
            'getGitoliteDriver',
            'getLogger',
        );
        $this->plugin               = \Mockery::mock(\GitPlugin::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->system_event_manager = \Mockery::spy(\Git_SystemEventManager::class);
        $this->gitolite_driver      = \Mockery::spy(\Git_GitoliteDriver::class);

        $this->plugin->shouldReceive('getGitSystemEventManager')->andReturns($this->system_event_manager);
        $this->plugin->shouldReceive('getGitoliteDriver')->andReturns($this->gitolite_driver);
        $this->plugin->shouldReceive('getLogger')->andReturns(\Mockery::spy(\TruncateLevelLogger::class));
    }

    public function itProcessGrokmirrorManifestUpdateInPostSystemEventsActions()
    {
        $this->gitolite_driver->shouldReceive('commit')
            ->once();

        $this->gitolite_driver->shouldReceive('push')
            ->once();

        $params = array(
            'executed_events_ids' => array(125),
            'queue_name' => 'git'
        );

        $this->plugin->post_system_events_actions($params);
    }

    public function itDoesNotProcessPostSystemEventsActionsIfNotGitRelated()
    {
        $this->gitolite_driver->shouldReceive('commit')
        ->never();

        $this->gitolite_driver->shouldReceive('push')
        ->never();

        $params = array(
            'executed_events_ids' => array(54156),
            'queue_name'          => 'owner'
        );

        $this->plugin->post_system_events_actions($params);
    }
}
