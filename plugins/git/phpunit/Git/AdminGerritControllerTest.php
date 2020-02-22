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

namespace Tuleap\Git;

use CSRFSynchronizerToken;
use Git_RemoteServer_GerritServer;
use Git_RemoteServer_GerritServerFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Response;
use Tuleap\Layout\IncludeAssets;
use User_SSHKeyValidator;

class AdminGerritControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Codendi_Request
     */
    private $request;
    private $csrf;
    /**
     * @var MockInterface|Git_RemoteServer_GerritServerFactory
     */
    private $factory;
    private $admin_page_renderer;
    private $admin;
    private $a_brand_new_server;
    private $an_existing_server;

    public function setUp() : void
    {
        $GLOBALS['Response']       = \Mockery::spy(Response::class);
        $this->csrf                = \Mockery::spy(CSRFSynchronizerToken::class);

        $this->request             = new \Codendi_Request([], \Mockery::spy(\ProjectManager::class));
        $this->request->set($this->csrf->getTokenName(), $this->csrf->getToken());
        $this->request->set('action', 'add-gerrit-server');

        $this->admin_page_renderer = \Mockery::spy(\Tuleap\Admin\AdminPageRenderer::class);

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

        $this->factory             = \Mockery::spy(
            Git_RemoteServer_GerritServerFactory::class,
            [
                'getServers'    => [1 => $this->an_existing_server],
                'getServerById' => $this->an_existing_server,
            ]
        );

        $this->admin               = new \Git_AdminGerritController(
            $this->csrf,
            $this->factory,
            $this->admin_page_renderer,
            \Mockery::spy(GerritServerResourceRestrictor::class),
            \Mockery::spy(RemoteServer\Gerrit\Restrictor::class),
            new AdminGerritBuilder(\Mockery::spy(User_SSHKeyValidator::class)),
            \Mockery::mock(IncludeAssets::class)
        );
    }

    public function tearDown() : void
    {
        unset($GLOBALS['Response']);
    }

    /**
     * @test
     */
    public function itDoesNotSaveAnythingIfTheRequestIsNotValid()
    {
        $this->request->set('action', 'add-gerrit-server');
        $this->request->set('server', false);
        $this->factory->shouldReceive('save')->never();
        $this->admin->process($this->request);
    }

    /**
     * @test
     */
    public function itDoesNotSaveAServerIfNoDataIsGiven()
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
        $this->factory->shouldReceive('save')->never();
        $this->admin->process($this->request);
    }

    /**
     * @test
     */
    public function itDoesNotSaveAServerIfItsHostIsEmpty()
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
        $this->factory->shouldReceive('save')->never();
        $this->admin->process($this->request);
    }

    /**
     * @test
     */
    public function itNotSavesAServerIfItsHostIsNotEmptyAndAllOtherDataAreEmpty()
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
        $this->factory->shouldReceive('save')->never();
        $this->admin->process($this->request);
    }

    /**
     * @test
     */
    public function itCheckWithCSRFIfTheRequestIsForged()
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
        $this->csrf->shouldReceive('check')->once();
        $this->admin->process($this->request);
    }

    /**
     * @test
     */
    public function itSavesNewGerritServer()
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
        $this->factory->shouldReceive('save')->with(\Mockery::on(function (Git_RemoteServer_GerritServer $param) use ($s) {
            return $s == $param;
        }))->once();
        $this->admin->process($this->request);
    }

    /**
     * @test
     */
    public function itRedirectsAfterSave()
    {
        $this->request->set('action', 'add-gerrit-server');
        $GLOBALS['Response']->shouldReceive('redirect')->once();
        $this->admin->process($this->request);
    }

    /**
     * @test
     */
    public function itUpdatesExistingGerritServer()
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
        $this->factory->shouldReceive('save')->with($this->an_existing_server)->once();
        $this->admin->process($this->request);
    }

    /**
     * @test
     */
    public function itUpdatesExistingGerritServerIfNoAuthentificationType()
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
        $this->factory->shouldReceive('save')->with($this->an_existing_server)->once();
        $this->admin->process($this->request);
    }

    /**
     * @test
     */
    public function itDeletesGerritServer()
    {
        $this->request->set('action', 'delete-gerrit-server');
        $this->request->set('gerrit_server_id', 1);
        $this->factory->shouldReceive('delete')->with($this->an_existing_server)->once();
        $this->factory->shouldReceive('save')->with($this->an_existing_server)->never();
        $this->admin->process($this->request);
    }
}
