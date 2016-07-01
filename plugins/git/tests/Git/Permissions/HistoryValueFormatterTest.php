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
use Git;

require_once dirname(__FILE__).'/../../bootstrap.php';

class HistoryValueFormatterTest extends TuleapTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->ugroup_01 = stub('ProjectUGroup')->getId()->returns(101);
        $this->ugroup_02 = stub('ProjectUGroup')->getId()->returns(102);
        $this->ugroup_03 = stub('ProjectUGroup')->getId()->returns(103);

        stub($this->ugroup_01)->getName()->returns('Contributors');
        stub($this->ugroup_02)->getName()->returns('Developers');
        stub($this->ugroup_03)->getName()->returns('Admins');

        $this->project             = stub('Project')->getID()->returns(101);
        $this->repository          = aGitRepository()->withId(1)->withProject($this->project)->build();
        $this->migrated_repository = aGitRepository()
            ->withId(1)
            ->withProject($this->project)
            ->withRemoteServerId(1)
            ->build();

        $this->permissions_manager = mock('PermissionsManager');
        $this->ugroup_manager      = mock('UGroupManager');
        $this->retriever           = mock('Tuleap\Git\Permissions\FineGrainedRetriever');
        $this->default_factory     = mock('Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory');

        $this->formatter           = new HistoryValueFormatter(
            $this->permissions_manager,
            $this->ugroup_manager,
            $this->retriever,
            $this->default_factory
        );

        stub($this->ugroup_manager)->getUgroupsById($this->project)->returns(array(
            101 => $this->ugroup_01,
            102 => $this->ugroup_02,
            103 => $this->ugroup_03,
        ));
    }

    public function itExportsValueWithoutFineGrainedPermissionsForRepository()
    {
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject(
            $this->project,
            1,
            Git::PERM_READ
        )->returns(array(101));

        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject(
            $this->project,
            1,
            Git::PERM_WRITE
        )->returns(array(102));

        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject(
            $this->project,
            1,
            Git::PERM_WPLUS
        )->returns(array(103));

        $expected_result = <<<EOS
Read: Contributors
Write: Developers
Rewind: Admins
EOS;

        $result = $this->formatter->formatValueForRepository($this->repository);

        $this->assertEqual($result, $expected_result);
    }

    public function itDoesNotExportWriteAndRewindIfRepositoryIsMigratedOnGerrit()
    {
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject(
            $this->project,
            1,
            Git::PERM_READ
        )->returns(array(101));

        $expected_result = <<<EOS
Read: Contributors
EOS;

        $result = $this->formatter->formatValueForRepository($this->migrated_repository);

        $this->assertEqual($result, $expected_result);
    }

    public function itExportsValueWithoutFineGrainedPermissionsForProject()
    {
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject(
            $this->project,
            101,
            Git::DEFAULT_PERM_READ
        )->returns(array(101));

        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject(
            $this->project,
            101,
            Git::DEFAULT_PERM_WRITE
        )->returns(array(102));

        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject(
            $this->project,
            101,
            Git::DEFAULT_PERM_WPLUS
        )->returns(array(103));

        $expected_result = <<<EOS
Read: Contributors
Write: Developers
Rewind: Admins
EOS;

        $result = $this->formatter->formatValueForProject($this->project);

        $this->assertEqual($result, $expected_result);
    }

    public function itDoesNotExportWriteAndRewindIfRepositoryUsesFineGrainedPermissions()
    {
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject(
            $this->project,
            1,
            Git::PERM_READ
        )->returns(array(101));

        stub($this->retriever)->doesRepositoryUseFineGrainedPermissions($this->migrated_repository)->returns(true);

        $expected_result = <<<EOS
Read: Contributors
EOS;

        $result = $this->formatter->formatValueForRepository($this->migrated_repository);

        $this->assertEqual($result, $expected_result);
    }

    public function itExportsValueWithFineGrainedPermissionsForProject()
    {
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject(
            $this->project,
            101,
            Git::DEFAULT_PERM_READ
        )->returns(array(101));

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
            array($this->ugroup_02),
            array($this->ugroup_03)
        );

        $tag_fine_grained_permission = new DefaultFineGrainedPermission(
            2,
            101,
            'refs/tags/*',
            array($this->ugroup_01),
            array()
        );

        stub($this->retriever)->doesProjectUseFineGrainedPermissions($this->project)->returns(true);

        stub($this->default_factory)->getBranchesFineGrainedPermissionsForProject($this->project)
            ->returns(array(1 => $branch_fine_grained_permission));

        stub($this->default_factory)->getTagsFineGrainedPermissionsForProject($this->project)
            ->returns(array(2 => $tag_fine_grained_permission));

        $result = $this->formatter->formatValueForProject($this->project);

        $this->assertEqual($result, $expected_result);
    }
}
