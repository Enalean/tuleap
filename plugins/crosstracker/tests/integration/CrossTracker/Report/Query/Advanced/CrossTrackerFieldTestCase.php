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

namespace Tuleap\CrossTracker\Report\Query\Advanced;

use EventManager;
use ForgeConfig;
use Tuleap\CrossTracker\CrossTrackerReportDao;
use Tuleap\CrossTracker\Widget\ProjectCrossTrackerSearch;
use Tuleap\Dashboard\Project\ProjectDashboardDao;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\DB\DBFactory;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Widget\WidgetFactory;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;
use UserManager;

abstract class CrossTrackerFieldTestCase extends TestIntegrationTestCase
{
    use TemporaryTestDirectory;

    /**
     * @before
     */
    protected function generateForgeConfig(): void
    {
        ForgeConfig::set('sys_supported_languages', 'en_US,fr_FR');
        ForgeConfig::set('sys_lang', 'en_US');
        ForgeConfig::set('codendi_cache_dir', $this->getTmpDir());
        ForgeConfig::set('sys_incdir', __DIR__ . '/../../../../../../../../site-content');
        $_SERVER['REQUEST_URI'] = '';
    }

    /**
     * @after
     */
    protected function unsetServer(): void
    {
        unset($_SERVER['REQUEST_URI']);
    }

    protected function addReportToProject(int $report_id, int $project_id): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->insert('plugin_crosstracker_query', ['id' => $report_id, 'query' => '']);
        $widget_dao   = new DashboardWidgetDao(
            new WidgetFactory(
                UserManager::instance(),
                new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
                EventManager::instance(),
            )
        );
        $dao          = new ProjectDashboardDao($widget_dao);
        $dashboard_id = $dao->save($project_id, 'Main Dashboard');
        $line_id      = $widget_dao->createLine($dashboard_id, 'project', 0);
        $column_id    = $widget_dao->createColumn($line_id, 0);
        self::assertTrue($widget_dao->insertWidgetInColumnWithRank(ProjectCrossTrackerSearch::NAME, $report_id, $column_id, 0));
        self::assertNotNull((new CrossTrackerReportDao())->searchCrossTrackerWidgetByCrossTrackerReportId($report_id));
    }
}
