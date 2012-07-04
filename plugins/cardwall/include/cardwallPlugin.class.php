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
            }
        }
        return parent::getHooksAndCallbacks();
    }

    public function tracker_event_trackers_duplicated($params) {
        foreach ($params['tracker_mapping'] as $from_tracker_id => $to_tracker_id) {
            $this->getOnTopDao()->duplicate($from_tracker_id, $to_tracker_id);
            $this->getOnTopColumnDao()->duplicate($from_tracker_id, $to_tracker_id);
            $this->getOnTopColumnMappingFieldDao()->duplicate($from_tracker_id, $to_tracker_id, $params['field_mapping']);
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
            //Build the instance from the row
            $params['instance'] = new Cardwall_Renderer(
                $this,
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
        switch ($params['func']) {
            case 'admin-cardwall':
                if ($params['tracker']->userIsAdmin($params['user'])) {
                    $this->displayAdminOnTop($params['tracker'], $params['layout']);
                    $params['nothing_has_been_done'] = false;
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $params['tracker']->getId());
                }
                break;
            case 'admin-cardwall-update':
                if ($params['tracker']->userIsAdmin($params['user'])) {
                    $this->getCSRFToken($params['tracker']->getId())->check();
                    $this->getOnTopConfigUpdater($params['tracker'])->process($params['request']);
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $params['tracker']->getId() .'&func=admin-cardwall');
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $params['tracker']->getId());
                }
                break;
        }
    }

    /**
     * @return Cardwall_OnTop_Config_Updater
     */
    private function getOnTopConfigUpdater(Tracker $tracker) {
        $dao              = $this->getOnTopDao();
        $column_dao       = $this->getOnTopColumnDao();
        $mappingfield_dao = $this->getOnTopColumnMappingFieldDao();
        $tracker_factory  = TrackerFactory::instance();
        $element_factory  = Tracker_FormElementFactory::instance();
        require_once 'OnTop/Config/Updater.class.php';
        require_once 'OnTop/Config/Command/EnableCardwallOnTop.class.php';
        require_once 'OnTop/Config/Command/CreateColumn.class.php';
        require_once 'OnTop/Config/Command/UpdateColumns.class.php';
        require_once 'OnTop/Config/Command/DeleteColumns.class.php';
        require_once 'OnTop/Config/Command/CreateMappingField.class.php';
        require_once 'OnTop/Config/Command/UpdateMappingFields.class.php';
        require_once 'OnTop/Config/Command/DeleteMappingFields.class.php';
        $updater = new Cardwall_OnTop_Config_Updater();
        $updater->addCommand(new Cardwall_OnTop_Config_Command_EnableCardwallOnTop($tracker, $dao));
        $updater->addCommand(new Cardwall_OnTop_Config_Command_CreateColumn($tracker, $column_dao));
        $updater->addCommand(new Cardwall_OnTop_Config_Command_UpdateColumns($tracker, $column_dao));
        $updater->addCommand(new Cardwall_OnTop_Config_Command_DeleteColumns($tracker, $column_dao));
        $updater->addCommand(new Cardwall_OnTop_Config_Command_CreateMappingField($tracker, $mappingfield_dao, $tracker_factory));
        $updater->addCommand(new Cardwall_OnTop_Config_Command_UpdateMappingFields($tracker, $mappingfield_dao, $tracker_factory, $element_factory));
        $updater->addCommand(new Cardwall_OnTop_Config_Command_DeleteMappingFields($tracker, $mappingfield_dao, $tracker_factory));
        return $updater;
    }

    private function displayAdminOnTop(Tracker $tracker, Tracker_IDisplayTrackerLayout $layout) {
        $tracker->displayAdminItemHeader($layout, 'plugin_cardwall');
        $checked = $this->getOnTopDao()->isEnabled($tracker->getId()) ? 'checked="checked"' : '';
        $html  = '';
        $html .= '<form action="'. TRACKER_BASE_URL.'/?tracker='. $tracker->getId() .'&amp;func=admin-cardwall-update' .'" METHOD="POST">';
        $html .= $this->getCSRFToken($tracker->getId())->fetchHTMLInput();
        $html .= '<p>';
        $html .= '<input type="hidden" name="cardwall_on_top" value="0" />';
        $html .= '<label class="checkbox">';
        $html .= '<input type="checkbox" name="cardwall_on_top" value="1" id="cardwall_on_top" '. $checked .'/> ';
        $html .= $GLOBALS['Language']->getText('plugin_cardwall', 'on_top_label');
        $html .= '</label>';
        $html .= '</p>';
        if ($checked) {
            $html .= '<blockquote>';
            $html .= $this->fetchColumnDefinition($tracker);
            $html .= '</blockquote>';
        }
        $html .= '<input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        $html .= '</form>';
        echo $html;
        $tracker->displayFooter($layout);
    }

    private function fetchColumnDefinition(Tracker $tracker) {
        $hp       = Codendi_HTMLPurifier::instance();
        $html     = '';
        $trackers = TrackerFactory::instance()->getTrackersByGroupId($tracker->getGroupId());
        $trackers = array_diff($trackers, array($tracker));
        $field    = $tracker->getStatusField();
        if ($field) {
            $html .= '<p>'. 'The column used for the cardwall will be bound to the current status field ('. $hp->Purify($field->getLabel()) .') of this tracker.' .'</p>';
            $html .= 'TODO: display such columns';
            $html .= '<p>'. 'Maybe you wanna choose your own set of columns?' .'</p>';
        } else {
            $columns_raws = $this->getOnTopColumnDao()->searchColumnsByTrackerId($tracker->getId());
            if (!count($columns_raws)) {
                $html .= '<p>'. 'There is no semantic status defined for this tracker. Therefore you must configure yourself the columns used for cardwall.' .'</p>';
            }
            $html .= '<table><thead><tr valign="bottom">';
            $html .= '<td></td>';
            foreach ($columns_raws as $raw) {
                $html .= '<td>';
                $html .= '<input type="text" name="column['. (int)$raw['id'] .'][label]" value="'. $hp->purify($raw['label']) .'" />';
                $html .= '</td>';
            }
            $html .= '<td>';
            $html .= '<label>'. 'New column:'. '<br /><input type="text" name="new_column" value="" placeholder="'. 'Eg: On Going' .'" /></label>';
            $html .= '</td>';
            $html .= '<td>'. $GLOBALS['Language']->getText('global', 'btn_delete') .'</td>';
            $html .= '</tr></thead>';
            $html .= '<tbody>';
            $mapping_fields = $this->getOnTopColumnMappingFieldDao()->searchMappingFields($tracker->getId());
            foreach ($mapping_fields as $i => $row) {
                $mapping_tracker = TrackerFactory::instance()->getTrackerById($row['tracker_id']);
                $trackers = array_diff($trackers, array($mapping_tracker));
                $html .= '<tr class="'. html_get_alt_row_color($i + 1) .'" valign="top">';
                $html .= '<td>';
                $html .= $hp->purify($mapping_tracker->getName()) .'<br />';
                $field = Tracker_FormElementFactory::instance()->getFieldById($row['field_id']);
                $html .= '<select name="mapping_field['. (int)$mapping_tracker->getId() .']">';
                if (!$field) {
                    $html .= '<option>'. $GLOBALS['Language']->getText('global', 'please_choose_dashed') .'</option>';
                }
                foreach (Tracker_FormElementFactory::instance()->getUsedSbFields($mapping_tracker) as $sb_field) {
                    $selected = $field == $sb_field ? 'selected="selected"' : '';
                    $html .= '<option value="'. (int)$sb_field->getId() .'" '. $selected .'>'. $hp->purify($sb_field->getLabel()) .'</option>';
                }
                $html .= '</select>';
                $html .= '</td>';
                foreach ($columns_raws as $raw) {
                    $html .= '<td>';
                    $html .= '</td>';
                }
                $html .= '<td>';
                $html .= '</td>';
                $html .= '<td>';
                $html .= '<input type="checkbox" name="delete_mapping[]" value="'. (int)$mapping_tracker->getId() .'" />';
                $html .= '</td>';
                $html .= '</tr>';
            }
            if (count($columns_raws) && count($trackers)) {
                $colspan = count($columns_raws) + 2;
                $html .= '<tr>';
                $html .= '<td colspan="'. $colspan .'">';
                $html .= '<p>Wanna add a custom mapping for one of your trackers? (If no custom mapping, then duck typing on value labels will be used)</p>';
                $html .= '<select name="add_mapping_on">';
                $html .= '<option>'. $GLOBALS['Language']->getText('global', 'please_choose_dashed') .'</option>';
                foreach ($trackers as $new_tracker) {
                    $html .= '<option value="'. $new_tracker->getId() .'">'. $hp->purify($new_tracker->getName()) .'</option>';
                }
                $html .= '</select>';
                $html .= '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }
        return $html;
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
            $params['panes'][] = new Cardwall_Pane($params['milestone'], $this->getPluginInfo()->getPropVal('display_qr_code'));
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
}

?>