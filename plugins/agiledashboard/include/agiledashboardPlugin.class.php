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
        // Do not load the plugin if tracker is not installed & active
        if (defined('TRACKER_BASE_URL')) {
            $this->_addHook('cssfile', 'cssfile', false);
            $this->_addHook(Event::COMBINED_SCRIPTS, 'combined_scripts', false);
            $this->_addHook(TRACKER_EVENT_INCLUDE_CSS_FILE, 'tracker_event_include_css_file', false);
            $this->_addHook(TRACKER_EVENT_TRACKERS_DUPLICATED, 'tracker_event_trackers_duplicated', false);
            $this->_addHook(TRACKER_EVENT_BUILD_ARTIFACT_FORM_ACTION, 'tracker_event_build_artifact_form_action', false);
            $this->_addHook(TRACKER_EVENT_REDIRECT_AFTER_ARTIFACT_CREATION_OR_UPDATE, 'tracker_event_redirect_after_artifact_creation_or_update', false);
        }
    }
    
    public function tracker_event_include_css_file($params) {
        $params['include_tracker_css_file'] = true;
    }
    
    public function tracker_event_trackers_duplicated($params) {
        require_once 'Planning/PlanningDao.class.php';
        require_once TRACKER_BASE_DIR.'/Tracker/TrackerFactory.class.php';
        require_once 'Planning/PlanningFactory.class.php';
        
        PlanningFactory::build()->duplicatePlannings(
            $params['group_id'],
            $params['tracker_mapping']
        );
    }
    
    public function tracker_event_redirect_after_artifact_creation_or_update($params) {
        $requested_planning = $this->extractPlanningAndArtifactFromRequest($params['request']);
        if ($requested_planning) {
            require_once 'Planning/PlanningFactory.class.php';
            $planning_factory = PlanningFactory::build();
            $planning         = $planning_factory->getPlanning($requested_planning['planning_id']);
            if ($planning) {
                $GLOBALS['Response']->redirect('/plugins/agiledashboard/?'. http_build_query(array(
                    'group_id'    => $planning->getGroupId(),
                    'planning_id' => $planning->getId(),
                    'action'      => 'show',
                    'aid'         => $requested_planning['artifact_id'],
                )));
            }
        }
    }
    
    public function tracker_event_build_artifact_form_action($params) {
        $requested_planning = $this->extractPlanningAndArtifactFromRequest($params['request']);
        if ($requested_planning) {
            $key   = 'planning['. $requested_planning['planning_id'] .']';
            $value = $requested_planning['artifact_id'];
            $params['query_parameters'][$key] = $value;
        }
    }
    
    private function extractPlanningAndArtifactFromRequest(Codendi_Request $request) {
        $from_planning = $request->get('planning');
        if (is_array($from_planning) && count($from_planning)) {
            list($planning_id, $planning_artifact_id) = each($from_planning);
            return array(
                'planning_id' => $planning_id, 
                'artifact_id' => $planning_artifact_id
            );
        }
    }
    
    /**
     * @return AgileDashboardPluginInfo
     */
    public function getPluginInfo() {
        if (!$this->pluginInfo) {
            include_once 'AgileDashboardPluginInfo.class.php';
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
                $this->getPluginPath().'/js/drag-n-drop.js',
                $this->getPluginPath().'/js/planning-view.js',
            )
        );
    }
    
    public function process(Codendi_Request $request) {
        require_once 'AgileDashboardRouter.class.php';
        $router = new AgileDashboardRouter($this);
        $router->route($request);
    }
}

?>
