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

namespace Tuleap\Git\Permissions;

use Git;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

require_once __DIR__ . '/../../bootstrap.php';

final class PermissionChangesDetectorForRepositoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private PermissionChangesDetector $detector;
    private $git_permission_manager;
    private $retriever;
    private FineGrainedPermission $branch_fine_grained_permission;
    private FineGrainedPermission $tag_fine_grained_permission;
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->git_permission_manager = \Mockery::spy(\GitPermissionsManager::class);
        $this->retriever              = \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRetriever::class);

        $this->detector = new PermissionChangesDetector(
            $this->git_permission_manager,
            $this->retriever
        );

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

        $this->repository = \Mockery::mock(\GitRepository::class)->shouldReceive('getId')->andReturn(1)->getMock();
    }

    public function testItDetectsChangesIfABranchPermissionIsAdded(): void
    {
        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturns(true);

        $has_changes = $this->detector->areThereChangesInPermissionsForRepository(
            $this->repository,
            [],
            'on',
            [$this->branch_fine_grained_permission],
            [],
            []
        );

        $this->assertTrue($has_changes);
    }

    public function testItDetectsChangesIfATagPermissionIsAdded(): void
    {
        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturns(true);

        $has_changes = $this->detector->areThereChangesInPermissionsForRepository(
            $this->repository,
            [],
            'on',
            [],
            [$this->tag_fine_grained_permission],
            []
        );

        $this->assertTrue($has_changes);
    }

    public function testItDetectsChangesIfAtLeastOneFineGrainedPermissionIsUpdated(): void
    {
        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturns(true);

        $has_changes = $this->detector->areThereChangesInPermissionsForRepository(
            $this->repository,
            [],
            'on',
            [],
            [],
            [$this->tag_fine_grained_permission]
        );

        $this->assertTrue($has_changes);
    }

    public function testItDetectsChangesIfFineGrainedPermissionAreEnabled(): void
    {
        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturns(false);

        $has_changes = $this->detector->areThereChangesInPermissionsForRepository(
            $this->repository,
            [],
            'on',
            [],
            [],
            []
        );

        $this->assertTrue($has_changes);
    }

    public function testItDetectsChangesIfFineGrainedPermissionAreDisabled(): void
    {
        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturns(true);

        $has_changes = $this->detector->areThereChangesInPermissionsForRepository(
            $this->repository,
            [],
            false,
            [],
            [],
            []
        );

        $this->assertTrue($has_changes);
    }

    public function testItDetectsChangesIfGlobalPermissionAreChanged(): void
    {
        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturns(false);
        $this->git_permission_manager->shouldReceive('getRepositoryGlobalPermissions')->with($this->repository)->andReturns([
            Git::PERM_READ => ['3'],
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

        $this->assertTrue($has_changes);
    }

    public function testItDoesNotDetectChangesIfNothingChangedWithFineGrainedPermissions(): void
    {
        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturns(true);
        $this->git_permission_manager->shouldReceive('getRepositoryGlobalPermissions')->with($this->repository)->andReturns([
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

        $this->assertFalse($has_changes);
    }

    public function testItDoesNotDetectChangesIfNothingChangedWithoutFineGrainedPermissions(): void
    {
        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturns(false);
        $this->git_permission_manager->shouldReceive('getRepositoryGlobalPermissions')->with($this->repository)->andReturns([
            Git::PERM_READ => ['3'],
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

        $this->assertFalse($has_changes);
    }
}
