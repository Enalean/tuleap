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
final class UserAccountManagerPushSSHKeysTest extends TestCase
{
    private PFUser $user;
    private Git_Driver_Gerrit&MockObject $gerrit_driver;
    private Git_Driver_Gerrit_GerritDriverFactory&MockObject $gerrit_driver_factory;
    private Git_RemoteServer_GerritServerFactory&MockObject $remote_gerrit_factory;
    private Git_Driver_Gerrit_User&MockObject $gerrit_user;
    private Git_Driver_Gerrit_UserAccountManager&MockObject $user_account_manager;
    private Git_RemoteServer_GerritServer&MockObject $remote_server1;
    private Git_RemoteServer_GerritServer&MockObject $remote_server2;

    #[\Override]
    protected function setUp(): void
    {
        $this->user = new PFUser([
            'language_id' => 'en',
            'ldap_id'     => 'testUser',
        ]);
        $key1       = 'key1';
        $key2       = 'key2';

        $this->user->setAuthorizedKeys($key1 . PFUser::SSH_KEY_SEPARATOR . $key2);

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

        $this->remote_server1 = $this->createMock(Git_RemoteServer_GerritServer::class);
        $this->remote_server2 = $this->createMock(Git_RemoteServer_GerritServer::class);

        $this->remote_gerrit_factory->method('getRemoteServersForUser')->with($this->user)->willReturn([
            $this->remote_server1,
            $this->remote_server2,
        ]);
    }

    public function testItDoesntPushIfUserHasNoRemoteServers(): void
    {
        $remote_gerrit_factory = $this->createMock(Git_RemoteServer_GerritServerFactory::class);
        $remote_gerrit_factory->method('getRemoteServersForUser')->with($this->user)->willReturn([]);

        $user_account_manager = new Git_Driver_Gerrit_UserAccountManager($this->gerrit_driver_factory, $remote_gerrit_factory);
        $this->gerrit_driver->expects($this->never())->method('addSSHKeyToAccount');
        $this->gerrit_driver->expects($this->never())->method('removeSSHKeyFromAccount');

        $user_account_manager->pushSSHKeys($this->user);
    }

    public function testItDoesntPushIfUserHasNoKeys(): void
    {
        $this->user->setAuthorizedKeys('');

        $this->remote_gerrit_factory->expects($this->never())->method('getRemoteServersForUser')->with($this->user);
        $this->gerrit_driver->expects($this->never())->method('addSSHKeyToAccount');
        $this->gerrit_driver->expects($this->never())->method('removeSSHKeyFromAccount');

        $this->user_account_manager->pushSSHKeys($this->user);
    }

    public function testItCallsTheDriverToAddAndRemoveKeysTheRightNumberOfTimes(): void
    {
        $pushed_keys = ['key1', 'key2'];
        $matcher     = self::exactly(4);

        $this->gerrit_driver->expects($matcher)->method('addSSHKeyToAccount')->willReturnCallback(function (...$parameters) use ($matcher, $pushed_keys) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($this->remote_server1, $parameters[0]);
                self::assertSame($this->gerrit_user, $parameters[1]);
                self::assertSame($pushed_keys[0], $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($this->remote_server1, $parameters[0]);
                self::assertSame($this->gerrit_user, $parameters[1]);
                self::assertSame($pushed_keys[1], $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                self::assertSame($this->remote_server2, $parameters[0]);
                self::assertSame($this->gerrit_user, $parameters[1]);
                self::assertSame($pushed_keys[0], $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 4) {
                self::assertSame($this->remote_server2, $parameters[0]);
                self::assertSame($this->gerrit_user, $parameters[1]);
                self::assertSame($pushed_keys[1], $parameters[2]);
            }
        });
        $matcher = self::exactly(4);
        $this->gerrit_driver->expects($matcher)->method('removeSSHKeyFromAccount')->willReturnCallback(function (...$parameters) use ($matcher, $pushed_keys) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($this->remote_server1, $parameters[0]);
                self::assertSame($this->gerrit_user, $parameters[1]);
                self::assertSame($pushed_keys[0], $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($this->remote_server1, $parameters[0]);
                self::assertSame($this->gerrit_user, $parameters[1]);
                self::assertSame($pushed_keys[1], $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                self::assertSame($this->remote_server2, $parameters[0]);
                self::assertSame($this->gerrit_user, $parameters[1]);
                self::assertSame($pushed_keys[0], $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 4) {
                self::assertSame($this->remote_server2, $parameters[0]);
                self::assertSame($this->gerrit_user, $parameters[1]);
                self::assertSame($pushed_keys[1], $parameters[2]);
            }
        });

        $this->user_account_manager->pushSSHKeys($this->user);
    }

    public function testItThrowsAnExceptionIfGerritDriverFails(): void
    {
        $this->gerrit_driver->method('addSSHKeyToAccount')->willThrowException(new Git_Driver_Gerrit_Exception());
        $this->gerrit_driver->method('removeSSHKeyFromAccount');

        $this->expectException(Git_UserSynchronisationException::class);
        $this->user_account_manager->pushSSHKeys($this->user);
    }
}
