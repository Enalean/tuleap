<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All rights reserved
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

declare(strict_types=1);

namespace Tuleap\Git\Gitolite;

use EventManager;
use Git;
use Git_Driver_Gerrit_ProjectCreatorStatus;
use Git_Gitolite_ConfigPermissionsSerializer;
use GitRepository;
use PermissionsManager;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectUGroup;
use Tuleap\Git\Permissions\FineGrainedPermission;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ConfigPermissionsSerializerFineGrainedPermissionsTest extends TestCase
{
    private FineGrainedRetriever&MockObject $retriever;
    private FineGrainedPermissionFactory&MockObject $factory;
    private Git_Gitolite_ConfigPermissionsSerializer $serializer;
    private GitRepository $repository;
    private ProjectUGroup $ugroup_01;
    private ProjectUGroup $ugroup_02;
    private ProjectUGroup $ugroup_03;
    private ProjectUGroup $ugroup_nobody;
    private FineGrainedPermission $permission_01;
    private FineGrainedPermission $permission_02;
    private FineGrainedPermission $permission_03;
    private RegexpFineGrainedRetriever&MockObject $regexp_retriever;

    public function setUp(): void
    {
        $this->retriever        = $this->createMock(FineGrainedRetriever::class);
        $this->factory          = $this->createMock(FineGrainedPermissionFactory::class);
        $this->regexp_retriever = $this->createMock(RegexpFineGrainedRetriever::class);

        $event_manager    = $this->createMock(EventManager::class);
        $this->serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            $this->createMock(Git_Driver_Gerrit_ProjectCreatorStatus::class),
            'whatever',
            $this->retriever,
            $this->factory,
            $this->regexp_retriever,
            $event_manager
        );
        $event_manager->method('processEvent');


        $project = ProjectTestBuilder::aProject()->withUnixName('')->build();

        $this->repository = GitRepositoryTestBuilder::aProjectRepository()->withId(1)->inProject($project)->build();

        $permissions_manager = $this->createMock(PermissionsManager::class);
        $permissions_manager->method('getAuthorizedUGroupIdsForProject')
            ->with(self::anything(), self::anything(), Git::PERM_READ)
            ->willReturn([ProjectUGroup::REGISTERED]);

        $this->ugroup_01 = ProjectUGroupTestBuilder::aCustomUserGroup(101)->build();
        $this->ugroup_02 = ProjectUGroupTestBuilder::aCustomUserGroup(102)->build();
        $this->ugroup_03 = ProjectUGroupTestBuilder::aCustomUserGroup(103)->build();

        $this->ugroup_nobody = ProjectUGroupTestBuilder::buildNobody();

        $this->permission_01 = new FineGrainedPermission(
            1,
            1,
            'refs/heads/master',
            [],
            []
        );

        $this->permission_02 = new FineGrainedPermission(
            2,
            1,
            'refs/tags/v1',
            [],
            []
        );

        $this->permission_03 = new FineGrainedPermission(
            3,
            1,
            'refs/heads/dev/*',
            [],
            []
        );

        PermissionsManager::setInstance($permissions_manager);
    }

    public function tearDown(): void
    {
        PermissionsManager::clearInstance();
    }

    public function testItMustFollowTheExpectedOrderForPermission(): void
    {
        $this->factory->method('getBranchesFineGrainedPermissionsForRepository')->willReturn([1 => $this->permission_01]);
        $this->factory->method('getTagsFineGrainedPermissionsForRepository')->willReturn([]);

        $writers   = [$this->ugroup_01, $this->ugroup_02];
        $rewinders = [$this->ugroup_03];

        $this->permission_01->setWriters($writers);
        $this->permission_01->setRewinders($rewinders);

        $this->retriever->method('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->willReturn(true);
        $this->regexp_retriever->method('areRegexpActivatedForRepository')->willReturn(false);

        $config = $this->serializer->getForRepository($this->repository);

        $expected = <<<EOS
 R   = @site_active @_project_members
 RW+ refs/heads/master$ = @ug_103
 RW refs/heads/master$ = @ug_101 @ug_102
 - refs/heads/master$ = @all

EOS;

        self::assertSame($expected, $config);
    }

    public function testItFetchesFineGrainedPermissions(): void
    {
        $this->factory->method('getBranchesFineGrainedPermissionsForRepository')->willReturn([1 => $this->permission_01]);
        $this->factory->method('getTagsFineGrainedPermissionsForRepository')->willReturn([2 => $this->permission_02]);

        $writers   = [$this->ugroup_01, $this->ugroup_02];
        $rewinders = [$this->ugroup_03];

        $this->permission_01->setWriters($writers);
        $this->permission_01->setRewinders($rewinders);

        $this->permission_02->setWriters($writers);
        $this->permission_02->setRewinders($rewinders);

        $this->retriever->method('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->willReturn(true);
        $this->regexp_retriever->method('areRegexpActivatedForRepository')->willReturn(false);

        $config = $this->serializer->getForRepository($this->repository);

        $expected = <<<EOS
 R   = @site_active @_project_members
 RW+ refs/heads/master$ = @ug_103
 RW refs/heads/master$ = @ug_101 @ug_102
 - refs/heads/master$ = @all
 RW+ refs/tags/v1$ = @ug_103
 RW refs/tags/v1$ = @ug_101 @ug_102
 - refs/tags/v1$ = @all

EOS;

        self::assertSame($expected, $config);
    }

    public function testItDealsWithNobody(): void
    {
        $this->factory->method('getBranchesFineGrainedPermissionsForRepository')->willReturn([1 => $this->permission_01]);
        $this->factory->method('getTagsFineGrainedPermissionsForRepository')->willReturn([2 => $this->permission_02]);

        $writers          = [$this->ugroup_01, $this->ugroup_02];
        $rewinders        = [$this->ugroup_03];
        $rewinders_nobody = [$this->ugroup_nobody];

        $this->permission_01->setWriters($writers);
        $this->permission_01->setRewinders($rewinders);

        $this->permission_02->setWriters($writers);
        $this->permission_02->setRewinders($rewinders_nobody);

        $this->retriever->method('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->willReturn(true);
        $this->regexp_retriever->method('areRegexpActivatedForRepository')->willReturn(false);

        $config = $this->serializer->getForRepository($this->repository);

        $expected = <<<EOS
 R   = @site_active @_project_members
 RW+ refs/heads/master$ = @ug_103
 RW refs/heads/master$ = @ug_101 @ug_102
 - refs/heads/master$ = @all
 RW refs/tags/v1$ = @ug_101 @ug_102
 - refs/tags/v1$ = @all

EOS;

        self::assertSame($expected, $config);
    }

    public function testItDealsWithStarPath(): void
    {
        $this->factory->method('getBranchesFineGrainedPermissionsForRepository')->willReturn([1 => $this->permission_03]);
        $this->factory->method('getTagsFineGrainedPermissionsForRepository')->willReturn([2 => $this->permission_02]);

        $writers   = [$this->ugroup_01, $this->ugroup_02];
        $rewinders = [$this->ugroup_03];

        $this->permission_03->setWriters($writers);
        $this->permission_03->setRewinders($rewinders);

        $this->permission_02->setWriters($writers);
        $this->permission_02->setRewinders($rewinders);

        $this->retriever->method('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->willReturn(true);
        $this->regexp_retriever->method('areRegexpActivatedForRepository')->willReturn(false);

        $config = $this->serializer->getForRepository($this->repository);

        $expected = <<<EOS
 R   = @site_active @_project_members
 RW+ refs/heads/dev/.*$ = @ug_103
 RW refs/heads/dev/.*$ = @ug_101 @ug_102
 - refs/heads/dev/.*$ = @all
 RW+ refs/tags/v1$ = @ug_103
 RW refs/tags/v1$ = @ug_101 @ug_102
 - refs/tags/v1$ = @all

EOS;

        self::assertSame($expected, $config);
    }

    public function testItDealsWithNoUgroupSelected(): void
    {
        $this->factory->method('getBranchesFineGrainedPermissionsForRepository')->willReturn([1 => $this->permission_01]);
        $this->factory->method('getTagsFineGrainedPermissionsForRepository')->willReturn([2 => $this->permission_02]);

        $writers   = [$this->ugroup_01, $this->ugroup_02];
        $rewinders = [];

        $this->permission_01->setWriters($writers);
        $this->permission_01->setRewinders($rewinders);

        $this->permission_02->setWriters($writers);
        $this->permission_02->setRewinders($rewinders);

        $this->retriever->method('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->willReturn(true);
        $this->regexp_retriever->method('areRegexpActivatedForRepository')->willReturn(false);

        $config = $this->serializer->getForRepository($this->repository);

        $expected = <<<EOS
 R   = @site_active @_project_members
 RW refs/heads/master$ = @ug_101 @ug_102
 - refs/heads/master$ = @all
 RW refs/tags/v1$ = @ug_101 @ug_102
 - refs/tags/v1$ = @all

EOS;

        self::assertSame($expected, $config);
    }

    public function testItDeniesPatternIfNobodyCanWriteAndRewind(): void
    {
        $this->factory->method('getBranchesFineGrainedPermissionsForRepository')->willReturn([1 => $this->permission_01]);
        $this->factory->method('getTagsFineGrainedPermissionsForRepository')->willReturn([2 => $this->permission_02]);

        $writers   = [$this->ugroup_nobody];
        $rewinders = [];

        $this->permission_01->setWriters($writers);
        $this->permission_01->setRewinders($rewinders);

        $this->permission_02->setWriters($writers);
        $this->permission_02->setRewinders($rewinders);

        $this->retriever->method('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->willReturn(true);
        $this->regexp_retriever->method('areRegexpActivatedForRepository')->willReturn(false);

        $config = $this->serializer->getForRepository($this->repository);

        $expected = <<<EOS
 R   = @site_active @_project_members
 - refs/heads/master$ = @all
 - refs/tags/v1$ = @all

EOS;

        self::assertSame($expected, $config);
    }

    public function testItAddEndCharacterAtPatternEndWhenRegexpAreDisabled(): void
    {
        $this->factory->method('getBranchesFineGrainedPermissionsForRepository')->willReturn([1 => $this->permission_01]);
        $this->factory->method('getTagsFineGrainedPermissionsForRepository')->willReturn([2 => $this->permission_02]);

        $writers   = [$this->ugroup_01, $this->ugroup_02];
        $rewinders = [$this->ugroup_03];

        $this->permission_01->setWriters($writers);
        $this->permission_01->setRewinders($rewinders);

        $this->permission_02->setWriters($writers);
        $this->permission_02->setRewinders($rewinders);

        $this->retriever->method('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->willReturn(true);
        $this->regexp_retriever->method('areRegexpActivatedForRepository')->with($this->repository)->willReturn(false);

        $config = $this->serializer->getForRepository($this->repository);

        $expected = <<<EOS
 R   = @site_active @_project_members
 RW+ refs/heads/master$ = @ug_103
 RW refs/heads/master$ = @ug_101 @ug_102
 - refs/heads/master$ = @all
 RW+ refs/tags/v1$ = @ug_103
 RW refs/tags/v1$ = @ug_101 @ug_102
 - refs/tags/v1$ = @all

EOS;

        self::assertSame($expected, $config);
    }

    public function testItDoesntUpdatePatternWhenRegexpAreEnabled(): void
    {
        $this->factory->method('getBranchesFineGrainedPermissionsForRepository')->willReturn([1 => $this->permission_01]);
        $this->factory->method('getTagsFineGrainedPermissionsForRepository')->willReturn([2 => $this->permission_02]);

        $writers   = [$this->ugroup_01, $this->ugroup_02];
        $rewinders = [$this->ugroup_03];

        $this->permission_01->setWriters($writers);
        $this->permission_01->setRewinders($rewinders);

        $this->permission_02->setWriters($writers);
        $this->permission_02->setRewinders($rewinders);

        $this->retriever->method('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->willReturn(true);
        $this->regexp_retriever->method('areRegexpActivatedForRepository')->with($this->repository)->willReturn(true);

        $config = $this->serializer->getForRepository($this->repository);

        $expected = <<<EOS
 R   = @site_active @_project_members
 RW+ refs/heads/master = @ug_103
 RW refs/heads/master = @ug_101 @ug_102
 - refs/heads/master = @all
 RW+ refs/tags/v1 = @ug_103
 RW refs/tags/v1 = @ug_101 @ug_102
 - refs/tags/v1 = @all

EOS;

        self::assertSame($expected, $config);
    }
}
