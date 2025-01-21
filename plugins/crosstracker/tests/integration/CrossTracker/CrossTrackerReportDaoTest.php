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
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class CrossTrackerReportDaoTest extends TestIntegrationTestCase
{
    private CrossTrackerReportDao $report_dao;
    private CrossTrackerReportCreator $creator;

    protected function setUp(): void
    {
        $this->report_dao = new CrossTrackerReportDao();
        $this->creator    = new CrossTrackerReportCreator($this->report_dao);
    }

    public function testCRUD(): void
    {
        $report_id = $this->createAndRetrieveReport();
        $this->updateAndRetrieveExpertModeReport($report_id);
        $this->deleteAndRetrieve($report_id);
    }

    private function createAndRetrieveReport(): int
    {
        $result = $this->creator->createReportAndReturnLastId(ProjectDashboardController::DASHBOARD_TYPE);
        self::assertTrue(Result::isOk($result));
        $report_id = $result->value;

        $retrieved_report   = $this->report_dao->searchReportById($report_id);
        $expected_tql_query = "SELECT @pretty_title, @submitted_by, @last_update_date, @status, @assigned_to FROM @project = 'self' WHERE @status = OPEN() ORDER BY @last_update_date DESC";

        self::assertNotNull($retrieved_report);
        self::assertSame($expected_tql_query, $retrieved_report['query']);

        return $report_id;
    }

    private function updateAndRetrieveExpertModeReport(int $report_id): void
    {
        $expert_query = "SELECT @id FROM @project = 'self' WHERE @title != ''";
        $this->report_dao->updateQuery($report_id, $expert_query);

        $retrieved_report = $this->report_dao->searchReportById($report_id);
        self::assertNotNull($retrieved_report);
        self::assertSame($expert_query, $retrieved_report['query']);
    }

    private function deleteAndRetrieve(int $report_id): void
    {
        $this->report_dao->delete($report_id);

        self::assertNull($this->report_dao->searchReportById($report_id));
    }

    public function testItCreatesANewExpertReportFromUserDashboard(): void
    {
        $result = $this->creator->createReportAndReturnLastId(UserDashboardController::DASHBOARD_TYPE);
        self::assertTrue(Result::isOk($result));
        $last_report_id = $result->value;

        $sql_result         = $this->report_dao->searchReportById($last_report_id);
        $expected_tql_query = 'SELECT @pretty_title, @submitted_by, @last_update_date, @status FROM @project = MY_PROJECTS() WHERE @status = OPEN() AND @assigned_to = MYSELF() ORDER BY @last_update_date DESC';

        self::assertNotNull($sql_result);
        self::assertSame($expected_tql_query, $sql_result['query']);
    }
}
