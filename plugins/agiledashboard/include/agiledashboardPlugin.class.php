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

            $this->_addHook(Event::SYSTRAY);
            $this->_addHook(Event::IMPORT_XML_PROJECT_CARDWALL_DONE);
            $this->_addHook(Event::EXPORT_XML_PROJECT);

            if (defined('CARDWALL_BASE_DIR')) {
                $this->_addHook(CARDWALL_EVENT_GET_SWIMLINE_TRACKER, 'cardwall_event_get_swimline_tracker', false);
            }
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
            $params['tracker'] = $planning->getBacklogTracker();
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



        $artifact_linker = new Planning_ArtifactLinker($this->getArtifactFactory(), PlanningFactory::build());
        $last_milestone_artifact = $artifact_linker->linkBacklogWithPlanningItems($params['request'], $params['artifact']);

        $requested_planning = $this->extractPlanningAndArtifactFromRequest($params['request']);



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
        $redirect_to_artifact = $requested_planning['artifact_id'];
        if ($redirect_to_artifact == -1) {
            $redirect_to_artifact = $artifact->getId();
        }
        $redirect->base_url = '/plugins/agiledashboard/';
        $redirect->query_parameters = array(
            'group_id'    => $planning->getGroupId(),
            'planning_id' => $planning->getId(),
            'action'      => 'show',
            'aid'         => $redirect_to_artifact,
            'pane'        => $requested_planning['pane_identifier'],
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
            'pane'        => $requested_planning['pane_identifier'],
        );
    }

    public function tracker_event_build_artifact_form_action($params) {
        $this->setQueryParametersFromRequest($params['request'], $params['redirect']);
        if ($params['request']->exist('child_milestone')) {
            $params['redirect']->query_parameters['child_milestone'] = $params['request']->getValidated('child_milestone', 'uint', 0);
        }
    }

    private function setQueryParametersFromRequest(Codendi_Request $request, Tracker_Artifact_Redirect $redirect) {
        $requested_planning = $this->extractPlanningAndArtifactFromRequest($request);
        if ($requested_planning) {
            $key   = 'planning['. $requested_planning['pane_identifier'] .']['. $requested_planning['planning_id'] .']';
            $value = $requested_planning['artifact_id'];
            $redirect->query_parameters[$key] = $value;
        }
    }

    private function extractPlanningAndArtifactFromRequest(Codendi_Request $request) {
        $planning = $request->get('planning');
        if (! is_array($planning) || ! count($planning)) {
            return;
        }
        list($pane_identifier, $from_planning) = each($planning);
        if (is_array($from_planning) && count($from_planning)) {
            list($planning_id, $planning_artifact_id) = each($from_planning);
            return array(
                'pane_identifier' => $pane_identifier,
                'planning_id'     => $planning_id,
                'artifact_id'     => $planning_artifact_id
            );
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
        $planning_factory               = $this->getPlanningFactory();
        $milestone_factory              = $this->getMilestoneFactory();
        $hierarchy_factory              = $this->getHierarchyFactory();
        $pane_presenter_builder_factory = $this->getPanePresenterBuilderFactory($milestone_factory);

        $pane_factory = $this->getPaneFactory($request, $planning_factory, $milestone_factory, $hierarchy_factory, $pane_presenter_builder_factory);

        $milestone_controller_factory = new Planning_MilestoneControllerFactory(
            $this,
            ProjectManager::instance(),
            $milestone_factory,
            $this->getPlanningFactory(),
            $hierarchy_factory,
            $pane_presenter_builder_factory,
            $pane_factory
        );

        $router = new AgileDashboardRouter(
            $this,
            $milestone_factory,
            $planning_factory,
            new Planning_ShortAccessFactory($planning_factory, $pane_factory),
            $milestone_controller_factory
        );

        $router->route($request);
    }

    /** @return Planning_MilestonePaneFactory */
    private function getPaneFactory(
        Codendi_Request $request,
        PlanningFactory $planning_factory,
        Planning_MilestoneFactory $milestone_factory,
        Tracker_HierarchyFactory $hierarchy_factory,
        AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory $pane_presenter_builder_factory
    ) {
        $legacy_planning_pane_factory = new Planning_MilestoneLegacyPlanningPaneFactory(
            $request,
            $milestone_factory,
            $hierarchy_factory,
            new Planning_ViewBuilderFactory($request, $planning_factory),
            $this->getThemePath()
        );

        return new Planning_MilestonePaneFactory(
            $request,
            $milestone_factory,
            $pane_presenter_builder_factory,
            $legacy_planning_pane_factory,
            new AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder($hierarchy_factory, $planning_factory),
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
            $milestone_factory
        );
    }

    private function getPanePresenterBuilderFactory($milestone_factory) {
        return new AgileDashboard_Milestone_Pane_PanePresenterBuilderFactory(
            $this->getBacklogStrategyFactory(),
            $this->getBacklogRowCollectionFactory($milestone_factory),
            $milestone_factory
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
}

?>
