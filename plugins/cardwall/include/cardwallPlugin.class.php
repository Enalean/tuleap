<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
require_once 'constants.php';

/**
 * CardwallPlugin
 */
class cardwallPlugin extends Plugin {
    
    /** 
     * @var Cardwall_OnTop_ConfigFactory
     */
    private $config_factory;
    
    public function getConfigFactory() {
        if (!$this->config_factory) {
            require_once 'OnTop/ConfigFactory.class.php';
            require_once TRACKER_BASE_DIR. '/Tracker/TrackerFactory.class.php';
            $tracker_factory  = TrackerFactory::instance();
            $element_factory  = Tracker_FormElementFactory::instance();
            $this->config_factory = new Cardwall_OnTop_ConfigFactory($tracker_factory, $element_factory);
        }
        return $this->config_factory;
    }

    const RENDERER_TYPE = 'plugin_cardwall';


    public function getHooksAndCallbacks() {
        if (defined('TRACKER_BASE_URL')) {
            $this->_addHook('cssfile',                           'cssFile',                           false);
            $this->_addHook('javascript_file',                   'jsFile',                            false);
            $this->_addHook('tracker_report_renderer_types' ,    'tracker_report_renderer_types',     false);
            $this->_addHook('tracker_report_renderer_instance',  'tracker_report_renderer_instance',  false);
            $this->_addHook(TRACKER_EVENT_ADMIN_ITEMS,           'tracker_event_admin_items',         false);
            $this->_addHook(TRACKER_EVENT_PROCESS,               'tracker_event_process',             false);
            $this->_addHook(TRACKER_EVENT_TRACKERS_DUPLICATED,   'tracker_event_trackers_duplicated', false);
            $this->_addHook(TRACKER_EVENT_BUILD_ARTIFACT_FORM_ACTION, 'tracker_event_build_artifact_form_action', false);
            $this->_addHook(TRACKER_EVENT_REDIRECT_AFTER_ARTIFACT_CREATION_OR_UPDATE, 'tracker_event_redirect_after_artifact_creation_or_update', false);

            if (defined('AGILEDASHBOARD_BASE_DIR')) {
                $this->_addHook(AGILEDASHBOARD_EVENT_ADDITIONAL_PANES_ON_MILESTONE, 'agiledashboard_event_additional_panes_on_milestone', false);
                $this->_addHook(AGILEDASHBOARD_EVENT_MILESTONE_SELECTOR_REDIRECT, 'agiledashboard_event_milestone_selector_redirect', false);
            }
        }
        return parent::getHooksAndCallbacks();
    }

    
    // TODO : transform into a OnTop_Config_Command, and move code to ConfigFactory
    public function tracker_event_trackers_duplicated($params) {
        foreach ($params['tracker_mapping'] as $from_tracker_id => $to_tracker_id) {
            if ($this->getOnTopDao()->duplicate($from_tracker_id, $to_tracker_id)) {
                $this->getOnTopColumnDao()->duplicate($from_tracker_id, $to_tracker_id, $params);
                $this->getOnTopColumnMappingFieldDao()->duplicate($from_tracker_id, $to_tracker_id, $params['tracker_mapping'], $params['field_mapping']);
                $this->getOnTopColumnMappingFieldValueDao()->duplicate($from_tracker_id, $to_tracker_id, $params['tracker_mapping'], $params['field_mapping'], $params['plugin_cardwall_column_mapping']);
            }
        }
    }

    /**
     * This hook ask for types of report renderer
     *
     * @param array types Input/Output parameter. Expected format: $types['my_type'] => 'Label of the type'
     */
    public function tracker_report_renderer_types($params) {
        $params['types'][self::RENDERER_TYPE] = $GLOBALS['Language']->getText('plugin_cardwall', 'title');
    }

