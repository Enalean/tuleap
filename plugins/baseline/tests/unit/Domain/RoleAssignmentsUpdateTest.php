<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Baseline\Domain;

use Tuleap\Baseline\Adapter\ProjectProxy;
use Tuleap\Baseline\Support\RoleAssignmentTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RoleAssignmentsUpdateTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItThrowsWhenAssignedRolesDoNotBelongToGivenProject(): void
    {
        $project     = ProjectProxy::buildFromProject(ProjectTestBuilder::aProject()->withId(102)->build());
        $assignments = RoleAssignmentTestBuilder::aRoleAssignment(new RoleBaselineAdmin())
            ->withUserGroups(ProjectUGroupTestBuilder::buildProjectMembers())
            ->withProject(ProjectProxy::buildFromProject(ProjectTestBuilder::aProject()->withId(105)->build()))
            ->build();

        $this->expectException(UserGroupDoesNotExistOrBelongToCurrentBaselineProjectException::class);

        RoleAssignmentsUpdate::build(
            $project,
            ...$assignments
        );
    }

    public function testItTBuilds(): void
    {
        $project     = ProjectProxy::buildFromProject(ProjectTestBuilder::aProject()->withId(102)->build());
        $assignments = RoleAssignmentTestBuilder::aRoleAssignment(new RoleBaselineAdmin())
            ->withUserGroups(ProjectUGroupTestBuilder::buildProjectMembers())
            ->withProject(ProjectProxy::buildFromProject(ProjectTestBuilder::aProject()->withId(102)->build()))
            ->build();

        $update = RoleAssignmentsUpdate::build(
            $project,
            ...$assignments
        );

        self::assertEquals($project, $update->getProject());
        self::assertEquals($assignments, $update->getAssignments());
    }
}
