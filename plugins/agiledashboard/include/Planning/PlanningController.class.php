<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\BaseController;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\FormElement\Burnup;
use Tuleap\AgileDashboard\Planning\Admin\AdditionalPlanningConfigurationWarningsRetriever;
use Tuleap\AgileDashboard\Planning\Admin\PlanningEditionPresenterBuilder;
use Tuleap\AgileDashboard\Planning\Admin\PlanningWarningPossibleMisconfigurationPresenter;
use Tuleap\AgileDashboard\Planning\Admin\UpdateRequestValidator;
use Tuleap\AgileDashboard\Planning\BacklogTrackersUpdateChecker;
use Tuleap\AgileDashboard\Planning\ImportTemplateFormPresenter;
use Tuleap\AgileDashboard\Planning\PlanningAdministrationDelegation;
use Tuleap\AgileDashboard\Planning\PlanningUpdater;
use Tuleap\AgileDashboard\Planning\RootPlanning\RootPlanningEditionEvent;
use Tuleap\AgileDashboard\Planning\RootPlanning\UpdateIsAllowedChecker;
use Tuleap\AgileDashboard\Planning\ScrumPlanningFilter;
use Tuleap\AgileDashboard\Planning\TrackerHaveAtLeastOneAddToTopBacklogPostActionException;
use Tuleap\AgileDashboard\Planning\TrackersHaveAtLeastOneHierarchicalLinkException;
use Tuleap\AgileDashboard\Planning\TrackersWithHierarchicalLinkDefinedNotFoundException;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Tracker\Report\TrackerNotFoundException;

/**
 * Handles the HTTP actions related to a planning.
 */
