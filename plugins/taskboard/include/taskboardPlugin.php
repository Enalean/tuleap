<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\REST\v1\AdditionalPanesForMilestoneEvent;
use Tuleap\AgileDashboard\REST\v1\PaneInfoRepresentation;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Taskboard\AgileDashboard\MilestoneIsAllowedChecker;
use Tuleap\Taskboard\AgileDashboard\TaskboardPane;
use Tuleap\Taskboard\AgileDashboard\TaskboardPaneInfo;
use Tuleap\Taskboard\AgileDashboard\TaskboardPaneInfoBuilder;
use Tuleap\Taskboard\Board\BoardPresenterBuilder;
use Tuleap\Taskboard\Column\ColumnPresenterCollectionRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\TrackerMappingPresenterBuilder;
use Tuleap\Taskboard\REST\ResourcesInjector;
use Tuleap\Taskboard\Routing\MilestoneExtractor;
use Tuleap\Taskboard\Tracker\TrackerPresenterCollectionBuilder;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;

require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class taskboardPlugin extends Plugin
{
    public const NAME = 'taskboard';

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-taskboard', __DIR__ . '/../site-content');
    }

    public function getDependencies(): array
    {
        return ['agiledashboard'];
    }

    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new \Tuleap\Taskboard\Plugin\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(CollectRoutesEvent::NAME);

        if (defined('AGILEDASHBOARD_BASE_URL')) {
            $this->addHook(AGILEDASHBOARD_EVENT_ADDITIONAL_PANES_ON_MILESTONE);
            $this->addHook(AdditionalPanesForMilestoneEvent::NAME);
        }

        return parent::getHooksAndCallbacks();
    }

    /** @see Event::REST_RESOURCES */
    public function restResources(array $params): void
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    public function routeGet(): \Tuleap\Taskboard\Routing\TaskboardController
    {
        $agiledashboard_plugin = PluginManager::instance()->getPluginByName('agiledashboard');
        if (! $agiledashboard_plugin instanceof AgileDashboardPlugin) {
            throw new RuntimeException('Cannot instantiate Agiledashboard plugin');
        }

        return new \Tuleap\Taskboard\Routing\TaskboardController(
            new MilestoneExtractor(
                $agiledashboard_plugin->getMilestoneFactory(),
                $this->getMilestoneIsAllowedChecker()
            ),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates'),
            $agiledashboard_plugin->getAllBreadCrumbsForMilestoneBuilder(),
            new BoardPresenterBuilder(
                $agiledashboard_plugin->getMilestonePaneFactory(),
                ColumnPresenterCollectionRetriever::build(),
                new AgileDashboard_BacklogItemDao(),
                TrackerPresenterCollectionBuilder::build()
            ),
            $agiledashboard_plugin->getIncludeAssets(),
            new IncludeAssets(
                __DIR__ . '/../../../src/www/assets/taskboard/themes',
                '/assets/taskboard/themes'
            ),
            new IncludeAssets(
                __DIR__ . '/../../../src/www/assets/taskboard/scripts',
                '/assets/taskboard/scripts'
            ),
            new VisitRecorder(new RecentlyVisitedDao())
        );
    }

    public function collectRoutesEvent(CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->addGroup(
            '/taskboard',
            function (FastRoute\RouteCollector $r) {
                $r->get('/{project_name:[A-z0-9-]+}/{id:\d+}', $this->getRouteHandler('routeGet'));
            }
        );
    }

    /** @see AGILEDASHBOARD_EVENT_ADDITIONAL_PANES_ON_MILESTONE */
    public function agiledashboardEventAdditionalPanesOnMilestone(array $params): void
    {
        $milestone = $params['milestone'];
        assert($milestone instanceof Planning_Milestone);

        $pane_info = $this->getPaneInfoForMilestone($milestone);
        if ($pane_info === null) {
            return;
        }

        if (strpos($_SERVER['REQUEST_URI'], '/taskboard/') === 0) {
            $pane_info->setActive(true);
            $params['active_pane'] = new TaskboardPane($pane_info);
        }

        $params['panes'][] = $pane_info;
    }

    private function getCardwallOnTopDao(): Cardwall_OnTop_Dao
    {
        return new Cardwall_OnTop_Dao();
    }

    public function additionalPanesForMilestoneEvent(AdditionalPanesForMilestoneEvent $event): void
    {
        $milestone = $event->getMilestone();

        $pane = $this->getPaneInfoForMilestone($milestone);
        if ($pane !== null) {
            $representation = new PaneInfoRepresentation();
            $representation->build($pane);

            $event->add($representation);
        }
    }

    public function getPaneInfoForMilestone(Planning_Milestone $milestone): ?TaskboardPaneInfo
    {
        $pane_builder = new TaskboardPaneInfoBuilder($this->getMilestoneIsAllowedChecker());

        return $pane_builder->getPaneForMilestone($milestone);
    }

    public function getMilestoneIsAllowedChecker(): MilestoneIsAllowedChecker
    {
        return new MilestoneIsAllowedChecker(
            $this->getCardwallOnTopDao(),
            PluginManager::instance(),
            $this
        );
    }
}
