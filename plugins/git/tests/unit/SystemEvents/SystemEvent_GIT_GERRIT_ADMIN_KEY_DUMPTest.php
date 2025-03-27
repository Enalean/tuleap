<?php
/**
 * Copyright Enalean (c) 2011 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Git\SystemEvents;

use Git_Gitolite_SSHKeyDumper;
use Git_RemoteServer_Gerrit_ReplicationSSHKey;
use Git_RemoteServer_GerritServer;
use Git_RemoteServer_GerritServerFactory;
use Git_RemoteServer_NotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP;
use Tuleap\Git\Gitolite\SSHKey\InvalidKeysCollector;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMPTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP&MockObject $event;
    private Git_RemoteServer_GerritServerFactory&MockObject $gerrit_server_factory;
    private Git_Gitolite_SSHKeyDumper&MockObject $ssh_key_dumper;

    protected function setUp(): void
    {
        $this->ssh_key_dumper        = $this->createMock(Git_Gitolite_SSHKeyDumper::class);
        $this->gerrit_server_factory = $this->createMock(Git_RemoteServer_GerritServerFactory::class);
        $this->event                 = $this->createPartialMock(SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP::class, ['done', 'error']);
        $this->event->injectDependencies($this->gerrit_server_factory, $this->ssh_key_dumper);
    }

    public function testItAddsKeyForAServer(): void
    {
        $gerrit_server_id = 7;
        $this->event->setParameters("$gerrit_server_id");

        $gerrit_server = $this->createMock(Git_RemoteServer_GerritServer::class);
        $gerrit_server->method('getId')->willReturn($gerrit_server_id);
        $gerrit_server->method('getReplicationKey');
        $this->ssh_key_dumper->method('dumpSSHKeys');
        $this->gerrit_server_factory->expects($this->once())->method('getServerById')
            ->with($gerrit_server_id)
            ->willReturn($gerrit_server);

        $this->event->method('error');
        $this->event->process();
    }

    public function testItDumpsTheNewKeyForServer(): void
    {
        $gerrit_server_id = 7;
        $this->event->setParameters("$gerrit_server_id");

        $gerrit_server = new Git_RemoteServer_GerritServer(
            $gerrit_server_id,
            '$host',
            '$ssh_port',
            '$http_port',
            '$login',
            '$identity_file',
            'ssh-rsa blablabla',
            false,
            '2.5',
            'ikshjdshg',
            ''
        );
        $this->gerrit_server_factory->method('getServerById')->willReturn($gerrit_server);

        $this->ssh_key_dumper->expects($this->once())->method('dumpSSHKeys')->with(
            self::callback(function (Git_RemoteServer_Gerrit_ReplicationSSHKey $key) use ($gerrit_server_id) {
                return $key->getGerritHostId() === $gerrit_server_id;
            }),
            self::isInstanceOf(InvalidKeysCollector::class)
        );

        $this->event->method('error');
        $this->event->process();
    }

    public function testItDeleteCorrespondingKeyWhenNoServer(): void
    {
        $gerrit_server_id = 7;
        $this->event->setParameters("$gerrit_server_id");

        $this->gerrit_server_factory->method('getServerById')->willThrowException(new Git_RemoteServer_NotFoundException($gerrit_server_id));

        $this->ssh_key_dumper->expects($this->once())->method('dumpSSHKeys')->with(
            self::callback(function (Git_RemoteServer_Gerrit_ReplicationSSHKey $key) use ($gerrit_server_id) {
                return $key->getGerritHostId() === $gerrit_server_id;
            }),
            self::isInstanceOf(InvalidKeysCollector::class)
        );

        $this->event->method('error');
        $this->event->process();
    }

    public function testItMarkAsDoneWhenDumpWorks(): void
    {
        $this->event->setParameters('7');

        $gerrit_server = $this->createMock(Git_RemoteServer_GerritServer::class);
        $gerrit_server->method('getId')->willReturn(7);
        $gerrit_server->method('getReplicationKey');
        $this->gerrit_server_factory->method('getServerById')->willReturn($gerrit_server);
        $this->ssh_key_dumper->method('dumpSSHKeys')->willReturn(true);

        $this->event->expects($this->once())->method('done');

        $this->event->process();
    }

    public function testItMarkAsErrorWhenDumpDoesntWork(): void
    {
        $this->event->setParameters('7');

        $gerrit_server = $this->createMock(Git_RemoteServer_GerritServer::class);
        $gerrit_server->method('getId')->willReturn(7);
        $gerrit_server->method('getReplicationKey');
        $this->gerrit_server_factory->method('getServerById')->willReturn($gerrit_server);
        $this->ssh_key_dumper->method('dumpSSHKeys')->willReturn(false);

        $this->event->expects($this->once())->method('error');

        $this->event->process();
    }
}
