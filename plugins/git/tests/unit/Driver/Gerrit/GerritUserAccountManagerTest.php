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

use Git_Driver_Gerrit_Exception;
use Git_Driver_Gerrit_User;
use Git_Driver_Gerrit_UserAccountManager;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class GerritUserAccountManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private $user;
    private $gerrit_driver;
    private $gerrit_driver_factory;
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
    /**
     * @var string[]
     */
    private array $original_keys;
    /**
     * @var string[]
     */
    private array $new_keys;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user                  = new PFUser([
            'language_id' => 'en',
            'ldap_id'     => 'testUser',
        ]);
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

        $this->original_keys = [
            'Im a key',
            'Im a key',
            'Im another key',
            'Im an identical key',
            'Im an additional key',
        ];

        $this->new_keys = [
            'Im a new key',
            'Im another new key',
            'Im another new key',
            'Im an identical key',
        ];

        $this->remote_server1 = \Mockery::spy(\Git_RemoteServer_GerritServer::class);
        $this->remote_server2 = \Mockery::spy(\Git_RemoteServer_GerritServer::class);

        $this->remote_gerrit_factory->shouldReceive('getRemoteServersForUser')
            ->with($this->user)
            ->andReturns([$this->remote_server1, $this->remote_server2]);
    }

    public function testItCallsRemoteServerFactory(): void
    {
        $this->expectNotToPerformAssertions();
        $this->user_account_manager->synchroniseSSHKeys($this->original_keys, $this->new_keys, $this->user);
    }

    public function testItDoesntSynchroniseIfUserHasNoRemoteServers(): void
    {
        $remote_gerrit_factory = \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class)->shouldReceive('getRemoteServersForUser')->with($this->user)->andReturns([])->getMock();

        $this->gerrit_driver->shouldReceive('addSSHKeyToAccount')->never();
        $this->gerrit_driver->shouldReceive('removeSSHKeyFromAccount')->never();

        $user_account_manager = new Git_Driver_Gerrit_UserAccountManager($this->gerrit_driver_factory, $remote_gerrit_factory);

        $user_account_manager->synchroniseSSHKeys($this->original_keys, $this->new_keys, $this->user);
    }

    public function testItDoesntSynchroniseIfKeysAreTheSame(): void
    {
        $original_keys = [];
        $new_keys      = [];

        $this->remote_gerrit_factory->shouldReceive('getRemoteServersForUser')->with($this->user)->never();
        $this->gerrit_driver->shouldReceive('addSSHKeyToAccount')->never();
        $this->gerrit_driver->shouldReceive('removeSSHKeyFromAccount')->never();

        $this->user_account_manager->synchroniseSSHKeys($original_keys, $new_keys, $this->user);
    }

    public function testItCallsTheDriverToAddAndRemoveKeysTheRightNumberOfTimes(): void
    {
        $added_keys = [
            'Im a new key',
            'Im another new key',
        ];

        $removed_keys = [
            'Im a key',
            'Im another key',
            'Im an additional key',
        ];

        $this->gerrit_driver->shouldReceive('addSSHKeyToAccount')->atLeast()->once();
        $this->gerrit_driver->shouldReceive('addSSHKeyToAccount')->with($this->remote_server1, $this->user, $added_keys[1]);
        $this->gerrit_driver->shouldReceive('addSSHKeyToAccount')->with($this->remote_server2, $this->user, $added_keys[1]);
        $this->gerrit_driver->shouldReceive('addSSHKeyToAccount')->with($this->remote_server1, $this->user, $added_keys[0]);
        $this->gerrit_driver->shouldReceive('addSSHKeyToAccount')->with($this->remote_server2, $this->user, $added_keys[0]);

        $this->gerrit_driver->shouldReceive('removeSSHKeyFromAccount')->atLeast()->once();
        $this->gerrit_driver->shouldReceive('removeSSHKeyFromAccount')->with($this->remote_server1, $this->user, $removed_keys[0]);
        $this->gerrit_driver->shouldReceive('removeSSHKeyFromAccount')->with($this->remote_server2, $this->user, $removed_keys[0]);
        $this->gerrit_driver->shouldReceive('removeSSHKeyFromAccount')->with($this->remote_server1, $this->user, $removed_keys[1]);
        $this->gerrit_driver->shouldReceive('removeSSHKeyFromAccount')->with($this->remote_server2, $this->user, $removed_keys[1]);
        $this->gerrit_driver->shouldReceive('removeSSHKeyFromAccount')->with($this->remote_server1, $this->user, $removed_keys[2]);
        $this->gerrit_driver->shouldReceive('removeSSHKeyFromAccount')->with($this->remote_server2, $this->user, $removed_keys[2]);

        $this->user_account_manager->synchroniseSSHKeys($this->original_keys, $this->new_keys, $this->user);
    }

    public function testItThrowsAnExceptionIfGerritDriverFails(): void
    {
        $this->gerrit_driver->shouldReceive('addSSHKeyToAccount')->andThrows(new Git_Driver_Gerrit_Exception());

        $this->expectException(\Git_UserSynchronisationException::class);
        $this->user_account_manager->synchroniseSSHKeys($this->original_keys, $this->new_keys, $this->user);
    }
}
