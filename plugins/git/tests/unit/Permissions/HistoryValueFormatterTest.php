<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Permissions;

use Git;
use GitRepository;
use PermissionsManager;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectUGroup;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HistoryValueFormatterTest extends TestCase
{
    private GitRepository $repository;
    private GitRepository $migrated_repository;
    private ProjectUGroup $ugroup_01;
    private ProjectUGroup $ugroup_02;
    private ProjectUGroup $ugroup_03;
    private Project $project;
    private PermissionsManager&MockObject $permissions_manager;
    private FineGrainedRetriever&MockObject $retriever;
    private DefaultFineGrainedPermissionFactory&MockObject $default_factory;
    private FineGrainedPermissionFactory&MockObject $factory;
    private HistoryValueFormatter $formatter;

    #[\Override]
    protected function setUp(): void
    {
        $this->ugroup_01 = ProjectUGroupTestBuilder::aCustomUserGroup(101)->withName('Contributors')->build();
        $this->ugroup_02 = ProjectUGroupTestBuilder::aCustomUserGroup(102)->withName('Developers')->build();
        $this->ugroup_03 = ProjectUGroupTestBuilder::aCustomUserGroup(103)->withName('Admins')->build();

        $this->project    = ProjectTestBuilder::aProject()->build();
        $this->repository = GitRepositoryTestBuilder::aProjectRepository()->withId(1)->inProject($this->project)->build();

        $this->migrated_repository = GitRepositoryTestBuilder::aProjectRepository()->withId(1)->inProject($this->project)
            ->migratedToGerrit()->build();

        $this->permissions_manager = $this->createMock(PermissionsManager::class);
        $ugroup_manager            = $this->createMock(UGroupManager::class);
        $this->retriever           = $this->createMock(FineGrainedRetriever::class);
        $this->default_factory     = $this->createMock(DefaultFineGrainedPermissionFactory::class);
        $this->factory             = $this->createMock(FineGrainedPermissionFactory::class);

        $this->formatter = new HistoryValueFormatter(
            $this->permissions_manager,
            $ugroup_manager,
            $this->retriever,
            $this->default_factory,
            $this->factory
        );

        $ugroup_manager->method('getUgroupsById')->with($this->project)->willReturn([
            101 => $this->ugroup_01,
            102 => $this->ugroup_02,
            103 => $this->ugroup_03,
        ]);
    }

    public function testItExportsValueWithoutFineGrainedPermissionsForRepository(): void
    {
        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')
            ->with($this->project, 1, self::anything())
            ->willReturnCallback(static fn($project, $id, $perm) => match ($perm) {
                Git::PERM_READ  => [101],
                Git::PERM_WRITE => [102],
                Git::PERM_WPLUS => [103],
            });
        $this->retriever->method('doesRepositoryUseFineGrainedPermissions');

        $expected_result = <<<EOS
Read: Contributors
Write: Developers
Rewind: Admins
EOS;

        $result = $this->formatter->formatValueForRepository($this->repository);

        self::assertEquals($expected_result, $result);
    }

    public function testItDoesNotExportWriteAndRewindIfRepositoryIsMigratedOnGerrit(): void
    {
        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')->with($this->project, 1, Git::PERM_READ)->willReturn([101]);
        $this->retriever->method('doesRepositoryUseFineGrainedPermissions');

        $expected_result = <<<EOS
Read: Contributors
EOS;

        $result = $this->formatter->formatValueForRepository($this->migrated_repository);

        self::assertEquals($expected_result, $result);
    }

    public function testItExportsValueWithoutFineGrainedPermissionsForProject(): void
    {
        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')
            ->with($this->project, 101, self::anything())
            ->willReturnCallback(static fn($project, $id, $perm) => match ($perm) {
                Git::DEFAULT_PERM_READ  => [101],
                Git::DEFAULT_PERM_WRITE => [102],
                Git::DEFAULT_PERM_WPLUS => [103],
            });
        $this->retriever->method('doesProjectUseFineGrainedPermissions');

        $expected_result = <<<EOS
Read: Contributors
Write: Developers
Rewind: Admins
EOS;

        $result = $this->formatter->formatValueForProject($this->project);

        self::assertEquals($expected_result, $result);
    }

    public function testItExportsValueWithFineGrainedPermissionsForRepository(): void
    {
        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')->with($this->project, 1, Git::PERM_READ)->willReturn([101]);

        $expected_result = <<<EOS
Read: Contributors
refs/heads/master Write: Developers
refs/heads/master Rewind: Admins
refs/tags/* Write: Contributors
EOS;

        $branch_fine_grained_permission = new FineGrainedPermission(
            1,
            1,
            'refs/heads/master',
            [$this->ugroup_02],
            [$this->ugroup_03]
        );

        $tag_fine_grained_permission = new FineGrainedPermission(
            2,
            1,
            'refs/tags/*',
            [$this->ugroup_01],
            []
        );

        $this->retriever->method('doesRepositoryUseFineGrainedPermissions')->with($this->migrated_repository)->willReturn(true);

        $this->factory->method('getBranchesFineGrainedPermissionsForRepository')->with($this->migrated_repository)->willReturn([1 => $branch_fine_grained_permission]);

        $this->factory->method('getTagsFineGrainedPermissionsForRepository')->with($this->migrated_repository)->willReturn([2 => $tag_fine_grained_permission]);

        $result = $this->formatter->formatValueForRepository($this->migrated_repository);

        self::assertEquals($expected_result, $result);
    }

    public function testItExportsValueWithFineGrainedPermissionsForProject(): void
    {
        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')->with($this->project, 101, Git::DEFAULT_PERM_READ)->willReturn([101]);

        $expected_result = <<<EOS
Read: Contributors
refs/heads/master Write: Developers
refs/heads/master Rewind: Admins
refs/tags/* Write: Contributors
EOS;

        $branch_fine_grained_permission = new DefaultFineGrainedPermission(
            1,
            101,
            'refs/heads/master',
            [$this->ugroup_02],
            [$this->ugroup_03]
        );

        $tag_fine_grained_permission = new DefaultFineGrainedPermission(
            2,
            101,
            'refs/tags/*',
            [$this->ugroup_01],
            []
        );

        $this->retriever->method('doesProjectUseFineGrainedPermissions')->with($this->project)->willReturn(true);

        $this->default_factory->method('getBranchesFineGrainedPermissionsForProject')->with($this->project)->willReturn([1 => $branch_fine_grained_permission]);

        $this->default_factory->method('getTagsFineGrainedPermissionsForProject')->with($this->project)->willReturn([2 => $tag_fine_grained_permission]);

        $result = $this->formatter->formatValueForProject($this->project);

        self::assertEquals($expected_result, $result);
    }
}
