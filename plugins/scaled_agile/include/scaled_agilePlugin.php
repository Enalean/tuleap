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

use Tuleap\AgileDashboard\BlockScrumAccess;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\Planning\ConfigurationCheckDelegation;
use Tuleap\AgileDashboard\Planning\PlanningAdministrationDelegation;
use Tuleap\AgileDashboard\Planning\RootPlanning\DisplayTopPlanningAppEvent;
use Tuleap\AgileDashboard\Planning\RootPlanning\RootPlanningEditionEvent;
use Tuleap\AgileDashboard\REST\v1\Milestone\OriginalProjectCollector;
use Tuleap\Layout\ServiceUrlCollector;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\ScaledAgile\Adapter\Program\Backlog\AsynchronousCreation\CreateProgramIncrementsRunner;
use Tuleap\ScaledAgile\Adapter\Program\Backlog\AsynchronousCreation\PendingArtifactCreationDao;
use Tuleap\ScaledAgile\Adapter\Program\Backlog\AsynchronousCreation\TaskBuilder;
use Tuleap\ScaledAgile\Adapter\Program\Backlog\CreationCheck\RequiredFieldChecker;
use Tuleap\ScaledAgile\Adapter\Program\Backlog\CreationCheck\SemanticChecker;
use Tuleap\ScaledAgile\Adapter\Program\Backlog\CreationCheck\StatusSemanticChecker;
use Tuleap\ScaledAgile\Adapter\Program\Backlog\CreationCheck\WorkflowChecker;
use Tuleap\ScaledAgile\Adapter\Program\Backlog\ProgramIncrement\ArtifactLinkFieldAdapter;
use Tuleap\ScaledAgile\Adapter\Program\Backlog\ProgramIncrement\DescriptionFieldAdapter;
use Tuleap\ScaledAgile\Adapter\Program\Backlog\ProgramIncrement\ReplicationDataAdapter;
use Tuleap\ScaledAgile\Adapter\Program\Backlog\ProgramIncrement\Source\NatureAnalyzerException;
use Tuleap\ScaledAgile\Adapter\Program\Backlog\ProgramIncrement\Source\SourceArtifactNatureAnalyzer;
use Tuleap\ScaledAgile\Adapter\Program\Backlog\ProgramIncrement\StatusFieldAdapter;
use Tuleap\ScaledAgile\Adapter\Program\Backlog\ProgramIncrement\SynchronizedFieldsAdapter;
use Tuleap\ScaledAgile\Adapter\Program\Backlog\ProgramIncrement\TimeFrameFieldsAdapter;
use Tuleap\ScaledAgile\Adapter\Program\Backlog\ProgramIncrement\TitleFieldAdapter;
use Tuleap\ScaledAgile\Adapter\Program\Hierarchy\ScaledAgileHierarchyDAO;
use Tuleap\ScaledAgile\Adapter\Program\Plan\PlanDao;
use Tuleap\ScaledAgile\Adapter\Program\Plan\ProgramAdapter;
use Tuleap\ScaledAgile\Adapter\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ScaledAgile\Adapter\Program\PlanningAdapter;
use Tuleap\ScaledAgile\Adapter\Program\PlanningCheck\ConfigurationUserCanNotSeeProgramException;
use Tuleap\ScaledAgile\Adapter\Program\PlanningCheck\PlanningProgramAdapter;
use Tuleap\ScaledAgile\Adapter\Program\PlanningCheck\ProgramNotFoundException;
use Tuleap\ScaledAgile\Adapter\Program\PlanningCheck\UserCanNotAccessToProgramException;
use Tuleap\ScaledAgile\Adapter\Program\ProgramDao;
use Tuleap\ScaledAgile\Adapter\ProjectAdapter;
use Tuleap\ScaledAgile\Adapter\Team\TeamDao;
use Tuleap\ScaledAgile\DisplayProgramBacklogController;
use Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation\ArtifactCreatedHandler;
use Tuleap\ScaledAgile\Program\Backlog\CreationCheck\ArtifactCreatorChecker;
use Tuleap\ScaledAgile\Program\Backlog\CreationCheck\ProgramIncrementArtifactCreatorChecker;
use Tuleap\ScaledAgile\Program\Backlog\PlanningCheck\ConfigurationChecker;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\ProgramIncrementArtifactLinkType;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollectionBuilder;
use Tuleap\ScaledAgile\Program\Backlog\TrackerCollectionFactory;
use Tuleap\ScaledAgile\REST\ResourcesInjector;
use Tuleap\ScaledAgile\ScaledAgileService;
use Tuleap\ScaledAgile\ScaledAgileTracker;
use Tuleap\ScaledAgile\Team\RootPlanning\RootPlanningEditionHandler;
use Tuleap\ScaledAgile\Workspace\ComponentInvolvedVerifier;
use Tuleap\Tracker\Artifact\CanSubmitNewArtifact;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;
use Tuleap\Tracker\Hierarchy\TrackerHierarchyDelegation;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';
require_once __DIR__ . '/../../agiledashboard/include/agiledashboardPlugin.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class scaled_agilePlugin extends Plugin
{
    public const SERVICE_SHORTNAME = 'plugin_scaled_agile';

    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-scaled_agile', __DIR__ . '/../site-content');
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(RootPlanningEditionEvent::NAME);
        $this->addHook(DisplayTopPlanningAppEvent::NAME);
        $this->addHook(NaturePresenterFactory::EVENT_GET_ARTIFACTLINK_NATURES, 'getArtifactLinkNatures');
        $this->addHook(NaturePresenterFactory::EVENT_GET_NATURE_PRESENTER, 'getNaturePresenter');
        $this->addHook(Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink::TRACKER_ADD_SYSTEM_NATURES, 'trackerAddSystemNatures');
        $this->addHook(CanSubmitNewArtifact::NAME);
        $this->addHook(ArtifactCreated::NAME);
        $this->addHook(WorkerEvent::NAME);
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(PlanningAdministrationDelegation::NAME);
        $this->addHook('tracker_usage', 'trackerUsage');
        $this->addHook('project_is_deleted', 'projectIsDeleted');
        $this->addHook(ConfigurationCheckDelegation::NAME);
        $this->addHook(BlockScrumAccess::NAME);
        $this->addHook(TrackerHierarchyDelegation::NAME);
        $this->addHook(OriginalProjectCollector::NAME);
        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);
        $this->addHook(ServiceUrlCollector::NAME);
        $this->addHook(CollectRoutesEvent::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getDependencies(): array
    {
        return ['tracker', 'agiledashboard', 'cardwall'];
    }

    public function service_classnames(array &$params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $params['classnames'][$this->getServiceShortname()] = ScaledAgileService::class;
    }

    public function getServiceShortname(): string
    {
        return self::SERVICE_SHORTNAME;
    }

    public function serviceUrlCollector(ServiceUrlCollector $collector): void
    {
        if ($collector->getServiceShortname() === $this->getServiceShortname()) {
            $collector->setUrl(
                sprintf('/scaled_agile/%s', urlencode($collector->getProject()->getUnixNameLowerCase()))
            );
        }
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $pluginInfo = new PluginInfo($this);
            $pluginInfo->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-scaled_agile', 'Scaled Agile Backlog'),
                    '',
                    dgettext('tuleap-scaled_agile', 'Extension of the Agile Dashboard plugin to allow planning of Backlog items across projects')
                )
            );
            $this->pluginInfo = $pluginInfo;
        }
        return $this->pluginInfo;
    }

    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup(
            '/scaled_agile',
            function (FastRoute\RouteCollector $r) {
                $r->get('/{project_name:[A-z0-9-]+}[/]', $this->getRouteHandler('routeGetScaledAgile'));
            }
        );
    }

    public function routeGetScaledAgile(): DisplayProgramBacklogController
    {
        return new DisplayProgramBacklogController(
            ProjectManager::instance(),
            new \Tuleap\Project\Flags\ProjectFlagsBuilder(new \Tuleap\Project\Flags\ProjectFlagsDao()),
            new ProgramAdapter(
                ProjectManager::instance(),
                new ProgramDao(),
                new ExplicitBacklogDao()
            ),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . "/../templates")
        );
    }

    public function rootPlanningEditionEvent(RootPlanningEditionEvent $event): void
    {
        $handler = new RootPlanningEditionHandler(new \Tuleap\ScaledAgile\Adapter\Team\TeamDao());
        $handler->handle($event);
    }

    public function displayTopPlanningAppEvent(DisplayTopPlanningAppEvent $event): void
    {
        $virtual_top_milestone = $event->getTopMilestone();
        $project_data = ProjectAdapter::build($virtual_top_milestone->getProject());
        $team_project_collection = $this->getTeamProjectCollectionBuilder()->getTeamProjectForAGivenProgramProject(
            $project_data
        );

        if ($team_project_collection->isEmpty() === true || $virtual_top_milestone->getPlanning()->getId() === null) {
            return;
        }

        $event->setBacklogItemsCannotBeAdded();

        $project_increment_creator_checker = $this->getProjectIncrementCreatorChecker();

        if (! $event->canUserCreateMilestone()) {
            return;
        }

        $planning_adapter = $this->getPlanningAdapter();
        $scaled_planning  = $planning_adapter->buildFromPlanning($virtual_top_milestone->getPlanning());

        $user_can_create_project_increment = $project_increment_creator_checker->canProgramIncrementBeCreated(
            $scaled_planning,
            $event->getUser()
        );

        if (! $user_can_create_project_increment) {
            $event->setUserCannotCreateMilestone();
        }
    }

    /**
     * @see NaturePresenterFactory::EVENT_GET_ARTIFACTLINK_NATURES
     */
    public function getArtifactLinkNatures(array $params): void
    {
        $params['natures'][] = new ProgramIncrementArtifactLinkType();
    }

    /**
     * @see NaturePresenterFactory::EVENT_GET_NATURE_PRESENTER
     */
    public function getNaturePresenter(array $params): void
    {
        if ($params['shortname'] === ProgramIncrementArtifactLinkType::ART_LINK_SHORT_NAME) {
            $params['presenter'] = new ProgramIncrementArtifactLinkType();
        }
    }

    /**
     * @see Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink::TRACKER_ADD_SYSTEM_NATURES
     */
    public function trackerAddSystemNatures(array $params): void
    {
        $params['natures'][] = ProgramIncrementArtifactLinkType::ART_LINK_SHORT_NAME;
    }

    public function canSubmitNewArtifact(CanSubmitNewArtifact $can_submit_new_artifact): void
    {
        $artifact_creator_checker = new ArtifactCreatorChecker(
            $this->getPlanningAdapter(),
            $this->getProjectIncrementCreatorChecker()
        );

        $tracker_data = new ScaledAgileTracker($can_submit_new_artifact->getTracker());
        $project_data = ProjectAdapter::build($can_submit_new_artifact->getTracker()->getProject());
        if (
            ! $artifact_creator_checker->canCreateAnArtifact(
                $can_submit_new_artifact->getUser(),
                $tracker_data,
                $project_data
            )
        ) {
            $can_submit_new_artifact->disableArtifactSubmission();
        }
    }

    public function workerEvent(WorkerEvent $event): void
    {
        $create_mirrors_runner = $this->getProgramIncrementRunner();
        $create_mirrors_runner->addListener($event);
    }

    public function trackerArtifactCreated(ArtifactCreated $event): void
    {
        $program_dao = new ProgramDao();
        $logger      = $this->getLogger();

        $logger->debug(
            sprintf(
                "Store program create with #%d by user #%d",
                (int) $event->getArtifact()->getId(),
                (int) $event->getUser()->getId()
            )
        );

        $handler = new ArtifactCreatedHandler(
            $program_dao,
            $this->getProgramIncrementRunner(),
            new PendingArtifactCreationDao(),
            $this->getPlanningAdapter()
        );
        $handler->handle($event);
    }

    /**
     * @see         Event::REST_RESOURCES
     *
     * @psalm-param array{restler: \Luracast\Restler\Restler} $params
     */
    public function restResources(array $params): void
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    public function planningAdministrationDelegation(
        PlanningAdministrationDelegation $planning_administration_delegation
    ): void {
        $component_involved_verifier = new ComponentInvolvedVerifier(
            new \Tuleap\ScaledAgile\Adapter\Team\TeamDao(),
            new ProgramDao()
        );
        $project_data                = ProjectAdapter::build($planning_administration_delegation->getProject());
        if ($component_involved_verifier->isInvolvedInAScaledAgileWorkspace($project_data)) {
            $planning_administration_delegation->enablePlanningAdministrationDelegation();
        }
    }

    public function trackerHierarchyDelegation(
        TrackerHierarchyDelegation $tracker_hierarchy_delegation
    ): void {
        if ((new ScaledAgileHierarchyDAO())->isPartOfAHierarchy(new ScaledAgileTracker($tracker_hierarchy_delegation->getTracker()))) {
            $tracker_hierarchy_delegation->enableTrackerHierarchyDelegation($this->getPluginInfo()->getPluginDescriptor()->getFullName());
        }
    }

    public function trackerUsage(array $params): void
    {
        if ((new PlanDao())->isPartOfAPlan(new ScaledAgileTracker($params['tracker']))) {
            $params['result'] = [
                'can_be_deleted' => false,
                'message'        => $this->getPluginInfo()->getPluginDescriptor()->getFullName()
            ];
        }
    }

    public function projectIsDeleted(): void
    {
        (new \Tuleap\ScaledAgile\Adapter\Workspace\WorkspaceDAO())->dropUnusedComponents();
    }

    public function externalParentCollector(OriginalProjectCollector $original_project_collector): void
    {
        $source_analyser = new SourceArtifactNatureAnalyzer(new TeamDao(), ProjectManager::instance(), Tracker_ArtifactFactory::instance());
        $artifact = $original_project_collector->getOriginalArtifact();
        $user = $original_project_collector->getUser();

        try {
            $project = $source_analyser->retrieveProjectOfMirroredArtifact($artifact, $user);
            if (! $project) {
                return;
            }

            $original_project_collector->setOriginalProject($project);
        } catch (NatureAnalyzerException $exception) {
            $logger = $this->getLogger();
            $logger->debug($exception->getMessage(), ['exception' => $exception]);
        }
    }

    public function configurationCheckDelegation(ConfigurationCheckDelegation $configuration_check_delegation): void
    {
        $planning_program_adapter = new PlanningProgramAdapter(
            TrackerFactory::instance(),
            ProjectManager::instance(),
            new URLVerification(),
            new PlanDao(),
            new ProgramDao(),
            new TeamDao()
        );
        $configuration_checker    = new ConfigurationChecker($planning_program_adapter);
        try {
            $configuration_checker->getProgramIncrementTracker(
                $configuration_check_delegation->getUser(),
                $configuration_check_delegation->getProject()
            );
        } catch (ConfigurationUserCanNotSeeProgramException | ProgramNotFoundException | UserCanNotAccessToProgramException | ProjectIsNotAProgramException $e) {
            $configuration_check_delegation->disablePlanning();
            $this->getLogger()->debug($e->getMessage());
        }
    }

    public function blockScrumAccess(BlockScrumAccess $block_scrum_access): void
    {
        $program_store = new ProgramDao();
        if ($program_store->isProjectAProgramProject((int) $block_scrum_access->getProject()->getID())) {
            $block_scrum_access->disableScrumAccess();
        }
    }

    private function getProjectIncrementCreatorChecker(): ProgramIncrementArtifactCreatorChecker
    {
        $form_element_factory    = \Tracker_FormElementFactory::instance();
        $timeframe_dao           = new \Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao();
        $semantic_status_factory = new Tracker_Semantic_StatusFactory();
        $logger                  = $this->getLogger();

        return new ProgramIncrementArtifactCreatorChecker(
            $this->getTeamProjectCollectionBuilder(),
            new TrackerCollectionFactory(
                $this->getPlanningAdapter()
            ),
            new SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder(
                new SynchronizedFieldsAdapter(
                    new ArtifactLinkFieldAdapter($form_element_factory),
                    new TitleFieldAdapter(new Tracker_Semantic_TitleFactory()),
                    new DescriptionFieldAdapter(new Tracker_Semantic_DescriptionFactory()),
                    new StatusFieldAdapter($semantic_status_factory),
                    new TimeFrameFieldsAdapter(new SemanticTimeframeBuilder($timeframe_dao, $form_element_factory))
                )
            ),
            new SemanticChecker(
                new \Tracker_Semantic_TitleDao(),
                new \Tracker_Semantic_DescriptionDao(),
                $timeframe_dao,
                new StatusSemanticChecker(new Tracker_Semantic_StatusDao(), $semantic_status_factory),
            ),
            new RequiredFieldChecker($logger),
            new WorkflowChecker(
                new Workflow_Dao(),
                new Tracker_Rule_Date_Dao(),
                new Tracker_Rule_List_Dao(),
                $logger
            ),
            $logger
        );
    }

    private function getLogger(): \Psr\Log\LoggerInterface
    {
        return BackendLogger::getDefaultLogger("scaled_agile_syslog");
    }

    private function getPlanningAdapter(): PlanningAdapter
    {
        return new PlanningAdapter(\PlanningFactory::build());
    }

    private function getTeamProjectCollectionBuilder(): TeamProjectsCollectionBuilder
    {
        return new TeamProjectsCollectionBuilder(
            new ProgramDao(),
            $this->getProjectDataAdapter()
        );
    }

    private function getProjectDataAdapter(): ProjectAdapter
    {
        return new ProjectAdapter(ProjectManager::instance());
    }

    private function getProgramIncrementRunner(): CreateProgramIncrementsRunner
    {
        $logger = $this->getLogger();

        return new CreateProgramIncrementsRunner(
            $this->getLogger(),
            new QueueFactory($logger),
            new ReplicationDataAdapter(
                Tracker_ArtifactFactory::instance(),
                UserManager::instance(),
                new PendingArtifactCreationDao(),
                Tracker_Artifact_ChangesetFactoryBuilder::build()
            ),
            new TaskBuilder()
        );
    }
}
