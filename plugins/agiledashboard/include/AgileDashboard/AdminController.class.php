<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard;

use AdminKanbanPresenter;
use AgileDashboard_ConfigurationManager;
use AgileDashboard_FirstKanbanCreator;
use AgileDashboard_FirstScrumCreator;
use AgileDashboard_KanbanFactory;
use AgileDashboard_KanbanManager;
use AgileDashboardConfigurationResponse;
use AgileDashboardKanbanConfigurationUpdater;
use AgileDashboardScrumConfigurationUpdater;
use ArtifactTypeFactory;
use Codendi_Request;
use CSRFSynchronizerToken;
use EventManager;
use Feedback;
use FRSLog;
use PFUser;
use PlanningFactory;
use Project;
use ProjectCreator;
use ProjectHistoryDao;
use ProjectManager;
use ProjectXMLImporter;
use ProjectXMLImporterLogger;
use ReferenceManager;
use ServiceManager;
use Tracker_ReportFactory;
use TrackerFactory;
use TrackerXmlImport;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeUpdater;
use Tuleap\AgileDashboard\FormElement\Burnup\ProjectsCountModeDao;
use Tuleap\AgileDashboard\Kanban\TrackerReport\TrackerReportDao;
use Tuleap\AgileDashboard\Kanban\TrackerReport\TrackerReportUpdater;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDisabler;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneEnabler;
use Tuleap\AgileDashboard\Scrum\ScrumPresenterBuilder;
use Tuleap\Dashboard\Project\ProjectDashboardDao;
use Tuleap\Dashboard\Project\ProjectDashboardDuplicator;
use Tuleap\Dashboard\Project\ProjectDashboardRetriever;
use Tuleap\Dashboard\Project\ProjectDashboardSaver;
use Tuleap\Dashboard\Project\ProjectDashboardXMLImporter;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Dashboard\Widget\DashboardWidgetRetriever;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\FRSPermissionDao;
use Tuleap\FRS\UploadedLinksDao;
use Tuleap\FRS\UploadedLinksUpdater;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\Label\LabelDao;
use Tuleap\Project\UgroupDuplicator;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdderWithoutStatusCheckAndNotifications;
use Tuleap\Project\UGroups\Membership\MemberAdder;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDao;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDuplicator;
use Tuleap\Project\UserRemover;
use Tuleap\Project\UserRemoverDao;
use Tuleap\Service\ServiceCreator;
use Tuleap\Widget\WidgetFactory;
use UGroupBinding;
use UGroupDao;
use UGroupManager;
use UGroupUserDao;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;
use UserManager;
use XML_RNGValidator;
use XMLImportHelper;

class AdminController extends BaseController
{

    /** @var AgileDashboard_KanbanFactory */
    private $kanban_factory;

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var AgileDashboard_KanbanManager */
    private $kanban_manager;

    /** @var AgileDashboard_ConfigurationManager */
    private $config_manager;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var EventManager */
    private $event_manager;

    /** @var Project */
    private $project;

    /** @var AgileDashboardCrumbBuilder */
    private $service_crumb_builder;

    /** @var AdministrationCrumbBuilder */
    private $admin_crumb_builder;

    /**
     * @var CountElementsModeChecker
     */
    private $count_elements_mode_checker;
    /**
     * @var ScrumPresenterBuilder
     */
    private $scrum_presenter_builder;

    public function __construct(
        Codendi_Request $request,
        PlanningFactory $planning_factory,
        AgileDashboard_KanbanManager $kanban_manager,
        AgileDashboard_KanbanFactory $kanban_factory,
        AgileDashboard_ConfigurationManager $config_manager,
        TrackerFactory $tracker_factory,
        EventManager $event_manager,
        AgileDashboardCrumbBuilder $service_crumb_builder,
        AdministrationCrumbBuilder $admin_crumb_builder,
        CountElementsModeChecker $count_elements_mode_checker,
        ScrumPresenterBuilder $scrum_presenter_builder
    ) {
        parent::__construct('agiledashboard', $request);

        $this->group_id                    = (int) $this->request->get('group_id');
        $this->project                     = $this->request->getProject();
        $this->planning_factory            = $planning_factory;
        $this->kanban_manager              = $kanban_manager;
        $this->kanban_factory              = $kanban_factory;
        $this->config_manager              = $config_manager;
        $this->tracker_factory             = $tracker_factory;
        $this->event_manager               = $event_manager;
        $this->service_crumb_builder       = $service_crumb_builder;
        $this->admin_crumb_builder         = $admin_crumb_builder;
        $this->count_elements_mode_checker = $count_elements_mode_checker;
        $this->scrum_presenter_builder     = $scrum_presenter_builder;
    }

