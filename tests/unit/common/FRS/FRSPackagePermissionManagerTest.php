<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\FRS;

use FRSPackage;
use FRSPackageFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;

class FRSPackagePermissionManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

    public function setUp(): void
    {
        $this->permission_manager = \Mockery::mock(FRSPermissionManager::class);
        $this->package_factory    = \Mockery::mock(FRSPackageFactory::class);

        $this->package_permission_manager = new PackagePermissionManager(
            $this->permission_manager,
            $this->package_factory
        );

        $this->user    = \Mockery::mock(PFUser::class);
        $this->project = new \Project(['group_id' => 101]);
    }

    public function testItReturnsTrueWhenPackageIsHiddenAndUserIsFrsAdmin(): void
    {
        $this->package = new FRSPackage(['status_id' => FRSPackage::STATUS_HIDDEN]);
        $this->package_factory->shouldReceive('userCanAdmin')->with($this->user, $this->project->getId())->andReturnTrue();

        $this->assertTrue(
            $this->package_permission_manager->canUserSeePackage($this->user, $this->package, $this->project)
        );
    }

    public function testItReturnsFalseWhenPackageIsHiddenAndUserDoesntHaveAdminPermissions(): void
    {
        $this->package = new FRSPackage(['status_id' => FRSPackage::STATUS_HIDDEN]);
        $this->package_factory->shouldReceive('userCanAdmin')->with($this->user, $this->project->getId())->andReturnFalse();

        $this->assertFalse(
            $this->package_permission_manager->canUserSeePackage($this->user, $this->package, $this->project)
        );
    }

    public function testItReturnsTrueWhenPackageIsActiveAndUserCanAccessFrsService(): void
    {
        $this->package = new FRSPackage(['status_id' => FRSPackage::STATUS_ACTIVE]);
        $this->permission_manager->shouldReceive('userCanRead')->with($this->project, $this->user)->andReturnTrue();

        $this->assertTrue(
            $this->package_permission_manager->canUserSeePackage($this->user, $this->package, $this->project)
        );
    }

    public function testItReturnsFalseWhenPackageIsActiveAndUserCannotAccessFrsService(): void
    {
        $this->package = new FRSPackage(['status_id' => FRSPackage::STATUS_ACTIVE]);
        $this->permission_manager->shouldReceive('userCanRead')->with($this->project, $this->user)->andReturnFalse();

        $this->assertFalse(
            $this->package_permission_manager->canUserSeePackage($this->user, $this->package, $this->project)
        );
    }
}
