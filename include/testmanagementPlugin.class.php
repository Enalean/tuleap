<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
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

use Tuleap\BurningParrotCompatiblePageEvent;
use Tuleap\Layout\IncludeAssets;
use Tuleap\project\Event\ProjectServiceBeforeActivation;
use Tuleap\TestManagement\Config;
use Tuleap\TestManagement\Dao;
use Tuleap\TestManagement\FirstConfigCreator;
use Tuleap\TestManagement\Nature\NatureCoveredByOverrider;
use Tuleap\TestManagement\Nature\NatureCoveredByPresenter;
use Tuleap\TestManagement\REST\ResourcesInjector;
use Tuleap\TestManagement\TestManagementPluginInfo;
use Tuleap\TestManagement\UserIsNotAdministratorException;
use Tuleap\TestManagement\XMLImport;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater;
use Tuleap\Tracker\Events\ArtifactLinkTypeCanBeUnused;
use Tuleap\Tracker\Events\GetEditableTypesInProject;
use Tuleap\Tracker\Events\XMLImportArtifactLinkTypeCanBeDisabled;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;

require_once 'constants.php';

class testmanagementPlugin extends Plugin
{
    /**
     * Plugin constructor
     */
    public function __construct($id)
    {
        parent::__construct($id);
        $this->filesystem_path = TESTMANAGEMENT_BASE_DIR;
        $this->setScope(self::SCOPE_PROJECT);

        bindtextdomain('tuleap-testmanagement', TESTMANAGEMENT_GETTEXT_DIR);
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(Event::REST_PROJECT_RESOURCES);
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(Event::SERVICE_ICON);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);
        $this->addHook(Event::REGISTER_PROJECT_CREATION);
        $this->addHook(NaturePresenterFactory::EVENT_GET_ARTIFACTLINK_NATURES);
        $this->addHook(NaturePresenterFactory::EVENT_GET_NATURE_PRESENTER);
        $this->addHook(BurningParrotCompatiblePageEvent::NAME);
        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);
        $this->addHook(ProjectServiceBeforeActivation::NAME);

        if (defined('AGILEDASHBOARD_BASE_URL')) {
            $this->addHook(AGILEDASHBOARD_EVENT_ADDITIONAL_PANES_ON_MILESTONE);
        }

        if (defined('TRACKER_BASE_URL')) {
            $this->addHook(TRACKER_EVENT_COMPLEMENT_REFERENCE_INFORMATION);
            $this->addHook(TRACKER_EVENT_ARTIFACT_LINK_NATURE_REQUESTED);
            $this->addHook(TRACKER_EVENT_PROJECT_CREATION_TRACKERS_REQUIRED);
            $this->addHook(TRACKER_EVENT_TRACKERS_DUPLICATED);
            $this->addHook(Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink::TRACKER_ADD_SYSTEM_NATURES);

            $this->addHook(Event::IMPORT_XML_PROJECT_TRACKER_DONE);
            $this->addHook(GetEditableTypesInProject::NAME);
            $this->addHook(ArtifactLinkTypeCanBeUnused::NAME);
            $this->addHook(XMLImportArtifactLinkTypeCanBeDisabled::NAME);
            $this->addHook(Tracker_FormElementFactory::GET_CLASSNAMES);
        }

        return parent::getHooksAndCallbacks();
    }

    public function getDependencies()
    {
        return ['tracker'];
    }

    public function getServiceShortname() {
        return 'plugin_testmanagement';
    }

    /** @see Tracker_FormElementFactory::GET_CLASSNAMES */
    public function tracker_formelement_get_classnames($params)
    {
        $params['fields']['ttmstepdef'] = \Tuleap\TestManagement\Step\Definition\Field\StepDefinition::class;
    }

    public function isUsedByProject(Project $project)
    {
        return $project->usesService($this->getServiceShortname());
    }

    public function service_icon($params) {
        $params['list_of_icon_unicodes'][$this->getServiceShortname()] = '\e813';
    }

    public function service_classnames($params) {
        $params['classnames'][$this->getServiceShortname()] = 'Tuleap\\TestManagement\\Service';
    }

    public function register_project_creation($params)
    {
        $project_manager = ProjectManager::instance();
        $template        = $project_manager->getProject($params['template_id']);
        $project         = $project_manager->getProject($params['group_id']);

        if ($params['project_creation_data']->projectShouldInheritFromTemplate() && $this->isUsedByProject($template)) {
            $this->allowProjectToUseNature($template, $project);
        }
    }

    private function allowProjectToUseNature(Project $template, Project $project)
    {
        if (! $this->getArtifactLinksUsageUpdater()->isProjectAllowedToUseArtifactLinkTypes($template)) {
            $this->getArtifactLinksUsageUpdater()->forceUsageOfArtifactLinkTypes($project);
        }
    }

    /**
     * @return ArtifactLinksUsageUpdater
     */
    private function getArtifactLinksUsageUpdater()
    {
        return new ArtifactLinksUsageUpdater(new ArtifactLinksUsageDao());
    }

    public function event_get_artifactlink_natures($params)
    {
        $params['natures'][] = new NatureCoveredByPresenter();
    }

    public function event_get_nature_presenter($params)
    {
        if ($params['shortname'] === NatureCoveredByPresenter::NATURE_COVERED_BY) {
            $params['presenter'] = new NatureCoveredByPresenter();
        }
    }

    public function tracker_event_artifact_link_nature_requested(array $params)
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($params['project_id']);
        if ($this->isUsedByProject($project)) {
            $to_artifact             = $params['to_artifact'];
            $new_linked_artifact_ids = explode(',', $params['submitted_value']['new_values']);

            $overrider        = new NatureCoveredByOverrider(new Config(new Dao()), new ArtifactLinksUsageDao());
            $overridingNature = $overrider->getOverridingNature($project, $to_artifact, $new_linked_artifact_ids);

            if (! empty($overridingNature)) {
                $params['nature'] = $overridingNature;
            }
        }
    }

    public function tracker_event_complement_reference_information(array $params) {
        $tracker = $params['artifact']->getTracker();
        $project = $tracker->getProject();

        $plugin_testmanagement_is_used = $project->usesService($this->getServiceShortname());
        if ($plugin_testmanagement_is_used) {
            $reference_information = array(
                'title' => $GLOBALS['Language']->getText('plugin_testmanagement', 'references_graph_title'),
                'links' => array()
            );

            $link = array(
                'icon' => $this->getPluginPath() . '/themes/BurningParrot/images/artifact-link-graph.svg',
                'link' => $this->getPluginPath() . '/?group_id=' . $tracker->getGroupId() . '#/graph/' . $params['artifact']->getId(),
                'label'=> $GLOBALS['Language']->getText('plugin_testmanagement', 'references_graph_url')
            );

            $reference_information['links'][] = $link;
            $params['reference_information'][] = $reference_information;
        }
    }

    /**
     * List TestManagement trackers to duplicate
     *
     * @param array $params The project duplication parameters (source project id, tracker ids list)
     *
     */
    public function tracker_event_project_creation_trackers_required(array $params)
    {
        $config = new Config(new Dao());
        $project = ProjectManager::instance()->getProject($params['project_id']);

        $plugin_testmanagement_is_used = $project->usesService($this->getServiceShortname());
        if (! $plugin_testmanagement_is_used) {
            return;
        }

        $params['tracker_ids_list'] = array_merge(
            $params['tracker_ids_list'],
            array(
                $config->getCampaignTrackerId($project),
                $config->getTestDefinitionTrackerId($project),
                $config->getTestExecutionTrackerId($project)
            )
        );
    }

    /**
     * Configure new project's TestManagement trackers
     *
     * @param mixed array $params The duplication params (tracker_mapping array, field_mapping array)
     *
     */
    public function tracker_event_trackers_duplicated(array $params)
    {
        $config = new Config(new Dao());
        $from_project = ProjectManager::instance()->getProject($params['source_project_id']);
        $to_project = ProjectManager::instance()->getProject($params['group_id']);

        $plugin_testmanagement_is_used = $to_project->usesService($this->getServiceShortname());
        if (! $plugin_testmanagement_is_used) {
            return;
        }

        $config_creator = new FirstConfigCreator(
            $config,
            TrackerFactory::instance(),
            TrackerXmlImport::build(new XMLImportHelper(UserManager::instance())),
            new BackendLogger()
        );
        $config_creator->createConfigForProjectFromTemplate($to_project, $from_project, $params['tracker_mapping']);
    }

    /**
     * Add tab in Agile Dashboard Planning view to redirect to TestManagement
     * @param mixed array $params
     */
    public function agiledashboard_event_additional_panes_on_milestone($params)
    {
        $milestone = $params['milestone'];
        $project   = $milestone->getProject();
        if ($project->usesService($this->getServiceShortname())) {
            $params['panes'][] = new Tuleap\TestManagement\AgileDashboardPaneInfo($milestone);
        }
    }

    /**
     * @return TestManagementPluginInfo
     */
    public function getPluginInfo() {
        if (!$this->pluginInfo) {
            $this->pluginInfo = new TestManagementPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function burning_parrot_compatible_page(BurningParrotCompatiblePageEvent $event)
    {
        if ($this->currentRequestIsForPlugin()) {
            $event->setIsInBurningParrotCompatiblePage();
        }
    }

    public function burning_parrot_get_stylesheets(array $params)
    {
        if ($this->currentRequestIsForPlugin()) {
            $variant = $params['variant'];
            $params['stylesheets'][] = $this->getThemePath() .'/css/style-'. $variant->getName() .'.css';
        }
    }

    public function burning_parrot_get_javascript_files(array $params)
    {
        if ($this->currentRequestIsForPlugin()) {
            $ckeditor_path = '/scripts/ckeditor-4.3.2/';
            $GLOBALS['HTML']->includeFooterJavascriptSnippet('window.CKEDITOR_BASEPATH = "'. $ckeditor_path .'";');
            $params['javascript_files'][] = $ckeditor_path .'ckeditor.js';

            $params['javascript_files'][] = '/scripts/codendi/Tooltip.js';
            $params['javascript_files'][] = '/scripts/codendi/Tooltip-loader.js';

            $test_management_include_assets = new IncludeAssets(
                TESTMANAGEMENT_BASE_DIR . '/www/scripts/angular/bin/assets',
                TESTMANAGEMENT_BASE_URL . '/scripts/angular/bin/assets'
            );

            $params['javascript_files'][] = $test_management_include_assets->getFileURL('testmanagement.js');
        }
    }

    public function process(Codendi_Request $request) {
        $config          = new Config(new Dao());
        $tracker_factory = TrackerFactory::instance();
        $project_manager = ProjectManager::instance();
        $user_manager    = UserManager::instance();
        $event_manager   = EventManager::instance();

        $router = new Tuleap\TestManagement\Router(
            $this,
            $config,
            $tracker_factory,
            $project_manager,
            $user_manager,
            $event_manager,
            $this->getArtifactLinksUsageUpdater()
        );

        try {
            $router->route($request);
        } catch (UserIsNotAdministratorException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-testmanagement', 'Permission denied')
            );
            $router->renderIndex($request);
        }
    }

    /**
     * @see REST_RESOURCES
     */
    public function rest_resources(array $params) {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    /**
     * @see REST_PROJECT_RESOURCES
     */
    public function rest_project_resources(array $params) {
        $injector = new ResourcesInjector();
        $injector->declareProjectResource($params['resources'], $params['project']);
    }

    public function tracker_add_system_natures($params)
    {
        $params['natures'][] = NatureCoveredByPresenter::NATURE_COVERED_BY;
    }

    public function import_xml_project_tracker_done(array $params)
    {
        $importer = new XMLImport(new Config(new Dao()));
        $importer->import($params['project'], $params['extraction_path'], $params['mapping']);
    }

    public function tracker_get_editable_type_in_project(GetEditableTypesInProject $event)
    {
        $project = $event->getProject();

        if ($this->isAllowed($project->getId())) {
            $event->addType(new NatureCoveredByPresenter());
        }
    }

    public function tracker_artifact_link_can_be_unused(ArtifactLinkTypeCanBeUnused $event)
    {
        $type    = $event->getType();
        $project = $event->getProject();

        if ($type->shortname === NatureCoveredByPresenter::NATURE_COVERED_BY) {
            $event->setTypeIsCheckedByPlugin();

            if (! $project->usesService($this->getServiceShortname())) {
                $event->setTypeIsUnusable();
            }
        }
    }

    public function project_service_before_activation(ProjectServiceBeforeActivation $event)
    {
        $service_short_name = $event->getServiceShortname();
        $project            = $event->getProject();

        if ($service_short_name !== $this->getServiceShortname()) {
            return;
        }

        $event->pluginSetAValue();

        $dao                          = new ArtifactLinksUsageDao();
        $covered_by_type_is_activated = ! $dao->isTypeDisabledInProject(
            $project->getID(),
            NatureCoveredByPresenter::NATURE_COVERED_BY
        );

        if (! $project->usesService($this->getServiceShortname()) && $covered_by_type_is_activated) {
            $event->serviceCanBeActivated();
        } else {
            $message = sprintf(
                dgettext(
                    'tuleap-testmanagement',
                    'Service %s cannot be activated because the artifact link type "%s" is not activated'
                ),
                $service_short_name,
                NatureCoveredByPresenter::NATURE_COVERED_BY
            );

            $event->setWarningMessage($message);
        }
    }

    public function tracker_xml_import_artifact_link_can_be_disabled(XMLImportArtifactLinkTypeCanBeDisabled $event)
    {
        if ($event->getTypeName() !== NatureCoveredByPresenter::NATURE_COVERED_BY) {
            return;
        }

        $event->setTypeIsCheckedByPlugin();
        $project = $event->getProject();

        if (! $project->usesService($this->getServiceShortname())) {
            $event->setTypeIsUnusable();
        } else {
            $event->setMessage(NatureCoveredByPresenter::NATURE_COVERED_BY . " type is forced because the service testmanagement is used.");
        }
    }
}
