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

use Tuleap\Git\Gitolite\SSHKey\InvalidKeysCollector;

require_once __DIR__ . '/../bootstrap.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMPTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    /** @var SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP */
    private $event;
    /** @var Git_RemoteServer_GerritServerFactory */
    private $gerrit_server_factory;
    /** @var Git_Gitolite_SSHKeyDumper */
    private $ssh_key_dumper;

    protected function setUp() : void
    {
        parent::setUp();
        $this->ssh_key_dumper = \Mockery::spy(\Git_Gitolite_SSHKeyDumper::class);
        $this->gerrit_server_factory = \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class);
        $this->event = \Mockery::mock(\SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->event->injectDependencies($this->gerrit_server_factory, $this->ssh_key_dumper);
    }

    public function testItAddsKeyForAServer() : void
    {
        $gerrit_server_id = 7;
        $this->event->setParameters("$gerrit_server_id");

        $this->gerrit_server_factory->shouldReceive('getServerById')
            ->with($gerrit_server_id)
            ->once()
            ->andReturns(\Mockery::spy(\Git_RemoteServer_GerritServer::class));

        $this->event->process();
    }

    public function testItDumpsTheNewKeyForServer() : void
    {
        $gerrit_server_id = 7;
        $this->event->setParameters("$gerrit_server_id");

        $replication_key = 'ssh-rsa blablabla';
        $use_ssl         = false;
        $gerrit_version  = '2.5';
        $http_password   = 'ikshjdshg';

        $gerrit_server = new Git_RemoteServer_GerritServer(
            $gerrit_server_id,
            '$host',
            '$ssh_port',
            '$http_port',
            '$login',
            '$identity_file',
            $replication_key,
            $use_ssl,
            $gerrit_version,
            $http_password,
            ''
        );
        $this->gerrit_server_factory->shouldReceive('getServerById')->andReturns($gerrit_server);

        $this->ssh_key_dumper->shouldReceive('dumpSSHKeys')->with(
            Mockery::on(function (Git_RemoteServer_Gerrit_ReplicationSSHKey $key) use ($gerrit_server_id) {
                return $key->getGerritHostId() === $gerrit_server_id;
            }),
            Mockery::type(InvalidKeysCollector::class)
        )
            ->once();

        $this->event->process();
    }

    public function testItDeleteCorrespondingKeyWhenNoServer() : void
    {
        $gerrit_server_id = 7;
        $this->event->setParameters("$gerrit_server_id");

        $this->gerrit_server_factory->shouldReceive('getServerById')->andThrows(new Git_RemoteServer_NotFoundException($gerrit_server_id));

        $this->ssh_key_dumper->shouldReceive('dumpSSHKeys')->with(
            Mockery::on(function (Git_RemoteServer_Gerrit_ReplicationSSHKey $key) use ($gerrit_server_id) {
                return $key->getGerritHostId() === $gerrit_server_id;
            }),
            Mockery::type(InvalidKeysCollector::class)
        )
            ->once();

        $this->event->process();
    }

    public function testItMarkAsDoneWhenDumpWorks() : void
    {
        $this->event->setParameters("7");

        $this->gerrit_server_factory->shouldReceive('getServerById')->andReturns(\Mockery::spy(\Git_RemoteServer_GerritServer::class));
        $this->ssh_key_dumper->shouldReceive('dumpSSHKeys')->andReturns(true);

        $this->event->shouldReceive('done')->once();

        $this->event->process();
    }

    public function testItMarkAsErrorWhenDumpDoesntWork() : void
    {
        $this->event->setParameters("7");

        $this->gerrit_server_factory->shouldReceive('getServerById')->andReturns(\Mockery::spy(\Git_RemoteServer_GerritServer::class));
        $this->ssh_key_dumper->shouldReceive('dumpSSHKeys')->andReturns(false);

        $this->event->shouldReceive('error')->once();

        $this->event->process();
    }
}
