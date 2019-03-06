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

namespace Tuleap\FRS;

use TuleapTestCase;
use PermissionsDao;
use Project;
use PFUser;

class FRSPermissionManagerTest extends TuleapTestCase
{
    private $permission_manager;
    private $user;
    private $project;

    public function setUp()
    {
        $this->permission_dao     = mock('Tuleap\FRS\FRSPermissionDao');
        $this->permission_factory = mock('Tuleap\FRS\FRSPermissionFactory');
        $this->project            = mock('Project');
        $this->user               = mock('PFUser');
        stub($this->project)->getId()->returns(101);

        $this->permission_manager = new FRSPermissionManager(
            $this->permission_dao,
            $this->permission_factory
        );
    }

    public function itReturnsTrueIfUserIsProjectAdmin()
    {
        stub($this->user)->isAdmin()->returns(true);

        $this->assertTrue($this->permission_manager->isAdmin($this->project, $this->user));
    }

    public function itRetrunsTrusIfUserIsInFrsGroupAdmin()
    {
        stub($this->user)->isAdmin()->returns(false);

        $permissions = array(
            '5' => new FRSPermission('101', FRSPermission::FRS_ADMIN, '5'),
            '4' => new FRSPermission('101', FRSPermission::FRS_ADMIN, '4')
        );

        stub($this->permission_factory)->getFrsUgroupsByPermission()->returns($permissions);
        stub($this->user)->isMemberOfUGroup()->returns(true);

        $this->assertTrue($this->permission_manager->isAdmin($this->project, $this->user));
    }

    public function itReturnsFalseIfUserIsNotProjectAdminAndUserIsNotInFrsGroupAdmin()
    {
        stub($this->user)->isAdmin()->returns(false);

        $permissions = array(
            '5' => new FRSPermission('101', FRSPermission::FRS_ADMIN, '5'),
            '4' => new FRSPermission('101', FRSPermission::FRS_ADMIN, '4')
        );

        stub($this->permission_factory)->getFrsUgroupsByPermission()->returns($permissions);
        stub($this->user)->isMemberOfUGroup()->returns(false);

        $this->assertFalse($this->permission_manager->isAdmin($this->project, $this->user));
    }
}
