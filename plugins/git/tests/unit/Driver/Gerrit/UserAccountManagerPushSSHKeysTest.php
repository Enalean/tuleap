<?php
/**
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
 */

namespace Tuleap\Git\Driver\Gerrit;

use Git_Driver_Gerrit;
use Git_Driver_Gerrit_Exception;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_User;
use Git_Driver_Gerrit_UserAccountManager;
use Git_RemoteServer_GerritServer;
use Git_RemoteServer_GerritServerFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class UserAccountManagerPushSSHKeysTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private PFUser $user;
    /**
     * @var Git_Driver_Gerrit&\Mockery\MockInterface
     */
    private $gerrit_driver;
    /**
     * @var \Mockery\MockInterface&Git_Driver_Gerrit_GerritDriverFactory
     */
    private $gerrit_driver_factory;
    /**
     * @var Git_RemoteServer_GerritServerFactory&\Mockery\MockInterface
     */
    private $remote_gerrit_factory;
    /**
     * @var Git_Driver_Gerrit_User&\Mockery\MockInterface
     */
    private $gerrit_user;
    /**
     * @var \Mockery\MockInterface&Git_Driver_Gerrit_UserAccountManager
     */
    private $user_account_manager;
    /**
     * @var Git_RemoteServer_GerritServer&\Mockery\MockInterface
     */
    private $remote_server1;
    /**
     * @var Git_RemoteServer_GerritServer&\Mockery\MockInterface
     */
    private $remote_server2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = new PFUser([
            'language_id' => 'en',
            'ldap_id'     => 'testUser',
        ]);
        $key1       = 'key1';
        $key2       = 'key2';

        $this->user->setAuthorizedKeys($key1 . PFUser::SSH_KEY_SEPARATOR . $key2);

        $this->gerrit_driver         = \Mockery::spy(\Git_Driver_Gerrit::class);
        $this->gerrit_driver_factory = \Mockery::spy(\Git_Driver_Gerrit_GerritDriverFactory::class)->shouldReceive('getDriver')->andReturns($this->gerrit_driver)->getMock();
        $this->remote_gerrit_factory = \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class);
        $this->gerrit_user           = \Mockery::spy(\Git_Driver_Gerrit_User::class);

        $this->user_account_manager = \Mockery::mock(
            \Git_Driver_Gerrit_UserAccountManager::class,
            [$this->gerrit_driver_factory, $this->remote_gerrit_factory]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->user_account_manager->shouldReceive('getGerritUser')->andReturns($this->gerrit_user);

        $this->remote_server1 = \Mockery::spy(\Git_RemoteServer_GerritServer::class);
        $this->remote_server2 = \Mockery::spy(\Git_RemoteServer_GerritServer::class);

        $this->remote_gerrit_factory->shouldReceive('getRemoteServersForUser')->with($this->user)->andReturns([
            $this->remote_server1,
            $this->remote_server2,
        ]);
    }

    public function testItDoesntPushIfUserHasNoRemoteServers(): void
    {
        $remote_gerrit_factory = \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class)->shouldReceive('getRemoteServersForUser')->with($this->user)->andReturns([])->getMock();

        $user_account_manager = new Git_Driver_Gerrit_UserAccountManager($this->gerrit_driver_factory, $remote_gerrit_factory);
        $this->gerrit_driver->shouldReceive('addSSHKeyToAccount')->never();
        $this->gerrit_driver->shouldReceive('removeSSHKeyFromAccount')->never();

        $user_account_manager->pushSSHKeys($this->user);
    }

    public function testItDoesntPushIfUserHasNoKeys(): void
    {
        $this->user->setAuthorizedKeys('');

        $this->remote_gerrit_factory->shouldReceive('getRemoteServersForUser')->with($this->user)->never();
        $this->gerrit_driver->shouldReceive('addSSHKeyToAccount')->never();
        $this->gerrit_driver->shouldReceive('removeSSHKeyFromAccount')->never();

        $this->user_account_manager->pushSSHKeys($this->user);
    }

    public function testItCallsTheDriverToAddAndRemoveKeysTheRightNumberOfTimes(): void
    {
        $pushed_keys = [
            'Im a new key',
            'Im another new key',
        ];

        $this->gerrit_driver->shouldReceive('addSSHKeyToAccount')->with($this->remote_server1, $this->user, $pushed_keys[1]);
        $this->gerrit_driver->shouldReceive('addSSHKeyToAccount')->with($this->remote_server2, $this->user, $pushed_keys[1]);
        $this->gerrit_driver->shouldReceive('addSSHKeyToAccount')->with($this->remote_server1, $this->user, $pushed_keys[0]);
        $this->gerrit_driver->shouldReceive('addSSHKeyToAccount')->with($this->remote_server2, $this->user, $pushed_keys[0]);

        $this->gerrit_driver->shouldReceive('removeSSHKeyFromAccount')->with($this->remote_server1, $this->user, $pushed_keys[0]);
        $this->gerrit_driver->shouldReceive('removeSSHKeyFromAccount')->with($this->remote_server2, $this->user, $pushed_keys[0]);
        $this->gerrit_driver->shouldReceive('removeSSHKeyFromAccount')->with($this->remote_server1, $this->user, $pushed_keys[1]);
        $this->gerrit_driver->shouldReceive('removeSSHKeyFromAccount')->with($this->remote_server2, $this->user, $pushed_keys[1]);

        //for each remote server
        $this->gerrit_driver->shouldReceive('addSSHKeyToAccount')->times(4);
        $this->gerrit_driver->shouldReceive('removeSSHKeyFromAccount')->times(4);

        $this->user_account_manager->pushSSHKeys($this->user);
    }

    public function testItThrowsAnExceptionIfGerritDriverFails(): void
    {
        $this->gerrit_driver->shouldReceive('addSSHKeyToAccount')->andThrows(new Git_Driver_Gerrit_Exception());

        $this->expectException(\Git_UserSynchronisationException::class);
        $this->user_account_manager->pushSSHKeys($this->user);
    }
}
