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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalResponseMock;

require_once 'bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitPluginGetRemoteServersForUserTest extends TestCase
{
    use MockeryPHPUnitIntegration, GlobalResponseMock;

    /**
     *
     * @var GitPlugin
     */
    private $plugin;
    private $user_account_manager;
    private $gerrit_server_factory;
    private $logger;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plugin = \Mockery::mock(\GitPlugin::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $this->user_account_manager = \Mockery::spy(\Git_UserAccountManager::class);
        $this->plugin->setUserAccountManager($this->user_account_manager);

        $this->gerrit_server_factory = \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class);
        $this->plugin->shouldReceive('getGerritServerFactory')->andReturns($this->gerrit_server_factory);

        $this->logger = \Mockery::spy(\Psr\Log\LoggerInterface::class);
        $this->plugin->setLogger($this->logger);

        $this->user = \Mockery::spy(\PFUser::class);

        $_POST['ssh_key_push'] = true;
    }

    protected function tearDown(): void
    {
        unset($_POST['ssh_key_push']);
        parent::tearDown();
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

    public function testItLogsAnErrorIfSSHKeyPushFails()
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

    public function testItAddsResponseFeedbackIfSSHKeyPushFails()
    {
        $params = array(
            'user' => $this->user,
            'html' => '',
        );

        $this->user_account_manager->shouldReceive('pushSSHKeys')->andThrows(new Git_UserSynchronisationException());

        $GLOBALS['Response']->shouldReceive('addFeedback')->once();

        $this->gerrit_server_factory->shouldReceive('getRemoteServersForUser')->andReturns([]);

        $this->plugin->getRemoteServersForUser($params);
    }
}
