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

use TuleapTestCase;
use Git;

require_once __DIR__.'/../../bootstrap.php';

class PermissionChangesDetectorForProjectTest extends TuleapTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
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
            array(),
            array()
        );

        $this->default_tag_fine_grained_permission = new DefaultFineGrainedPermission(
            2,
            101,
            'refs/tags/*',
            array(),
            array()
        );
    }

    public function itDetectsChangesForProjectIfABranchPermissionIsAdded()
    {
        $this->retriever->shouldReceive('doesProjectUseFineGrainedPermissions')->with($this->project)->andReturns(true);

        $has_changes = $this->detector->areThereChangesInPermissionsForProject(
            $this->project,
            array(),
            array(),
            array(),
            'on',
            array($this->default_branch_fine_grained_permission),
            array(),
            array()
        );

        $this->assertTrue($has_changes);
    }

    public function itDetectsChangesForProjectIfATagPermissionIsAdded()
    {
        $this->retriever->shouldReceive('doesProjectUseFineGrainedPermissions')->with($this->project)->andReturns(true);

        $has_changes = $this->detector->areThereChangesInPermissionsForProject(
            $this->project,
            array(),
            array(),
            array(),
            'on',
            array(),
            array($this->default_tag_fine_grained_permission),
            array()
        );

        $this->assertTrue($has_changes);
    }

    public function itDetectsChangesForProjectIfAtLeastOneFineGrainedPermissionIsUpdated()
    {
        $this->retriever->shouldReceive('doesProjectUseFineGrainedPermissions')->with($this->project)->andReturns(true);

        $has_changes = $this->detector->areThereChangesInPermissionsForProject(
            $this->project,
            array(),
            array(),
            array(),
            'on',
            array(),
            array(),
            array($this->default_branch_fine_grained_permission)
        );

        $this->assertTrue($has_changes);
    }

    public function itDetectsChangesForProjectIfFineGrainedPermissionAreEnabled()
    {
        $this->retriever->shouldReceive('doesProjectUseFineGrainedPermissions')->with($this->project)->andReturns(false);

        $has_changes = $this->detector->areThereChangesInPermissionsForProject(
            $this->project,
            array(),
            array(),
            array(),
            'on',
            array(),
            array(),
            array()
        );

        $this->assertTrue($has_changes);
    }

    public function itDetectsChangesForProjectIfFineGrainedPermissionAreDisabled()
    {
        $this->retriever->shouldReceive('doesProjectUseFineGrainedPermissions')->with($this->project)->andReturns(true);

        $has_changes = $this->detector->areThereChangesInPermissionsForProject(
            $this->project,
            array(),
            array(),
            array(),
            false,
            array(),
            array(),
            array()
        );

        $this->assertTrue($has_changes);
    }

    public function itDetectsChangesForProjectIfGlobalPermissionAreChanged()
    {
        $this->retriever->shouldReceive('doesProjectUseFineGrainedPermissions')->with($this->project)->andReturns(false);
        $this->git_permission_manager->shouldReceive('getProjectGlobalPermissions')->with($this->project)->andReturns(array(
            Git::DEFAULT_PERM_READ => array('3'),
            Git::DEFAULT_PERM_WRITE => array('4'),
            Git::DEFAULT_PERM_WPLUS => array(),
        ));

        $has_changes = $this->detector->areThereChangesInPermissionsForProject(
            $this->project,
            array('3', '101'),
            array('4'),
            array(),
            false,
            array(),
            array(),
            array()
        );

        $this->assertTrue($has_changes);
    }

    public function itDoesNotDetectChangesForProjectIfNothingChangedWithFineGrainedPermissions()
    {
        $this->retriever->shouldReceive('doesProjectUseFineGrainedPermissions')->with($this->project)->andReturns(true);
        $this->git_permission_manager->shouldReceive('getProjectGlobalPermissions')->with($this->project)->andReturns(array(
            Git::DEFAULT_PERM_READ => array('3')
        ));

        $has_changes = $this->detector->areThereChangesInPermissionsForProject(
            $this->project,
            array('3'),
            array(),
            array(),
            'on',
            array(),
            array(),
            array()
        );

        $this->assertFalse($has_changes);
    }

    public function itDoesNotDetectChangesForProjectIfNothingChangedWithoutFineGrainedPermissions()
    {
        $this->retriever->shouldReceive('doesProjectUseFineGrainedPermissions')->with($this->project)->andReturns(false);
        $this->git_permission_manager->shouldReceive('getProjectGlobalPermissions')->with($this->project)->andReturns(array(
            Git::DEFAULT_PERM_READ => array('3'),
            Git::DEFAULT_PERM_WRITE => array('4'),
            Git::DEFAULT_PERM_WPLUS => array(),
        ));

        $has_changes = $this->detector->areThereChangesInPermissionsForProject(
            $this->project,
            array('3'),
            array('4'),
            array(),
            false,
            array(),
            array(),
            array()
        );

        $this->assertFalse($has_changes);
    }
}
