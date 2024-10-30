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

namespace Tuleap\CrossTracker;

use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\User\UserDashboardController;
use Tuleap\DB\DBFactory;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

final class CrossTrackerReportDaoTest extends TestIntegrationTestCase
{
    private const PROJECT_ID = 134;
    private CrossTrackerReportDao $report_dao;
    private TrackerDatabaseBuilder $tracker_builder;
    private CrossTrackerReportCreator $creator;

    protected function setUp(): void
    {
        $this->report_dao      = new CrossTrackerReportDao();
        $this->tracker_builder = new TrackerDatabaseBuilder(DBFactory::getMainTuleapDBConnection()->getDB());
        $this->creator         = new CrossTrackerReportCreator($this->report_dao);
    }

    public function testCRUD(): void
    {
        $first_tracker  = $this->tracker_builder->buildTracker(self::PROJECT_ID, 'stories');
        $second_tracker = $this->tracker_builder->buildTracker(self::PROJECT_ID, 'bugs');
        $this->tracker_builder->buildTracker(self::PROJECT_ID, 'tasks'); // To check that not all trackers are used

        $report_id = $this->createAndRetrieveEmptyReport();
        $this->updateAndRetrieveDefaultModeReport($report_id, $first_tracker, $second_tracker);
        $this->checkTrackersAreUsed($first_tracker, $second_tracker);
        $this->updateAndRetrieveExpertModeReport($report_id);
        $this->deleteAndRetrieve($report_id);
    }

    private function createAndRetrieveEmptyReport(): int
    {
        $report_id = (int) $this->report_dao->create();

        $retrieved_report = $this->report_dao->searchReportById($report_id);
        self::assertNotNull($retrieved_report);
        self::assertSame('', $retrieved_report['expert_query']);
        self::assertSame(0, $retrieved_report['expert_mode']);

        return $report_id;
    }

    private function updateAndRetrieveDefaultModeReport(
        int $report_id,
        \Tracker $first_tracker,
        \Tracker $second_tracker,
    ): void {
        $expert_query = '@title != ""';
        $this->report_dao->updateReport($report_id, [$first_tracker, $second_tracker], $expert_query, false);

        $retrieved_report = $this->report_dao->searchReportById($report_id);
        self::assertNotNull($retrieved_report);
        self::assertSame($expert_query, $retrieved_report['expert_query']);
        self::assertSame(0, $retrieved_report['expert_mode']);

        $retrieved_trackers = $this->report_dao->searchReportTrackersById($report_id);
        self::assertCount(2, $retrieved_trackers);
        [$first_retrieved_tracker, $second_retrieved_tracker] = $retrieved_trackers;
        self::assertSame($first_tracker->getId(), $first_retrieved_tracker['tracker_id']);
        self::assertSame($second_tracker->getId(), $second_retrieved_tracker['tracker_id']);
    }

    private function checkTrackersAreUsed(\Tracker $first_tracker, \Tracker $second_tracker): void
    {
        $rows             = $this->report_dao->searchTrackersIdUsedByCrossTrackerByProjectId(self::PROJECT_ID);
        $used_tracker_ids = array_map(static fn(array $row): int => $row['id'], $rows);
        self::assertCount(2, $used_tracker_ids);
        self::assertContains($first_tracker->getId(), $used_tracker_ids);
        self::assertContains($second_tracker->getId(), $used_tracker_ids);
    }

    private function updateAndRetrieveExpertModeReport(int $report_id): void
    {
        $expert_query = "SELECT @id FROM @project = 'self' WHERE @title != ''";
        $this->report_dao->updateReport($report_id, [], $expert_query, true);

        $retrieved_report = $this->report_dao->searchReportById($report_id);
        self::assertNotNull($retrieved_report);
        self::assertSame($expert_query, $retrieved_report['expert_query']);
        self::assertSame(1, $retrieved_report['expert_mode']);

        self::assertEmpty($this->report_dao->searchReportTrackersById($report_id));
    }

    private function deleteAndRetrieve(int $report_id): void
    {
        $this->report_dao->delete($report_id);

        self::assertNull($this->report_dao->searchReportById($report_id));
        self::assertEmpty($this->report_dao->searchReportTrackersById($report_id));
        self::assertEmpty($this->report_dao->searchTrackersIdUsedByCrossTrackerByProjectId(self::PROJECT_ID));
    }

    public function testItCreatesANewExpertReportFromUserDashboard(): void
    {
        $result = $this->creator->createReportAndReturnLastId(UserDashboardController::DASHBOARD_TYPE);
        self::assertTrue(Result::isOk($result));
        $last_report_id = $result->value;

        $sql_result         = $this->report_dao->searchReportById($last_report_id);
        $expected_tql_query = 'SELECT @pretty_title, @submitted_by, @last_update_date, @status FROM @project = MY_PROJECTS() WHERE @status = OPEN() AND @assigned_to = MYSELF() ORDER BY @last_update_date DESC';

        self::assertSame(1, $sql_result['expert_mode']);
        self::assertSame($expected_tql_query, $sql_result['expert_query']);
    }

    public function testItCreatesANewExpertReportFromProjectDashboard(): void
    {
        $result = $this->creator->createReportAndReturnLastId(ProjectDashboardController::DASHBOARD_TYPE);
        self::assertTrue(Result::isOk($result));
        $last_report_id = $result->value;

        $sql_result         = $this->report_dao->searchReportById($last_report_id);
        $expected_tql_query = "SELECT @pretty_title, @submitted_by, @last_update_date, @status, @assigned_to FROM @project = 'self' WHERE @status = OPEN() ORDER BY @last_update_date DESC";

        self::assertSame(1, $sql_result['expert_mode']);
        self::assertSame($expected_tql_query, $sql_result['expert_query']);
    }
}