class Planning_Controller extends BaseController //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * Fetch the cardwall configuration html
     *
     * Parameters:
     * 'tracker' => The Planning Tracker of the planning that is being configured
     * 'view'    => The HTML to be fetched
     */
    public const AGILEDASHBOARD_EVENT_PLANNING_CONFIG = 'agiledashboard_event_planning_config';

    /**
     * Update a planning
     *
     * Parameters:
     * 'tracker' => The Planning Tracker of the planning that is being configured
     * 'request' => The standard request object
     */
    public const AGILEDASHBOARD_EVENT_PLANNING_CONFIG_UPDATE = 'agiledashboard_event_planning_config_update';

    public const AGILE_DASHBOARD_TEMPLATE_NAME = 'agile_dashboard_template.xml';

    private PlanningFactory $planning_factory;
    private ProjectManager $project_manager;
    private AgileDashboard_XMLFullStructureExporter $xml_exporter;
    private PlanningPermissionsManager $planning_permissions_manager;
    private ScrumPlanningFilter $scrum_planning_filter;
    private Tracker_FormElementFactory $tracker_form_element_factory;
    private Project $project;
    private AgileDashboardCrumbBuilder $service_crumb_builder;
    private AdministrationCrumbBuilder $admin_crumb_builder;
    private DBTransactionExecutor $transaction_executor;
    private ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao;
    private PlanningUpdater $planning_updater;
    private EventManager $event_manager;
    private Planning_RequestValidator $planning_request_validator;
    private UpdateIsAllowedChecker $root_planning_update_checker;
    private PlanningEditionPresenterBuilder $planning_edition_presenter_builder;
    private UpdateRequestValidator $update_request_validator;

    public function __construct(
        Codendi_Request $request,
        PlanningFactory $planning_factory,
        ProjectManager $project_manager,
        AgileDashboard_XMLFullStructureExporter $xml_exporter,
        PlanningPermissionsManager $planning_permissions_manager,
        ScrumPlanningFilter $scrum_planning_filter,
        Tracker_FormElementFactory $tracker_form_element_factory,
        AgileDashboardCrumbBuilder $service_crumb_builder,
        AdministrationCrumbBuilder $admin_crumb_builder,
        DBTransactionExecutor $transaction_executor,
        ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao,
        PlanningUpdater $planning_updater,
        EventManager $event_manager,
        Planning_RequestValidator $planning_request_validator,
        UpdateIsAllowedChecker $root_planning_update_checker,
        PlanningEditionPresenterBuilder $planning_edition_presenter_builder,
        UpdateRequestValidator $update_request_validator,
        private BacklogTrackersUpdateChecker $backlog_trackers_update_checker,
    ) {
        parent::__construct('agiledashboard', $request);

        $this->project                            = $this->request->getProject();
        $this->group_id                           = $this->project->getID();
        $this->planning_factory                   = $planning_factory;
        $this->project_manager                    = $project_manager;
        $this->xml_exporter                       = $xml_exporter;
        $this->planning_permissions_manager       = $planning_permissions_manager;
        $this->scrum_planning_filter              = $scrum_planning_filter;
        $this->tracker_form_element_factory       = $tracker_form_element_factory;
        $this->service_crumb_builder              = $service_crumb_builder;
        $this->admin_crumb_builder                = $admin_crumb_builder;
        $this->transaction_executor               = $transaction_executor;
        $this->artifacts_in_explicit_backlog_dao  = $artifacts_in_explicit_backlog_dao;
        $this->planning_updater                   = $planning_updater;
        $this->event_manager                      = $event_manager;
        $this->planning_request_validator         = $planning_request_validator;
        $this->root_planning_update_checker       = $root_planning_update_checker;
        $this->planning_edition_presenter_builder = $planning_edition_presenter_builder;
        $this->update_request_validator           = $update_request_validator;
    }

    /**
     * @return bool
     */
    private function isUserAdmin()
    {
        return $this->request->getProject()->userIsAdmin($this->request->getCurrentUser());
    }

    /**
     * Redirects a non-admin user to the agile dashboard home page
     */
    private function redirectNonAdmin()
    {
        if (! $this->isUserAdmin()) {
            $this->redirect(['group_id' => $this->group_id]);
        }
    }

    /**
     * @param \Closure(string $title, BreadCrumbCollection $breadcrumbs, \Tuleap\Layout\HeaderConfiguration $header_configuration): void $displayHeader
     * @param \Closure(): void $displayFooter
     */
    public function new_(\Closure $displayHeader, \Closure $displayFooter): void
    {
        $planning  = $this->planning_factory->buildNewPlanning($this->group_id);
        $presenter = $this->getFormPresenter($this->request->getCurrentUser(), $planning);

        $title = dgettext('tuleap-agiledashboard', 'New Planning');

        $displayHeader(
            $title,
            $this->getBreadcrumbs(),
            HeaderConfigurationBuilder::get($title)
                ->inProject($this->project, \AgileDashboardPlugin::PLUGIN_SHORTNAME)
                ->build()
        );
        echo $this->renderToString('new', $presenter);
        $displayFooter();
    }

    /**
     * @param \Closure(string $title, BreadCrumbCollection $breadcrumbs, \Tuleap\Layout\HeaderConfiguration $header_configuration): void $displayHeader
     * @param \Closure(): void $displayFooter
     */
    public function importForm(\Closure $displayHeader, \Closure $displayFooter): void
    {
        $this->redirectNonAdmin();
        $project = $this->getProjectFromRequest();
        $this->redirectToMainAdministrationPageWhenPlanningManagementIsDelegatedToAnotherPlugin($project);

        $template_file = new Valid_File('template_file');
        $template_file->required();

        if ($this->request->validFile($template_file)) {
            $this->importConfiguration();
        }

        $presenter = new ImportTemplateFormPresenter($project);

        $GLOBALS['HTML']->addJavascriptAsset(
            new JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../scripts/administration/frontend-assets',
                    '/assets/agiledashboard/administration'
                ),
                'src/main.ts'
            )
        );

        $title = dgettext('tuleap-agiledashboard', 'New Planning');

        $displayHeader(
            $title,
            $this->getBreadcrumbs(),
            HeaderConfigurationBuilder::get($title)
                ->inProject($this->project, \AgileDashboardPlugin::PLUGIN_SHORTNAME)
                ->build()
        );
        echo $this->renderToString('import', $presenter);
        $displayFooter();
    }

    private function importConfiguration()
    {
        $xml_importer = ProjectXMLImporter::build(new XMLImportHelper(UserManager::instance()), \ProjectCreator::buildSelfByPassValidation());

        try {
            $xml_importer->collectBlockingErrorsWithoutImporting($this->group_id, $_FILES["template_file"]["tmp_name"])
                ->andThen(function () use ($xml_importer): Ok|Err {
                    $config = new ImportConfig();
                    return $xml_importer->import($config, $this->group_id, $_FILES["template_file"]["tmp_name"]);
                })
                ->match(
                    function (): void {
                        $GLOBALS['Response']->addFeedback(
                            Feedback::INFO,
                            dgettext('tuleap-agiledashboard', 'The configuration has been successfully imported!')
                        );
                    },
                    function (\Tuleap\NeverThrow\Fault $fault): void {
                        $GLOBALS['Response']->addFeedback(Feedback::ERROR, (string) $fault);
                    }
                );
        } catch (Exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-agiledashboard', 'Unable to import the configuration!'));
        }
    }

    /**
     * Exports the agile dashboard configuration as an XML file
     */
    public function exportToFile(): void
    {
        $this->checkUserIsAdmin();
        try {
            $project = $this->getProjectFromRequest();
            $this->redirectToMainAdministrationPageWhenPlanningManagementIsDelegatedToAnotherPlugin($project);
            $xml = $this->getFullConfigurationAsXML($project);
        } catch (Exception $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-agiledashboard', 'Unable to export the configuration'));
            $this->redirect(['group_id' => $this->group_id, 'action' => 'admin']);
        }

        $GLOBALS['Response']->sendXMLAttachementFile($xml, self::AGILE_DASHBOARD_TEMPLATE_NAME);
    }

    /**
     * @return Project
     * @throws Project_NotFoundException
     */
    private function getProjectFromRequest()
    {
        return $this->project_manager->getValidProject($this->group_id);
    }

    private function getFullConfigurationAsXML(Project $project)
    {
        return $this->xml_exporter->export($project);
    }

    public function create(): void
    {
        $this->checkUserIsAdmin();

        if ($this->planning_request_validator->isValid($this->request)) {
            $this->planning_factory->createPlanning(
                $this->group_id,
                PlanningParameters::fromArray(
                    $this->request->get('planning')
                )
            );

            $this->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-agiledashboard', 'Planning succesfully created.')
            );

            $this->redirect(['group_id' => $this->group_id, 'action' => 'admin']);
        } else {
            // TODO: Error message should reflect validation detail
            $this->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-agiledashboard', 'Planning name, backlog tracker and planning tracker are mandatory.')
            );
            $this->redirect(['group_id' => $this->group_id, 'action' => 'new']);
        }
    }

    /**
     * @param \Closure(string $title, BreadCrumbCollection $breadcrumbs, \Tuleap\Layout\HeaderConfiguration $header_configuration): void $displayHeader
     * @param \Closure(): void $displayFooter
     */
    public function edit(\Closure $displayHeader, \Closure $displayFooter): void
    {
        $this->checkUserIsAdmin();

        $planning_id = $this->request->get('planning_id');
        $planning    = $this->planning_factory->getPlanning($planning_id);
        if ($planning === null) {
            throw new \Tuleap\AgileDashboard\Planning\NotFoundException($planning_id);
        }
        $presenter = $this->planning_edition_presenter_builder->build($planning, $this->request->getCurrentUser(), $this->project);

        $include_assets = new IncludeAssets(
            __DIR__ . '/../../frontend-assets',
            '/assets/agiledashboard'
        );
        $GLOBALS['HTML']->addStylesheet($include_assets->getFileURL('planning-admin-colorpicker.css'));
        $GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('planning-admin.js'));

        $title = dgettext('tuleap-agiledashboard', 'Edit');

        $displayHeader(
            $title,
            $this->getBreadcrumbs(),
            HeaderConfigurationBuilder::get($title)
                ->inProject($this->project, \AgileDashboardPlugin::PLUGIN_SHORTNAME)
                ->withBodyClass(['agiledashboard-body'])
                ->build()
        );
        echo $this->renderToString('admin-scrum/edit-planning', $presenter);
        $displayFooter();
    }

    private function getFormPresenter(PFUser $user, Planning $planning)
    {
        $group_id = $planning->getGroupId();

        $available_trackers = $this->planning_factory->getAvailableBacklogTrackers($user, $group_id);
        $cardwall_admin     = $this->getCardwallConfiguration($planning);

        $planning_trackers_filtered = $this->scrum_planning_filter->getPlanningTrackersFiltered(
            $planning,
            $user,
            $this->group_id
        );

        $backlog_trackers_filtered = $this->scrum_planning_filter->getBacklogTrackersFiltered(
            $available_trackers,
            $planning
        );

        $include_assets = new IncludeAssets(
            __DIR__ . '/../../frontend-assets',
            '/assets/agiledashboard'
        );
        $GLOBALS['HTML']->addStylesheet($include_assets->getFileURL('planning-admin-colorpicker.css'));
        $GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('planning-admin.js'));

        return new Planning_FormPresenter(
            $this->planning_permissions_manager,
            $planning,
            $backlog_trackers_filtered,
            $planning_trackers_filtered,
            $cardwall_admin,
            $this->getWarnings($planning),
        );
    }

    private function getCardwallConfiguration(Planning $planning)
    {
        $tracker = $planning->getPlanningTracker();
        $view    = null;

        $this->event_manager->processEvent(
            self::AGILEDASHBOARD_EVENT_PLANNING_CONFIG,
            [
                'tracker' => $tracker,
                'view'    => &$view,
            ]
        );

        return $view;
    }

    public function update(): void
    {
        $this->checkUserIsAdmin();

        $updated_planning_id = (int) $this->request->get('planning_id');
        $original_planning   = $this->planning_factory->getPlanning($updated_planning_id);
        if ($original_planning === null) {
            $this->addFeedback(
                Feedback::ERROR,
                sprintf(
                    dgettext('tuleap-agiledashboard', 'Could not find planning with id %s.'),
                    $updated_planning_id
                )
            );
            $this->redirect(
                ['group_id' => $this->group_id, 'planning_id' => $updated_planning_id, 'action' => 'edit']
            );
        }
        $event = new RootPlanningEditionEvent($this->project, $original_planning);
        $this->event_manager->dispatch($event);
        $already_used_milestone_tracker_ids = $this->planning_factory->getPlanningTrackerIdsByGroupId($this->group_id);
        $validated_parameters               = $this->update_request_validator->getValidatedPlanning(
            $original_planning,
            $this->request,
            $already_used_milestone_tracker_ids,
            $event->getMilestoneTrackerModificationBan()
        );
        if (! $validated_parameters) {
            $this->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-agiledashboard', 'Planning name, backlog tracker and planning tracker are mandatory.')
            );
        } else {
            $user = $this->request->getCurrentUser();
            try {
                $this->backlog_trackers_update_checker->checkProvidedBacklogTrackersAreValid($validated_parameters);
                $this->root_planning_update_checker->checkUpdateIsAllowed($original_planning, $validated_parameters, $user);
                $this->planning_updater->update($user, $this->project, $updated_planning_id, $validated_parameters);

                $this->addFeedback(
                    Feedback::INFO,
                    dgettext('tuleap-agiledashboard', 'Planning succesfully updated.')
                );
            } catch (
                TrackerHaveAtLeastOneAddToTopBacklogPostActionException |
                TrackersHaveAtLeastOneHierarchicalLinkException |
                TrackersWithHierarchicalLinkDefinedNotFoundException $exception
            ) {
                $this->addFeedback(Feedback::ERROR, $exception->getMessage());
            } catch (TrackerNotFoundException $exception) {
                $this->addFeedback(
                    Feedback::ERROR,
                    sprintf(
                        dgettext('tuleap-agiledashboard', 'The tracker %s is not found'),
                        (string) $validated_parameters->planning_tracker_id
                    )
                );
            }
        }

        $this->updateCardwallConfig();

        $this->redirect(
            [
                'group_id'    => $this->group_id,
                'planning_id' => $this->request->get('planning_id'),
                'action'      => 'edit',
            ]
        );
    }

    private function updateCardwallConfig()
    {
        $tracker = $this->getPlanning()->getPlanningTracker();

        $this->event_manager->processEvent(
            self::AGILEDASHBOARD_EVENT_PLANNING_CONFIG_UPDATE,
            [
                'request' => $this->request,
                'tracker' => $tracker,
            ]
        );
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function delete(): void
    {
        $this->checkUserIsAdmin();
        $this->redirectToMainAdministrationPageWhenPlanningManagementIsDelegatedToAnotherPlugin($this->project);

        $planning_id = $this->request->get('planning_id');
        $user        = $this->request->getCurrentUser();
        $project     = $this->request->getProject();

        $this->transaction_executor->execute(
            function () use ($user, $planning_id, $project) {
                $root_planning = $this->planning_factory->getRootPlanning($user, $project->getID());
                if ($root_planning && (int) $root_planning->getId() === (int) $planning_id) {
                    $this->artifacts_in_explicit_backlog_dao->removeExplicitBacklogOfPlanning((int) $planning_id);
                }
                $this->planning_factory->deletePlanning($planning_id);
            }
        );

        $this->redirect(['group_id' => $this->group_id, 'action' => 'admin']);
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
        if ($this->request->existAndNonEmpty('action')) {
            $breadcrumbs->addBreadCrumb(
                $this->admin_crumb_builder->build($this->project)
            );
        }

        return $breadcrumbs;
    }

    private function getPlanning()
    {
        $planning_id = $this->request->get('planning_id');
        return $this->planning_factory->getPlanning($planning_id);
    }

    private function addBurnupWarning(array &$warning_list, Tracker $planning_tracker)
    {
        $burnup_fields = $this->tracker_form_element_factory->getFormElementsByType($planning_tracker, Burnup::TYPE);

        if ($burnup_fields && $burnup_fields[0]->isUsed()) {
            $semantic_url = TRACKER_BASE_URL . "?" . http_build_query(
                [
                    "tracker" => $planning_tracker->getId(),
                    "func"    => "admin-formElements",
                ]
            );

            $semantic_name = dgettext('tuleap-agiledashboard', 'Burnup field');

            $warning_list[] = new PlanningWarningPossibleMisconfigurationPresenter($semantic_url, $semantic_name);
        }
    }

    private function addOtherWarnings(array &$warning_list, Tracker $planning_tracker)
    {
        $event = new AdditionalPlanningConfigurationWarningsRetriever($planning_tracker);
        $this->event_manager->processEvent($event);

        foreach ($event->getAllWarnings() as $warning) {
            $warning_list[] = $warning;
        }
    }

    /**
     *
     * @return array
     */
    private function getWarnings(Planning $planning)
    {
        $warning_list = [];

        $this->addBurnupWarning($warning_list, $planning->getPlanningTracker());
        $this->addOtherWarnings($warning_list, $planning->getPlanningTracker());

        return $warning_list;
    }

    public function redirectToMainAdministrationPageWhenPlanningManagementIsDelegatedToAnotherPlugin(Project $project): void
    {
        $planning_administration_delegation = new PlanningAdministrationDelegation($project);
        $this->event_manager->dispatch($planning_administration_delegation);

        if ($planning_administration_delegation->isPlanningAdministrationDelegated()) {
            $this->redirect(['group_id' => $project->getID(), 'action' => 'admin']);
        }
    }
}
