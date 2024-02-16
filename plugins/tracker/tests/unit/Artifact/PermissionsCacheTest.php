<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use Tracker_UserWithReadAllPermission;
use Tracker_Workflow_WorkflowUser;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\User\TuleapFunctionsUser;

final class PermissionsCacheTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItUsesCacheWhenPossible(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(102)->build();
        $user     = UserTestBuilder::aUser()->withId(101)->build();

        $permission_checker = $this->createMock(\Tracker_Permission_PermissionChecker::class);

        $permission_checker->expects(self::once())->method('userCanView')->with($user, $artifact)->willReturn(true);

        PermissionsCache::userCanView($artifact, $user, $permission_checker);
        PermissionsCache::userCanView($artifact, $user, $permission_checker);
    }

    public function testItAlwaysReturnsTrueForWorkflowUser(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(102)->build();
        $user     = new Tracker_Workflow_WorkflowUser();

        $permission_checker = $this->createMock(\Tracker_Permission_PermissionChecker::class);

        self::assertTrue(
            PermissionsCache::userCanView($artifact, $user, $permission_checker),
        );
    }

    public function testItAlwaysReturnsTrueForFunctionsUser(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(102)->build();
        $user     = new TuleapFunctionsUser();

        $permission_checker = $this->createMock(\Tracker_Permission_PermissionChecker::class);

        self::assertTrue(
            PermissionsCache::userCanView($artifact, $user, $permission_checker),
        );
    }

    public function testItAlwaysReturnsTrueForReadAllPermissionsUser(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(102)->build();
        $user     = new Tracker_UserWithReadAllPermission(
            UserTestBuilder::aUser()->build(),
        );

        $permission_checker = $this->createMock(\Tracker_Permission_PermissionChecker::class);

        self::assertTrue(
            PermissionsCache::userCanView($artifact, $user, $permission_checker),
        );
    }
}
