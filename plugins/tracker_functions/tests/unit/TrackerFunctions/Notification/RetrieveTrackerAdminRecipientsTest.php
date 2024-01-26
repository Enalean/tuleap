<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\TrackerFunctions\Notification;

use Tracker;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\Project\ProjectAdminsUGroupRetrieverStub;
use Tuleap\Test\Stubs\UGroupRetrieverStub;

final class RetrieveTrackerAdminRecipientsTest extends TestCase
{
    public function testItFaultWhenRetrieveNoUser(): void
    {
        $tracker      = $this->createMock(Tracker::class);
        $admin_ugroup = ProjectUGroupTestBuilder::buildProjectAdmins();
        $admin_ugroup->setMembers();
        $retriever = new RetrieveTrackerAdminRecipients(
            ProjectAdminsUGroupRetrieverStub::build($admin_ugroup),
            UGroupRetrieverStub::buildWithUserGroups()
        );

        $tracker->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());
        $tracker->method('getAuthorizedUgroupsByPermissionType')->willReturn([]);

        $result = $retriever->retrieveRecipients($tracker);

        self::assertTrue(Result::isErr($result));
        self::assertEquals('No tracker administrator found', (string) $result->error);
    }

    public function testItRemoveDuplicates(): void
    {
        $alice        = UserTestBuilder::buildWithId(102);
        $bob          = UserTestBuilder::buildWithId(103);
        $dylan        = UserTestBuilder::buildWithId(104);
        $tracker      = $this->createMock(Tracker::class);
        $admin_ugroup = ProjectUGroupTestBuilder::buildProjectAdmins();
        $admin_ugroup->setMembers($alice, $bob);
        $tracker_ugroup = ProjectUGroupTestBuilder::aCustomUserGroup(101)
            ->withUsers($bob, $dylan)
            ->build();
        $retriever      = new RetrieveTrackerAdminRecipients(
            ProjectAdminsUGroupRetrieverStub::build($admin_ugroup),
            UGroupRetrieverStub::buildWithUserGroups($tracker_ugroup)
        );

        $tracker->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());
        $tracker->method('getAuthorizedUgroupsByPermissionType')->willReturn([Tracker::PERMISSION_ADMIN => [101]]);

        $result = $retriever->retrieveRecipients($tracker);
        self::assertTrue(Result::isOk($result));
        $users = $result->unwrapOr([]);
        self::assertCount(3, $users);
        self::assertContains($alice, $users);
        self::assertContains($bob, $users);
        self::assertContains($dylan, $users);
    }
}
