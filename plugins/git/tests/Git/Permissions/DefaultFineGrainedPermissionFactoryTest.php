<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

require_once __DIR__ .'/../../bootstrap.php';

class DefaultFineGrainedPermissionFactoryTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->dao            = safe_mock(FineGrainedDao::class);
        $this->ugroup_manager = \Mockery::spy(\UGroupManager::class);
        $this->normalizer     = \Mockery::spy(\PermissionsNormalizer::class);

        $this->factory = new DefaultFineGrainedPermissionFactory(
            $this->dao,
            $this->ugroup_manager,
            $this->normalizer,
            \Mockery::spy(\PermissionsManager::class),
            new PatternValidator(
                new FineGrainedPatternValidator(),
                new FineGrainedRegexpValidator(),
                \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class)
            ),
            new FineGrainedPermissionSorter(),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class)
        );

        $this->project         = \Mockery::spy(\Project::class)->shouldReceive('getID')->andReturns(101)->getMock();
        $this->project_manager = \Mockery::spy(\ProjectManager::class);

        $ugroup_01 = \Mockery::spy(\ProjectUGroup::class)->shouldReceive('getId')->andReturns(101)->getMock();
        $ugroup_02 = \Mockery::spy(\ProjectUGroup::class)->shouldReceive('getId')->andReturns(102)->getMock();
        $ugroup_03 = \Mockery::spy(\ProjectUGroup::class)->shouldReceive('getId')->andReturns(103)->getMock();

        $this->ugroup_manager->shouldReceive('getById')->with(101)->andReturns($ugroup_01);
        $this->ugroup_manager->shouldReceive('getById')->with(102)->andReturns($ugroup_02);
        $this->ugroup_manager->shouldReceive('getById')->with(103)->andReturns($ugroup_03);
        $this->project_manager->shouldReceive('getProject')->with(101)->andReturns($this->project);
        $this->normalizer->shouldReceive('getNormalizedUGroupIds')->andReturns(array());

        $this->dao->shouldReceive('searchDefaultBranchesFineGrainedPermissions')->andReturns(\TestHelper::arrayToDar(array(
            'id'         => 1,
            'project_id' => 101,
            'pattern'    => 'refs/heads/master',
        )));

        $this->dao->shouldReceive('searchDefaultTagsFineGrainedPermissions')->andReturns(\TestHelper::arrayToDar(array(
            'id'         => 2,
            'project_id' => 101,
            'pattern'    => 'refs/tags/v1',
        )));

        $this->dao->shouldReceive('searchDefaultWriterUgroupIdsForFineGrainedPermissions')->with(1)->andReturns(\TestHelper::arrayToDar(array('ugroup_id' => 101), array('ugroup_id' => 102)));

        $this->dao->shouldReceive('searchDefaultRewinderUgroupIdsForFineGrainePermissions')->with(1)->andReturns(\TestHelper::arrayToDar(array(
            'ugroup_id' => 103,
        )));

        $this->dao->shouldReceive('searchDefaultWriterUgroupIdsForFineGrainedPermissions')->with(2)->andReturns(\TestHelper::arrayToDar(array(
            'ugroup_id' => 101,
        )));

        $this->dao->shouldReceive('searchDefaultRewinderUgroupIdsForFineGrainePermissions')->with(2)->andReturns(\TestHelper::arrayToDar(array(
            'ugroup_id' => 102,
        )));
    }

    public function itRetrievesUpdatedPermissions()
    {
        $request = aRequest()->with('edit-branch-write', array(
            1 => array(101, 102),
        ))->with('edit-branch-rewind', array(
            1 => array(102),
        ))->with('edit-tag-write', array(
            2 => array(101),
        ))->with('edit-tag-rewind', array(
            2 => array(102),
        ))->with('group_id', 101)
          ->withProjectManager($this->project_manager)
          ->build();

        $updated = $this->factory->getUpdatedPermissionsFromRequest($request, $this->project);

        $this->assertArrayNotEmpty($updated);
        $this->assertCount($updated, 1);
        $this->assertEqual(array_keys($updated), array(1));
    }

    public function itDealsWithRemovedUgroups()
    {
        $request = aRequest()->with('edit-branch-write', array(
            1 => array(101, 102),
        ))->with('edit-branch-rewind', array(
            1 => array(103),
        ))->with('edit-tag-rewind', array(
            2 => array(102),
        ))->with('group_id', 101)
          ->withProjectManager($this->project_manager)
          ->build();

        $updated = $this->factory->getUpdatedPermissionsFromRequest($request, $this->project);

        $this->assertArrayNotEmpty($updated);
        $this->assertCount($updated, 1);
        $this->assertEqual(array_keys($updated), array(2));
    }
}
