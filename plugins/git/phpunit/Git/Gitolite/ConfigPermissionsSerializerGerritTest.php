<?php
/**
 * Copyright (c) Enalean, 2018. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Git\Gitolite;

use EventManager;
use Git;
use Git_Driver_Gerrit_ProjectCreatorStatus;
use Git_Gitolite_ConfigPermissionsSerializer;
use Mockery;
use PermissionsManager;
use PHPUnit\Framework\TestCase;
use ProjectUGroup;

require_once __DIR__ . '/../../bootstrap.php';

class ConfigPermissionsSerializerGerritTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $serializer;
    private $repository;
    private $gerrit_status;

    public function setUp(): void
    {
        parent::setUp();

        $project = Mockery::spy(\Project::class);
        $project->shouldReceive('getId')->andReturn(102);
        $project->shouldReceive('getUnixName')->andReturn('gpig');

        $this->repository = Mockery::spy(\GitRepository::class);
        $this->repository->shouldReceive('getId')->andReturn(1001);
        $this->repository->shouldReceive('getProject')->andReturn($project);

        $this->permissions_manager = Mockery::spy(PermissionsManager::class);
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')
            ->with(Mockery::any(), Mockery::any(), Git::PERM_READ)
            ->andReturn([ProjectUGroup::REGISTERED]);
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')
            ->with(Mockery::any(), Mockery::any(), Git::PERM_WRITE)
            ->andReturn([ProjectUGroup::PROJECT_MEMBERS]);
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')
            ->with(Mockery::any(), Mockery::any(), Git::PERM_WPLUS)
            ->andReturn([]);

        PermissionsManager::setInstance($this->permissions_manager);

        $this->gerrit_status = Mockery::spy(\Git_Driver_Gerrit_ProjectCreatorStatus::class);

        $mapper = Mockery::spy(\Git_Mirror_MirrorDataMapper::class);
        $mapper->shouldReceive('fetchAllRepositoryMirrors')->andReturn([]);
        $this->serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            $mapper,
            $this->gerrit_status,
            'whatever',
            Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRetriever::class),
            Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionFactory::class),
            Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class),
            Mockery::spy(EventManager::class)
        );
    }

    public function tearDown(): void
    {
        PermissionsManager::clearInstance();
        parent::tearDown();
    }

    public function testItGeneratesTheDefaultConfiguration()
    {
        $this->assertSame(
            " R   = @site_active @gpig_project_members\n" .
            " RW  = @gpig_project_members\n",
            $this->serializer->getForRepository($this->repository)
        );
    }

    public function testItGrantsEverythingToGerritUserAfterMigrationIsDoneWithSuccess()
    {
        $this->repository->shouldReceive('isMigratedToGerrit')->andReturn(true);
        $this->repository->shouldReceive('getRemoteServerId')->andReturn(2);

        $this->gerrit_status->shouldReceive('getStatus')->with($this->repository)->andReturn(
            Git_Driver_Gerrit_ProjectCreatorStatus::DONE
        );

        $this->assertSame(
            " R   = @site_active @gpig_project_members\n" .
            " RW+ = forge__gerrit_2\n",
            $this->serializer->getForRepository($this->repository)
        );
    }

    public function testItDoesntGrantAllPermissionsToGerritIfMigrationIsWaitingForExecution()
    {
        $this->repository->shouldReceive('isMigratedToGerrit')->andReturn(true);
        $this->repository->shouldReceive('getRemoteServerId')->andReturn(2);

        $this->gerrit_status->shouldReceive('getStatus')->with($this->repository)->andReturn(
            Git_Driver_Gerrit_ProjectCreatorStatus::QUEUE
        );

        $this->assertSame(
            " R   = @site_active @gpig_project_members\n" .
            " RW  = @gpig_project_members\n",
            $this->serializer->getForRepository($this->repository)
        );
    }

    public function testItDoesntGrantAllPermissionsToGerritIfMigrationIsError()
    {
        $this->repository->shouldReceive('isMigratedToGerrit')->andReturn(true);
        $this->repository->shouldReceive('getRemoteServerId')->andReturn(2);

        $this->gerrit_status->shouldReceive('getStatus')->with($this->repository)->andReturn(
            Git_Driver_Gerrit_ProjectCreatorStatus::ERROR
        );

        $this->assertSame(
            " R   = @site_active @gpig_project_members\n" .
            " RW  = @gpig_project_members\n",
            $this->serializer->getForRepository($this->repository)
        );
    }
}
