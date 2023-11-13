<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Backend;

use BackendSVN;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Project_AccessRestrictedException;
use ProjectUGroup;
use Tuleap\FakeDataAccessResult;
use Tuleap\GlobalSVNPollution;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use UGroupDao;

class BackendSVNTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalSVNPollution;

    /**
     * @var MockObject&BackendSVN
     */
    private $backend;

    /**
     * @before
     */
    public function createInstance(): void
    {
        $this->backend = $this->createPartialMock(BackendSVN::class, [
            'getUGroupDao',
            'getUGroupFromRow',
            'getProjectAccessChecker',
        ]);
    }

    public function testItAddsProjectMembers()
    {
        $project = $this->createMock(Project::class);
        $project->method('getMembersUserNames')->willReturn(
            [
                ['user_name' => 'user1'],
                ['user_name' => 'user2'],
            ]
        );

        $project_members_line = $this->backend->getSVNAccessProjectMembers($project);

        self::assertEquals("members = user1, user2\n", $project_members_line);
    }

    public function testItAddUserGroupMembers()
    {
        $user1 = UserTestBuilder::aUser()->withUserName('user1')->build();
        $user2 = UserTestBuilder::aUser()->withUserName('user2')->build();

        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $dao = $this->createMock(UGroupDao::class);
        $dao->method('searchByGroupId')->willReturn(new FakeDataAccessResult([['name' => 'Perms']]));

        $ugroup = $this->createMock(ProjectUGroup::class);
        $ugroup->method('getMembers')->willReturn([$user1, $user2]);
        $ugroup->method('getName')->willReturn('Perms');

        $project_access_checker = $this->createMock(ProjectAccessChecker::class);
        $project_access_checker->method('checkUserCanAccessProject')->withConsecutive(
            [$user1, $project],
            [$user2, $project],
        );

        $this->backend->method('getUGroupDao')->willReturn($dao);
        $this->backend->method('getUGroupFromRow')->willReturn($ugroup);
        $this->backend->method('getProjectAccessChecker')->willReturn($project_access_checker);

        $ugroup_members_line = $this->backend->getSVNAccessUserGroupMembers($project);

        self::assertEquals("Perms = user1, user2\n\n", $ugroup_members_line);
    }

    public function testItRejectUsersThatCannotAccessToProjects()
    {
        $user1 = UserTestBuilder::aUser()->withUserName('user1')->build();
        $user2 = UserTestBuilder::aUser()->withUserName('user2')->build();

        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $dao = $this->createMock(UGroupDao::class);
        $dao->method('searchByGroupId')->willReturn(new FakeDataAccessResult([['name' => 'Perms']]));

        $ugroup = $this->createMock(ProjectUGroup::class);
        $ugroup->method('getMembers')->willReturn([$user1, $user2]);
        $ugroup->method('getName')->willReturn('Perms');

        $project_access_checker = $this->createMock(ProjectAccessChecker::class);
        $project_access_checker->method('checkUserCanAccessProject')->withConsecutive(
            [$user1, $project],
            [$user2, $project],
        )->will(
            $this->onConsecutiveCalls(
                true,
                $this->throwException(new Project_AccessRestrictedException())
            )
        );

        $this->backend->method('getUGroupDao')->willReturn($dao);
        $this->backend->method('getUGroupFromRow')->willReturn($ugroup);
        $this->backend->method('getProjectAccessChecker')->willReturn($project_access_checker);

        $ugroup_members_line = $this->backend->getSVNAccessUserGroupMembers($project);

        self::assertEquals("Perms = user1\n\n", $ugroup_members_line);
    }
}
