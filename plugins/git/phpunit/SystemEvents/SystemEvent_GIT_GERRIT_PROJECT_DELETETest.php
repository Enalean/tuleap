<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';

class SystemEvent_GIT_GERRIT_PROJECT_DELETE_BaseTest extends TestCase  // phpcs:ignore
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Git_RemoteServer_GerritServer|MockInterface
     */
    private $server;
    /**
     * @var Git_Backend_Gitolite|MockInterface
     */
    private $backend;
    /**
     * @var Git_Driver_Gerrit|MockInterface
     */
    private $driver;
    /**
     * @var GitRepositoryFactory|MockInterface
     */
    private $repository_factory;
    /**
     * @var SystemEvent_GIT_GERRIT_PROJECT_DELETE|MockInterface
     */
    private $event;

    public function setUp(): void
    {
        parent::setUp();

        $this->repository_factory = Mockery::mock(GitRepositoryFactory::class);
        $server_factory           = Mockery::mock(Git_RemoteServer_GerritServerFactory::class);
        $driver_factory           = Mockery::mock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $this->driver             = Mockery::mock(Git_Driver_Gerrit::class);
        $driver_factory->shouldReceive('getDriver')->andReturn($this->driver);
        $this->backend = Mockery::mock(Git_Backend_Gitolite::class);

        $this->event = Mockery::mock(SystemEvent_GIT_GERRIT_PROJECT_DELETE::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->event->injectDependencies($this->repository_factory, $server_factory, $driver_factory);

        $this->server = Mockery::mock(Git_RemoteServer_GerritServer::class);
        $server_factory->shouldReceive('getServerById')->andReturn($this->server);
    }

    public function testItDeletesGerritProject()
    {
        $forge_project_name = 'Hello_kitty';
        $forge_project      = Mockery::mock(Project::class);
        $forge_project->shouldReceive('getUnixName')->andReturn($forge_project_name);

        $repository_name = 'mouse';

        $repository = Mockery::mock(GitRepository::class);
        $repository->shouldReceive('getProject')->andReturn($forge_project);
        $repository->shouldReceive('getName')->andReturn($repository_name);
        $repository->shouldReceive('getBackend')->andReturn($this->backend);
        $this->repository_factory->shouldReceive('getRepositoryById')->andReturn($repository);

        $gerrit_project_full_name = $forge_project_name . '/' . $repository_name;

        $repository_id    = 154;
        $remote_server_id = 33;
        $this->event->shouldReceive(
            [
                'getParametersAsArray' => [
                    $repository_id,
                    $remote_server_id
                ]
            ]
        );

        $this->driver->shouldReceive('deleteProject')->with($this->server, $gerrit_project_full_name);
        $this->backend->shouldReceive('setGerritProjectAsDeleted')->with($repository);

        $this->assertTrue($this->event->process());
    }

    public function testItDeletesGerritProjectWhenRepositoryIsDeleted()
    {
        $forge_project_name = 'Hello_kitty';
        $forge_project      = Mockery::mock(Project::class);
        $forge_project->shouldReceive('getUnixName')->andReturn($forge_project_name);

        $repository_name = 'mouse';

        $repository = Mockery::mock(GitRepository::class);
        $repository->shouldReceive('getProject')->andReturn($forge_project);
        $repository->shouldReceive('getName')->andReturn($repository_name);
        $repository->shouldReceive('getBackend')->andReturn($this->backend);
        $this->repository_factory->shouldReceive('getRepositoryById')->andReturn(null);
        $this->repository_factory->shouldReceive('getDeletedRepository')->andReturn($repository);

        $gerrit_project_full_name = $forge_project_name . '/' . $repository_name;

        $repository_id    = 154;
        $remote_server_id = 33;
        $this->event->shouldReceive(
            [
                'getParametersAsArray' => [
                    $repository_id,
                    $remote_server_id
                ]
            ]
        );

        $this->driver->shouldReceive('deleteProject')->with($this->server, $gerrit_project_full_name);
        $this->backend->shouldReceive('setGerritProjectAsDeleted')->with($repository);

        $this->assertTrue($this->event->process());
    }

    public function testItDoNothingIfRepositotyIsNotFound()
    {
        $forge_project_name = 'Hello_kitty';
        $forge_project      = Mockery::mock(Project::class);
        $forge_project->shouldReceive('getUnixName')->andReturn($forge_project_name);

        $repository_name = 'mouse';

        $repository = Mockery::mock(GitRepository::class);
        $repository->shouldReceive('getProject')->andReturn($forge_project);
        $repository->shouldReceive('getName')->andReturn($repository_name);
        $repository->shouldReceive('getBackend')->andReturn($this->backend);
        $this->repository_factory->shouldReceive('getRepositoryById')->andReturn(null);
        $this->repository_factory->shouldReceive('getDeletedRepository')->andReturn(null);

        $repository_id    = 154;
        $remote_server_id = 33;
        $this->event->shouldReceive(
            [
                'getParametersAsArray' => [
                    $repository_id,
                    $remote_server_id
                ]
            ]
        );

        $this->driver->shouldReceive('deleteProject')->never();
        $this->backend->shouldReceive('setGerritProjectAsDeleted')->never();

        $this->assertFalse($this->event->process());
    }
}
