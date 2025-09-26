<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\AgileDashboard\Planning;

use PHPUnit\Framework\MockObject\MockObject;
use PlanningFactory;
use PlanningPermissionsManager;
use TrackerFactory;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PlanningFactoryDuplicationTest extends TestCase
{
    private PlanningFactory&MockObject $partial_factory;
    private PlanningFactory $planning_factory;
    private PlanningPermissionsManager&MockObject $planning_permissions_manager;
    private PlanningDao&MockObject $planning_dao;
    private \PFUser $user;

    #[\Override]
    protected function setUp(): void
    {
        $this->planning_dao                 = $this->createMock(PlanningDao::class);
        $tracker_factory                    = $this->createMock(TrackerFactory::class);
        $this->planning_permissions_manager = $this->createMock(PlanningPermissionsManager::class);

        $this->planning_factory = new PlanningFactory(
            $this->planning_dao,
            $tracker_factory,
            $this->planning_permissions_manager
        );

        $this->user = UserTestBuilder::buildWithDefaults();

        $this->partial_factory = $this->getMockBuilder(PlanningFactory::class)
            ->setConstructorArgs([$this->planning_dao, $tracker_factory, $this->planning_permissions_manager])
            ->onlyMethods(['getPlanning'])
            ->getMock();
    }

    public function testItDuplicatesPlannings(): void
    {
        $group_id = 123;

        $sprint_tracker_id      = 1;
        $story_tracker_id       = 2;
        $bug_tracker_id         = 3;
        $faq_tracker_id         = 4;
        $sprint_tracker_copy_id = 5;
        $story_tracker_copy_id  = 6;
        $bug_tracker_copy_id    = 7;
        $faq_tracker_copy_id    = 8;

        $this->partial_factory
            ->method('getPlanning')
            ->with($this->user, 1)
            ->willReturn(PlanningBuilder::aPlanning(123)->build());

        $tracker_mapping = [
            $sprint_tracker_id => $sprint_tracker_copy_id,
            $story_tracker_id  => $story_tracker_copy_id,
            $bug_tracker_id    => $bug_tracker_copy_id,
            $faq_tracker_id    => $faq_tracker_copy_id,
        ];

        $rows = [
            [
                'id'                  => 1,
                'name'                => 'Foo',
                'group_id'            => $group_id,
                'planning_tracker_id' => $sprint_tracker_id,
                'backlog_title'       => 'Release Backlog',
                'plan_title'          => 'Sprint Plan',
            ],
        ];

        $this->planning_dao->method('searchByMilestoneTrackerIds')->with(array_keys($tracker_mapping))->willReturn($rows);

        $this->planning_dao->method('searchBacklogTrackersByPlanningId')->with(1)->willReturn([
            ['planning_id' => 1, 'tracker_id' => $story_tracker_id],
        ]);

        $parameters = [
            'id'                  => 1,
            'name'                => 'Sprint Planning',
            'group_id'            => 101,
            'backlog_title'       => 'Backlog',
            'plan_title'          => 'Plan',
            'planning_tracker_id' => $sprint_tracker_copy_id,
            'backlog_tracker_ids' => [$story_tracker_copy_id],
        ];

        $this->planning_dao->method('searchById')->with(1)->willReturn($parameters);
        $this->planning_dao->expects($this->once())->method('createPlanning')
            ->with($group_id, self::anything());

        $this->planning_permissions_manager->method('getGroupIdsWhoHasPermissionOnPlanning');

        $this->partial_factory->duplicatePlannings($this->user, $group_id, $tracker_mapping, []);
    }

    public function testItDoesNothingIfThereAreNoTrackerMappings(): void
    {
        $group_id              = 123;
        $empty_tracker_mapping = [];

        $this->planning_dao->expects($this->never())->method('createPlanning');

        $this->planning_factory->duplicatePlannings($this->user, $group_id, $empty_tracker_mapping, []);
    }

    public function testItTranslatesUgroupsIdsFromUgroupsMapping(): void
    {
        $group_id = 123;

        $sprint_tracker_id      = 1;
        $story_tracker_id       = 2;
        $bug_tracker_id         = 3;
        $faq_tracker_id         = 4;
        $sprint_tracker_copy_id = 5;
        $story_tracker_copy_id  = 6;
        $bug_tracker_copy_id    = 7;
        $faq_tracker_copy_id    = 8;

        $this->partial_factory
            ->method('getPlanning')
            ->with($this->user, 1)
            ->willReturn(PlanningBuilder::aPlanning(123)->build());

        $tracker_mapping = [
            $sprint_tracker_id => $sprint_tracker_copy_id,
            $story_tracker_id  => $story_tracker_copy_id,
            $bug_tracker_id    => $bug_tracker_copy_id,
            $faq_tracker_id    => $faq_tracker_copy_id,
        ];

        $rows = [
            [
                'id'                  => 1,
                'name'                => 'Foo',
                'group_id'            => $group_id,
                'planning_tracker_id' => $sprint_tracker_id,
                'backlog_title'       => 'Release Backlog',
                'plan_title'          => 'Sprint Plan',
            ],
        ];

        $this->planning_dao->method('searchByMilestoneTrackerIds')->with(array_keys($tracker_mapping))->willReturn($rows);

        $this->planning_dao->method('searchBacklogTrackersByPlanningId')->with(1)->willReturn([
            ['planning_id' => 1, 'tracker_id' => $story_tracker_id],
        ]);

        $parameters = [
            'id'                  => 1,
            'name'                => 'Sprint planning',
            'group_id'            => 101,
            'backlog_title'       => 'Backlog',
            'plan_title'          => 'Plan',
            'planning_tracker_id' => $sprint_tracker_copy_id,
            'backlog_tracker_ids' => [$story_tracker_copy_id],
        ];
        $this->planning_dao->method('searchById')->with(1)->willReturn($parameters);

        $expected_ugroups = [113, 114];
        $this->planning_permissions_manager->expects($this->once())->method('savePlanningPermissionForUgroups')->with(
            self::anything(),
            self::anything(),
            PlanningPermissionsManager::PERM_PRIORITY_CHANGE,
            $expected_ugroups
        );

        $ugroups_mapping = [
            103 => 113,
            104 => 114,
        ];

        $this->planning_permissions_manager->method('getGroupIdsWhoHasPermissionOnPlanning')->willReturn($ugroups_mapping);
        $this->planning_dao->method('createPlanning');

        $this->partial_factory->duplicatePlannings($this->user, $group_id, $tracker_mapping, $ugroups_mapping);
    }
}
