<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/plugin/Plugin.class.php';
require_once 'autoload.php';
require_once 'constants.php';

/**
 * AgileDashboardPlugin
 */
class AgileDashboardPlugin extends Plugin {

    private $service;

    /**
     * Plugin constructor
     */
    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
    }

    public function getHooksAndCallbacks() {
        // Do not load the plugin if tracker is not installed & active
        if (defined('TRACKER_BASE_URL')) {
            require_once dirname(__FILE__) .'/../../tracker/include/autoload.php';
            $this->_addHook('cssfile', 'cssfile', false);
            $this->_addHook(Event::JAVASCRIPT, 'javascript', false);
            $this->_addHook(Event::COMBINED_SCRIPTS, 'combined_scripts', false);
            $this->_addHook(TRACKER_EVENT_INCLUDE_CSS_FILE, 'tracker_event_include_css_file', false);
            $this->_addHook(TRACKER_EVENT_TRACKERS_DUPLICATED, 'tracker_event_trackers_duplicated', false);
            $this->_addHook(TRACKER_EVENT_BUILD_ARTIFACT_FORM_ACTION, 'tracker_event_build_artifact_form_action', false);
            $this->_addHook(TRACKER_EVENT_ARTIFACT_ASSOCIATION_EDITED, 'tracker_event_artifact_association_edited', false);
            $this->_addHook(TRACKER_EVENT_REDIRECT_AFTER_ARTIFACT_CREATION_OR_UPDATE, 'tracker_event_redirect_after_artifact_creation_or_update', false);
            $this->_addHook(TRACKER_EVENT_ARTIFACT_PARENTS_SELECTOR, 'event_artifact_parents_selector', false);
            $this->_addHook(TRACKER_EVENT_MANAGE_SEMANTICS, 'tracker_event_manage_semantics', false);
            $this->_addHook(TRACKER_EVENT_SEMANTIC_FROM_XML, 'tracker_event_semantic_from_xml');
            $this->_addHook(TRACKER_EVENT_SOAP_SEMANTICS, 'tracker_event_soap_semantics');
            $this->addHook(TRACKER_EVENT_GET_SEMANTIC_FACTORIES);
            $this->addHook('plugin_statistics_service_usage');
            $this->addHook(TRACKER_EVENT_REPORT_DISPLAY_ADDITIONAL_CRITERIA);
            $this->addHook(TRACKER_EVENT_REPORT_PROCESS_ADDITIONAL_QUERY);
            $this->addHook(TRACKER_EVENT_REPORT_SAVE_ADDITIONAL_CRITERIA);
            $this->addHook(TRACKER_EVENT_REPORT_LOAD_ADDITIONAL_CRITERIA);

            $this->_addHook(Event::SYSTRAY);
            $this->_addHook(Event::IMPORT_XML_PROJECT_CARDWALL_DONE);
            $this->_addHook(Event::EXPORT_XML_PROJECT);
            $this->addHook(Event::REST_RESOURCES);
        }
        return parent::getHooksAndCallbacks();
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies() {
        return array('tracker');
    }

    public function cardwall_event_get_swimline_tracker($params) {
        $planning_factory = $this->getPlanningFactory();
        if ($planning = $planning_factory->getPlanningByPlanningTracker($params['tracker'])) {
            $params['backlog_trackers'] = $planning->getBacklogTrackers();
        }
    }

    /**
     * @see TRACKER_EVENT_REPORT_DISPLAY_ADDITIONAL_CRITERIA
     */
    public function tracker_event_report_display_additional_criteria($params) {
        $backlog_tracker = $params['tracker'];
        if (! $backlog_tracker) {
            return;
        }

        $planning_factory = $this->getPlanningFactory();
        $provider = new AgileDashboard_Milestone_MilestoneReportCriterionProvider(
            new AgileDashboard_Milestone_SelectedMilestoneIdProvider($params['additional_criteria']),
            new AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider(
                new AgileDashboard_Planning_NearestPlanningTrackerProvider($planning_factory),
                new AgileDashboard_Milestone_MilestoneDao(),
                Tracker_HierarchyFactory::instance(),
                $planning_factory
            )
        );
        $additional_criterion = $provider->getCriterion($backlog_tracker);

        if (! $additional_criterion) {
            return;
        }

        $params['array_of_html_criteria'][] = $additional_criterion;
    }

    /**
     * @see TRACKER_EVENT_REPORT_PROCESS_ADDITIONAL_QUERY
     */
    public function tracker_event_report_process_additional_query($params) {
        $backlog_tracker = $params['tracker'];

        $milestone_id_provider = new AgileDashboard_Milestone_SelectedMilestoneIdProvider($params['additional_criteria']);
        $milestone_id = $milestone_id_provider->getMilestoneId();
        if (! $milestone_id) {
            return;
        }

        $provider = new AgileDashboard_BacklogItem_SubBacklogItemProvider(
            new AgileDashboard_BacklogItem_SubBacklogItemDao(),
            new AgileDashboard_Planning_ParentBacklogTrackerCollectionProvider()
        );
        $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($milestone_id);

        if (! $artifact) {
            return;
        }

        $milestone                  = $this->getMilestoneFactory()->getMilestoneFromArtifact($artifact);
        $params['result'][]         = $provider->getMatchingIds($milestone, $backlog_tracker);
        $params['search_performed'] = true;
    }

    /**
     * @see TRACKER_EVENT_REPORT_SAVE_ADDITIONAL_CRITERIA
     */
    public function tracker_event_report_save_additional_criteria($params) {
        $dao                   = new MilestoneReportCriterionDao();
        $milestone_id_provider = new AgileDashboard_Milestone_SelectedMilestoneIdProvider($params['additional_criteria']);

        $milestone_id = $milestone_id_provider->getMilestoneId();
        if ($milestone_id) {
            $dao->save($params['report']->getId(), $milestone_id);
        } else {
            $dao->delete($params['report']->getId());
        }
    }

    /**
     * @see TRACKER_EVENT_REPORT_LOAD_ADDITIONAL_CRITERIA
     */
    public function tracker_event_report_load_additional_criteria($params) {
        $dao        = new MilestoneReportCriterionDao();
        $report_id  = $params['report']->getId();
        $field_name = AgileDashboard_Milestone_MilestoneReportCriterionProvider::FIELD_NAME;

        $row = $dao->searchByReportId($report_id)->getRow();
        if ($row){
            $params['additional_criteria_values'][$field_name]['value'] = $row['milestone_id'];
        }
    }

    public function event_artifact_parents_selector($params) {
        $artifact_parents_selector = new Planning_ArtifactParentsSelector(
            $this->getArtifactFactory(),
            PlanningFactory::build(),
            $this->getMilestoneFactory(),
            $this->getHierarchyFactory()
        );
        $event_listener = new Planning_ArtifactParentsSelectorEventListener($this->getArtifactFactory(), $artifact_parents_selector, HTTPRequest::instance());
        $event_listener->process($params);
    }

    public function tracker_event_include_css_file($params) {
        $params['include_tracker_css_file'] = true;
    }

    public function tracker_event_trackers_duplicated($params) {
        require_once TRACKER_BASE_DIR.'/Tracker/TrackerFactory.class.php';

        PlanningFactory::build()->duplicatePlannings(
            $params['group_id'],
            $params['tracker_mapping']
        );
    }

    public function tracker_event_redirect_after_artifact_creation_or_update($params) {
        $params_extractor        = new AgileDashboard_PaneRedirectionExtractor();
        $artifact_linker         = new Planning_ArtifactLinker($this->getArtifactFactory(), PlanningFactory::build());
        $last_milestone_artifact = $artifact_linker->linkBacklogWithPlanningItems($params['request'], $params['artifact']);
        $requested_planning      = $params_extractor->extractParametersFromRequest($params['request']);

        if ($requested_planning) {
            $this->redirectOrAppend($params['request'], $params['artifact'], $params['redirect'], $requested_planning, $last_milestone_artifact);
        }
    }

    private function redirectOrAppend(Codendi_Request $request, Tracker_Artifact $artifact, Tracker_Artifact_Redirect $redirect, $requested_planning, Tracker_Artifact $last_milestone_artifact = null) {
        $planning = PlanningFactory::build()->getPlanning($requested_planning['planning_id']);

        if ($planning && ! $redirect->stayInTracker()) {
            $this->redirectToPlanning($artifact, $requested_planning, $planning, $redirect);
        } elseif (! $redirect->stayInTracker()) {
            $this->redirectToTopPlanning($artifact, $requested_planning, $redirect);
        } else {
             $this->setQueryParametersFromRequest($request, $redirect);
             // Pass the right parameters so parent can be created in the right milestone (see updateBacklogs)
             if ($planning && $last_milestone_artifact && $redirect->mode == Tracker_Artifact_Redirect::STATE_CREATE_PARENT) {
                 $redirect->query_parameters['child_milestone'] = $last_milestone_artifact->getId();
             }
        }
    }

    private function redirectToPlanning(Tracker_Artifact $artifact, $requested_planning, Planning $planning, Tracker_Artifact_Redirect $redirect) {
        $redirect_to_artifact = $requested_planning[AgileDashboard_PaneRedirectionExtractor::ARTIFACT_ID];
        if ($redirect_to_artifact == -1) {
            $redirect_to_artifact = $artifact->getId();
        }
        $redirect->base_url = '/plugins/agiledashboard/';
        $redirect->query_parameters = array(
            'group_id'    => $planning->getGroupId(),
            'planning_id' => $planning->getId(),
            'action'      => 'show',
            'aid'         => $redirect_to_artifact,
            'pane'        => $requested_planning[AgileDashboard_PaneRedirectionExtractor::PANE],
        );
    }

    private function redirectToTopPlanning(Tracker_Artifact $artifact, $requested_planning, Tracker_Artifact_Redirect $redirect) {
        $redirect->base_url = '/plugins/agiledashboard/';
        $group_id = null;

        if ($artifact->getTracker() &&  $artifact->getTracker()->getProject()) {
            $group_id = $artifact->getTracker()->getProject()->getID();
        }

        $redirect->query_parameters = array(
            'group_id'    => $group_id,
            'action'      => 'show-top',
            'pane'        => $requested_planning['pane'],
        );
    }

    public function tracker_event_build_artifact_form_action($params) {
        $this->setQueryParametersFromRequest($params['request'], $params['redirect']);
        if ($params['request']->exist('child_milestone')) {
            $params['redirect']->query_parameters['child_milestone'] = $params['request']->getValidated('child_milestone', 'uint', 0);
        }
    }

    private function setQueryParametersFromRequest(Codendi_Request $request, Tracker_Artifact_Redirect $redirect) {
        $params_extractor   = new AgileDashboard_PaneRedirectionExtractor();
        $requested_planning = $params_extractor->extractParametersFromRequest($request);
        if ($requested_planning) {
            $key   = 'planning['. $requested_planning[AgileDashboard_PaneRedirectionExtractor::PANE] .']['. $requested_planning[AgileDashboard_PaneRedirectionExtractor::PLANNING_ID] .']';
            $value = $requested_planning[AgileDashboard_PaneRedirectionExtractor::ARTIFACT_ID];
            $redirect->query_parameters[$key] = $value;
        }
    }

    /**
     * @return AgileDashboardPluginInfo
     */
    public function getPluginInfo() {
        if (!$this->pluginInfo) {
            $this->pluginInfo = new AgileDashboardPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function cssfile($params) {
        // Only show the stylesheet if we're actually in the AgileDashboard pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }

    public function combined_scripts($params) {
        $params['scripts'] = array_merge(
            $params['scripts'],
            array(
                $this->getPluginPath().'/js/load-more-milestones.js',
                $this->getPluginPath().'/js/MilestoneContent.js',
                $this->getPluginPath().'/js/planning.js',
                $this->getPluginPath().'/js/OuterGlow.js',
                $this->getPluginPath().'/js/expand-collapse.js',
                $this->getPluginPath().'/js/planning-view.js',
            )
        );
    }

    public function javascript($params) {
        include $GLOBALS['Language']->getContent('script_locale', null, 'agiledashboard');
        echo PHP_EOL;
    }

    public function process(Codendi_Request $request) {
        $planning_factory             = $this->getPlanningFactory();
        $milestone_factory            = $this->getMilestoneFactory();
        $hierarchy_factory            = $this->getHierarchyFactory();
        $submilestone_finder          = new AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder($hierarchy_factory, $planning_factory);

        $pane_info_factory = new AgileDashboard_PaneInfoFactory(
            $request->getCurrentUser(),
            $submilestone_finder,
            $this->getThemePath()
        );

        $pane_presenter_builder_factory = $this->getPanePresenterBuilderFactory($milestone_factory, $pane_info_factory);

        $pane_factory = $this->getPaneFactory(
            $request,
            $milestone_factory,
            $pane_presenter_builder_factory,
            $submilestone_finder,
            $pane_info_factory
        );
        $top_milestone_pane_factory = $this->getTopMilestonePaneFactory($request, $pane_presenter_builder_factory);

        $milestone_controller_factory = new Planning_MilestoneControllerFactory(
            $this,
            ProjectManager::instance(),
            $milestone_factory,
            $this->getPlanningFactory(),
            $hierarchy_factory,
            $pane_presenter_builder_factory,
            $pane_factory,
            $top_milestone_pane_factory
        );

        $router = new AgileDashboardRouter(
            $this,
            $milestone_factory,
            $planning_factory,
            new Planning_ShortAccessFactory($planning_factory, $pane_info_factory),
            $milestone_controller_factory
        );

        $router->route($request);
    }

    /** @return Planning_MilestonePaneFactory */
    private function getPaneFactory(
        Codendi_Request $request,
        Planning_MilestoneFactory $milestone_factory,
        AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory $pane_presenter_builder_factory,
        AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder $submilestone_finder,
        AgileDashboard_PaneInfoFactory $pane_info_factory
    ) {
        return new Planning_MilestonePaneFactory(
            $request,
            $milestone_factory,
            $pane_presenter_builder_factory,
            $submilestone_finder,
            $pane_info_factory,
            $this->getThemePath()
        );
    }

    private function getTopMilestonePaneFactory($request, $pane_presenter_builder_factory) {
        return new Planning_VirtualTopMilestonePaneFactory(
            $request,
            $pane_presenter_builder_factory,
            $this->getThemePath()
        );
    }

    /**
     * Builds a new PlanningFactory instance.
     *
     * @return PlanningFactory
     */
    protected function getPlanningFactory() {
        return PlanningFactory::build();
    }

    /**
     * Builds a new Planning_MilestoneFactory instance.
     * @return Planning_MilestoneFactory
     */
    protected function getMilestoneFactory() {
        return new Planning_MilestoneFactory(
            $this->getPlanningFactory(),
            $this->getArtifactFactory(),
            Tracker_FormElementFactory::instance(),
            TrackerFactory::instance()
        );
    }

    private function getArtifactFactory() {
        return Tracker_ArtifactFactory::instance();
    }

    private function getHierarchyFactory() {
        return Tracker_HierarchyFactory::instance();
    }

    private function getBacklogStrategyFactory() {
        return new AgileDashboard_Milestone_Backlog_BacklogStrategyFactory(
            new AgileDashboard_BacklogItemDao(),
            $this->getArtifactFactory(),
            PlanningFactory::build()
        );
    }

    private function getBacklogRowCollectionFactory($milestone_factory) {
        return new AgileDashboard_Milestone_Backlog_BacklogRowCollectionFactory(
            new AgileDashboard_BacklogItemDao(),
            $this->getArtifactFactory(),
            Tracker_FormElementFactory::instance(),
            $milestone_factory,
            $this->getPlanningFactory()
        );
    }

    private function getPanePresenterBuilderFactory($milestone_factory, $pane_info_factory) {
        $icon_factory = new AgileDashboard_PaneIconLinkPresenterCollectionFactory($pane_info_factory);

        return new AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory(
            $this->getBacklogStrategyFactory(),
            $this->getBacklogRowCollectionFactory($milestone_factory),
            $milestone_factory,
            new AgileDashboard_Milestone_Pane_Planning_PlanningSubMilestonePresenterFactory($milestone_factory, $icon_factory)
        );
    }

    public function tracker_event_artifact_association_edited($params) {
        if ($params['request']->isAjax()) {
            $capacity         = $this->getFieldValue($params['form_element_factory'], $params['user'], $params['artifact'], Planning_Milestone::CAPACITY_FIELD_NAME);
            $remaining_effort = $this->getFieldValue($params['form_element_factory'], $params['user'], $params['artifact'], Planning_Milestone::REMAINING_EFFORT_FIELD_NAME);

            header('Content-type: application/json');
            echo json_encode(array(
                'remaining_effort' => $remaining_effort,
                'is_over_capacity' => $capacity !== null && $remaining_effort !== null && $capacity < $remaining_effort,
            ));
        }
    }

    private function getFieldValue(Tracker_FormElementFactory $form_element_factory, PFUser $user, Tracker_Artifact $artifact, $field_name) {
        $field = $form_element_factory->getComputableFieldByNameForUser(
            $artifact->getTracker()->getId(),
            $field_name,
            $user
        );
        if ($field) {
            return $field->getComputedValue($user, $artifact);
        }
        return 0;
    }

    /**
     * @see Event::SYSTRAY
     */
    public function systray($params) {
        $params['action'] = 'generate_systray_data';
        $request = new Codendi_Request($params);

        $this->process($request);
    }

    /**
     * @see Event::TRACKER_EVENT_MANAGE_SEMANTICS
     */
    public function tracker_event_manage_semantics($parameters) {
        $tracker   = $parameters['tracker'];
        /* @var $semantics Tracker_SemanticCollection */
        $semantics = $parameters['semantics'];

        $effort_semantic = AgileDashBoard_Semantic_InitialEffort::load($tracker);
        $semantics->add($effort_semantic->getShortName(), $effort_semantic);
    }

    /**
     * @see Event::TRACKER_EVENT_SEMANTIC_FROM_XML
     */
    public function tracker_event_semantic_from_xml(&$parameters) {
        $tracker    = $parameters['tracker'];
        $xml        = $parameters['xml'];
        $xmlMapping = $parameters['xml_mapping'];
        $type       = $parameters['type'];

        if ($type == AgileDashBoard_Semantic_InitialEffort::NAME) {
            $parameters['semantic'] = $this->getSemanticInitialEffortFactory()->getInstanceFromXML($xml, $xmlMapping, $tracker);
        }
    }

    /**
     * @see TRACKER_EVENT_GET_SEMANTIC_FACTORIES
     */
    public function tracker_event_get_semantic_factories($params) {
        $params['factories'][] = $this->getSemanticInitialEffortFactory();
    }

    protected function getSemanticInitialEffortFactory() {
        return AgileDashboard_Semantic_InitialEffortFactory::instance();
    }

    /**
     * Augment $params['semantics'] with names of AgileDashboard semantics
     * 
     * @see TRACKER_EVENT_SOAP_SEMANTICS
     */
    public function tracker_event_soap_semantics(&$params) {
        $params['semantics'][] = AgileDashBoard_Semantic_InitialEffort::NAME;
    }

    /**
     * @see Event::EXPORT_XML_PROJECT
     * @param $array $params
     */
    public function export_xml_project($params) {
        $params['action']     = 'export';
        $params['project_id'] = $params['project']->getId();
        $request              = new Codendi_Request($params);
        $this->process($request);
    }

    /**
     *
     * @param array $param
     *  Expected key/ values:
     *      project_id  int             The ID of the project for the import
     *      xml_content SimpleXmlObject A string of valid xml
     *      mapping     array           An array of mappings between xml tracker IDs and their true IDs
     *
     */
    public function import_xml_project_cardwall_done($params) {
        $params['action'] = 'import';
        $request          = new Codendi_Request($params);
        $this->process($request);
    }

    public function plugin_statistics_service_usage($params) {
        $dao = new AgileDashboard_Dao();

        $params['csv_exporter']->buildDatas($dao->getProjectsWithADActivated(), "Agile Dashboard activated");
    }

    /**
     * @see REST_RESOURCES
     */
    public function rest_resources($params) {
        $injector = new AgileDashboard_REST_ResourcesInjector();
        $injector->populate($params['restler']);
    }
}

?>
