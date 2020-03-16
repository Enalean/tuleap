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

declare(strict_types=1);

namespace Tuleap\Project;

use Event;
use Mockery as M;
use EventManager;
use PHPUnit\Framework\TestCase;
use ProjectUGroup;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Project\UGroups\Membership\MemberAdder;
use UGroupBinding;
use UGroupDao;
use UGroupManager;

class UgroupDuplicatorTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration, GlobalLanguageMock;

    /**
     * @var M\MockInterface|UGroupDao
     */
    private $dao;
    /**
     * @var M\MockInterface|UGroupManager
     */
    private $manager;
    /**
     * @var M\MockInterface|UGroupBinding
     */
    private $binding;
    /**
     * @var EventManager|M\MockInterface
     */
    private $event_manager;
    /**
     * @var UgroupDuplicator
     */
    private $ugroup_duplicator;
    /**
     * @var M\MockInterface|MemberAdder
     */
    private $member_adder;

    protected function setUp(): void
    {
        $this->dao = M::mock(UGroupDao::class);
        $this->manager = M::mock(UGroupManager::class);
        $this->binding = M::mock(UGroupBinding::class);
        $this->member_adder = M::mock(MemberAdder::class);
        $this->event_manager = M::mock(EventManager::class);
        $this->ugroup_duplicator = new UgroupDuplicator($this->dao, $this->manager, $this->binding, $this->member_adder, $this->event_manager);
    }

    public function testItDuplicatesOnlyStaticGroups()
    {
        $template       = M::mock(\Project::class);
        $new_project_id = 120;
        $ugroup_mapping  = [];

        $this->manager->shouldReceive('getStaticUGroups')->with($template)->once()->andReturns([]);

        $this->ugroup_duplicator->duplicateOnProjectCreation($template, $new_project_id, $ugroup_mapping);

        $this->assertEmpty($ugroup_mapping);
    }

    public function testItReturnsTheMappingBetweenSourceAndDestinationUGroups()
    {
        $template       = M::mock(\Project::class);
        $new_project_id = 120;
        $ugroup_mapping  = [];

        $source_ugroup_id = 201;
        $source_ugroup = M::mock(ProjectUGroup::class, ['getId' => $source_ugroup_id, 'isStatic' => true, 'isBound' => false, 'getMembers' => [ ]]);
        $this->manager->shouldReceive('getStaticUGroups')->with($template)->once()->andReturns(
            [
                $source_ugroup,
            ]
        );

        $new_ugroup_id = 301;
        $new_ugroup = M::mock(ProjectUGroup::class, ['getId' => $new_ugroup_id]);
        $this->dao->shouldReceive('createUgroupFromSourceUgroup')->with($source_ugroup_id, $new_project_id)->once()->andReturns($new_ugroup_id);
        $this->manager->shouldReceive('getById')->with($new_ugroup_id)->once()->andReturn($new_ugroup);

        $this->event_manager->shouldReceive('processEvent')->with(Event::UGROUP_DUPLICATION, ['source_ugroup' => $source_ugroup, 'new_ugroup_id' => $new_ugroup_id])->once();

        $this->dao->shouldReceive('createBinding')->with($new_project_id, $source_ugroup_id, $new_ugroup_id)->once();

        $this->ugroup_duplicator->duplicateOnProjectCreation($template, $new_project_id, $ugroup_mapping);
        $this->assertEquals([201 => 301], $ugroup_mapping);
    }

    public function testItAddUsersFromSourceGroup()
    {
        $template       = M::mock(\Project::class);
        $new_project_id = 120;
        $ugoup_mapping  = [];

        $source_ugroup_id = 201;
        $user1 = new \PFUser(['user_id' => 1]);
        $user2 = new \PFUser(['user_id' => 2]);
        $source_ugroup = M::mock(ProjectUGroup::class, ['getId' => $source_ugroup_id, 'isStatic' => true, 'isBound' => false, 'getMembers' => [$user1, $user2]]);
        $this->manager->shouldReceive('getStaticUGroups')->with($template)->once()->andReturns(
            [
                $source_ugroup,
            ]
        );

        $new_ugroup_id = 301;
        $new_ugroup = M::mock(ProjectUGroup::class, ['getId' => $new_ugroup_id]);
        $this->dao->shouldReceive('createUgroupFromSourceUgroup')->with($source_ugroup_id, $new_project_id)->once()->andReturns($new_ugroup_id);
        $this->manager->shouldReceive('getById')->with($new_ugroup_id)->once()->andReturn($new_ugroup);

        $this->event_manager->shouldReceive('processEvent')->with(Event::UGROUP_DUPLICATION, ['source_ugroup' => $source_ugroup, 'new_ugroup_id' => $new_ugroup_id])->once();

        $this->dao->shouldReceive('createBinding')->with($new_project_id, $source_ugroup_id, $new_ugroup_id)->once();

        $this->member_adder->shouldReceive('addMember')->with($user1, $new_ugroup)->once();
        $this->member_adder->shouldReceive('addMember')->with($user2, $new_ugroup)->once();

        $this->ugroup_duplicator->duplicateOnProjectCreation($template, $new_project_id, $ugoup_mapping);
    }

    public function testItAddUsersWithExceptionHandling()
    {
        $template       = M::mock(\Project::class);
        $new_project_id = 120;
        $ugoup_mapping  = [];

        $source_ugroup_id = 201;
        $user1 = new \PFUser(['user_id' => 1]);
        $user2 = new \PFUser(['user_id' => 2]);
        $source_ugroup = M::mock(ProjectUGroup::class, ['getId' => $source_ugroup_id, 'isStatic' => true, 'isBound' => false, 'getMembers' => [$user1, $user2]]);
        $this->manager->shouldReceive('getStaticUGroups')->with($template)->once()->andReturns(
            [
                $source_ugroup,
            ]
        );

        $new_ugroup_id = 301;
        $new_ugroup = M::mock(ProjectUGroup::class, ['getId' => $new_ugroup_id]);
        $this->dao->shouldReceive('createUgroupFromSourceUgroup')->with($source_ugroup_id, $new_project_id)->once()->andReturns($new_ugroup_id);
        $this->manager->shouldReceive('getById')->with($new_ugroup_id)->once()->andReturn($new_ugroup);

        $this->event_manager->shouldReceive('processEvent')->with(Event::UGROUP_DUPLICATION, ['source_ugroup' => $source_ugroup, 'new_ugroup_id' => $new_ugroup_id])->once();

        $this->dao->shouldReceive('createBinding')->with($new_project_id, $source_ugroup_id, $new_ugroup_id)->once();

        $this->member_adder->shouldReceive('addMember')->andThrows(new CannotAddRestrictedUserToProjectNotAllowingRestricted($user1, M::mock(\Project::class, ['getID' => 505])));

        $this->ugroup_duplicator->duplicateOnProjectCreation($template, $new_project_id, $ugoup_mapping);
    }
}
