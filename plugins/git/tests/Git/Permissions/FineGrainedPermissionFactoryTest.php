<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

require_once dirname(__FILE__).'/../../bootstrap.php';

class FineGrainedPermissionFactoryTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->dao            = mock('Tuleap\Git\Permissions\FineGrainedDao');
        $this->ugroup_manager = mock('UGroupManager');
        $this->normalizer     = mock('PermissionsNormalizer');

        $this->factory = new FineGrainedPermissionFactory(
            $this->dao,
            $this->ugroup_manager,
            $this->normalizer,
            mock('PermissionsManager'),
            new PatternValidator(
                new FineGrainedPatternValidator(),
                new FineGrainedRegexpValidator(),
                mock('Tuleap\Git\Permissions\RegexpFineGrainedRetriever')
            ),
            new FineGrainedPermissionSorter(),
            mock('Tuleap\Git\XmlUgroupRetriever')
        );

        $this->repository = aGitRepository()->withId(43)->build();

        $project               = mock('Project');
        $this->project_manager = mock('ProjectManager');

        $ugroup_01 = stub('ProjectUGroup')->getId()->returns(101);
        $ugroup_02 = stub('ProjectUGroup')->getId()->returns(102);
        $ugroup_03 = stub('ProjectUGroup')->getId()->returns(103);

        stub($this->ugroup_manager)->getById(101)->returns($ugroup_01);
        stub($this->ugroup_manager)->getById(102)->returns($ugroup_02);
        stub($this->ugroup_manager)->getById(103)->returns($ugroup_03);
        stub($this->project_manager)->getProject(101)->returns($project);
        stub($this->normalizer)->getNormalizedUGroupIds()->returns(array());

        stub($this->dao)->searchBranchesFineGrainedPermissionsForRepository()->returnsDar(array(
            'id'            => 1,
            'repository_id' => 43,
            'pattern'       => 'refs/heads/master',
        ));

        stub($this->dao)->searchTagsFineGrainedPermissionsForRepository()->returnsDar(array(
            'id'            => 2,
            'repository_id' => 43,
            'pattern'       => 'refs/tags/v1',
        ));

        stub($this->dao)->searchWriterUgroupIdsForFineGrainedPermissions(1)->returnsDar(
            array('ugroup_id' => 101),
            array('ugroup_id' => 102)
        );

        stub($this->dao)->searchRewinderUgroupIdsForFineGrainePermissions(1)->returnsDar(array(
            'ugroup_id' => 103,
        ));

        stub($this->dao)->searchWriterUgroupIdsForFineGrainedPermissions(2)->returnsDar(array(
            'ugroup_id' => 101,
        ));

        stub($this->dao)->searchRewinderUgroupIdsForFineGrainePermissions(2)->returnsDar(array(
            'ugroup_id' => 102,
        ));
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

        $updated = $this->factory->getUpdatedPermissionsFromRequest($request, $this->repository);

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

        $updated = $this->factory->getUpdatedPermissionsFromRequest($request, $this->repository);

        $this->assertArrayNotEmpty($updated);
        $this->assertCount($updated, 1);
        $this->assertEqual(array_keys($updated), array(2));
    }
}
