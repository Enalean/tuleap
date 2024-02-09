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

namespace Tuleap\Baseline\Adapter\Administration;

use Tuleap\Baseline\Adapter\ProjectProxy;
use Tuleap\Baseline\Domain\RoleBaselineAdmin;
use Tuleap\Baseline\Support\RoleAssignmentTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;

class RoleAssignmentsHistoryEntryAdderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItAddsAProjectHistoryEntryForRoleAndGroups(): void
    {
        $project_id  = 666;
        $history_key = "some_history_entry_key";

        $dao         = $this->createMock(\ProjectHistoryDao::class);
        $project     = ProjectProxy::buildFromProject(ProjectTestBuilder::aProject()->withId($project_id)->build());
        $assignments = RoleAssignmentTestBuilder::aRoleAssignment(new RoleBaselineAdmin())->withUserGroups(
            ProjectUGroupTestBuilder::buildProjectMembers(),
            ProjectUGroupTestBuilder::aCustomUserGroup(105)->withName("Developers")->build()
        )->build();

        $dao->expects(self::once())->method("groupAddHistory")->with(
            $history_key,
            "ugroup_project_members_name_key,Developers",
            $project_id
        );

        (new RoleAssignmentsHistoryEntryAdder(
            $dao,
            ProjectByIDFactoryStub::buildWith(ProjectTestBuilder::aProject()->build()),
            ProvideCurrentUserStub::buildCurrentUserByDefault(),
        ))->addProjectHistoryEntryForRoleAndGroups(
            $project,
            $history_key,
            ...$assignments
        );
    }
}
