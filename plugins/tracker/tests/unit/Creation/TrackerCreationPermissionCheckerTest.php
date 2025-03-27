<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker;

#[DisableReturnValueGenerationForTestDoubles]
final class TrackerCreationPermissionCheckerTest extends TestCase
{
    private GlobalAdminPermissionsChecker&MockObject $permissions_checker;

    protected function setUp(): void
    {
        $this->permissions_checker = $this->createMock(GlobalAdminPermissionsChecker::class);
    }

    public function testItThrowsANotFoundExceptionWhenTrackerServiceIsNotActivatedInGivenProject(): void
    {
        $project = ProjectTestBuilder::aProject()->withoutServices()->build();

        $this->expectException(NotFoundException::class);

        $checker = new TrackerCreationPermissionChecker($this->permissions_checker);
        $checker->checkANewTrackerCanBeCreated($project, UserTestBuilder::buildWithDefaults());
    }

    public function testItThrowsAForbiddenExceptionWhenUserCantCreateTrackers(): void
    {
        $project = ProjectTestBuilder::aProject()->withUsedService('plugin_tracker')->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $this->permissions_checker->expects($this->once())->method('doesUserHaveTrackerGlobalAdminRightsOnProject')
            ->with($project, $user)->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $checker = new TrackerCreationPermissionChecker($this->permissions_checker);
        $checker->checkANewTrackerCanBeCreated($project, $user);
    }
}
