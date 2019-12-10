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

class PermissionChangesDetectorForRepositoryTest extends TuleapTestCase
{
    /**
     * @var PermissionChangesDetector
     */
    private $detector;

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

        $this->branch_fine_grained_permission = new FineGrainedPermission(
            1,
            1,
            'refs/heads/master',
            array(),
            array()
        );

        $this->tag_fine_grained_permission = new FineGrainedPermission(
            2,
            1,
            'refs/tags/*',
            array(),
            array()
        );

        $this->repository = aGitRepository()->withId(1)->build();
    }

    public function itDetectsChangesIfABranchPermissionIsAdded()
    {
        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturns(true);

        $has_changes = $this->detector->areThereChangesInPermissionsForRepository(
            $this->repository,
            array(),
            'on',
            array($this->branch_fine_grained_permission),
            array(),
            array()
        );

        $this->assertTrue($has_changes);
    }

    public function itDetectsChangesIfATagPermissionIsAdded()
    {
        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturns(true);

        $has_changes = $this->detector->areThereChangesInPermissionsForRepository(
            $this->repository,
            array(),
            'on',
            array(),
            array($this->tag_fine_grained_permission),
            array()
        );

        $this->assertTrue($has_changes);
    }

    public function itDetectsChangesIfAtLeastOneFineGrainedPermissionIsUpdated()
    {
        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturns(true);

        $has_changes = $this->detector->areThereChangesInPermissionsForRepository(
            $this->repository,
            array(),
            'on',
            array(),
            array(),
            array($this->tag_fine_grained_permission)
        );

        $this->assertTrue($has_changes);
    }

    public function itDetectsChangesIfFineGrainedPermissionAreEnabled()
    {
        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturns(false);

        $has_changes = $this->detector->areThereChangesInPermissionsForRepository(
            $this->repository,
            array(),
            'on',
            array(),
            array(),
            array()
        );

        $this->assertTrue($has_changes);
    }

    public function itDetectsChangesIfFineGrainedPermissionAreDisabled()
    {
        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturns(true);

        $has_changes = $this->detector->areThereChangesInPermissionsForRepository(
            $this->repository,
            array(),
            false,
            array(),
            array(),
            array()
        );

        $this->assertTrue($has_changes);
    }

    public function itDetectsChangesIfGlobalPermissionAreChanged()
    {
        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturns(false);
        $this->git_permission_manager->shouldReceive('getRepositoryGlobalPermissions')->with($this->repository)->andReturns(array(
            Git::PERM_READ => array('3'),
            Git::PERM_WRITE => array('4')
        ));

        $has_changes = $this->detector->areThereChangesInPermissionsForRepository(
            $this->repository,
            array(Git::PERM_READ => array('3', '101'), Git::PERM_WRITE => array('4')),
            false,
            array(),
            array(),
            array()
        );

        $this->assertTrue($has_changes);
    }

    public function itDoesNotDetectChangesIfNothingChangedWithFineGrainedPermissions()
    {
        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturns(true);
        $this->git_permission_manager->shouldReceive('getRepositoryGlobalPermissions')->with($this->repository)->andReturns(array(
            Git::PERM_READ => array('3')
        ));

        $has_changes = $this->detector->areThereChangesInPermissionsForRepository(
            $this->repository,
            array(Git::PERM_READ => array('3')),
            'on',
            array(),
            array(),
            array()
        );

        $this->assertFalse($has_changes);
    }

    public function itDoesNotDetectChangesIfNothingChangedWithoutFineGrainedPermissions()
    {
        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->repository)->andReturns(false);
        $this->git_permission_manager->shouldReceive('getRepositoryGlobalPermissions')->with($this->repository)->andReturns(array(
            Git::PERM_READ => array('3'),
            Git::PERM_WRITE => array('4')
        ));

        $has_changes = $this->detector->areThereChangesInPermissionsForRepository(
            $this->repository,
            array(Git::PERM_READ => array('3'), Git::PERM_WRITE => array('4')),
            false,
            array(),
            array(),
            array()
        );

        $this->assertFalse($has_changes);
    }
}