    /**
     * This hook asks to create a new instance of a renderer
     *
     * @param array $params:
     *              mixed  'instance' Output parameter. must contain the new instance
     *              string 'type' the type of the new renderer
     *              array  'row' the base properties identifying the renderer (id, name, description, rank)
     *              Report 'report' the report
     *
     * @return void
     */
    public function tracker_report_renderer_instance($params) {
        if ($params['type'] == self::RENDERER_TYPE) {
            require_once('Cardwall_Renderer.class.php');
            require_once('Cardwall_RendererDao.class.php');
            //First retrieve specific properties of the renderer that are not saved in the generic table
            if ( !isset($row['field_id']) ) {
                $row['field_id'] = null;
                if ($params['store_in_session']) {
                    $this->report_session = new Tracker_Report_Session($params['report']->id);
                    $this->report_session->changeSessionNamespace("renderers.{$params['row']['id']}");
                    $row['field_id'] = $this->report_session->get("field_id");
                }
                if (!$row['field_id']) {
                    $dao = new Cardwall_RendererDao();
                    $cardwall_row = $dao->searchByRendererId($params['row']['id'])->getRow();
                    if ($cardwall_row) {
                        $row['field_id'] = $cardwall_row['field_id'];
                    }
                }
            }

            $report = $params['report'];
            require_once('OnTop/ConfigEmpty.class.php');
            $config = new Cardwall_OnTop_ConfigEmpty();
            
            if ($report->tracker_id != 0) {
                $config = $this->getConfigFactory()->getOnTopConfigByTrackerId($report->tracker_id);
            }
            //Build the instance from the row
            $params['instance'] = new Cardwall_Renderer(
                $this,
                $config,
                $params['row']['id'],
                $params['report'],
                $params['row']['name'],
                $params['row']['description'],
                $params['row']['rank'],
                $row['field_id'],
                $this->getPluginInfo()->getPropVal('display_qr_code')
            );
            if ($params['store_in_session']) {
                $params['instance']->initiateSession();
            }
        }
    }

