<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

namespace Tuleap\Tracker\Notifications;

use ProjectUGroup;
use Tuleap\GlobalLanguageMock;
use Tuleap\Notification\UgroupToBeNotifiedPresenter;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use User_ForgeUGroup;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CollectionOfUserGroupPresenterBuilderTest extends TestCase
{
    use GlobalLanguageMock;

    public function testItReturnsAListOfUsersGroupsForNotificationPresenter(): void
    {
        $all_project_user_group = [
            new User_ForgeUGroup(ProjectUGroup::PROJECT_ADMIN, 'Project Admins', 'description 1'),
            new User_ForgeUGroup(ProjectUGroup::REGISTERED, 'Registered users', 'description 2'),
            new User_ForgeUGroup(150, 'B99', 'description 3'),
        ];

        $notified_user_groups = [
            new UgroupToBeNotifiedPresenter(ProjectUGroupTestBuilder::aCustomUserGroup(ProjectUGroup::PROJECT_ADMIN)->build()),
        ];

        $result = new CollectionOfUserGroupPresenterBuilder()->getAllUserGroupsPresenter(
            $all_project_user_group,
            $notified_user_groups
        );

        $expected_groups = [
            [
                'id'       => ProjectUGroup::PROJECT_ADMIN,
                'name'     => 'Project Admins',
                'selected' => true,
            ],
            [
                'id'       => 150,
                'name'     => 'B99',
                'selected' => false,
            ],
        ];

        self::assertEqualsCanonicalizing($expected_groups, $result);
    }
}
