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

use Tuleap\AgileDashboard\Event\GetAdditionalScrumAdminSection;
use Tuleap\AgileDashboard\Milestone\HeaderOptionsProvider;
use Tuleap\AgileDashboard\Milestone\Pane\PaneInfoCollector;
use Tuleap\AgileDashboard\Milestone\Pane\Planning\PlanningV2PaneInfo;
use Tuleap\AgileDashboard\Planning\AllowedAdditionalPanesToDisplayCollector;
use Tuleap\AgileDashboard\Planning\HeaderOptionsForPlanningProvider;
use Tuleap\AgileDashboard\Planning\PlanningDao;
use Tuleap\Cardwall\Agiledashboard\CardwallPaneInfo;
use Tuleap\Cardwall\CardwallIsAllowedEvent;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\NewDropdown\CurrentContextSectionToHeaderOptionsInserter;
use Tuleap\Project\Registration\RegisterProjectCreationEvent;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Taskboard\Admin\ScrumBoardTypeSelectorController;
use Tuleap\Taskboard\AgileDashboard\MilestoneIsAllowedChecker;
use Tuleap\Taskboard\AgileDashboard\TaskboardPane;
use Tuleap\Taskboard\AgileDashboard\TaskboardPaneInfo;
use Tuleap\Taskboard\AgileDashboard\TaskboardPaneInfoBuilder;
use Tuleap\Taskboard\AgileDashboard\TaskboardUsage;
use Tuleap\Taskboard\AgileDashboard\TaskboardUsageDao;
use Tuleap\Taskboard\AgileDashboard\TaskboardUsageDuplicator;
use Tuleap\Taskboard\Board\BoardPresenterBuilder;
use Tuleap\Taskboard\Column\ColumnPresenterCollectionRetriever;
use Tuleap\Taskboard\REST\ResourcesInjector;
use Tuleap\Taskboard\Routing\MilestoneExtractor;
use Tuleap\Taskboard\Tracker\TrackerPresenterCollectionBuilder;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\NewDropdown\TrackerNewDropdownLinkPresenterBuilder;

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

    #[\Tuleap\Plugin\ListeningToEventName(Event::REST_RESOURCES)]
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

        $tracker_new_dropdown_link_presenter_builder = new TrackerNewDropdownLinkPresenterBuilder();

        $tracker_dao                  = new TrackerDao();
        $planning_dao                 = new PlanningDao($tracker_dao);
        $planning_permissions_manager = new PlanningPermissionsManager();
        $planning_factory             = new PlanningFactory(
            $planning_dao,
            TrackerFactory::instance(),
            $planning_permissions_manager
        );

        $header_options_inserter = new CurrentContextSectionToHeaderOptionsInserter();

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
                TrackerPresenterCollectionBuilder::build(),
                Tracker_ArtifactFactory::instance()
            ),
            new IncludeViteAssets(
                __DIR__ . '/../scripts/taskboard/frontend-assets',
                '/assets/taskboard/taskboard'
            ),
            new VisitRecorder(new RecentlyVisitedDao()),
            new HeaderOptionsProvider(
                new AgileDashboard_Milestone_Backlog_BacklogFactory(
                    new AgileDashboard_BacklogItemDao(),
                    Tracker_ArtifactFactory::instance(),
                    $planning_factory,
                ),
                new AgileDashboard_PaneInfoIdentifier(),
                $tracker_new_dropdown_link_presenter_builder,
                new HeaderOptionsForPlanningProvider(
                    new AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder(
                        \Tracker_HierarchyFactory::instance(),
                        $planning_factory,
                    ),
                    $tracker_new_dropdown_link_presenter_builder,
                    $header_options_inserter,
                ),
                new \Tuleap\AgileDashboard\Milestone\ParentTrackerRetriever(
                    $planning_factory,
                ),
                $header_options_inserter
            ),
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup(
            '/taskboard',
            function (FastRoute\RouteCollector $r) {
                $r->get('/{project_name:[A-z0-9-]+}/{id:\d+}', $this->getRouteHandler('routeGet'));
            }
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function agiledashboardEventAdditionalPanesOnMilestone(PaneInfoCollector $collector): void
    {
        $pane_info = $this->getPaneInfoForMilestone($collector->getMilestone());
        if ($pane_info === null) {
            return;
        }

        if ($collector->getActivePaneContext() && strpos($_SERVER['REQUEST_URI'], '/taskboard/') === 0) {
            $pane_info->setActive(true);
            $collector->setActivePaneBuilder(
                static function () use ($pane_info): AgileDashboard_Pane {
                    return new TaskboardPane($pane_info);
                }
            );
        }

        if ($collector->has(CardwallPaneInfo::IDENTIFIER)) {
            $collector->addPaneAfter(CardwallPaneInfo::IDENTIFIER, $pane_info);

            return;
        }

        $collector->addPaneAfter(PlanningV2PaneInfo::IDENTIFIER, $pane_info);
    }

    private function getCardwallOnTopDao(): Cardwall_OnTop_Dao
    {
        return new Cardwall_OnTop_Dao();
    }

    private function getPaneInfoForMilestone(Planning_Milestone $milestone): ?TaskboardPaneInfo
    {
        $pane_builder = new TaskboardPaneInfoBuilder($this->getMilestoneIsAllowedChecker());

        return $pane_builder->getPaneForMilestone($milestone);
    }

    private function getMilestoneIsAllowedChecker(): MilestoneIsAllowedChecker
    {
        return new MilestoneIsAllowedChecker(
            $this->getCardwallOnTopDao(),
            $this->getTaskboardUsage(),
            PluginManager::instance(),
            $this
        );
    }

    private function getTaskboardUsage(): TaskboardUsage
    {
        return new TaskboardUsage(new TaskboardUsageDao());
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function cardwallIsAllowedEvent(CardwallIsAllowedEvent $event): void
    {
        if (! $this->getTaskboardUsage()->isCardwallAllowed($event->getProject())) {
            $event->disallowCardwall();
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getAdditionalScrumAdminSection(GetAdditionalScrumAdminSection $event): void
    {
        $event->addAdditionalSectionController(
            new ScrumBoardTypeSelectorController(
                $event->getProject(),
                new TaskboardUsageDao(),
                \TemplateRendererFactory::build()->getRenderer(__DIR__ . "/../templates/Admin")
            )
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function registerProjectCreationEvent(RegisterProjectCreationEvent $event): void
    {
        $duplicator = new TaskboardUsageDuplicator(new TaskboardUsageDao());
        $duplicator->duplicateUsage(
            (int) $event->getJustCreatedProject()->getID(),
            (int) $event->getTemplateProject()->getID()
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function allowedAdditionalPanesToDisplayCollector(AllowedAdditionalPanesToDisplayCollector $event): void
    {
        $event->add(TaskboardPaneInfo::NAME);
    }
}