    function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'CardwallPluginInfo')) {
            require_once('CardwallPluginInfo.class.php');
            $this->pluginInfo = new CardwallPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    function cssFile($params) {
        // Only show the stylesheet if we're actually in the Cardwall pages.
        // This stops styles inadvertently clashing with the main site.
        if (defined('AGILEDASHBOARD_BASE_DIR') && strpos($_SERVER['REQUEST_URI'], AGILEDASHBOARD_BASE_URL.'/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], TRACKER_BASE_URL.'/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/my/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/projects/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0 ) {
            echo '<link rel="stylesheet" type="text/css" href="'. $this->getThemePath() .'/css/style.css" />';
        }
    }

    function jsFile($params) {
        // Only show the js if we're actually in the Cardwall pages.
        // This stops styles inadvertently clashing with the main site.
        if (defined('AGILEDASHBOARD_BASE_DIR') && strpos($_SERVER['REQUEST_URI'], AGILEDASHBOARD_BASE_URL.'/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], TRACKER_BASE_URL.'/') === 0) {
            echo '<script type="text/javascript" src="'.$this->getPluginPath().'/script.js"></script>'."\n";
        }
    }

    function tracker_event_admin_items($params) {
        $params['items']['plugin_cardwall'] = array(
            'url'         => TRACKER_BASE_URL.'/?tracker='. $params['tracker']->getId() .'&amp;func=admin-cardwall',
            'short_title' => $GLOBALS['Language']->getText('plugin_cardwall','on_top_short_title'),
            'title'       => $GLOBALS['Language']->getText('plugin_cardwall','on_top_title'),
            'description' => $GLOBALS['Language']->getText('plugin_cardwall','on_top_description'),
            'img'         => $this->getThemePath() .'/images/ic/48/sticky-note.png',
        );
    }

    function tracker_event_process($params) {
        $tracker          = $params['tracker'];
        $tracker_id       = $tracker->getId();
        if (strpos($params['func'], 'admin-cardwall') !== false && ! $tracker->userIsAdmin($params['user'])) {
            $this->denyAccess($tracker_id);
        }

        $token            = $this->getCSRFToken($tracker_id);
        switch ($params['func']) {
            case 'admin-cardwall':
                require_once 'View/Admin.class.php';

                $admin_view = new Cardwall_View_Admin();
                $config     = $this->getConfigFactory()->getOnTopConfig($tracker);
                $admin_view->displayAdminOnTop($params['layout'], $token, $config);
                $params['nothing_has_been_done'] = false;
                break;
            case 'admin-cardwall-update':
                $token->check();
                $this->getConfigFactory()->getOnTopConfigUpdater($tracker)
                        ->process($params['request']);
                $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $tracker_id .'&func=admin-cardwall');
                break;
        }
    }

    private function denyAccess($tracker_id) {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
        $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $tracker_id);
    }


    /**
     * @return CSRFSynchronizerToken
     */
    private function getCSRFToken($tracker_id) {
        require_once 'common/include/CSRFSynchronizerToken.class.php';
        return new CSRFSynchronizerToken(TRACKER_BASE_URL.'/?tracker='. $tracker_id .'&amp;func=admin-cardwall-update');
    }

    public function agiledashboard_event_additional_panes_on_milestone($params) {
        $tracker  = $params['milestone']->getArtifact()->getTracker();

        if ($this->getOnTopDao()->isEnabled($tracker->getId())) {
            require_once 'Pane.class.php';
            $config = $this->getConfigFactory()->getOnTopConfig($tracker);
            $params['panes'][] = new Cardwall_Pane($params['milestone'], $this->getPluginInfo()->getPropVal('display_qr_code'), $config);
        }
    }

    public function agiledashboard_event_milestone_selector_redirect($params) {
        if ($params['milestone']->getArtifact()) {
            $tracker  = $params['milestone']->getArtifact()->getTracker();
            if ($this->getOnTopDao()->isEnabled($tracker->getId())) {
                $params['redirect_parameters']['pane'] = 'cardwall';
            }
        }
    }

    public function tracker_event_redirect_after_artifact_creation_or_update($params) {
        $cardwall = $params['request']->get('cardwall');
        if ($cardwall && $this->requestCanLeaveTheTracker($params['request'])) {
            list($redirect_to, $redirect_params) = each($cardwall);
            switch ($redirect_to) {
            case 'agile':
                $this->redirectToAgileDashboard($redirect_params);
                break;
            case 'renderer':
                $this->redirectToRenderer($redirect_params);
                break;
            }
        }
    }

    private function redirectToAgileDashboard(array $redirect_params) {
        list($planning_id, $artifact_id) = each($redirect_params);
        require_once AGILEDASHBOARD_BASE_DIR .'/Planning/PlanningFactory.class.php';
        $planning = PlanningFactory::build()->getPlanning($planning_id);
        if ($planning) {
            $GLOBALS['Response']->redirect(AGILEDASHBOARD_BASE_URL .'/?'. http_build_query(array(
                'group_id'    => $planning->getGroupId(),
                'planning_id' => $planning->getId(),
                'action'      => 'show',
                'aid'         => $artifact_id,
                'pane'        => 'cardwall',
            )));
        }
    }

    private function redirectToRenderer(array $redirect_params) {
        list($report_id, $renderer_id) = each($redirect_params);
        $GLOBALS['Response']->redirect(TRACKER_BASE_URL .'/?'. http_build_query(array(
            'report'   => $report_id,
            'renderer' => $renderer_id,
        )));
    }

    public function tracker_event_build_artifact_form_action($params) {
        $cardwall = $params['request']->get('cardwall');
        if ($cardwall) {
            list($key, $value) = explode('=', urldecode(http_build_query(array('cardwall' => $cardwall))));
            $params['query_parameters'][$key] = $value;
        }
    }

    private function requestCanLeaveTheTracker(Codendi_Request $request) {
        return ! ($request->get('submit_and_stay') || $request->get('submit_and_continue'));
    }
    
    /**
     * @return Cardwall_OnTop_Dao
     */
    private function getOnTopDao() {
        require_once 'OnTop/Dao.class.php';
        return new Cardwall_OnTop_Dao();
    }

    /**
     * @return Cardwall_OnTop_ColumnDao
     */
    private function getOnTopColumnDao() {
        require_once 'OnTop/ColumnDao.class.php';
        return new Cardwall_OnTop_ColumnDao();
    }

    /**
     * @return Cardwall_OnTop_ColumnMappingFieldDao
     */
    private function getOnTopColumnMappingFieldDao() {
        require_once 'OnTop/ColumnMappingFieldDao.class.php';
        return new Cardwall_OnTop_ColumnMappingFieldDao();
    }

    /**
     * @return Cardwall_OnTop_ColumnMappingFieldValueDao
     */
    private function getOnTopColumnMappingFieldValueDao() {
        require_once 'OnTop/ColumnMappingFieldValueDao.class.php';
        return new Cardwall_OnTop_ColumnMappingFieldValueDao();
    }

}
?>