    /**
     * @return BreadCrumbCollection
     */
    public function getBreadcrumbs()
    {
        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb(
            $this->service_crumb_builder->build(
                $this->getCurrentUser(),
                $this->project
            )
        );
        $breadcrumbs->addBreadCrumb(
            $this->admin_crumb_builder->build($this->project)
        );

        return $breadcrumbs;
    }

    public function adminScrum(): string
    {
        $include_assets = new IncludeAssets(
            AGILEDASHBOARD_BASE_DIR . '/../www/assets',
            AGILEDASHBOARD_BASE_URL . '/assets'
        );
        $GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('administration.js'));

        return $this->renderToString(
            'admin-scrum',
            $this->scrum_presenter_builder->getAdminScrumPresenter(
                $this->getCurrentUser(),
                $this->project
            )
        );
    }

    public function adminKanban()
    {
        return $this->renderToString(
            'admin-kanban',
            $this->getAdminKanbanPresenter(
                $this->getCurrentUser(),
                $this->group_id
            )
        );
    }

    public function adminCharts()
    {
        return $this->renderToString(
            "admin-charts",
            $this->getAdminChartsPresenter(
                $this->project
            )
        );
    }

    private function getAdminKanbanPresenter(PFUser $user, $project_id)
    {
        $has_kanban = count($this->kanban_factory->getListOfKanbansForProject($user, $project_id)) > 0;

        return new AdminKanbanPresenter(
            $project_id,
            $this->config_manager->kanbanIsActivatedForProject($project_id),
            $this->config_manager->getKanbanTitle($project_id),
            $has_kanban,
            \ForgeConfig::get('use_burnup_count_elements')
        );
    }

    public function updateConfiguration()
    {
        $token = new CSRFSynchronizerToken('/plugins/agiledashboard/?action=admin');
        $token->check();

        if (! $this->request->getCurrentUser()->isAdmin($this->group_id)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('global', 'perm_denied')
            );

            return;
        }

        $response = new AgileDashboardConfigurationResponse(
            $this->request->getProject(),
            $this->request->exist('home-ease-onboarding')
        );

        $user_manager = UserManager::instance();
        if ($this->request->exist('activate-kanban')) {
            $updater = new AgileDashboardKanbanConfigurationUpdater(
                $this->request,
                $this->config_manager,
                $response,
                new AgileDashboard_FirstKanbanCreator(
                    $this->request->getProject(),
                    $this->kanban_manager,
                    $this->tracker_factory,
                    TrackerXmlImport::build(new XMLImportHelper(UserManager::instance())),
                    $this->kanban_factory,
                    new TrackerReportUpdater(new TrackerReportDao()),
                    Tracker_ReportFactory::instance()
                )
            );
        } elseif ($this->request->exist("burnup-count-mode")) {
            $updater = new AgileDashboardChartsConfigurationUpdater(
                $this->request,
                new CountElementsModeUpdater(
                    new ProjectsCountModeDao()
                )
            );
        } else {
            $ugroup_user_dao   = new UGroupUserDao();
            $ugroup_manager    = new UGroupManager();
            $ugroup_binding    = new UGroupBinding($ugroup_user_dao, $ugroup_manager);
            $ugroup_duplicator = new UgroupDuplicator(
                new UGroupDao(),
                $ugroup_manager,
                $ugroup_binding,
                MemberAdder::build(ProjectMemberAdderWithoutStatusCheckAndNotifications::build()),
                $this->event_manager
            );

            $send_notifications = false;
            $force_activation   = true;

            $frs_permissions_creator = new FRSPermissionCreator(
                new FRSPermissionDao(),
                new UGroupDao(),
                new ProjectHistoryDao()
            );

            $widget_factory = new WidgetFactory(
                $user_manager,
                new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
                $this->event_manager
            );

            $widget_dao        = new DashboardWidgetDao($widget_factory);
            $project_dao       = new ProjectDashboardDao($widget_dao);
            $project_retriever = new ProjectDashboardRetriever($project_dao);
            $widget_retriever  = new DashboardWidgetRetriever($widget_dao);
            $duplicator        = new ProjectDashboardDuplicator(
                $project_dao,
                $project_retriever,
                $widget_dao,
                $widget_retriever,
                $widget_factory
            );

            $synchronized_project_membership_dao = new SynchronizedProjectMembershipDao();

            $project_creator = new ProjectCreator(
                ProjectManager::instance(),
                ReferenceManager::instance(),
                $user_manager,
                $ugroup_duplicator,
                $send_notifications,
                $frs_permissions_creator,
                $duplicator,
                new ServiceCreator(new \ServiceDao()),
                new LabelDao(),
                new DefaultProjectVisibilityRetriever(),
                new SynchronizedProjectMembershipDuplicator($synchronized_project_membership_dao),
                $force_activation
            );

            $scrum_mono_milestone_dao = new ScrumForMonoMilestoneDao();
            $logger                   = new ProjectXMLImporterLogger();
            $updater                  = new AgileDashboardScrumConfigurationUpdater(
                $this->request,
                $this->config_manager,
                $response,
                new AgileDashboard_FirstScrumCreator(
                    $this->request->getProject(),
                    $this->planning_factory,
                    $this->tracker_factory,
                    new ProjectXMLImporter(
                        $this->event_manager,
                        ProjectManager::instance(),
                        $user_manager,
                        new XML_RNGValidator(),
                        $ugroup_manager,
                        new XMLImportHelper($user_manager),
                        ServiceManager::instance(),
                        $logger,
                        $frs_permissions_creator,
                        new UserRemover(
                            ProjectManager::instance(),
                            $this->event_manager,
                            new ArtifactTypeFactory(false),
                            new UserRemoverDao(),
                            $user_manager,
                            new ProjectHistoryDao(),
                            new UGroupManager()
                        ),
                        ProjectMemberAdderWithoutStatusCheckAndNotifications::build(),
                        $project_creator,
                        new UploadedLinksUpdater(new UploadedLinksDao(), FRSLog::instance()),
                        new ProjectDashboardXMLImporter(
                            new ProjectDashboardSaver(
                                $project_dao
                            ),
                            $widget_factory,
                            $widget_dao,
                            $logger,
                            $this->event_manager
                        ),
                        $synchronized_project_membership_dao
                    )
                ),
                new ScrumForMonoMilestoneEnabler($scrum_mono_milestone_dao),
                new ScrumForMonoMilestoneDisabler($scrum_mono_milestone_dao),
                new ScrumForMonoMilestoneChecker($scrum_mono_milestone_dao, $this->planning_factory)
            );
        }

        return $updater->updateConfiguration();
    }

    public function createKanban()
    {
        $kanban_name = $this->request->get('kanban-name');
        $tracker_id  = $this->request->get('tracker-kanban');
        $tracker     = $this->tracker_factory->getTrackerById($tracker_id);
        $user        = $this->request->getCurrentUser();

        if (! $user->isAdmin($this->group_id)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('global', 'perm_denied')
            );

            return;
        }

        if (! $tracker_id) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugin_agiledashboard', 'no_tracker_selected')
            );

            $this->redirectToHome();

            return;
        }

        if ($this->kanban_manager->doesKanbanExistForTracker($tracker)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugin_agiledashboard', 'kanban_tracker_used')
            );

            $this->redirectToHome();

            return;
        }

        if ($this->kanban_manager->createKanban($kanban_name, $tracker_id)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('plugin_agiledashboard', 'kanban_created', [$kanban_name])
            );
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugin_agiledashboard', 'kanban_creation_error', [$kanban_name])
            );
        }

        $this->redirectToHome();
    }

    private function redirectToHome()
    {
        $this->redirect(
            [
                'group_id' => $this->group_id
            ]
        );
    }

    private function getAdminChartsPresenter(Project $project) : AdminChartsPresenter
    {
        $token = new CSRFSynchronizerToken('/plugins/agiledashboard/?action=admin');

        return new AdminChartsPresenter(
            $project,
            $token,
            $this->count_elements_mode_checker->burnupMustUseCountElementsMode($project)
        );
    }
}
