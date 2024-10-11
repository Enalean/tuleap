<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Gitolite;

use EventManager;
use Git;
use Git_Gitolite_ConfigPermissionsSerializer;
use Mockery;
use PermissionsManager;
use ProjectUGroup;

class ConfigPermissionsSerializerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Git_Gitolite_ConfigPermissionsSerializer
     */
    private $serializer;

    private $project;
    private $project_id = 100;

    private $repository;
    private $repository_id = 200;
    private PermissionsManager $permissions_manager;

    public function setUp(): void
    {
        parent::setUp();

        $this->project_id++;
        $this->repository_id++;

        $this->project = Mockery::spy(\Project::class);
        $this->project->shouldReceive('getId')->andReturns($this->project_id);
        $this->project->shouldReceive('getUnixName')->andReturns('project' . $this->project_id);

        $this->repository = Mockery::spy(\GitRepository::class);
        $this->repository->shouldReceive('getId')->andReturns($this->repository_id);
        $this->repository->shouldReceive('getProject')->andReturn($this->project);

        PermissionsManager::setInstance(Mockery::spy(\PermissionsManager::class));
        $this->permissions_manager = PermissionsManager::instance();

        $this->serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            Mockery::spy(\Git_Driver_Gerrit_ProjectCreatorStatus::class),
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

    public function testItReturnsEmptyStringForUnknownType()
    {
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->andReturns([]);
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, '__none__');
        $this->assertSame('', $result);
    }

    public function testItReturnsEmptyStringForAUserIdLowerOrEqualThan100()
    {
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->andReturns([100]);
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $this->assertSame('', $result);
    }

    public function testItReturnsStringWithUserIdIfIdGreaterThan100()
    {
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->andReturns([101]);
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $this->assertMatchesRegularExpression('/=\s@ug_101$/', $result);
    }

    public function testItReturnsSiteActiveIfUserGroupIsRegistered()
    {
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->andReturns([ProjectUGroup::REGISTERED]);
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $this->assertMatchesRegularExpression('/=\s@site_active @' . $this->project->getUnixName() . '_project_members$/', $result);
    }

    public function testItReturnsProjectNameWithProjectMemberIfUserIsProjectMember()
    {
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->andReturns([ProjectUGroup::PROJECT_MEMBERS]);
        $result       = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $project_name = 'project' . $this->project_id;
        $this->assertMatchesRegularExpression('/=\s@' . $project_name . '_project_members$/', $result);
    }

    public function testItReturnsProjectNameWithProjectAdminIfUserIsProjectAdmin()
    {
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->andReturns([ProjectUGroup::PROJECT_ADMIN]);
        $result       = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $project_name = 'project' . $this->project_id;
        $this->assertMatchesRegularExpression('/=\s@' . $project_name . '_project_admin$/', $result);
    }

    public function testItPrefixesWithRForReaders()
    {
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->andReturns([101]);
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $this->assertMatchesRegularExpression('/^\sR\s\s\s=/', $result);
    }

    public function testItPrefixesWithRWForWriters()
    {
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->andReturns([101]);
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_WRITE);
        $this->assertMatchesRegularExpression('/^\sRW\s\s=/', $result);
    }

    public function testItPrefixesWithRWPlusForWritersPlus()
    {
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->andReturns([101]);
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_WPLUS);
        $this->assertMatchesRegularExpression('/^\sRW\+\s=/', $result);
    }

    public function testItReturnsAllGroupsSeparatedBySpaceIfItHasDifferentGroups()
    {
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->andReturns([666, ProjectUGroup::REGISTERED]);
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $this->assertSame(' R   = @ug_666 @site_active @' . $this->project->getUnixName() . '_project_members' . PHP_EOL, $result);
    }

    public function testItDeniesAllAccessToRepository()
    {
        $result = $this->serializer->denyAccessForRepository();
        $this->assertSame(' - refs/.*$ = @all' . PHP_EOL, $result);
    }
}
