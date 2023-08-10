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

use FrsRelease;
use FRSReleaseFactory;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tuleap\Test\PHPUnit\TestCase;

class FRSReleasePermissionManagerTest extends TestCase
{
    /**
     * @var MockObject&FRSPermissionManager
     */
    private $frs_service_permission_manager;

    /**
     * @var MockObject&FRSReleaseFactory
     */
    private $release_factory;

    private ReleasePermissionManager $release_permission_manager;

    /**
     * @var MockObject&PFUser
     */
    private $user;

    /**
     * @var MockObject&Project
     */
    private $project;

    /**
     * @var MockObject&FrsRelease
     */
    private $release;

    protected function setUp(): void
    {
        $this->frs_service_permission_manager = $this->createMock(FRSPermissionManager::class);
        $this->release_factory                = $this->createMock(FRSReleaseFactory::class);

        $this->release_permission_manager = new ReleasePermissionManager(
            $this->frs_service_permission_manager,
            $this->release_factory
        );

        $this->project = $this->createConfiguredMock(Project::class, ['getID' => 100, 'isPublic' => false]);
        $this->user    = $this->createMock(PFUser::class);
        $this->release = $this->createMock(FRSRelease::class);
    }

    public function testItReturnsTrueWhenReleaseIsHiddenAndUserIsFrsAdmin()
    {
        $this->release->method('isHidden')->willReturn(true);
        $this->release->method('isActive');
        $this->release_factory->method('userCanAdmin')->with($this->user, $this->project->getID())->willReturn(true);

        self::assertTrue(
            $this->release_permission_manager->canUserSeeRelease($this->user, $this->release, $this->project)
        );
    }

    public function testItReturnsFalseWhenReleaseIsHiddenAndUserDoesntHaveAdminPermissions()
    {
        $this->release->method('isHidden')->willReturn(true);
        $this->release->method('isActive');
        $this->release_factory->method('userCanAdmin')->with($this->user, $this->project->getID())->willReturn(false);

        self::assertFalse(
            $this->release_permission_manager->canUserSeeRelease($this->user, $this->release, $this->project)
        );
    }

    public function testItReturnsTrueWHenReleaseIsActiveAndUserCanAccessFrsServiceAndUserCanAccessRelease()
    {
        $this->release->method('isActive')->willReturn(true);
        $this->release->method('getPackageID');
        $this->release->method('getReleaseID');
        $this->frs_service_permission_manager->method('userCanRead')->with($this->project, $this->user)->willReturn(true);
        $this->release_factory->method('userCanRead')->with($this->project->getID(), $this->release->getPackageID(), $this->release->getReleaseID())->willReturn(true);

        self::assertTrue(
            $this->release_permission_manager->canUserSeeRelease($this->user, $this->release, $this->project)
        );
    }

    public function testItReturnsFalseWhenReleaseIsActiveAndUserCannotAccessFrsService()
    {
        $this->release->method('isActive')->willReturn(true);
        $this->release->method('isHidden');
        $this->frs_service_permission_manager->method('userCanRead')->with($this->project, $this->user)->willReturn(false);

        self::assertFalse(
            $this->release_permission_manager->canUserSeeRelease($this->user, $this->release, $this->project)
        );
    }

    public function testItReturnsFalseWHenReleaseIsActiveAndUserCanAccessFrsServiceAndUserCanNotAccessRelease()
    {
        $this->release->method('isActive')->willReturn(true);
        $this->release->method('getPackageID');
        $this->release->method('getReleaseID');
        $this->release->method('isHidden');
        $this->frs_service_permission_manager->method('userCanRead')->with($this->project, $this->user)->willReturn(true);
        $this->release_factory->method('userCanRead')->with($this->project->getID(), $this->release->getPackageID(), $this->release->getReleaseID())->willReturn(false);

        self::assertFalse(
            $this->release_permission_manager->canUserSeeRelease($this->user, $this->release, $this->project)
        );
    }
}
