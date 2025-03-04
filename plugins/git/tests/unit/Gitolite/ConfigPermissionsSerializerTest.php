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

declare(strict_types=1);

namespace Tuleap\Git\Gitolite;

use EventManager;
use Git;
use Git_Driver_Gerrit_ProjectCreatorStatus;
use Git_Gitolite_ConfigPermissionsSerializer;
use GitRepository;
use PermissionsManager;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectUGroup;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ConfigPermissionsSerializerTest extends TestCase
{
    private Git_Gitolite_ConfigPermissionsSerializer $serializer;
    private Project $project;
    private int $project_id = 100;
    private GitRepository $repository;
    private int $repository_id = 200;
    private PermissionsManager&MockObject $permissions_manager;

    public function setUp(): void
    {
        $this->project_id++;
        $this->repository_id++;

        $this->project    = ProjectTestBuilder::aProject()->withId($this->project_id)->withUnixName("project$this->project_id")->build();
        $this->repository = GitRepositoryTestBuilder::aProjectRepository()->withId($this->repository_id)->inProject($this->project)->build();

        $this->permissions_manager = $this->createMock(PermissionsManager::class);
        PermissionsManager::setInstance($this->permissions_manager);

        $this->serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            $this->createMock(Git_Driver_Gerrit_ProjectCreatorStatus::class),
            'whatever',
            $this->createMock(FineGrainedRetriever::class),
            $this->createMock(FineGrainedPermissionFactory::class),
            $this->createMock(RegexpFineGrainedRetriever::class),
            $this->createMock(EventManager::class)
        );
    }

    public function tearDown(): void
    {
        PermissionsManager::clearInstance();
    }

    public function testItReturnsEmptyStringForUnknownType(): void
    {
        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')->willReturn([]);
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, '__none__');
        self::assertSame('', $result);
    }

    public function testItReturnsEmptyStringForAUserIdLowerOrEqualThan100(): void
    {
        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')->willReturn([100]);
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        self::assertSame('', $result);
    }

    public function testItReturnsStringWithUserIdIfIdGreaterThan100(): void
    {
        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')->willReturn([101]);
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        self::assertMatchesRegularExpression('/=\s@ug_101$/', $result);
    }

    public function testItReturnsSiteActiveIfUserGroupIsRegistered(): void
    {
        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')->willReturn([ProjectUGroup::REGISTERED]);
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        self::assertMatchesRegularExpression('/=\s@site_active @' . $this->project->getUnixName() . '_project_members$/', $result);
    }

    public function testItReturnsProjectNameWithProjectMemberIfUserIsProjectMember(): void
    {
        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')->willReturn([ProjectUGroup::PROJECT_MEMBERS]);
        $result       = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $project_name = 'project' . $this->project_id;
        self::assertMatchesRegularExpression('/=\s@' . $project_name . '_project_members$/', $result);
    }

    public function testItReturnsProjectNameWithProjectAdminIfUserIsProjectAdmin(): void
    {
        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')->willReturn([ProjectUGroup::PROJECT_ADMIN]);
        $result       = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        $project_name = 'project' . $this->project_id;
        self::assertMatchesRegularExpression('/=\s@' . $project_name . '_project_admin$/', $result);
    }

    public function testItPrefixesWithRForReaders(): void
    {
        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')->willReturn([101]);
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        self::assertMatchesRegularExpression('/^\sR\s\s\s=/', $result);
    }

    public function testItPrefixesWithRWForWriters(): void
    {
        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')->willReturn([101]);
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_WRITE);
        self::assertMatchesRegularExpression('/^\sRW\s\s=/', $result);
    }

    public function testItPrefixesWithRWPlusForWritersPlus(): void
    {
        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')->willReturn([101]);
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_WPLUS);
        self::assertMatchesRegularExpression('/^\sRW\+\s=/', $result);
    }

    public function testItReturnsAllGroupsSeparatedBySpaceIfItHasDifferentGroups(): void
    {
        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')->willReturn([666, ProjectUGroup::REGISTERED]);
        $result = $this->serializer->fetchConfigPermissions($this->project, $this->repository, Git::PERM_READ);
        self::assertSame(' R   = @ug_666 @site_active @' . $this->project->getUnixName() . '_project_members' . PHP_EOL, $result);
    }

    public function testItDeniesAllAccessToRepository(): void
    {
        $result = $this->serializer->denyAccessForRepository();
        self::assertSame(' - refs/.*$ = @all' . PHP_EOL, $result);
    }
}
