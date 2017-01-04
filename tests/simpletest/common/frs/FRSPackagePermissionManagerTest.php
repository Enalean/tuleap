<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

class FRSPackagePermissionManagerTest extends TuleapTestCase
{
    /**
     * @var FRSPermissionManager
     */
    private $permission_manager;

    /**
     * @var \FRSPackageFactory
     */
    private $package_factory;

    /**
     * @var \FRSPackage
     */
    private $package;

    /**
     * @var PackagePermissionManager
     */
    private $package_permission_manager;

    /**
     * @var \PFUser
     */
    private $user;

    /**
     * @var \Project
     */
    private $project;

    public function setUp()
    {
        $this->permission_manager = mock('Tuleap\FRS\FRSPermissionManager');
        $this->package_factory    = mock('FRSPackageFactory');


        $this->package_permission_manager = new PackagePermissionManager(
            $this->permission_manager,
            $this->package_factory
        );

        $this->user    = mock('PFUser');
        $this->package = mock('FRSPackage');
        $this->project = aMockProject()->withId(100)->build();
    }

    public function itReturnsTrueWhenPackageIsHiddenAndUserIsFrsAdmin()
    {
        stub($this->package)->isHidden()->returns(true);
        stub($this->package_factory)->userCanAdmin($this->user, $this->project->getId())->returns(true);

        $this->assertTrue(
            $this->package_permission_manager->canUserSeePackage($this->user, $this->package, $this->project)
        );
    }

    public function itReturnsFalseWhenPackageIsHiddenAndUserDoesntHaveAdminPermissions()
    {
        stub($this->package)->isHidden()->returns(true);
        stub($this->package_factory)->userCanAdmin($this->user, $this->project->getId())->returns(false);

        $this->assertFalse(
            $this->package_permission_manager->canUserSeePackage($this->user, $this->package, $this->project)
        );
    }

    public function itReturnsTrueWhenPackageIsActiveAndUserCanAccessFrsService()
    {
        stub($this->package)->isActive()->returns(true);
        stub($this->permission_manager)->userCanRead($this->project, $this->user)->returns(true);

        $this->assertTrue(
            $this->package_permission_manager->canUserSeePackage($this->user, $this->package, $this->project)
        );
    }

    public function itReturnsFalseWhenPackageIsActiveAndUserCannotAccessFrsService()
    {
        stub($this->package)->isActive()->returns(false);
        stub($this->permission_manager)->userCanRead($this->project, $this->user)->returns(false);

        $this->assertFalse(
            $this->package_permission_manager->canUserSeePackage($this->user, $this->package, $this->project)
        );
    }
}
