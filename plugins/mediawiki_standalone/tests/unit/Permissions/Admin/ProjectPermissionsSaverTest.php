<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Permissions\Admin;

use Tuleap\MediawikiStandalone\Instance\LogUsersOutInstanceTask;
use Tuleap\MediawikiStandalone\Permissions\ISaveProjectPermissionsStub;
use Tuleap\MediawikiStandalone\Service\MediawikiStandaloneService;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EnqueueTaskStub;

class ProjectPermissionsSaverTest extends TestCase
{
    private const PROJECT_ID = 101;

    public function testSave(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->withUsedService(MediawikiStandaloneService::SERVICE_SHORTNAME)
            ->build();

        $history_dao = $this->createMock(\ProjectHistoryDao::class);

        $permissions_dao = ISaveProjectPermissionsStub::buildSelf();

        $enqueue_task = new EnqueueTaskStub();

        $saver = new ProjectPermissionsSaver(
            $permissions_dao,
            $history_dao,
            $enqueue_task,
        );

        $readers = [
            ProjectUGroupTestBuilder::buildProjectMembers(),
            ProjectUGroupTestBuilder::aCustomUserGroup(102)->withName('Developers')->build(),
        ];
        $writers = [
            ProjectUGroupTestBuilder::aCustomUserGroup(102)->withName('Developers')->build(),
        ];
        $admins  = [
            ProjectUGroupTestBuilder::aCustomUserGroup(102)->withName('Developers')->build(),
        ];

        $history_dao
            ->expects(self::exactly(3))
            ->method('groupAddHistory')
            ->withConsecutive(
                [
                    'perm_granted_for_mediawiki_standalone_readers',
                    'ugroup_project_members_name_key,Developers',
                    self::PROJECT_ID,
                ],
                [
                    'perm_granted_for_mediawiki_standalone_writers',
                    'Developers',
                    self::PROJECT_ID,
                ],
                [
                    'perm_granted_for_mediawiki_standalone_admins',
                    'Developers',
                    self::PROJECT_ID,
                ],
            );

        $saver->save(
            $project,
            $readers,
            $writers,
            $admins,
        );

        self::assertEquals(
            [3, 102],
            $permissions_dao->getCapturedReadersUgroupIds()
        );

        self::assertEquals(
            [102],
            $permissions_dao->getCapturedWritersUgroupIds()
        );

        self::assertEquals(
            [102],
            $permissions_dao->getCapturedAdminsUgroupIds()
        );

        self::assertInstanceOf(LogUsersOutInstanceTask::class, $enqueue_task->queue_task);
    }
}
