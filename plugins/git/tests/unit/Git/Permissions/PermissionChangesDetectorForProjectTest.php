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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Git;

require_once __DIR__ . '/../../bootstrap.php';

class PermissionChangesDetectorForProjectTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function setUp(): void
    {
        parent::setUp();
        $this->git_permission_manager = \Mockery::spy(\GitPermissionsManager::class);
        $this->retriever              = \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRetriever::class);

        $this->detector = new PermissionChangesDetector(
            $this->git_permission_manager,
            $this->retriever
        );

        $this->project = \Mockery::spy(\Project::class)->shouldReceive('getId')->andReturns(101)->getMock();

        $this->default_branch_fine_grained_permission = new DefaultFineGrainedPermission(
            1,
            101,
            'refs/heads/master',
            [],
            []
        );

        $this->default_tag_fine_grained_permission = new DefaultFineGrainedPermission(
            2,
            101,
            'refs/tags/*',
            [],
            []
        );
    }

    public function testItDetectsChangesForProjectIfABranchPermissionIsAdded(): void
    {
        $this->retriever->shouldReceive('doesProjectUseFineGrainedPermissions')->with($this->project)->andReturns(true);

        $has_changes = $this->detector->areThereChangesInPermissionsForProject(
            $this->project,
            [],
            [],
            [],
            'on',
            [$this->default_branch_fine_grained_permission],
            [],
            []
        );

        $this->assertTrue($has_changes);
    }

    public function testItDetectsChangesForProjectIfATagPermissionIsAdded(): void
    {
        $this->retriever->shouldReceive('doesProjectUseFineGrainedPermissions')->with($this->project)->andReturns(true);

        $has_changes = $this->detector->areThereChangesInPermissionsForProject(
            $this->project,
            [],
            [],
            [],
            'on',
            [],
            [$this->default_tag_fine_grained_permission],
            []
        );

        $this->assertTrue($has_changes);
    }

    public function testItDetectsChangesForProjectIfAtLeastOneFineGrainedPermissionIsUpdated(): void
    {
        $this->retriever->shouldReceive('doesProjectUseFineGrainedPermissions')->with($this->project)->andReturns(true);

        $has_changes = $this->detector->areThereChangesInPermissionsForProject(
            $this->project,
            [],
            [],
            [],
            'on',
            [],
            [],
            [$this->default_branch_fine_grained_permission]
        );

        $this->assertTrue($has_changes);
    }

    public function testItDetectsChangesForProjectIfFineGrainedPermissionAreEnabled(): void
    {
        $this->retriever->shouldReceive('doesProjectUseFineGrainedPermissions')->with($this->project)->andReturns(false);

        $has_changes = $this->detector->areThereChangesInPermissionsForProject(
            $this->project,
            [],
            [],
            [],
            'on',
            [],
            [],
            []
        );

        $this->assertTrue($has_changes);
    }

    public function testItDetectsChangesForProjectIfFineGrainedPermissionAreDisabled(): void
    {
        $this->retriever->shouldReceive('doesProjectUseFineGrainedPermissions')->with($this->project)->andReturns(true);

        $has_changes = $this->detector->areThereChangesInPermissionsForProject(
            $this->project,
            [],
            [],
            [],
            false,
            [],
            [],
            []
        );

        $this->assertTrue($has_changes);
    }

    public function testItDetectsChangesForProjectIfGlobalPermissionAreChanged(): void
    {
        $this->retriever->shouldReceive('doesProjectUseFineGrainedPermissions')->with($this->project)->andReturns(false);
        $this->git_permission_manager->shouldReceive('getProjectGlobalPermissions')->with($this->project)->andReturns([
            Git::DEFAULT_PERM_READ => ['3'],
            Git::DEFAULT_PERM_WRITE => ['4'],
            Git::DEFAULT_PERM_WPLUS => [],
        ]);

        $has_changes = $this->detector->areThereChangesInPermissionsForProject(
            $this->project,
            ['3', '101'],
            ['4'],
            [],
            false,
            [],
            [],
            []
        );

        $this->assertTrue($has_changes);
    }

    public function testItDoesNotDetectChangesForProjectIfNothingChangedWithFineGrainedPermissions(): void
    {
        $this->retriever->shouldReceive('doesProjectUseFineGrainedPermissions')->with($this->project)->andReturns(true);
        $this->git_permission_manager->shouldReceive('getProjectGlobalPermissions')->with($this->project)->andReturns([
            Git::DEFAULT_PERM_READ => ['3']
        ]);

        $has_changes = $this->detector->areThereChangesInPermissionsForProject(
            $this->project,
            ['3'],
            [],
            [],
            'on',
            [],
            [],
            []
        );

        $this->assertFalse($has_changes);
    }

    public function testItDoesNotDetectChangesForProjectIfNothingChangedWithoutFineGrainedPermissions(): void
    {
        $this->retriever->shouldReceive('doesProjectUseFineGrainedPermissions')->with($this->project)->andReturns(false);
        $this->git_permission_manager->shouldReceive('getProjectGlobalPermissions')->with($this->project)->andReturns([
            Git::DEFAULT_PERM_READ => ['3'],
            Git::DEFAULT_PERM_WRITE => ['4'],
            Git::DEFAULT_PERM_WPLUS => [],
        ]);

        $has_changes = $this->detector->areThereChangesInPermissionsForProject(
            $this->project,
            ['3'],
            ['4'],
            [],
            false,
            [],
            [],
            []
        );

        $this->assertFalse($has_changes);
    }
}
