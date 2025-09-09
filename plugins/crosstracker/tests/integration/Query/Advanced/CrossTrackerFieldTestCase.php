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

namespace Tuleap\CrossTracker\Query\Advanced;

use EventManager;
use ForgeConfig;
use LogicException;
use PFUser;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\NumericResultRepresentation;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Special\LinkType\WithoutLinkTypeSelectFromBuilder;
use Tuleap\CrossTracker\Query\CrossTrackerArtifactQueryFactoryBuilder;
use Tuleap\CrossTracker\Query\CrossTrackerQuery;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerQueryContentRepresentation;
use Tuleap\CrossTracker\Widget\CrossTrackerWidgetRetriever;
use Tuleap\CrossTracker\Widget\CrossTrackerSearchWidget;
use Tuleap\CrossTracker\Widget\CrossTrackerWidgetDao;
use Tuleap\Dashboard\Project\ProjectDashboardDao;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\UUID;
use Tuleap\Option\Option;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesDoNotExistException;
use Tuleap\Widget\WidgetFactory;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;
use UserManager;
use function Psl\Type\string;

abstract class CrossTrackerFieldTestCase extends TestIntegrationTestCase
{
    use TemporaryTestDirectory;

    #[\PHPUnit\Framework\Attributes\Before]
    protected function generateForgeConfig(): void
    {
        ForgeConfig::set('sys_supported_languages', 'en_US,fr_FR');
        ForgeConfig::set('sys_lang', 'en_US');
        ForgeConfig::set('codendi_cache_dir', $this->getTmpDir());
        ForgeConfig::set('sys_incdir', __DIR__ . '/../../../../../../site-content');
        $_SERVER['REQUEST_URI'] = '';
    }

    #[\PHPUnit\Framework\Attributes\After]
    protected function unsetServer(): void
    {
        unset($_SERVER['REQUEST_URI']);
    }

    protected function addWidgetToProject(int $widget_id, int $project_id): UUID
    {
        $db           = DBFactory::getMainTuleapDBConnection()->getDB();
        $uuid_factory = new DatabaseUUIDV7Factory();
        $uuid         = $uuid_factory->buildUUIDBytes();
        $db->insert('plugin_crosstracker_query', [
            'id'        => $uuid,
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
        self::assertTrue($widget_dao->insertWidgetInColumnWithRank(CrossTrackerSearchWidget::NAME, $widget_id, $column_id, 0));
        $db->insert('plugin_crosstracker_widget', ['id' => $widget_id]);
        self::assertNotNull((new CrossTrackerWidgetDao())->searchCrossTrackerWidgetDashboardById($widget_id));

        return $uuid_factory->buildUUIDFromBytesData($uuid);
    }

    /**
     * @return list<int>
     * @throws SearchablesDoNotExistException
     * @throws SearchablesAreInvalidException
     */
    final protected function getMatchingArtifactIds(CrossTrackerQuery $query, PFUser $user): array
    {
        $cross_tracker_widget_retriever = new CrossTrackerWidgetRetriever(new CrossTrackerWidgetDao());
        $result                         = (new CrossTrackerArtifactQueryFactoryBuilder())
            ->getArtifactFactory(
                new WithoutLinkTypeSelectFromBuilder(),
                $cross_tracker_widget_retriever
            )
            ->getArtifactsMatchingQuery($cross_tracker_widget_retriever, $query, $user, 10, 0, Option::nothing(string()));
        return array_values(array_map(static function (array $artifact): int {
            if (! isset($artifact['@id']) || ! ($artifact['@id'] instanceof NumericResultRepresentation)) {
                throw new LogicException('Query result should contains @id column');
            }

            return (int) $artifact['@id']->value;
        }, $result->artifacts));
    }

    final protected function getQueryResults(CrossTrackerQuery $query, PFUser $user): CrossTrackerQueryContentRepresentation
    {
        $cross_tracker_widget_retriever = new CrossTrackerWidgetRetriever(new CrossTrackerWidgetDao());
        $result                         = (new CrossTrackerArtifactQueryFactoryBuilder())
            ->getArtifactFactory(new WithoutLinkTypeSelectFromBuilder(), $cross_tracker_widget_retriever)
            ->getArtifactsMatchingQuery($cross_tracker_widget_retriever, $query, $user, 10, 0, Option::nothing(string()));
        assert($result instanceof CrossTrackerQueryContentRepresentation);
        return $result;
    }
}
