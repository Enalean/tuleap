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
use FakeDataAccessResult;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Project_AccessRestrictedException;
use ProjectUGroup;
use Tuleap\GlobalSVNPollution;
use Tuleap\Project\ProjectAccessChecker;
use UGroupDao;

class BackendSVNTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalSVNPollution;

    /**
     * @var MockInterface|BackendSVN
     */
    private $backend;

    /**
     * @before
     */
    public function createInstance(): void
    {
        $this->backend                         = Mockery::mock(BackendSVN::class)->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function testItAddsProjectMembers()
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getMembersUserNames')->andReturn(
            [
                ['user_name' => 'user1'],
                ['user_name' => 'user2']
            ]
        );

        $project_members_line = $this->backend->getSVNAccessProjectMembers($project);

        $this->assertEquals("members = user1, user2\n", $project_members_line);
    }

    public function testItAddUserGroupMembers()
    {
        $user1 = Mockery::mock(PFUser::class);
        $user1->shouldReceive('getUserName')->andReturn('user1');

        $user2 = Mockery::mock(PFUser::class);
        $user2->shouldReceive('getUserName')->andReturn('user2');

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(101);

        $dao = Mockery::mock(UGroupDao::class);
        $dao->shouldReceive('searchByGroupId')->andReturn(new FakeDataAccessResult([['name' => 'Perms']]));

        $ugroup = Mockery::mock(ProjectUGroup::class);
        $ugroup->shouldReceive(
            [
                'getMembers' => [$user1, $user2],
                'getName'    => 'Perms'
            ]
        );

        $project_access_checker = Mockery::mock(ProjectAccessChecker::class);
        $project_access_checker
            ->shouldReceive('checkUserCanAccessProject')->with($user1, $project)->once();
        $project_access_checker
            ->shouldReceive('checkUserCanAccessProject')->with($user2, $project)->once();

        $this->backend->shouldReceive('getUGroupDao')->andReturn($dao);
        $this->backend->shouldReceive('getUGroupFromRow')->andReturn($ugroup);
        $this->backend->shouldReceive('getProjectAccessChecker')->andReturn($project_access_checker);

        $ugroup_members_line = $this->backend->getSVNAccessUserGroupMembers($project);

        $this->assertEquals("Perms = user1, user2\n\n", $ugroup_members_line);
    }

    public function testItRejectUsersThatCannotAccessToProjects()
    {
        $user1 = Mockery::mock(PFUser::class);
        $user1->shouldReceive('getUserName')->andReturn('user1');

        $user2 = Mockery::mock(PFUser::class);
        $user2->shouldReceive('getUserName')->andReturn('user2');

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(101);

        $dao = Mockery::mock(UGroupDao::class);
        $dao->shouldReceive('searchByGroupId')->andReturn(new FakeDataAccessResult([['name' => 'Perms']]));

        $ugroup = Mockery::mock(ProjectUGroup::class);
        $ugroup->shouldReceive(
            [
                'getMembers' => [$user1, $user2],
                'getName'    => 'Perms'
            ]
        );

        $project_access_checker = Mockery::mock(ProjectAccessChecker::class);
        $project_access_checker
            ->shouldReceive('checkUserCanAccessProject')->with($user1, $project)->once();
        $project_access_checker
            ->shouldReceive('checkUserCanAccessProject')->with($user2, $project)->once()->andThrow(
                Project_AccessRestrictedException::class
            );

        $this->backend->shouldReceive('getUGroupDao')->andReturn($dao);
        $this->backend->shouldReceive('getUGroupFromRow')->andReturn($ugroup);
        $this->backend->shouldReceive('getProjectAccessChecker')->andReturn($project_access_checker);

        $ugroup_members_line = $this->backend->getSVNAccessUserGroupMembers($project);

        $this->assertEquals("Perms = user1\n\n", $ugroup_members_line);
    }
}
