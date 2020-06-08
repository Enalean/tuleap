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

use FastRoute\RouteCollector;
use Tuleap\AgileDashboard\Milestone\Pane\PaneInfoCollector;
use Tuleap\AgileDashboard\Planning\AllowedAdditionalPanesToDisplayCollector;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\TestPlan\TestPlanController;
use Tuleap\TestPlan\TestPlanPane;
use Tuleap\TestPlan\TestPlanPaneDisplayable;
use Tuleap\TestPlan\TestPlanPaneInfo;
use Tuleap\TestPlan\TestPlanPresenterBuilder;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';
require_once __DIR__ . '/../../agiledashboard/include/agiledashboardPlugin.php';
require_once __DIR__ . '/../../testmanagement/include/testmanagementPlugin.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class testplanPlugin extends Plugin
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-testplan', __DIR__ . '/../site-content');
    }

    public function getDependencies(): array
    {
        return ['tracker', 'agiledashboard', 'testmanagement'];
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-testplan', 'TestPlan'),
                    '',
                    dgettext('tuleap-testplan', 'Integration between the agiledashboard and the testmanagement plugins')
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(PaneInfoCollector::NAME);
        $this->addHook(AllowedAdditionalPanesToDisplayCollector::NAME);

        $this->addHook(CollectRoutesEvent::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function agiledashboardEventAdditionalPanesOnMilestone(PaneInfoCollector $collector): void
    {
        $milestone = $collector->getMilestone();
        $project   = $milestone->getProject();

        if (
            ! (new TestPlanPaneDisplayable(new \Tuleap\TestManagement\Config(new \Tuleap\TestManagement\Dao(), TrackerFactory::instance())))
                ->isTestPlanPaneDisplayable($project)
        ) {
            return;
        }

        $pane_info = new TestPlanPaneInfo($milestone);
        if ($collector->getActivePaneContext() && strpos($_SERVER['REQUEST_URI'], TestPlanPaneInfo::URL) === 0) {
            $pane_info->setActive(true);
            $collector->setActivePaneBuilder(
                static function () use ($pane_info): AgileDashboard_Pane {
                    return new TestPlanPane($pane_info);
                }
            );
        }
        $collector->addPane($pane_info);
    }

    public function allowedAdditionalPanesToDisplayCollector(AllowedAdditionalPanesToDisplayCollector $event): void
    {
        $event->add(TestPlanPaneInfo::NAME);
    }

    public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup(
            TestPlanPaneInfo::URL,
            function (RouteCollector $r): void {
                $r->get('/{project_name:[A-z0-9-]+}/{id:\d+}', $this->getRouteHandler('routeGetPlan'));
            }
        );
    }

    public function routeGetPlan(): TestPlanController
    {
        $agiledashboard_plugin = PluginManager::instance()->getPluginByName('agiledashboard');
        if (! $agiledashboard_plugin instanceof AgileDashboardPlugin) {
            throw new RuntimeException('Cannot instantiate Agiledashboard plugin');
        }

        $tracker_factory       = TrackerFactory::instance();
        $testmanagement_config = new \Tuleap\TestManagement\Config(new \Tuleap\TestManagement\Dao(), $tracker_factory);

        return new TestPlanController(
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates'),
            $agiledashboard_plugin->getAllBreadCrumbsForMilestoneBuilder(),
            $agiledashboard_plugin->getIncludeAssets(),
            new IncludeAssets(
                __DIR__ . '/../../../src/www/assets/testplan',
                '/assets/testplan'
            ),
            new TestPlanPaneDisplayable(new \Tuleap\TestManagement\Config(new \Tuleap\TestManagement\Dao(), TrackerFactory::instance())),
            new VisitRecorder(new RecentlyVisitedDao()),
            Planning_MilestoneFactory::build(),
            new TestPlanPresenterBuilder(
                $agiledashboard_plugin->getMilestonePaneFactory(),
                $testmanagement_config,
                $tracker_factory,
            ),
            new Browser()
        );
    }
}
