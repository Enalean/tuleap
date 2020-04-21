<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\FRS;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class FRSReleasePermissionManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var FRSPermissionManager
     */
    private $frs_service_permission_manager;

    /**
     * @var \FRSReleaseFactory
     */
    private $release_factory;

    /**
     * @var ReleasePermissionManager
     */
    private $release_permission_manager;

    /**
     * @var \PFUser
     */
    private $user;

    /**
     * @var \Project
     */
    private $project;

    /**
     * @var \FrsRelease
     */
    private $release;

    protected function setUp(): void
    {
        $this->frs_service_permission_manager = \Mockery::spy(\Tuleap\FRS\FRSPermissionManager::class);
        $this->release_factory                = \Mockery::spy(\FRSReleaseFactory::class);

        $this->release_permission_manager = new ReleasePermissionManager(
            $this->frs_service_permission_manager,
            $this->release_factory
        );

        $this->project = \Mockery::spy(\Project::class, ['getID' => 100, 'getUnixName' => false, 'isPublic' => false]);
        $this->user    = \Mockery::spy(\PFUser::class);
        $this->release = \Mockery::spy(\FRSRelease::class);
    }

    public function testItReturnsTrueWhenReleaseIsHiddenAndUserIsFrsAdmin()
    {
        $this->release->shouldReceive('isHidden')->andReturns(true);
        $this->release_factory->shouldReceive('userCanAdmin')->with($this->user, $this->project->getID())->andReturns(true);

        $this->assertTrue(
            $this->release_permission_manager->canUserSeeRelease($this->user, $this->release, $this->project)
        );
    }

    public function testItReturnsFalseWhenReleaseIsHiddenAndUserDoesntHaveAdminPermissions()
    {
        $this->release->shouldReceive('isHidden')->andReturns(true);
        $this->release_factory->shouldReceive('userCanAdmin')->with($this->user, $this->project->getID())->andReturns(false);

        $this->assertFalse(
            $this->release_permission_manager->canUserSeeRelease($this->user, $this->release, $this->project)
        );
    }

    public function testItReturnsTrueWHenReleaseIsActiveAndUserCanAccessFrsServiceAndUserCanAccessRelease()
    {
        $this->release->shouldReceive('isActive')->andReturns(true);
        $this->frs_service_permission_manager->shouldReceive('userCanRead')->with($this->project, $this->user)->andReturns(true);
        $this->release_factory->shouldReceive('userCanRead')->with($this->project->getID(), $this->release->getPackageID(), $this->release->getReleaseID())->andReturns(true);

        $this->assertTrue(
            $this->release_permission_manager->canUserSeeRelease($this->user, $this->release, $this->project)
        );
    }

    public function testItReturnsFalseWhenReleaseIsActiveAndUserCannotAccessFrsService()
    {
        $this->release->shouldReceive('isActive')->andReturns(true);
        $this->frs_service_permission_manager->shouldReceive('userCanRead')->with($this->project, $this->user)->andReturns(false);

        $this->assertFalse(
            $this->release_permission_manager->canUserSeeRelease($this->user, $this->release, $this->project)
        );
    }

    public function testItReturnsFalseWHenReleaseIsActiveAndUserCanAccessFrsServiceAndUserCanNotAccessRelease()
    {
        $this->release->shouldReceive('isActive')->andReturns(true);
        $this->frs_service_permission_manager->shouldReceive('userCanRead')->with($this->project, $this->user)->andReturns(true);
        $this->release_factory->shouldReceive('userCanRead')->with($this->project->getID(), $this->release->getPackageID(), $this->release->getReleaseID())->andReturns(false);

        $this->assertFalse(
            $this->release_permission_manager->canUserSeeRelease($this->user, $this->release, $this->project)
        );
    }
}
