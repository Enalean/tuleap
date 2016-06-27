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
        $this->formatter           = new HistoryValueFormatter(
            $this->permissions_manager,
            $this->ugroup_manager
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
}
