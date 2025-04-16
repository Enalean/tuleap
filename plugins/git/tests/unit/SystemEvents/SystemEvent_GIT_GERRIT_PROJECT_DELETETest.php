<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Git_Backend_Gitolite;
use Git_Driver_Gerrit;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_RemoteServer_GerritServer;
use Git_RemoteServer_GerritServerFactory;
use GitRepositoryFactory;
use PHPUnit\Framework\MockObject\MockObject;
use SystemEvent_GIT_GERRIT_PROJECT_DELETE;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SystemEvent_GIT_GERRIT_PROJECT_DELETETest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Git_RemoteServer_GerritServer&MockObject $server;
    private Git_Backend_Gitolite&MockObject $backend;
    private Git_Driver_Gerrit&MockObject $driver;
    private GitRepositoryFactory&MockObject $repository_factory;
    private SystemEvent_GIT_GERRIT_PROJECT_DELETE $event;

    public function setUp(): void
    {
        $this->repository_factory = $this->createMock(GitRepositoryFactory::class);
        $server_factory           = $this->createMock(Git_RemoteServer_GerritServerFactory::class);
        $driver_factory           = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $this->driver             = $this->createMock(Git_Driver_Gerrit::class);
        $driver_factory->method('getDriver')->willReturn($this->driver);
        $this->backend = $this->createMock(Git_Backend_Gitolite::class);

        $this->event = new SystemEvent_GIT_GERRIT_PROJECT_DELETE(1, '', '', '', 1, '', '', '', '', '');
        $this->event->injectDependencies($this->repository_factory, $server_factory, $driver_factory);

        $this->server = $this->createMock(Git_RemoteServer_GerritServer::class);
        $server_factory->method('getServerById')->willReturn($this->server);
    }

    public function testItDeletesGerritProject(): void
    {
        $forge_project_name = 'hello_kitty';
        $forge_project      = ProjectTestBuilder::aProject()->withUnixName($forge_project_name)->build();
        $repository_name    = 'mouse';
        $repository         = GitRepositoryTestBuilder::aProjectRepository()->inProject($forge_project)
            ->withName($repository_name)->withBackend($this->backend)->build();
        $this->repository_factory->method('getRepositoryById')->willReturn($repository);

        $gerrit_project_full_name = $forge_project_name . '/' . $repository_name;

        $repository_id    = 154;
        $remote_server_id = 33;
        $this->event->setParameters("$repository_id::$remote_server_id");

        $this->driver->method('deleteProject')->with($this->server, $gerrit_project_full_name);
        $this->backend->method('setGerritProjectAsDeleted')->with($repository);

        self::assertTrue($this->event->process());
    }

    public function testItDeletesGerritProjectWhenRepositoryIsDeleted(): void
    {
        $forge_project_name = 'hello_kitty';
        $forge_project      = ProjectTestBuilder::aProject()->withUnixName($forge_project_name)->build();
        $repository_name    = 'mouse';
        $repository         = GitRepositoryTestBuilder::aProjectRepository()->inProject($forge_project)
            ->withName($repository_name)->withBackend($this->backend)->build();
        $this->repository_factory->method('getRepositoryById')->willReturn(null);
        $this->repository_factory->method('getDeletedRepository')->willReturn($repository);

        $gerrit_project_full_name = $forge_project_name . '/' . $repository_name;

        $repository_id    = 154;
        $remote_server_id = 33;
        $this->event->setParameters("$repository_id::$remote_server_id");

        $this->driver->method('deleteProject')->with($this->server, $gerrit_project_full_name);
        $this->backend->method('setGerritProjectAsDeleted')->with($repository);

        self::assertTrue($this->event->process());
    }

    public function testItDoNothingIfRepositotyIsNotFound(): void
    {
        $this->repository_factory->method('getRepositoryById')->willReturn(null);
        $this->repository_factory->method('getDeletedRepository')->willReturn(null);

        $repository_id    = 154;
        $remote_server_id = 33;
        $this->event->setParameters("$repository_id::$remote_server_id");

        $this->driver->expects($this->never())->method('deleteProject');
        $this->backend->expects($this->never())->method('setGerritProjectAsDeleted');

        self::assertFalse($this->event->process());
    }
}
