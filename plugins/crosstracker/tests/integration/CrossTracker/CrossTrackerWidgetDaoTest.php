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

use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class CrossTrackerWidgetDaoTest extends TestIntegrationTestCase
{
    private CrossTrackerWidgetDao $widget_dao;

    protected function setUp(): void
    {
        $this->widget_dao = new CrossTrackerWidgetDao();
    }

    public function testCRUD(): void
    {
        $widget_id = $this->createAndRetrieveReport();
        $this->updateAndRetrieveExpertModeReport($widget_id);
        $this->deleteAndRetrieve($widget_id);
    }

    private function createAndRetrieveReport(): int
    {
        self::assertFalse($this->widget_dao->searchWidgetExistence(1));
        $widget_id = $this->widget_dao->createWidget();
        self::assertTrue($this->widget_dao->searchWidgetExistence($widget_id));
        $expected_tql_query = "SELECT @pretty_title, @submitted_by, @last_update_date, @status, @assigned_to FROM @project = 'self' WHERE @status = OPEN() ORDER BY @last_update_date DESC";
        $this->widget_dao->insertQuery($widget_id, $expected_tql_query);

        $retrieved_report = $this->widget_dao->searchWidgetById($widget_id);

        self::assertNotNull($retrieved_report);
        self::assertSame($expected_tql_query, $retrieved_report['query']);

        return $widget_id;
    }

    private function updateAndRetrieveExpertModeReport(int $widget_id): void
    {
        $expert_query = "SELECT @id FROM @project = 'self' WHERE @title != ''";
        $this->widget_dao->updateQuery($widget_id, $expert_query);

        $retrieved_report = $this->widget_dao->searchWidgetById($widget_id);
        self::assertNotNull($retrieved_report);
        self::assertSame($expert_query, $retrieved_report['query']);
    }

    private function deleteAndRetrieve(int $widget_id): void
    {
        $this->widget_dao->deleteWidget($widget_id);

        self::assertNull($this->widget_dao->searchWidgetById($widget_id));
    }
}
