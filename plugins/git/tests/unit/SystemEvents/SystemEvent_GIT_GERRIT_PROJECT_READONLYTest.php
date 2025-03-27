<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Git\SystemEvents;

use Git_Driver_Gerrit;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_RemoteServer_GerritServer;
use Git_RemoteServer_GerritServerFactory;
use GitRepositoryFactory;
use SystemEvent_GIT_GERRIT_PROJECT_READONLY;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SystemEvent_GIT_GERRIT_PROJECT_READONLYTest extends TestCase
{
    public function testItMakesGerritProjectReadOnly(): void
    {
        $repository_factory = $this->createMock(GitRepositoryFactory::class);
        $server_factory     = $this->createMock(Git_RemoteServer_GerritServerFactory::class);
        $driver             = $this->createMock(Git_Driver_Gerrit::class);
        $driver_factory     = $this->createMock(Git_Driver_Gerrit_GerritDriverFactory::class);
        $forge_project      = ProjectTestBuilder::aProject()->withUnixName('projname')->build();
        $repository         = GitRepositoryTestBuilder::aProjectRepository()->inProject($forge_project)->withName('repo_01')->build();
        $server             = $this->createMock(Git_RemoteServer_GerritServer::class);

        $driver_factory->method('getDriver')->willReturn($driver);
        $server_factory->method('getServerById')->willReturn($server);
        $repository_factory->method('getRepositoryById')->willReturn($repository);

        $event = $this->createPartialMock(SystemEvent_GIT_GERRIT_PROJECT_READONLY::class, ['getParametersAsArray']);
        $event->injectDependencies(
            $repository_factory,
            $server_factory,
            $driver_factory
        );

        $repository_id    = 154;
        $remote_server_id = 33;
        $event->method('getParametersAsArray')->willReturn([$repository_id, $remote_server_id]);

        $driver->expects($this->once())->method('makeGerritProjectReadOnly')->with($server, 'projname/repo_01');

        $event->process();
    }
}
