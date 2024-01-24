<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use Tuleap\DB\DBFactory;

final class PlanningDAOTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private PlanningDao $dao;
    private static int $not_milestone_tracker_id;

    public static function setUpBeforeClass(): void
    {
        $db                             = DBFactory::getMainTuleapDBConnection()->getDB();
        self::$not_milestone_tracker_id = (int) $db->insertReturnId(
            'tracker',
            [
                'group_id'    => 107,
                'name'        => 'Not a milestone',
                'description' => 'Not a milestone',
                'item_name'   => 'not_milestone',
            ]
        );
    }

    protected function setUp(): void
    {
        $this->dao = new PlanningDao();
    }

    protected function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run('DELETE FROM plugin_agiledashboard_planning');
        $db->run('DELETE FROM plugin_agiledashboard_planning_backlog_tracker');
    }

    public static function tearDownAfterClass(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->delete('tracker', ['id' => self::$not_milestone_tracker_id]);
    }

    public function testAPlanningCanBeCreatedAndRemoved(): void
    {
        $project_id  = 107;
        $planning    = \PlanningParameters::fromArray(
            [
                'name'                => 'Release Planning',
                'backlog_title'       => 'Product Backlog',
                'plan_title'          => 'Release Plan',
                'backlog_tracker_ids' => [17, 48],
                'planning_tracker_id' => '25',
            ]
        );
        $planning_id = $this->dao->createPlanning($project_id, $planning);

        $planning_row = $this->dao->searchById($planning_id);
        $this->assertEquals(
            [
                'id'                  => $planning_id,
                'name'                => 'Release Planning',
                'group_id'            => $project_id,
                'planning_tracker_id' => 25,
                'backlog_title'       => 'Product Backlog',
                'plan_title'          => 'Release Plan',
            ],
            $planning_row
        );

        $backlog_tracker_rows = [];
        foreach ($this->dao->searchBacklogTrackersByPlanningId($planning_id) as $row) {
            $backlog_tracker_rows[] = $row['tracker_id'];
        }
        $this->assertContains(17, $backlog_tracker_rows);
        $this->assertContains(48, $backlog_tracker_rows);

        $this->dao->deletePlanning($planning_id);

        $this->assertNull($this->dao->searchById($planning_id));
        $this->assertEmpty($this->dao->searchBacklogTrackersByPlanningId($planning_id));
    }

    public function testAPlanningCanBeUpdated(): void
    {
        $project_id  = 107;
        $planning    = \PlanningParameters::fromArray(
            [
                'name'                => 'Release Planning',
                'backlog_title'       => 'Product Backlog',
                'plan_title'          => 'Release Plan',
                'backlog_tracker_ids' => [17, 48],
                'planning_tracker_id' => '25',
            ]
        );
        $planning_id = $this->dao->createPlanning($project_id, $planning);

        $updated_planning = \PlanningParameters::fromArray(
            [
                'name'                => 'Sprint Planning',
                'backlog_title'       => 'Epic Backlog',
                'plan_title'          => 'Sprint Plan',
                'backlog_tracker_ids' => [90, 57],
                'planning_tracker_id' => '32',
            ]
        );
        $this->dao->updatePlanning($planning_id, $updated_planning);

        $planning_row = $this->dao->searchById($planning_id);
        $this->assertEquals(
            [
                'id'                  => $planning_id,
                'name'                => 'Sprint Planning',
                'group_id'            => $project_id,
                'planning_tracker_id' => 32,
                'backlog_title'       => 'Epic Backlog',
                'plan_title'          => 'Sprint Plan',
            ],
            $planning_row
        );

        $backlog_tracker_rows = [];
        foreach ($this->dao->searchBacklogTrackersByPlanningId($planning_id) as $row) {
            $backlog_tracker_rows[] = $row['tracker_id'];
        }
        $this->assertContains(90, $backlog_tracker_rows);
        $this->assertContains(57, $backlog_tracker_rows);

        $this->dao->deletePlanning($planning_id);
    }

    public function testAPlanningCanBeFound(): void
    {
        $project_id                = 107;
        $milestone_tracker_id      = 25;
        $first_backlog_tracker_id  = 17;
        $second_backlog_tracker_id = 48;
        $planning                  = \PlanningParameters::fromArray(
            [
                'name'                => 'Release Planning',
                'backlog_title'       => 'Product Backlog',
                'plan_title'          => 'Release Plan',
                'backlog_tracker_ids' => [$first_backlog_tracker_id, $second_backlog_tracker_id],
                'planning_tracker_id' => (string) $milestone_tracker_id,
            ]
        );
        $planning_id               = $this->dao->createPlanning($project_id, $planning);

        $planning_rows_by_project = $this->dao->searchByProjectId($project_id);
        self::assertCount(1, $planning_rows_by_project);
        $this->assertContains($planning_id, $planning_rows_by_project[0]);

        $planning_row_by_milestone_tracker = $this->dao->searchByMilestoneTrackerId($milestone_tracker_id);
        self::assertNotNull($planning_row_by_milestone_tracker);
        $this->assertContains($planning_id, $planning_row_by_milestone_tracker);

        $planning_rows_by_multiple_milestone_trackers = $this->dao->searchByMilestoneTrackerIds(
            [$milestone_tracker_id, 404]
        );
        self::assertCount(1, $planning_rows_by_multiple_milestone_trackers);
        $this->assertContains($planning_id, $planning_rows_by_multiple_milestone_trackers[0]);

        $milestone_tracker_row = $this->dao->searchMilestoneTrackerIdsByProjectId($project_id);
        $this->assertContains($milestone_tracker_id, $milestone_tracker_row);

        $not_milestone_tracker_rows = [];
        $non_planning_rows          = $this->dao->searchNonPlanningTrackersByGroupId($project_id);
        self::assertNotFalse($non_planning_rows);
        foreach ($non_planning_rows as $row) {
            $not_milestone_tracker_rows[] = $row['id'];
        }
        $this->assertContains((string) self::$not_milestone_tracker_id, $not_milestone_tracker_rows);

        $planning_rows_by_backlog_tracker = $this->dao->searchByBacklogTrackerId($first_backlog_tracker_id);
        self::assertCount(1, $planning_rows_by_backlog_tracker);
        $this->assertContains($planning_id, $planning_rows_by_backlog_tracker[0]);

        $backlog_tracker_rows = $this->dao->searchBacklogTrackersByTrackerId($first_backlog_tracker_id);
        self::assertCount(1, $backlog_tracker_rows);
        $this->assertContains($first_backlog_tracker_id, $backlog_tracker_rows[0]);

        $backlog_tracker_rows_by_project = $this->dao->searchBacklogTrackerIdsByProjectId($project_id);
        $this->assertContains($first_backlog_tracker_id, $backlog_tracker_rows_by_project);
        $this->assertContains($second_backlog_tracker_id, $backlog_tracker_rows_by_project);

        $this->dao->deletePlanning($planning_id);
    }
}
