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

namespace Tuleap\Dashboard\Project;

use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Widget\WidgetFactory;

final class ProjectDashboardDaoTest extends TestIntegrationTestCase
{
    private ProjectDashboardDao $dao;
    private DashboardWidgetDao $widget_dao;

    protected function setUp(): void
    {
        $this->widget_dao = new DashboardWidgetDao(
            new WidgetFactory(
                \UserManager::instance(),
                new \User_ForgeUserGroupPermissionsManager(new \User_ForgeUserGroupPermissionsDao()),
                EventDispatcherStub::withIdentityCallback()
            )
        );
        $this->dao        = new ProjectDashboardDao($this->widget_dao);
    }

    public function testCRUD(): void
    {
        $project_id     = 198;
        $dashboard_name = 'traumatotactic electroencephalogram';
        $dashboard_id   = $this->dao->save($project_id, $dashboard_name);

        $other_name                = 'outbuzzed preincorporation';
        $same_project_dashboard_id = $this->dao->save($project_id, $other_name);

        $other_project_id           = 743;
        $other_project_dashboard_id = $this->dao->save($other_project_id, $dashboard_name);

        $row_by_id = $this->dao->searchById($dashboard_id);
        self::assertNotNull($row_by_id);
        self::assertSame($project_id, $row_by_id['project_id']);
        self::assertSame($dashboard_name, $row_by_id['name']);

        $search_all_rows   = $this->dao->searchAllProjectDashboards($project_id);
        $dashboard_ids_all = $this->getDashboardIds($search_all_rows);
        self::assertCount(2, $dashboard_ids_all);
        self::assertEqualsCanonicalizing([$dashboard_id, $same_project_dashboard_id], $dashboard_ids_all);

        $search_by_name_rows   = $this->dao->searchByProjectIdAndName($project_id, $dashboard_name);
        $dashboard_ids_by_name = $this->getDashboardIds($search_by_name_rows);
        self::assertCount(1, $dashboard_ids_by_name);
        self::assertEqualsCanonicalizing([$dashboard_id], $dashboard_ids_by_name);

        $new_name = 'principiate chondrenchyma';
        $this->dao->edit($dashboard_id, $new_name);

        $edited_row = $this->dao->searchById($dashboard_id);
        self::assertNotNull($edited_row);
        self::assertSame($project_id, $edited_row['project_id']);
        self::assertSame($new_name, $edited_row['name']);

        $duplicated_dashboard_id = $this->dao->duplicateDashboard($project_id, $other_project_id, $dashboard_id);

        $search_all_other_project_rows = $this->dao->searchAllProjectDashboards($other_project_id);
        $dashboard_ids_other_project   = $this->getDashboardIds($search_all_other_project_rows);
        self::assertCount(2, $dashboard_ids_other_project);
        self::assertEqualsCanonicalizing(
            [$other_project_dashboard_id, $duplicated_dashboard_id],
            $dashboard_ids_other_project
        );

        $this->dao->delete($project_id, $dashboard_id);

        $search_not_found_row = $this->dao->searchById($dashboard_id);
        self::assertNull($search_not_found_row);

        $search_all_not_found_rows = $this->dao->searchAllProjectDashboards($project_id);
        $dashboard_ids_not_found   = $this->getDashboardIds($search_all_not_found_rows);
        self::assertCount(1, $dashboard_ids_not_found);
        self::assertEqualsCanonicalizing([$same_project_dashboard_id], $dashboard_ids_not_found);

        // Check that widgets are also deleted
        $widgets = $this->widget_dao->searchUsedWidgetsContentByDashboardId(
            $dashboard_id,
            ProjectDashboardController::DASHBOARD_TYPE
        );
        self::assertEmpty($widgets);
    }

    /**
     * @param array<array{id: int}> $project_dashboard_rows
     * @return list<int>
     */
    private function getDashboardIds(array $project_dashboard_rows): array
    {
        return array_values(array_map(static fn(array $row) => $row['id'], $project_dashboard_rows));
    }
}
