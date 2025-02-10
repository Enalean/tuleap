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
use LogicException;
use PFUser;
use Tuleap\CrossTracker\CrossTrackerExpertReport;
use Tuleap\CrossTracker\CrossTrackerWidgetDao;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\NumericResultRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerReportContentRepresentation;
use Tuleap\CrossTracker\Tests\Report\ArtifactReportFactoryInstantiator;
use Tuleap\CrossTracker\Widget\ProjectCrossTrackerSearch;
use Tuleap\Dashboard\Project\ProjectDashboardDao;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\DB\DBFactory;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesDoNotExistException;
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

    protected function addReportToProject(int $widget_id, int $project_id): void
    {
        $db   = DBFactory::getMainTuleapDBConnection()->getDB();
        $uuid = new DatabaseUUIDV7Factory();
        $db->insert('plugin_crosstracker_query', [
            'id'        => $uuid->buildUUIDBytes(),
            'query'     => '',
            'title'     => '',
            'widget_id' => $widget_id,
        ]);
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
        self::assertTrue($widget_dao->insertWidgetInColumnWithRank(ProjectCrossTrackerSearch::NAME, $widget_id, $column_id, 0));
        $db->insert('plugin_crosstracker_widget', ['id' => $widget_id]);
        self::assertNotNull((new CrossTrackerWidgetDao())->searchCrossTrackerWidgetDashboardById($widget_id));
    }

    /**
     * @return list<int>
     * @throws SearchablesDoNotExistException
     * @throws SearchablesAreInvalidException
     */
    final protected function getMatchingArtifactIds(CrossTrackerExpertReport $report, PFUser $user): array
    {
        $result = (new ArtifactReportFactoryInstantiator())
            ->getFactory()
            ->getArtifactsMatchingReport($report, $user, 10, 0);
        return array_values(array_map(static function (array $artifact): int {
            if (! isset($artifact['@id']) || ! ($artifact['@id'] instanceof NumericResultRepresentation)) {
                throw new LogicException('Query result should contains @id column');
            }

            return (int) $artifact['@id']->value;
        }, $result->artifacts));
    }

    final protected function getQueryResults(CrossTrackerExpertReport $report, PFUser $user): CrossTrackerReportContentRepresentation
    {
        $result = (new ArtifactReportFactoryInstantiator())
            ->getFactory()
            ->getArtifactsMatchingReport($report, $user, 10, 0);
        assert($result instanceof CrossTrackerReportContentRepresentation);
        return $result;
    }
}
