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

require_once('common/plugin/Plugin.class.php');

/**
 * CardwallPlugin
 */
class cardwallPlugin extends Plugin {

    const RENDERER_TYPE = 'plugin_cardwall';

    public function __construct($id) {
        parent::__construct($id);
        if (defined('TRACKER_BASE_URL')) {
            $this->_addHook('cssfile',                           'cssFile',                           false);
            $this->_addHook('javascript_file',                   'jsFile',                            false);
            $this->_addHook('tracker_report_renderer_types' ,    'tracker_report_renderer_types',     false);
            $this->_addHook('tracker_report_renderer_instance',  'tracker_report_renderer_instance',  false);
            $this->_addHook(TRACKER_EVENT_ADMIN_ITEMS,           'tracker_event_admin_items',         false);
            $this->_addHook(TRACKER_EVENT_PROCESS,               'tracker_event_process',             false);
            
            if (defined('AGILEDASHBOARD_EVENT_ADDITIONAL_PANES_ON_MILESTONE')) {
                $this->_addHook(AGILEDASHBOARD_EVENT_ADDITIONAL_PANES_ON_MILESTONE, 'agiledashboard_event_additional_panes_on_milestone', false);
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
        if (strpos($_SERVER['REQUEST_URI'], TRACKER_BASE_URL.'/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/my/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/projects/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0 ) {
            echo '<link rel="stylesheet" type="text/css" href="'. $this->getThemePath() .'/css/style.css" />';
        }
    }
    
    function jsFile($params) {
        // Only show the js if we're actually in the Cardwall pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], TRACKER_BASE_URL.'/') === 0) {
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
                    $this->updateCardwallOnTop($params['tracker']->getId(), $params['request']->get('cardwall_on_top'));
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $params['tracker']->getId());
                }
                break;
        }
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
        $html .= '<input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        $html .= '</form>';
        echo $html;
        $tracker->displayFooter($layout);
    }
    
    private function updateCardwallOnTop($tracker_id, $is_enabled) {
        $this->getCSRFToken($tracker_id)->check();
        if ($is_enabled) {
            $this->getOnTopDao()->enable($tracker_id);
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_cardwall', 'on_top_enabled'));
        } else {
            $this->getOnTopDao()->disable($tracker_id);
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_cardwall', 'on_top_disabled'));
        }
        $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $tracker_id .'&func=admin-cardwall');
    }
    
    /**
     * @return Cardwall_OnTopDao
     */
    private function getOnTopDao() {
        require_once 'OnTopDao.class.php';
        return new Cardwall_OnTopDao();
    }
    
    /**
     * @return CSRFSynchronizerToken
     */
    private function getCSRFToken($tracker_id) {
        require_once 'common/include/CSRFSynchronizerToken.class.php';
        return new CSRFSynchronizerToken(TRACKER_BASE_URL.'/?tracker='. $tracker_id .'&amp;func=admin-cardwall-update');
    }
    
    public function agiledashboard_event_additional_panes_on_milestone($params) {
        $pane = new stdClass;
        $pane->identifier = 'cardwall';
        $pane->title      = 'Card Wall';
        $pane->content    = '<div class="tracker_renderer_board " id="anonymous_element_1"><label id="tracker_renderer_board-nifty"><input type="checkbox" onclick="$(this).up(\'div.tracker_renderer_board\').toggleClassName(\'nifty\'); new Ajax.Request(\'/toggler.php?id=tracker_renderer_board-nifty\');">free-hand drawing view</label><table width="100%" border="1" bordercolor="#ccc" cellspacing="2" cellpadding="10"><colgroup><col id="tracker_renderer_board_column-100"><col id="tracker_renderer_board_column-195"><col id="tracker_renderer_board_column-196"><col id="tracker_renderer_board_column-197"><col id="tracker_renderer_board_column-198"><col id="tracker_renderer_board_column-199"><col id="tracker_renderer_board_column-200"><col id="tracker_renderer_board_column-201"><col id="tracker_renderer_board_column-202"><col id="tracker_renderer_board_column-203"><col id="tracker_renderer_board_column-204"></colgroup><thead><tr><th>None</th><th>New</th><th>Analyzed</th><th>Accepted</th><th>Under Implementation</th><th>Ready for Review</th><th>Ready for Test</th><th>In Test</th><th>Approved</th><th>Deployed</th><th>Declined</th></tr></thead><tbody><tr valign="top"><td style="position: relative; "><ul><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-69" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=69">#69</a></div><div class="tracker_renderer_board_content">Dring Dring</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-68" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=68">#68</a></div><div class="tracker_renderer_board_content">Bicyclette !?!?</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-67" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=67">#67</a></div><div class="tracker_renderer_board_content">go to skool:)</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-74" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=74">#74</a></div><div class="tracker_renderer_board_content">Histoire</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-75" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=75">#75</a></div><div class="tracker_renderer_board_content">Jolie petite histoire</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-78" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=78">#78</a></div><div class="tracker_renderer_board_content">froufrou</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-79" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=79">#79</a></div><div class="tracker_renderer_board_content">qefvqev</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-80" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=80">#80</a></div><div class="tracker_renderer_board_content">efvv</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-81" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=81">#81</a></div><div class="tracker_renderer_board_content">efvv</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-82" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=82">#82</a></div><div class="tracker_renderer_board_content">efvv</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-83" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=83">#83</a></div><div class="tracker_renderer_board_content">dvfv</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-84" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=84">#84</a></div><div class="tracker_renderer_board_content">dvfv</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-88" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=88">#88</a></div><div class="tracker_renderer_board_content">ababa</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-77" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=77">#77</a></div><div class="tracker_renderer_board_content">E guaine</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-102" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=102">#102</a></div><div class="tracker_renderer_board_content">dddddd</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-76" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=76">#76</a></div><div class="tracker_renderer_board_content">Again</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-45" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=45">#45</a></div><div class="tracker_renderer_board_content">have an overview of the architecture</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-49" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=49">#49</a></div><div class="tracker_renderer_board_content">mmllm,,l</div></div></li><li class="tracker_renderer_board_postit anonymous_element_1_dummy_0" id="tracker_renderer_board_postit-26" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=26">#26</a></div><div class="tracker_renderer_board_content">sell tuleap</div></div></li></ul>&nbsp;</td><td style="position: relative; "><ul></ul>&nbsp;</td><td style="position: relative; "><ul><li class="tracker_renderer_board_postit anonymous_element_1_dummy_2" id="tracker_renderer_board_postit-25" style="position: relative; "><div class="card"><div class="card-actions"><a href="/plugins/tracker/?aid=25">#25</a></div><div class="tracker_renderer_board_content">shrink ui</div></div></li></ul>&nbsp;</td><td style="position: relative; "><ul></ul>&nbsp;</td><td style="position: relative; "><ul></ul>&nbsp;</td><td style="position: relative; "><ul></ul>&nbsp;</td><td style="position: relative; "><ul></ul>&nbsp;</td><td style="position: relative; "><ul></ul>&nbsp;</td><td style="position: relative; "><ul></ul>&nbsp;</td><td style="position: relative; "><ul></ul>&nbsp;</td><td style="position: relative; "><ul></ul>&nbsp;</td></tr></tbody></table></div>';
        $params['panes'][] = $pane;
    }
}

?>