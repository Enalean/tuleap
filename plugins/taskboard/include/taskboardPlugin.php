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
use Tuleap\AgileDashboard\Milestone\Pane\PaneInfoCollector;
use Tuleap\AgileDashboard\Milestone\Pane\Planning\PlanningV2PaneInfo;
use Tuleap\AgileDashboard\Planning\Presenters\AlternativeBoardLinkEvent;
use Tuleap\AgileDashboard\Planning\Presenters\AlternativeBoardLinkPresenter;
use Tuleap\AgileDashboard\REST\v1\AdditionalPanesForMilestoneEvent;
use Tuleap\AgileDashboard\REST\v1\PaneInfoRepresentation;
use Tuleap\Cardwall\Agiledashboard\CardwallPaneInfo;
use Tuleap\Cardwall\CardwallIsAllowedEvent;
use Tuleap\Layout\IncludeAssets;
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
        $this->addHook(GetAdditionalScrumAdminSection::NAME);
        $this->addHook(Event::REGISTER_PROJECT_CREATION);

        if (defined('AGILEDASHBOARD_BASE_URL')) {
            $this->addHook(PaneInfoCollector::NAME);
            $this->addHook(AdditionalPanesForMilestoneEvent::NAME);
            $this->addHook(AlternativeBoardLinkEvent::NAME);
        }

        if (defined('CARDWALL_BASE_URL')) {
            $this->addHook(CardwallIsAllowedEvent::NAME);
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
                __DIR__ . '/../../../src/www/assets/taskboard',
                '/assets/taskboard'
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

    public function agiledashboardEventAdditionalPanesOnMilestone(PaneInfoCollector $collector): void
    {
        if ($this->isIE11()) {
            return;
        }

        $pane_info = $this->getPaneInfoForMilestone($collector->getMilestone());
        if ($pane_info === null) {
            return;
        }

        if (strpos($_SERVER['REQUEST_URI'], '/taskboard/') === 0) {
            $pane_info->setActive(true);
            $collector->setActivePane(new TaskboardPane($pane_info));
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

    public function additionalPanesForMilestoneEvent(AdditionalPanesForMilestoneEvent $event): void
    {
        if ($this->isIE11()) {
            return;
        }

        $milestone = $event->getMilestone();

        $pane = $this->getPaneInfoForMilestone($milestone);
        if ($pane !== null) {
            $representation = new PaneInfoRepresentation();
            $representation->build($pane);

            $event->add($representation);
        }
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

    public function cardwallIsAllowedEvent(CardwallIsAllowedEvent $event): void
    {
        if ($this->isIE11()) {
            return;
        }

        if (! $this->getTaskboardUsage()->isCardwallAllowed($event->getProject())) {
            $event->disallowCardwall();
        }
    }

    public function alternativeBoardLinkEvent(AlternativeBoardLinkEvent $event): void
    {
        $pane = $this->getPaneInfoForMilestone($event->getMilestone());
        if ($pane !== null) {
            $event->setAlternativeBoardLink(
                new AlternativeBoardLinkPresenter(
                    $pane->getUri(),
                    $pane->getIconName(),
                    $pane->getTitle()
                )
            );
        }
    }

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

    /** @see Event::REGISTER_PROJECT_CREATION */
    public function registerProjectCreation(array $params): void
    {
        $project_id  = (int) $params['group_id'];
        $template_id = (int) $params['template_id'];

        $duplicator = new TaskboardUsageDuplicator(new TaskboardUsageDao());
        $duplicator->duplicateUsage($project_id, $template_id);
    }

    private function isIE11(): bool
    {
        return (new Browser())->isIE11();
    }
}
