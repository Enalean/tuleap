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

declare(strict_types=1);

namespace Tuleap\Git\Driver\Gerrit;

use Git_Driver_Gerrit;
use Git_Driver_Gerrit_Exception;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_User;
use Git_Driver_Gerrit_UserAccountManager;
use Git_RemoteServer_GerritServer;
use Git_RemoteServer_GerritServerFactory;
use Git_UserSynchronisationException;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GerritUserAccountManagerTest extends TestCase
{
    private PFUser $user;
    private Git_Driver_Gerrit&MockObject $gerrit_driver;
    private Git_Driver_Gerrit_GerritDriverFactory&MockObject $gerrit_driver_factory;
    private Git_RemoteServer_GerritServerFactory&MockObject $remote_gerrit_factory;
    private Git_RemoteServer_GerritServer&MockObject $remote_server1;
    private Git_RemoteServer_GerritServer&MockObject $remote_server2;
    private Git_Driver_Gerrit_User&MockObject $gerrit_user;
    private Git_Driver_Gerrit_UserAccountManager&MockObject $user_account_manager;
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
        $this->user                  = new PFUser([
            'language_id' => 'en',
            'ldap_id'     => 'testUser',
        ]);
        $this->gerrit_driver         = $this->createMock(Git_Driver_Gerrit::class);
        $this->gerrit_driver_factory = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $this->remote_gerrit_factory = $this->createMock(Git_RemoteServer_GerritServerFactory::class);
        $this->gerrit_user           = $this->createMock(Git_Driver_Gerrit_User::class);

        $this->gerrit_driver_factory->method('getDriver')->willReturn($this->gerrit_driver);

        $this->user_account_manager = $this->getMockBuilder(Git_Driver_Gerrit_UserAccountManager::class)
            ->setConstructorArgs([$this->gerrit_driver_factory, $this->remote_gerrit_factory])
            ->onlyMethods(['getGerritUser'])
            ->getMock();

        $this->user_account_manager->method('getGerritUser')->willReturn($this->gerrit_user);

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

        $this->remote_server1 = $this->createMock(Git_RemoteServer_GerritServer::class);
        $this->remote_server2 = $this->createMock(Git_RemoteServer_GerritServer::class);

        $this->remote_gerrit_factory->method('getRemoteServersForUser')
            ->with($this->user)->willReturn([$this->remote_server1, $this->remote_server2]);
    }

    public function testItCallsRemoteServerFactory(): void
    {
        $this->gerrit_driver->expects($this->atLeastOnce())->method('removeSSHKeyFromAccount');
        $this->gerrit_driver->expects($this->atLeastOnce())->method('addSSHKeyToAccount');
        $this->user_account_manager->synchroniseSSHKeys($this->original_keys, $this->new_keys, $this->user);
    }

    public function testItDoesntSynchroniseIfUserHasNoRemoteServers(): void
    {
        $remote_gerrit_factory = $this->createMock(Git_RemoteServer_GerritServerFactory::class);
        $remote_gerrit_factory->method('getRemoteServersForUser')->with($this->user)->willReturn([]);

        $this->gerrit_driver->expects($this->never())->method('addSSHKeyToAccount');
        $this->gerrit_driver->expects($this->never())->method('removeSSHKeyFromAccount');

        $user_account_manager = new Git_Driver_Gerrit_UserAccountManager($this->gerrit_driver_factory, $remote_gerrit_factory);

        $user_account_manager->synchroniseSSHKeys($this->original_keys, $this->new_keys, $this->user);
    }

    public function testItDoesntSynchroniseIfKeysAreTheSame(): void
    {
        $original_keys = [];
        $new_keys      = [];

        $this->remote_gerrit_factory->expects($this->never())->method('getRemoteServersForUser')->with($this->user);
        $this->gerrit_driver->expects($this->never())->method('addSSHKeyToAccount');
        $this->gerrit_driver->expects($this->never())->method('removeSSHKeyFromAccount');

        $this->user_account_manager->synchroniseSSHKeys($original_keys, $new_keys, $this->user);
    }

    public function testItCallsTheDriverToRemoveAllKeysBeforeAddingThem(): void
    {
        $added_keys = [
            'Im a new key',
            'Im another new key',
        ];

        $removed_keys = [
            'Im a key',
            'Im another key',
            'Im an additional key',

            'Im a new key',
            'Im another new key',
        ];

        $this->gerrit_driver->expects($this->atLeast(4))->method('addSSHKeyToAccount')
            ->with(
                self::callback(fn(Git_RemoteServer_GerritServer $server) => $server === $this->remote_server1 || $server === $this->remote_server2),
                $this->gerrit_user,
                self::callback(fn(string $key) => in_array($key, $added_keys)),
            );

        $this->gerrit_driver->expects($this->exactly(10))->method('removeSSHKeyFromAccount')
            ->with(
                self::callback(fn(Git_RemoteServer_GerritServer $server) => $server === $this->remote_server1 || $server === $this->remote_server2),
                $this->gerrit_user,
                self::callback(fn(string $key) => in_array($key, $removed_keys)),
            );

        $this->user_account_manager->synchroniseSSHKeys($this->original_keys, $this->new_keys, $this->user);
    }

    public function testItThrowsAnExceptionIfGerritDriverFails(): void
    {
        $this->gerrit_driver->method('addSSHKeyToAccount')->willThrowException(new Git_Driver_Gerrit_Exception());
        $this->gerrit_driver->method('removeSSHKeyFromAccount');

        $this->expectException(Git_UserSynchronisationException::class);
        $this->user_account_manager->synchroniseSSHKeys($this->original_keys, $this->new_keys, $this->user);
    }
}
