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
use Tuleap\AgileDashboard\Milestone\HeaderOptionsProvider;
use Tuleap\AgileDashboard\Milestone\Pane\PaneInfoCollector;
use Tuleap\AgileDashboard\Planning\AllowedAdditionalPanesToDisplayCollector;
use Tuleap\AgileDashboard\Planning\HeaderOptionsForPlanningProvider;
use Tuleap\AgileDashboard\Planning\PlanningDao;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\NewDropdown\CurrentContextSectionToHeaderOptionsInserter;
use Tuleap\TestManagement\GetURIForMilestoneFromTTM;
use Tuleap\TestPlan\REST\ResourcesInjector;
use Tuleap\TestPlan\TestDefinition\EventRedirectAfterArtifactCreationOrUpdateProcessor;
use Tuleap\TestPlan\TestDefinition\RedirectParameterInjector;
use Tuleap\TestPlan\TestPlanController;
use Tuleap\TestPlan\TestPlanHeaderOptionsProvider;
use Tuleap\TestPlan\TestPlanPane;
use Tuleap\TestPlan\TestPlanPaneDisplayable;
use Tuleap\TestPlan\TestPlanPaneInfo;
use Tuleap\TestPlan\TestPlanPresenterBuilder;
use Tuleap\TestPlan\TestPlanTestDefinitionTrackerRetriever;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\Artifact\RedirectAfterArtifactCreationOrUpdateEvent;
use Tuleap\Tracker\Artifact\Renderer\BuildArtifactFormActionEvent;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdaterDataFormater;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\NewDropdown\TrackerNewDropdownLinkPresenterBuilder;

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
                    dgettext('tuleap-testplan', 'Integration between the agiledashboard and the testmanagement plugins')
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function agiledashboardEventAdditionalPanesOnMilestone(PaneInfoCollector $collector): void
    {
        $milestone = $collector->getMilestone();

        if (! $this->isTestPlanPaneDisplayable($milestone, $collector->getCurrentUser())) {
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

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function allowedAdditionalPanesToDisplayCollector(AllowedAdditionalPanesToDisplayCollector $event): void
    {
        $event->add(TestPlanPaneInfo::NAME);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup(
            TestPlanPaneInfo::URL,
            function (RouteCollector $r): void {
                $r->get('/{project_name:[A-z0-9-]+}/{id:\d+}[/backlog_item/{backlog_item_id:\d+}[/test/{test_definition_id:\d+}]]', $this->getRouteHandler('routeGetPlan'));
            }
        );
    }

    /**
     * @see         Event::REST_RESOURCES
     *
     * @psalm-param array{restler: \Luracast\Restler\Restler} $params
     */
    #[\Tuleap\Plugin\ListeningToEventName(Event::REST_RESOURCES)]
    public function restResources(array $params): void
    {
        (new ResourcesInjector())->populate($params['restler']);
    }

    public function routeGetPlan(): TestPlanController
    {
        $agiledashboard_plugin = PluginManager::instance()->getPluginByName('agiledashboard');
        if (! $agiledashboard_plugin instanceof AgileDashboardPlugin) {
            throw new RuntimeException('Cannot instantiate Agiledashboard plugin');
        }

        $tracker_factory       = TrackerFactory::instance();
        $testmanagement_config = new \Tuleap\TestManagement\Config(new \Tuleap\TestManagement\Dao(), $tracker_factory);

        $tracker_new_dropdown_link_presenter_builder = new TrackerNewDropdownLinkPresenterBuilder();
        $event_manager                               = EventManager::instance();

        $tracker_dao                  = new TrackerDao();
        $planning_dao                 = new PlanningDao($tracker_dao);
        $planning_permissions_manager = new PlanningPermissionsManager();
        $planning_factory             = new PlanningFactory(
            $planning_dao,
            TrackerFactory::instance(),
            $planning_permissions_manager
        );

        $header_options_inserter = new CurrentContextSectionToHeaderOptionsInserter();

        return new TestPlanController(
            $this->buildTemplateRenderer(),
            $agiledashboard_plugin->getAllBreadCrumbsForMilestoneBuilder(),
            new IncludeAssets(
                __DIR__ . '/../frontend-assets',
                '/assets/testplan'
            ),
            new TestPlanPaneDisplayable(
                new \Tuleap\TestManagement\Config(new \Tuleap\TestManagement\Dao(), $tracker_factory),
                $tracker_factory,
            ),
            new VisitRecorder(new RecentlyVisitedDao()),
            Planning_MilestoneFactory::build(),
            new TestPlanPresenterBuilder(
                $agiledashboard_plugin->getMilestonePaneFactory(),
                $testmanagement_config,
                $tracker_factory,
                new TestPlanTestDefinitionTrackerRetriever($testmanagement_config, $tracker_factory),
                UserHelper::instance(),
                (new TypePresenterFactory(new TypeDao(), new ArtifactLinksUsageDao()))
            ),
            new TestPlanHeaderOptionsProvider(
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
                    $header_options_inserter,
                ),
                $testmanagement_config,
                $tracker_factory,
                $tracker_new_dropdown_link_presenter_builder,
                $header_options_inserter,
            ),
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getURIForMilestoneFromTTM(GetURIForMilestoneFromTTM $event): void
    {
        $milestone = $event->getMilestone();
        if (! $this->isTestPlanPaneDisplayable($milestone, $event->getCurrentUser())) {
            return;
        }

        $pane_info = new TestPlanPaneInfo($milestone);
        $event->setURI($pane_info->getUri());
    }

    private function isTestPlanPaneDisplayable(Planning_Milestone $milestone, PFUser $user): bool
    {
        $tracker_factory            = TrackerFactory::instance();
        $test_plan_pane_displayable = new TestPlanPaneDisplayable(
            new \Tuleap\TestManagement\Config(
                new \Tuleap\TestManagement\Dao(),
                $tracker_factory,
            ),
            $tracker_factory,
        );

        return $test_plan_pane_displayable->isTestPlanPaneDisplayable($milestone->getProject(), $user);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function buildArtifactFormActionEvent(BuildArtifactFormActionEvent $event): void
    {
        $redirect_parameter_injector = new RedirectParameterInjector(
            Tracker_ArtifactFactory::instance(),
            $GLOBALS['Response'],
            $this->buildAgileDashboardTemplateRenderer(),
        );
        $redirect_parameter_injector->injectAndInformUserAboutBacklogItemBeingCovered($event->getRequest(), $event->getRedirect());
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function redirectAfterArtifactCreationOrUpdateEvent(RedirectAfterArtifactCreationOrUpdateEvent $event): void
    {
        $tracker_artifact_factory = Tracker_ArtifactFactory::instance();

        $priority_manager = new Tracker_Artifact_PriorityManager(
            new Tracker_Artifact_PriorityDao(),
            new Tracker_Artifact_PriorityHistoryDao(),
            UserManager::instance(),
            $tracker_artifact_factory
        );

        $artifactlink_updater = new ArtifactLinkUpdater(
            $priority_manager,
            new ArtifactLinkUpdaterDataFormater()
        );

        $processor = new EventRedirectAfterArtifactCreationOrUpdateProcessor(
            $tracker_artifact_factory,
            $artifactlink_updater,
            new RedirectParameterInjector(
                Tracker_ArtifactFactory::instance(),
                $GLOBALS['Response'],
                $this->buildAgileDashboardTemplateRenderer(),
            )
        );
        $processor->process($event->getRequest(), $event->getRedirect(), $event->getArtifact());
    }

    private function buildTemplateRenderer(): TemplateRenderer
    {
        return TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates');
    }

    private function buildAgileDashboardTemplateRenderer(): TemplateRenderer
    {
        return TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../agiledashboard/templates');
    }
}
