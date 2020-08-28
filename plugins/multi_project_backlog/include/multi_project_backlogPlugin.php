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

use Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao;
use Tuleap\AgileDashboard\Planning\Admin\PlanningEditURLEvent;
use Tuleap\AgileDashboard\Planning\Admin\PlanningUpdatedEvent;
use Tuleap\AgileDashboard\Planning\MilestoneBurndownFieldChecker;
use Tuleap\AgileDashboard\Planning\RootPlanning\DisplayTopPlanningAppEvent;
use Tuleap\AgileDashboard\Planning\RootPlanning\RootPlanningEditionEvent;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Layout\IncludeAssets;
use Tuleap\MultiProjectBacklog\Aggregator\AggregatorDao;
use Tuleap\MultiProjectBacklog\Aggregator\ContributorProjectsCollectionBuilder;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\CreationCheck\ArtifactCreatorChecker;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\CreationCheck\MilestoneCreatorChecker;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\CreationCheck\StatusSemanticChecker;
use Tuleap\MultiProjectBacklog\Aggregator\MirroredArtifactLink\MirroredMilestoneArtifactLinkType;
use Tuleap\MultiProjectBacklog\Aggregator\PlannableItems\PlannableItemsCollectionBuilder;
use Tuleap\MultiProjectBacklog\Aggregator\PlannableItems\PlannableItemsTrackersDao;
use Tuleap\MultiProjectBacklog\Aggregator\PlannableItems\PlannableItemsTrackersUpdater;
use Tuleap\MultiProjectBacklog\Aggregator\PlannableItems\Presenter\PlannableItemsPerContributorPresenterCollectionBuilder;
use Tuleap\MultiProjectBacklog\Aggregator\ReadOnlyAggregatorAdminURLBuilder;
use Tuleap\MultiProjectBacklog\Aggregator\ReadOnlyAggregatorAdminViewController;
use Tuleap\MultiProjectBacklog\Contributor\ContributorDao;
use Tuleap\MultiProjectBacklog\Contributor\RootPlanning\RootPlanningEditionHandler;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Tracker\Artifact\CanSubmitNewArtifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../agiledashboard/include/agiledashboardPlugin.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class multi_project_backlogPlugin extends Plugin
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-multi_project_backlog', __DIR__ . '/../site-content');
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(PlanningEditURLEvent::NAME);
        $this->addHook(RootPlanningEditionEvent::NAME);
        $this->addHook(PlanningUpdatedEvent::NAME);
        $this->addHook(DisplayTopPlanningAppEvent::NAME);
        $this->addHook(NaturePresenterFactory::EVENT_GET_ARTIFACTLINK_NATURES, 'getArtifactLinkNatures');
        $this->addHook(NaturePresenterFactory::EVENT_GET_NATURE_PRESENTER, 'getNaturePresenter');
        $this->addHook(Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink::TRACKER_ADD_SYSTEM_NATURES, 'trackerAddSystemNatures');
        $this->addHook(CanSubmitNewArtifact::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getDependencies(): array
    {
        return ['tracker', 'agiledashboard'];
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $pluginInfo = new PluginInfo($this);
            $pluginInfo->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-multi_project_backlog', 'Multi-Project Backlog'),
                    '',
                    dgettext('tuleap-multi_project_backlog', 'Extension of the Agile Dashboard plugin to allow planning of Backlog items across projects')
                )
            );
            $this->pluginInfo = $pluginInfo;
        }
        return $this->pluginInfo;
    }

    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addRoute(
            ['GET'],
            '/project/{project_name:[A-z0-9-]+}/backlog/admin/{id:\d+}',
            $this->getRouteHandler('routeGETAggregatorReadOnlyAdminTopPlanning')
        );
    }

    public function routeGETAggregatorReadOnlyAdminTopPlanning(): ReadOnlyAggregatorAdminViewController
    {
        $agiledashboard_plugin = PluginManager::instance()->getPluginByName(AgileDashboardPlugin::PLUGIN_NAME);
        assert($agiledashboard_plugin instanceof AgileDashboardPlugin);

        return new ReadOnlyAggregatorAdminViewController(
            ProjectManager::instance(),
            PlanningFactory::build(),
            new AgileDashboardCrumbBuilder(
                $agiledashboard_plugin->getPluginPath()
            ),
            new AdministrationCrumbBuilder(),
            $this->buildTemplateRenderer(),
            new PlannableItemsCollectionBuilder(
                new PlannableItemsTrackersDao(),
                TrackerFactory::instance(),
                ProjectManager::instance()
            ),
            new PlannableItemsPerContributorPresenterCollectionBuilder(
                PlanningFactory::build()
            ),
            new IncludeAssets(
                __DIR__ . '/../../../src/www/assets/multi_project_backlog',
                '/assets/multi_project_backlog'
            )
        );
    }

    public function planningEditURLEvent(PlanningEditURLEvent $event): void
    {
        $planning      = $event->getPlanning();
        $root_planning = $event->getRootPlanning();

        $url_builder = new ReadOnlyAggregatorAdminURLBuilder(
            new AggregatorDao(),
            ProjectManager::instance()
        );

        $url = $url_builder->buildURL(
            $planning,
            $root_planning
        );

        if ($url !== null) {
            $event->setEditUrl($url);
        }
    }

    public function rootPlanningEditionEvent(RootPlanningEditionEvent $event): void
    {
        $handler = new RootPlanningEditionHandler(new ContributorDao());
        $handler->handle($event);
    }

    public function planningUpdatedEvent(PlanningUpdatedEvent $event): void
    {
        (new PlannableItemsTrackersUpdater(
            new ContributorDao(),
            new PlannableItemsTrackersDao(),
            PlanningFactory::build(),
            new DBTransactionExecutorWithConnection(
                DBFactory::getMainTuleapDBConnection()
            )
        ))->updatePlannableItemsTrackersFromPlanning(
            $event->getPlanning(),
            $event->getUser()
        );
    }

    private function buildTemplateRenderer(): TemplateRenderer
    {
        return TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates');
    }

    public function displayTopPlanningAppEvent(DisplayTopPlanningAppEvent $event): void
    {
        $contributor_projects_collection_builder = new ContributorProjectsCollectionBuilder(
            new AggregatorDao(),
            ProjectManager::instance()
        );

        $contributor_project_collection = $contributor_projects_collection_builder->getContributorProjectForAGivenAggregatorProject(
            $event->getTopMilestone()->getProject()
        );

        if ($contributor_project_collection->isEmpty() === true) {
            return;
        }

        $event->setBacklogItemsCannotBeAdded();

        $milestone_creator_checker = $this->getMilestoneCreatorChecker();

        if (! $event->canUserCreateMilestone()) {
            return;
        }
        $user_can_create_milestone = $milestone_creator_checker->canMilestoneBeCreated(
            $event->getTopMilestone(),
            $event->getUser()
        );

        if (! $user_can_create_milestone) {
            $event->setUserCannotCreateMilestone();
        }
    }

    /**
     * @see NaturePresenterFactory::EVENT_GET_ARTIFACTLINK_NATURES
     */
    public function getArtifactLinkNatures(array $params): void
    {
        $params['natures'][] = new MirroredMilestoneArtifactLinkType();
    }

    /**
     * @see NaturePresenterFactory::EVENT_GET_NATURE_PRESENTER
     */
    public function getNaturePresenter(array $params): void
    {
        if ($params['shortname'] === MirroredMilestoneArtifactLinkType::ART_LINK_SHORT_NAME) {
            $params['presenter'] = new MirroredMilestoneArtifactLinkType();
        }
    }

    /**
     * @see Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink::TRACKER_ADD_SYSTEM_NATURES
     */
    public function trackerAddSystemNatures(array $params): void
    {
        $params['natures'][] = MirroredMilestoneArtifactLinkType::ART_LINK_SHORT_NAME;
    }

    public function canSubmitNewArtifact(CanSubmitNewArtifact $can_submit_new_artifact): void
    {
        $form_element_factory     = \Tracker_FormElementFactory::instance();
        $artifact_creator_checker = new ArtifactCreatorChecker(
            new Planning_MilestoneFactory(
                PlanningFactory::build(),
                Tracker_ArtifactFactory::instance(),
                $form_element_factory,
                new AgileDashboard_Milestone_MilestoneStatusCounter(
                    new AgileDashboard_BacklogItemDao(),
                    new Tracker_ArtifactDao(),
                    Tracker_ArtifactFactory::instance()
                ),
                new PlanningPermissionsManager(),
                new AgileDashboard_Milestone_MilestoneDao(),
                new ScrumForMonoMilestoneChecker(
                    new ScrumForMonoMilestoneDao(),
                    PlanningFactory::build()
                ),
                new TimeframeBuilder(
                    new SemanticTimeframeBuilder(
                        new SemanticTimeframeDao(),
                        $form_element_factory
                    ),
                    \BackendLogger::getDefaultLogger()
                ),
                new MilestoneBurndownFieldChecker($form_element_factory)
            ),
            $this->getMilestoneCreatorChecker()
        );

        if (! $artifact_creator_checker->canCreateAnArtifact($can_submit_new_artifact->getUser(), $can_submit_new_artifact->getTracker())) {
            $can_submit_new_artifact->disableArtifactSubmission();
        }
    }

    private function getMilestoneCreatorChecker(): MilestoneCreatorChecker
    {
        $form_element_factory    = \Tracker_FormElementFactory::instance();
        $timeframe_dao           = new \Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao();
        $semantic_status_factory = new Tracker_Semantic_StatusFactory();

        return new MilestoneCreatorChecker(
            new ContributorProjectsCollectionBuilder(
                new AggregatorDao(),
                ProjectManager::instance()
            ),
            new \Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerCollectionBuilder(
                \PlanningFactory::build()
            ),
            new \Tuleap\MultiProjectBacklog\Aggregator\Milestone\SynchronizedFieldCollectionBuilder(
                $form_element_factory,
                new Tracker_Semantic_TitleFactory(),
                new Tracker_Semantic_DescriptionFactory(),
                $semantic_status_factory,
                new \Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder($timeframe_dao, $form_element_factory)
            ),
            new \Tuleap\MultiProjectBacklog\Aggregator\Milestone\CreationCheck\SemanticChecker(
                new \Tracker_Semantic_TitleDao(),
                new \Tracker_Semantic_DescriptionDao(),
                $timeframe_dao,
                new StatusSemanticChecker(new Tracker_Semantic_StatusDao(), $semantic_status_factory),
            ),
            BackendLogger::getDefaultLogger("multi_project_backlog_syslog")
        );
    }
}
