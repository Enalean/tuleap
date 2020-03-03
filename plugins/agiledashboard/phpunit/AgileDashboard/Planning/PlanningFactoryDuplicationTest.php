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

declare(strict_types = 1);

namespace Tuleap\AgileDashboard\Planning;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Planning;
use PlanningDao;
use PlanningFactory;
use PlanningParameters;
use PlanningPermissionsManager;
use TestHelper;
use TrackerFactory;

class PlanningFactoryDuplicationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\Mock | PlanningFactory
     */
    private $partial_factory;
    /**
     * @var PlanningFactory
     */
    private $planning_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningPermissionsManager
     */
    private $planning_permissions_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningDao
     */
    private $planning_dao;

    protected function setUp(): void
    {
        $this->planning_dao                 = Mockery::spy(PlanningDao::class);
        $tracker_factory                    = Mockery::spy(TrackerFactory::class);
        $this->planning_permissions_manager = Mockery::spy(PlanningPermissionsManager::class);

        $this->planning_factory = new PlanningFactory(
            $this->planning_dao,
            $tracker_factory,
            $this->planning_permissions_manager
        );

        $this->partial_factory = Mockery::mock(
            PlanningFactory::class,
            [$this->planning_dao, $tracker_factory, $this->planning_permissions_manager]
        )->makePartial()->shouldAllowMockingProtectedMethods();
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

        $this->partial_factory->shouldReceive('getPlanning')
            ->with(1)
            ->andReturns(Mockery::spy(Planning::class));

        $tracker_mapping = [
            $sprint_tracker_id => $sprint_tracker_copy_id,
            $story_tracker_id  => $story_tracker_copy_id,
            $bug_tracker_id    => $bug_tracker_copy_id,
            $faq_tracker_id    => $faq_tracker_copy_id
        ];

        $rows = TestHelper::arrayToDar(
            [
                'id'                  => 1,
                'name'                => 'Foo',
                'group_id'            => $group_id,
                'planning_tracker_id' => $sprint_tracker_id,
                'backlog_title'       => 'Release Backlog',
                'plan_title'          => 'Sprint Plan'
            ]
        );

        $this->planning_dao->shouldReceive('searchByPlanningTrackerIds')->with(
            array_keys($tracker_mapping)
        )->andReturns($rows);

        $this->planning_dao->shouldReceive('searchBacklogTrackersById')->with(1)->andReturns(
            TestHelper::arrayToDar(
                [
                    'planning_id' => 1,
                    'tracker_id'  => $story_tracker_id
                ]
            )
        );

        $parameters = [
            'id'                  => 1,
            'name'                => 'Sprint Planning',
            'group_id'            => 101,
            'backlog_title'       => 'Backlog',
            'plan_title'          => 'Plan',
            'planning_tracker_id' => $sprint_tracker_copy_id,
            'backlog_tracker_ids' => [$story_tracker_copy_id]
        ];

        $this->planning_dao->shouldReceive('searchById')->with(1)->andReturns(TestHelper::arrayToDar($parameters));
        $expected_parameters = PlanningParameters::fromArray($parameters);

        $this->planning_dao->shouldReceive('createPlanning')
            ->withArgs([$group_id, Mockery::capture($expected_parameters)])
            ->once();

        $this->partial_factory->duplicatePlannings($group_id, $tracker_mapping, []);
    }

    public function testItDoesNothingIfThereAreNoTrackerMappings(): void
    {
        $group_id              = 123;
        $empty_tracker_mapping = [];

        $this->planning_dao->shouldReceive('createPlanning')->never();

        $this->planning_factory->duplicatePlannings($group_id, $empty_tracker_mapping, []);
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

        $this->partial_factory->shouldReceive('getPlanning')
            ->with(1)
            ->andReturns(Mockery::spy(Planning::class));

        $tracker_mapping = [
            $sprint_tracker_id => $sprint_tracker_copy_id,
            $story_tracker_id  => $story_tracker_copy_id,
            $bug_tracker_id    => $bug_tracker_copy_id,
            $faq_tracker_id    => $faq_tracker_copy_id
        ];

        $rows = TestHelper::arrayToDar(
            [
                'id'                  => 1,
                'name'                => 'Foo',
                'group_id'            => $group_id,
                'planning_tracker_id' => $sprint_tracker_id,
                'backlog_title'       => 'Release Backlog',
                'plan_title'          => 'Sprint Plan'
            ]
        );

        $this->planning_dao->shouldReceive('searchByPlanningTrackerIds')->with(
            array_keys($tracker_mapping)
        )->andReturns($rows);

        $this->planning_dao->shouldReceive('searchBacklogTrackersById')->with(1)->andReturns(
            TestHelper::arrayToDar(
                [
                    'planning_id' => 1,
                    'tracker_id'  => $story_tracker_id
                ]
            )
        );

        $parameters = [
            'id'                  => 1,
            'name'                => 'Sprint planning',
            'group_id'            => 101,
            'backlog_title'       => 'Backlog',
            'plan_title'          => 'Plan',
            'planning_tracker_id' => $sprint_tracker_copy_id,
            'backlog_tracker_ids' => [$story_tracker_copy_id]
        ];
        $this->planning_dao->shouldReceive('searchById')->with(1)->andReturns(
            TestHelper::arrayToDar($parameters)
        );

        $expected_ugroups = [113, 114];
        $this->planning_permissions_manager->shouldReceive('savePlanningPermissionForUgroups')->with(
            Mockery::any(),
            Mockery::any(),
            PlanningPermissionsManager::PERM_PRIORITY_CHANGE,
            $expected_ugroups
        )->once();

        $ugroups_mapping = [
            103 => 113,
            104 => 114
        ];

        $this->planning_permissions_manager->shouldReceive('getGroupIdsWhoHasPermissionOnPlanning')
            ->andReturn($ugroups_mapping);

        $this->partial_factory->duplicatePlannings($group_id, $tracker_mapping, $ugroups_mapping);
    }
}
