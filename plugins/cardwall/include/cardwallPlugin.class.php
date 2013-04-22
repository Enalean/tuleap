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
require_once 'autoload.php';

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
            $tracker_factory  = TrackerFactory::instance();
            $element_factory  = Tracker_FormElementFactory::instance();
            $this->config_factory = new Cardwall_OnTop_ConfigFactory($tracker_factory, $element_factory);
        }
        return $this->config_factory;
    }

    const RENDERER_TYPE = 'plugin_cardwall';


    public function getHooksAndCallbacks() {
        if (defined('TRACKER_BASE_URL')) {
            $this->addHook('cssfile');
            $this->addHook('javascript_file');
            $this->addHook('tracker_report_renderer_types');
            $this->addHook('tracker_report_renderer_instance');
            $this->addHook(TRACKER_EVENT_ADMIN_ITEMS);
            $this->addHook(TRACKER_EVENT_PROCESS);
            $this->addHook(TRACKER_EVENT_TRACKERS_DUPLICATED);
            $this->addHook(TRACKER_EVENT_BUILD_ARTIFACT_FORM_ACTION);
            $this->addHook(TRACKER_EVENT_REDIRECT_AFTER_ARTIFACT_CREATION_OR_UPDATE);
            $this->_addHook(Event::JAVASCRIPT);
            $this->addHook(Event::EXPORT_XML_PROJECT);
            $this->addHook(Event::IMPORT_XML_PROJECT_TRACKER_DONE);

            if (defined('AGILEDASHBOARD_BASE_DIR')) {
                $this->addHook(AGILEDASHBOARD_EVENT_ADDITIONAL_PANES_ON_MILESTONE);
                $this->addHook(AGILEDASHBOARD_EVENT_INDEX_PAGE);
                $this->addHook(AGILEDASHBOARD_EVENT_MILESTONE_SELECTOR_REDIRECT);
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

    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'CardwallPluginInfo')) {
            $this->pluginInfo = new CardwallPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function cssfile($params) {
        // Only show the stylesheet if we're actually in the Cardwall pages.
        // This stops styles inadvertently clashing with the main site.
        if ($this->isAgileDashboardOrTrackerUrl() ||
            strpos($_SERVER['REQUEST_URI'], '/my/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/projects/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0 ) {
            echo '<link rel="stylesheet" type="text/css" href="'. $this->getThemePath() .'/css/style.css" />';
            echo '<link rel="stylesheet" type="text/css" href="'. $this->getPluginPath() .'/themes/default/select2/select2.css" />';
        }
    }

    public function javascript_file($params) {
        // Only show the js if we're actually in the Cardwall pages.
        // This stops styles inadvertently clashing with the main site.
        if ($this->isAgileDashboardOrTrackerUrl()) {
            echo $this->getJavascriptIncludesForScripts(array(
                'ajaxInPlaceEditorExtensions.js',
                'cardwall.js',
                'script.js',
                'admin.js',
                'select2.min.js',
            ));
        }
    }

    private function getJavascriptIncludesForScripts(array $script_names) {
        $html = '';
        foreach ($script_names as $script_name) {
            $html .= '<script type="text/javascript" src="'.$this->getPluginPath().'/js/'.$script_name.'"></script>'."\n";
        }
        return $html;
    }

    private function isAgileDashboardOrTrackerUrl() {
        return (defined('AGILEDASHBOARD_BASE_DIR') &&
                strpos($_SERVER['REQUEST_URI'], AGILEDASHBOARD_BASE_URL.'/') === 0 ||
                strpos($_SERVER['REQUEST_URI'], TRACKER_BASE_URL.'/') === 0);
    }

    public function javascript($params) {
        include $GLOBALS['Language']->getContent('script_locale', null, 'cardwall', '.js');
        echo PHP_EOL;
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
            $pane_info = new Cardwall_PaneInfo($params['milestone'], $this->getThemePath());
            if ($params['request']->get('pane') == Cardwall_PaneInfo::IDENTIFIER) {
                $pane_info->setActive(true);
                $params['active_pane'] = $this->getCardwallPane($pane_info, $params['milestone'], $params['user'], $params['milestone_factory']);
            }
            $params['panes'][] = $pane_info;
        }
    }

    public function agiledashboard_event_index_page($params) {
        $pane_info = new Cardwall_PaneInfo($params['milestone'], $this->getThemePath());
        $params['pane'] = $this->getCardwallPane($pane_info, $params['milestone'], $params['user'], $params['milestone_factory']);
    }

    protected function getCardwallPane(Cardwall_PaneInfo $info, Planning_Milestone $milestone, PFUser $user, Planning_MilestoneFactory $milestone_factory) {
        $tracker = $milestone->getArtifact()->getTracker();
        if ($this->getOnTopDao()->isEnabled($tracker->getId())) {
            $config = $this->getConfigFactory()->getOnTopConfig($tracker);
            return new Cardwall_Pane(
                $info,
                $milestone,
                $this->getPluginInfo()->getPropVal('display_qr_code'),
                $config,
                $user,
                $milestone_factory
            );
        }
        return null;
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
        $redirect = $params['redirect'];
        if ($cardwall) {
            if (!$redirect->stayInTracker()) {
                list($redirect_to, $redirect_params) = each($cardwall);
                switch ($redirect_to) {
                case 'agile':
                    $this->redirectToAgileDashboard($redirect, $redirect_params);
                    break;
                case 'renderer':
                    $this->redirectToRenderer($redirect, $redirect_params);
                    break;
                }
            } else {
                $this->appendCardwallParameter($redirect, $cardwall);
            }
        }
    }

    private function redirectToAgileDashboard(Tracker_Artifact_Redirect $redirect, array $redirect_params) {
        list($planning_id, $artifact_id) = each($redirect_params);
        $planning = PlanningFactory::build()->getPlanning($planning_id);
        if ($planning) {
            $redirect->base_url         = AGILEDASHBOARD_BASE_URL;
            $redirect->query_parameters = array(
                'group_id'    => $planning->getGroupId(),
                'planning_id' => $planning->getId(),
                'action'      => 'show',
                'aid'         => $artifact_id,
                'pane'        => 'cardwall',
            );
        }
    }

    private function redirectToRenderer(Tracker_Artifact_Redirect $redirect, array $redirect_params) {
        list($report_id, $renderer_id) = each($redirect_params);
        $redirect->base_url            = TRACKER_BASE_URL;
        $redirect->query_parameters    = array(
            'report'   => $report_id,
            'renderer' => $renderer_id,
        );
    }

    public function tracker_event_build_artifact_form_action($params) {
        $cardwall = $params['request']->get('cardwall');
        if ($cardwall) {
            $this->appendCardwallParameter($params['redirect'], $cardwall);
        }
    }

    private function appendCardwallParameter(Tracker_Artifact_Redirect $redirect, $cardwall) {
        list($key, $value) = explode('=', urldecode(http_build_query(array('cardwall' => $cardwall))));
        $redirect->query_parameters[$key] = $value;
    }

    /**
     * @param array $params parameters send by Event
     * Parameters:
     *  'project'  => The given project
     *  'into_xml' => The SimpleXMLElement to fill in
     */
    public function export_xml_project ($params) {

        if (! isset($params['project']) || ! isset($params['into_xml'])) {
            throw new CardwallEventParamsNotFoundException();
        }

        if (! $params['project'] instanceof Project || !  $params['into_xml'] instanceof SimpleXMLElement) {
            throw new CardwallEventParamsWithoutGoodTypesException();
        }

        $tracker_factory = TrackerFactory::instance();

        $cardwall_xml_export = new CardwallConfigXmlExport(
            $params['project'],
            $tracker_factory,
            new Cardwall_OnTop_ConfigFactory(
                $tracker_factory,
                Tracker_FormElementFactory::instance()
            )
        );

        $cardwall_xml_export->export($params['into_xml']);
    }

    /**
     *
     * @param array $params
     * @see Event::IMPORT_XML_PROJECT_TRACKER_DONE
     */
    public function import_xml_project_tracker_done($params) {
        $cardwall_ontop_import = new CardwallConfigXmlImport($params['project']->getId(), $params['xml_content'], $params['mapping'], new Cardwall_OnTop_Dao, EventManager::instance());
        $cardwall_ontop_import->import();
    }

    /**
     * @return Cardwall_OnTop_Dao
     */
    private function getOnTopDao() {
        return new Cardwall_OnTop_Dao();
    }

    /**
     * @return Cardwall_OnTop_ColumnDao
     */
    private function getOnTopColumnDao() {
        return new Cardwall_OnTop_ColumnDao();
    }

    /**
     * @return Cardwall_OnTop_ColumnMappingFieldDao
     */
    private function getOnTopColumnMappingFieldDao() {
        return new Cardwall_OnTop_ColumnMappingFieldDao();
    }

    /**
     * @return Cardwall_OnTop_ColumnMappingFieldValueDao
     */
    private function getOnTopColumnMappingFieldValueDao() {
        return new Cardwall_OnTop_ColumnMappingFieldValueDao();
    }

}
?>
