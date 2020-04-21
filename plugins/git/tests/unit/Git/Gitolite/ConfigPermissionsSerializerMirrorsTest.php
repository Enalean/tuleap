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
use Git_Gitolite_ConfigPermissionsSerializer;
use Git_Mirror_Mirror;
use GitRepository;
use Mockery;
use PermissionsManager;
use PHPUnit\Framework\TestCase;
use ProjectUGroup;

require_once __DIR__ . '/../../bootstrap.php';

class ConfigPermissionsSerializerMirrorsTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $serializer;
    private $mirror_mapper;
    private $repository;
    private $mirror_1;
    private $mirror_2;
    private $permissions_manager;
    private $project;

    public function setUp(): void
    {
        parent::setUp();
        $this->mirror_mapper = Mockery::spy(\Git_Mirror_MirrorDataMapper::class);
        $this->serializer    = new Git_Gitolite_ConfigPermissionsSerializer(
            $this->mirror_mapper,
            Mockery::spy(\Git_Driver_Gerrit_ProjectCreatorStatus::class),
            'whatever',
            Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRetriever::class),
            Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionFactory::class),
            Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class),
            Mockery::spy(EventManager::class)
        );

        $this->project = Mockery::spy(\Project::class);
        $this->project->shouldReceive('getUnixName')->andReturn('foo');

        $this->repository = Mockery::spy(GitRepository::class);
        $this->repository->shouldReceive('getId')->andReturn(115);
        $this->repository->shouldReceive('getProject')->andReturn($this->project);

        $user_mirror1     = Mockery::spy(\PFUser::class);
        $user_mirror1->shouldReceive('getUserName')->andReturn('git_mirror_1');
        $this->mirror_1   = new Git_Mirror_Mirror($user_mirror1, 1, 'url', 'hostname', 'EUR');

        $user_mirror2     = Mockery::spy(\PFUser::class);
        $user_mirror2->shouldReceive('getUserName')->andReturn('git_mirror_2');
        $this->mirror_2   = new Git_Mirror_Mirror($user_mirror2, 2, 'url', 'hostname', 'IND');

        $this->permissions_manager = Mockery::spy(\PermissionsManager::class);
        PermissionsManager::setInstance($this->permissions_manager);
    }

    public function tearDown(): void
    {
        PermissionsManager::clearInstance();
        parent::tearDown();
    }

    public function testItGrantsReadPermissionToOneMirror()
    {
        $this->mirror_mapper->shouldReceive('fetchAllRepositoryMirrors')->with($this->repository)->andReturn(
            array(
                $this->mirror_1
            )
        );

        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')
            ->andReturn(array(ProjectUGroup::REGISTERED));

        $result = $this->serializer->getForRepository($this->repository);
        $this->assertMatchesRegularExpression('/^ R   = git_mirror_1$/m', $result);
    }

    public function testItGrantsReadPermissionToTwoMirrors()
    {
        $this->mirror_mapper->shouldReceive('fetchAllRepositoryMirrors')->with($this->repository)->andReturn(
            array(
                $this->mirror_1,
                $this->mirror_2,
            )
        );

        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')
            ->andReturn(array(ProjectUGroup::REGISTERED));

        $result = $this->serializer->getForRepository($this->repository);
        $this->assertMatchesRegularExpression('/^ R   = git_mirror_1 git_mirror_2$/m', $result);
    }

    public function testItHasNoMirrors()
    {
        $this->mirror_mapper->shouldReceive('fetchAllRepositoryMirrors')->with($this->repository)->andReturn([]);
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->andReturn([]);

        $result = $this->serializer->getForRepository($this->repository);
        $this->assertSame('', $result);
    }
}
