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

require_once __DIR__ . '/../bootstrap.php';

class SystemEvent_GIT_GERRIT_PROJECT_READONLYTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItMakesGerritProjectReadOnly(): void
    {
        $repository_factory = \Mockery::spy(\GitRepositoryFactory::class);
        $server_factory     = \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class);
        $driver             = \Mockery::spy(\Git_Driver_Gerrit::class);
        $driver_factory     = \Mockery::spy(\Git_Driver_Gerrit_GerritDriverFactory::class)->shouldReceive('getDriver')->andReturns($driver)->getMock();
        $repository         = \Mockery::spy(\GitRepository::class);
        $forge_project      = \Mockery::spy(\Project::class)->shouldReceive('getUnixName')->andReturns('projname')->getMock();
        $server             = \Mockery::spy(\Git_RemoteServer_GerritServer::class);

        $repository->shouldReceive('getProject')->andReturns($forge_project);
        $repository->shouldReceive('getName')->andReturns('repo_01');
        $server_factory->shouldReceive('getServerById')->andReturns($server);
        $repository_factory->shouldReceive('getRepositoryById')->andReturns($repository);

        $event = \Mockery::mock(\SystemEvent_GIT_GERRIT_PROJECT_READONLY::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $event->injectDependencies(
            $repository_factory,
            $server_factory,
            $driver_factory
        );

        $repository_id    = 154;
        $remote_server_id = 33;
        $event->shouldReceive('getParametersAsArray')->andReturns(array(
            $repository_id,
            $remote_server_id,
        ));

        $driver->shouldReceive('makeGerritProjectReadOnly')->with($server, 'projname/repo_01')->once();

        $event->process();
    }
}
