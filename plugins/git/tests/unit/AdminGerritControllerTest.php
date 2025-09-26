<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Git;

use Codendi_Request;
use Git_AdminGerritController;
use Git_RemoteServer_GerritServer;
use Git_RemoteServer_GerritServerFactory;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectManager;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Git\RemoteServer\Gerrit\Restrictor;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\JavascriptAssetGenericBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use User_SSHKeyValidator;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AdminGerritControllerTest extends TestCase
{
    use GlobalResponseMock;

    private Codendi_Request $request;
    private CSRFSynchronizerTokenStub $csrf;
    private Git_RemoteServer_GerritServerFactory&MockObject $factory;
    private Git_AdminGerritController $admin;
    private Git_RemoteServer_GerritServer $a_brand_new_server;
    private Git_RemoteServer_GerritServer $an_existing_server;

    #[\Override]
    public function setUp(): void
    {
        $this->csrf = CSRFSynchronizerTokenStub::buildSelf();

        $this->request = new Codendi_Request([], $this->createMock(ProjectManager::class));
        $this->request->set($this->csrf->getTokenName(), $this->csrf->getToken());
        $this->request->set('action', 'add-gerrit-server');

        $this->a_brand_new_server = new Git_RemoteServer_GerritServer(
            0,
            'host',
            '1234',
            '80',
            'new_login',
            '/path/to/file',
            '',
            1,
            '2.8+',
            'azerty',
            '',
        );

        $this->an_existing_server = new Git_RemoteServer_GerritServer(
            1,
            'g.example.com',
            '1234',
            '80',
            'login',
            '/path/to/file',
            'replication_key',
            0,
            '2.8+',
            'azerty',
            'azerty',
        );

        $this->factory = $this->createMock(Git_RemoteServer_GerritServerFactory::class);
        $this->factory->method('getServers')->willReturn([1 => $this->an_existing_server]);
        $this->factory->method('getServerById')->willReturn($this->an_existing_server);

        $ssh_key_validator = $this->createMock(User_SSHKeyValidator::class);
        $ssh_key_validator->method('validateAllKeys');
        $this->admin = new Git_AdminGerritController(
            $this->csrf,
            $this->factory,
            $this->createMock(AdminPageRenderer::class),
            $this->createMock(GerritServerResourceRestrictor::class),
            $this->createMock(Restrictor::class),
            new AdminGerritBuilder($ssh_key_validator),
            JavascriptAssetGenericBuilder::build(),
        );
    }

    public function testItDoesNotSaveAnythingIfTheRequestIsNotValid(): void
    {
        $this->request->set('action', 'add-gerrit-server');
        $this->request->set('server', false);
        $this->factory->expects($this->never())->method('save');
        $this->admin->process($this->request);
    }

    public function testItDoesNotSaveAServerIfNoDataIsGiven(): void
    {
        $this->request->set('action', 'add-gerrit-server');
        $this->request->set('host', '');
        $this->request->set('ssh_port', '');
        $this->request->set('http_port', '');
        $this->request->set('login', '');
        $this->request->set('identity_file', '');
        $this->request->set('replication_key', '');
        $this->request->set('use_ssl', '');
        $this->request->set('http_password', '');
        $this->request->set('replication_password', '');
        $this->factory->expects($this->never())->method('save');
        $this->admin->process($this->request);
    }

    public function testItDoesNotSaveAServerIfItsHostIsEmpty(): void
    {
        $this->request->set('action', 'add-gerrit-server');
        $this->request->set('host', '');
        $this->request->set('ssh_port', '1234');
        $this->request->set('http_port', '80');
        $this->request->set('login', 'new_login');
        $this->request->set('identity_file', '/path/to/file');
        $this->request->set('replication_key', '');
        $this->request->set('use_ssl', 0);
        $this->request->set('http_password', 'azerty');
        $this->request->set('replication_password', 'azerty');
        $this->factory->expects($this->never())->method('save');
        $this->admin->process($this->request);
    }

    public function testItNotSavesAServerIfItsHostIsNotEmptyAndAllOtherDataAreEmpty(): void
    {
        $this->request->set('action', 'add-gerrit-server');
        $this->request->set('host', 'awesome_host');
        $this->request->set('ssh_port', '');
        $this->request->set('http_port', '');
        $this->request->set('login', '');
        $this->request->set('identity_file', '');
        $this->request->set('replication_key', '');
        $this->request->set('use_ssl', '');
        $this->request->set('http_password', '');
        $this->request->set('replication_password', '');
        $this->factory->expects($this->never())->method('save');
        $this->admin->process($this->request);
    }

    public function testItCheckWithCSRFIfTheRequestIsForged(): void
    {
        $this->request->set('action', 'add-gerrit-server');
        $this->request->set('host', 'host');
        $this->request->set('ssh_port', '1234');
        $this->request->set('http_port', '80');
        $this->request->set('login', 'new_login');
        $this->request->set('identity_file', '/path/to/file');
        $this->request->set('replication_key', 'replication_key');
        $this->request->set('use_ssl', 1);
        $this->request->set('http_password', 'azerty');
        $this->request->set('replication_password', 'azerty');
        $this->factory->method('save');
        $this->factory->method('updateReplicationPassword');
        $this->admin->process($this->request);
        self::assertTrue($this->csrf->hasBeenChecked());
    }

    public function testItSavesNewGerritServer(): void
    {
        $this->request->set('action', 'add-gerrit-server');
        $this->request->set('host', 'host');
        $this->request->set('ssh_port', '1234');
        $this->request->set('http_port', '80');
        $this->request->set('login', 'new_login');
        $this->request->set('identity_file', '/path/to/file');
        $this->request->set('replication_key', 'replication_key');
        $this->request->set('use_ssl', 1);
        $this->request->set('http_password', 'azerty');
        $this->request->set('replication_password', 'azerty');
        $s = $this->a_brand_new_server;
        $this->factory->expects($this->once())->method('save')->with($s);
        $this->factory->method('updateReplicationPassword');
        $this->admin->process($this->request);
    }

    public function testItRedirectsAfterSave(): void
    {
        $this->request->set('action', 'add-gerrit-server');
        $GLOBALS['Response']->expects($this->once())->method('redirect');
        $this->admin->process($this->request);
    }

    public function testItUpdatesExistingGerritServer(): void
    {
        $this->request->set('action', 'edit-gerrit-server');
        $this->request->set('gerrit_server_id', 1);
        $this->request->set('host', 'g.example.com');
        $this->request->set('ssh_port', '1234');
        $this->request->set('http_port', '80');
        $this->request->set('login', 'new_login');
        $this->request->set('identity_file', '/path/to/file');
        $this->request->set('replication_key', 'replication_key');
        $this->request->set('use_ssl', 1);
        $this->request->set('http_password', 'azerty');
        $this->request->set('replication_password', 'azerty');
        $this->factory->expects($this->once())->method('save')->with($this->an_existing_server);
        $this->admin->process($this->request);
    }

    public function testItUpdatesExistingGerritServerIfNoAuthentificationType(): void
    {
        $this->request->set('action', 'edit-gerrit-server');
        $this->request->set('gerrit_server_id', 1);
        $this->request->set('host', 'g.example.com');
        $this->request->set('ssh_port', '1234');
        $this->request->set('http_port', '80');
        $this->request->set('login', 'new_login');
        $this->request->set('identity_file', '/path/to/file');
        $this->request->set('replication_key', 'replication_key');
        $this->request->set('use_ssl', 1);
        $this->request->set('http_password', 'azerty');
        $this->request->set('replication_password', 'azerty');
        $this->factory->expects($this->once())->method('save')->with($this->an_existing_server);
        $this->admin->process($this->request);
    }

    public function testItDeletesGerritServer(): void
    {
        $this->request->set('action', 'delete-gerrit-server');
        $this->request->set('gerrit_server_id', 1);
        $this->factory->expects($this->once())->method('delete')->with($this->an_existing_server);
        $this->factory->expects($this->never())->method('save')->with($this->an_existing_server);
        $this->admin->process($this->request);
    }
}
