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
use GitPermissionsManager;
use GitRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PermissionChangesDetectorForRepositoryTest extends TestCase
{
    private PermissionChangesDetector $detector;
    private GitPermissionsManager&MockObject $git_permission_manager;
    private FineGrainedRetriever&MockObject $retriever;
    private FineGrainedPermission $branch_fine_grained_permission;
    private FineGrainedPermission $tag_fine_grained_permission;
    private GitRepository $repository;

    protected function setUp(): void
    {
        $this->git_permission_manager = $this->createMock(GitPermissionsManager::class);
        $this->retriever              = $this->createMock(FineGrainedRetriever::class);

        $this->detector = new PermissionChangesDetector($this->git_permission_manager, $this->retriever);

        $this->branch_fine_grained_permission = new FineGrainedPermission(
            1,
            1,
            'refs/heads/master',
            [],
            []
        );

        $this->tag_fine_grained_permission = new FineGrainedPermission(
            2,
            1,
            'refs/tags/*',
            [],
            []
        );

        $this->repository = GitRepositoryTestBuilder::aProjectRepository()->withId(1)->build();
    }

    public function testItDetectsChangesIfABranchPermissionIsAdded(): void
    {
        $this->retriever->method('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->willReturn(true);
        $this->git_permission_manager->method('getRepositoryGlobalPermissions');

        $has_changes = $this->detector->areThereChangesInPermissionsForRepository(
            $this->repository,
            [],
            'on',
            [$this->branch_fine_grained_permission],
            [],
            []
        );

        self::assertTrue($has_changes);
    }

    public function testItDetectsChangesIfATagPermissionIsAdded(): void
    {
        $this->retriever->method('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->willReturn(true);
        $this->git_permission_manager->method('getRepositoryGlobalPermissions');

        $has_changes = $this->detector->areThereChangesInPermissionsForRepository(
            $this->repository,
            [],
            'on',
            [],
            [$this->tag_fine_grained_permission],
            []
        );

        self::assertTrue($has_changes);
    }

    public function testItDetectsChangesIfAtLeastOneFineGrainedPermissionIsUpdated(): void
    {
        $this->retriever->method('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->willReturn(true);
        $this->git_permission_manager->method('getRepositoryGlobalPermissions');

        $has_changes = $this->detector->areThereChangesInPermissionsForRepository(
            $this->repository,
            [],
            'on',
            [],
            [],
            [$this->tag_fine_grained_permission]
        );

        self::assertTrue($has_changes);
    }

    public function testItDetectsChangesIfFineGrainedPermissionAreEnabled(): void
    {
        $this->retriever->method('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->willReturn(false);

        $has_changes = $this->detector->areThereChangesInPermissionsForRepository(
            $this->repository,
            [],
            'on',
            [],
            [],
            []
        );

        self::assertTrue($has_changes);
    }

    public function testItDetectsChangesIfFineGrainedPermissionAreDisabled(): void
    {
        $this->retriever->method('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->willReturn(true);

        $has_changes = $this->detector->areThereChangesInPermissionsForRepository(
            $this->repository,
            [],
            false,
            [],
            [],
            []
        );

        self::assertTrue($has_changes);
    }

    public function testItDetectsChangesIfGlobalPermissionAreChanged(): void
    {
        $this->retriever->method('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->willReturn(false);
        $this->git_permission_manager->method('getRepositoryGlobalPermissions')->with($this->repository)->willReturn([
            Git::PERM_READ  => ['3'],
            Git::PERM_WRITE => ['4'],
        ]);

        $has_changes = $this->detector->areThereChangesInPermissionsForRepository(
            $this->repository,
            [Git::PERM_READ => ['3', '101'], Git::PERM_WRITE => ['4']],
            false,
            [],
            [],
            []
        );

        self::assertTrue($has_changes);
    }

    public function testItDoesNotDetectChangesIfNothingChangedWithFineGrainedPermissions(): void
    {
        $this->retriever->method('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->willReturn(true);
        $this->git_permission_manager->method('getRepositoryGlobalPermissions')->with($this->repository)->willReturn([
            Git::PERM_READ => ['3'],
        ]);

        $has_changes = $this->detector->areThereChangesInPermissionsForRepository(
            $this->repository,
            [Git::PERM_READ => ['3']],
            'on',
            [],
            [],
            []
        );

        self::assertFalse($has_changes);
    }

    public function testItDoesNotDetectChangesIfNothingChangedWithoutFineGrainedPermissions(): void
    {
        $this->retriever->method('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->willReturn(false);
        $this->git_permission_manager->method('getRepositoryGlobalPermissions')->with($this->repository)->willReturn([
            Git::PERM_READ  => ['3'],
            Git::PERM_WRITE => ['4'],
        ]);

        $has_changes = $this->detector->areThereChangesInPermissionsForRepository(
            $this->repository,
            [Git::PERM_READ => ['3'], Git::PERM_WRITE => ['4']],
            false,
            [],
            [],
            []
        );

        self::assertFalse($has_changes);
    }
}
