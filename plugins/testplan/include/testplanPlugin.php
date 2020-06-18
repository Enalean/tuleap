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
use Tuleap\TestManagement\GetURIForMilestoneFromTTM;
use Tuleap\TestPlan\REST\ResourcesInjector;
use Tuleap\TestPlan\TestDefinition\EventRedirectAfterArtifactCreationOrUpdateProcessor;
use Tuleap\TestPlan\TestDefinition\RedirectParameterInjector;
use Tuleap\TestPlan\TestPlanController;
use Tuleap\TestPlan\TestPlanPane;
use Tuleap\TestPlan\TestPlanPaneDisplayable;
use Tuleap\TestPlan\TestPlanPaneInfo;
use Tuleap\TestPlan\TestPlanPresenterBuilder;
use Tuleap\TestPlan\TestPlanTestDefinitionTrackerRetriever;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;

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
        $this->addHook(GetURIForMilestoneFromTTM::NAME);
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(TRACKER_EVENT_BUILD_ARTIFACT_FORM_ACTION);
        $this->addHook(TRACKER_EVENT_REDIRECT_AFTER_ARTIFACT_CREATION_OR_UPDATE);

        return parent::getHooksAndCallbacks();
    }

    public function agiledashboardEventAdditionalPanesOnMilestone(PaneInfoCollector $collector): void
    {
        $milestone = $collector->getMilestone();

        if (! $this->isTestPlanPaneDisplayable($milestone)) {
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

    /**
     * @see         Event::REST_RESOURCES
     *
     * @psalm-param array{restler: \Luracast\Restler\Restler} $params
     */
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

        return new TestPlanController(
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates'),
            $agiledashboard_plugin->getAllBreadCrumbsForMilestoneBuilder(),
            $agiledashboard_plugin->getIncludeAssets(),
            new IncludeAssets(
                __DIR__ . '/../../../src/www/assets/testplan',
                '/assets/testplan'
            ),
            new TestPlanPaneDisplayable(
                new \Tuleap\TestManagement\Config(new \Tuleap\TestManagement\Dao(), TrackerFactory::instance())
            ),
            new VisitRecorder(new RecentlyVisitedDao()),
            Planning_MilestoneFactory::build(),
            new TestPlanPresenterBuilder(
                $agiledashboard_plugin->getMilestonePaneFactory(),
                $testmanagement_config,
                $tracker_factory,
                new TestPlanTestDefinitionTrackerRetriever($testmanagement_config, $tracker_factory)
            ),
            new Browser()
        );
    }

    public function getURIForMilestoneFromTTM(GetURIForMilestoneFromTTM $event): void
    {
        $milestone = $event->getMilestone();
        if (! $this->isTestPlanPaneDisplayable($milestone)) {
            return;
        }

        $pane_info = new TestPlanPaneInfo($milestone);
        $event->setURI($pane_info->getUri());
    }

    private function isTestPlanPaneDisplayable(Planning_Milestone $milestone): bool
    {
        $test_plan_pane_displayable = new TestPlanPaneDisplayable(
            new \Tuleap\TestManagement\Config(
                new \Tuleap\TestManagement\Dao(),
                TrackerFactory::instance()
            )
        );

        return $test_plan_pane_displayable->isTestPlanPaneDisplayable($milestone->getProject());
    }

    /**
     * @see TRACKER_EVENT_BUILD_ARTIFACT_FORM_ACTION
     */
    public function trackerEventBuildArtifactFormAction(array $params): void
    {
        $request = $params['request'];
        assert($request instanceof Codendi_Request);

        $redirect = $params['redirect'];
        assert($redirect instanceof Tracker_Artifact_Redirect);

        (new RedirectParameterInjector())->inject($request, $redirect);
    }

    /**
     * @see TRACKER_EVENT_REDIRECT_AFTER_ARTIFACT_CREATION_OR_UPDATE
     */
    public function trackerEventRedirectAfterArtifactCreationOrUpdate(array $params): void
    {
        $request = $params['request'];
        assert($request instanceof Codendi_Request);

        $redirect = $params['redirect'];
        assert($redirect instanceof Tracker_Artifact_Redirect);

        $artifact = $params['artifact'];
        assert($artifact instanceof Tracker_Artifact);

        $tracker_artifact_factory = Tracker_ArtifactFactory::instance();

        $priority_manager = new Tracker_Artifact_PriorityManager(
            new Tracker_Artifact_PriorityDao(),
            new Tracker_Artifact_PriorityHistoryDao(),
            UserManager::instance(),
            $tracker_artifact_factory
        );

        $artifactlink_updater = new ArtifactLinkUpdater(
            $priority_manager
        );

        $processor = new EventRedirectAfterArtifactCreationOrUpdateProcessor(
            $tracker_artifact_factory,
            $artifactlink_updater,
            new RedirectParameterInjector()
        );
        $processor->process($request, $redirect, $artifact);
    }
}
