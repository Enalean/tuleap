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
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PermissionChangesDetectorForProjectTest extends TestCase
{
    private GitPermissionsManager&MockObject $git_permission_manager;
    private FineGrainedRetriever&MockObject $retriever;
    private PermissionChangesDetector $detector;
    private Project $project;
    private DefaultFineGrainedPermission $default_branch_fine_grained_permission;
    private DefaultFineGrainedPermission $default_tag_fine_grained_permission;

    #[\Override]
    public function setUp(): void
    {
        $this->git_permission_manager = $this->createMock(GitPermissionsManager::class);
        $this->retriever              = $this->createMock(FineGrainedRetriever::class);

        $this->detector = new PermissionChangesDetector($this->git_permission_manager, $this->retriever);

        $this->project = ProjectTestBuilder::aProject()->build();

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
        $this->retriever->method('doesProjectUseFineGrainedPermissions')->with($this->project)->willReturn(true);
        $this->git_permission_manager->method('getProjectGlobalPermissions');

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

        self::assertTrue($has_changes);
    }

    public function testItDetectsChangesForProjectIfATagPermissionIsAdded(): void
    {
        $this->retriever->method('doesProjectUseFineGrainedPermissions')->with($this->project)->willReturn(true);
        $this->git_permission_manager->method('getProjectGlobalPermissions');

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

        self::assertTrue($has_changes);
    }

    public function testItDetectsChangesForProjectIfAtLeastOneFineGrainedPermissionIsUpdated(): void
    {
        $this->retriever->method('doesProjectUseFineGrainedPermissions')->with($this->project)->willReturn(true);
        $this->git_permission_manager->method('getProjectGlobalPermissions');

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

        self::assertTrue($has_changes);
    }

    public function testItDetectsChangesForProjectIfFineGrainedPermissionAreEnabled(): void
    {
        $this->retriever->method('doesProjectUseFineGrainedPermissions')->with($this->project)->willReturn(false);

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

        self::assertTrue($has_changes);
    }

    public function testItDetectsChangesForProjectIfFineGrainedPermissionAreDisabled(): void
    {
        $this->retriever->method('doesProjectUseFineGrainedPermissions')->with($this->project)->willReturn(true);

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

        self::assertTrue($has_changes);
    }

    public function testItDetectsChangesForProjectIfGlobalPermissionAreChanged(): void
    {
        $this->retriever->method('doesProjectUseFineGrainedPermissions')->with($this->project)->willReturn(false);
        $this->git_permission_manager->method('getProjectGlobalPermissions')->with($this->project)->willReturn([
            Git::DEFAULT_PERM_READ  => ['3'],
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

        self::assertTrue($has_changes);
    }

    public function testItDoesNotDetectChangesForProjectIfNothingChangedWithFineGrainedPermissions(): void
    {
        $this->retriever->method('doesProjectUseFineGrainedPermissions')->with($this->project)->willReturn(true);
        $this->git_permission_manager->method('getProjectGlobalPermissions')->with($this->project)->willReturn([
            Git::DEFAULT_PERM_READ => ['3'],
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

        self::assertFalse($has_changes);
    }

    public function testItDoesNotDetectChangesForProjectIfNothingChangedWithoutFineGrainedPermissions(): void
    {
        $this->retriever->method('doesProjectUseFineGrainedPermissions')->with($this->project)->willReturn(false);
        $this->git_permission_manager->method('getProjectGlobalPermissions')->with($this->project)->willReturn([
            Git::DEFAULT_PERM_READ  => ['3'],
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

        self::assertFalse($has_changes);
    }
}
