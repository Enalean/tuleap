<?php
/**
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
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

class GraphOnTrackersPlugin extends Plugin {


    var $report_id;
    var $chunksz;
    var $offset;
    var $advsrch;
    var $morder;
    var $prefs;
    var $group_id;
    var $atid;
    var $set;
    var $report_graphic_id;
    var $allowedForProject;

    /**
     * Class constructor
     *
     * @param integer $id plugin id
     */
    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_PROJECT);
        
        $this->addHook('cssfile',                           'cssFile',                           false);
        $this->addHook('tracker_collapsable_sections',      'tracker_collapsable_sections',      false);
        $this->addHook('tracker_urlparam_processing','tracker_urlparam_processing',false);
        $this->addHook('tracker_user_pref','tracker_user_pref',false);
        $this->addHook('tracker_form_browse_add_in','tracker_form_browse_add_in',false);
        $this->addHook('tracker_after_report','tracker_after_report',false);
        $this->addHook('tracker_graphic_report_admin','tracker_graphic_report_admin',false);
        $this->addHook('tracker_graphic_report_add_link','tracker_graphic_report_add_link',false);
        $this->addHook('tracker_graphic_report_admin_header','tracker_graphic_report_admin_header',false);
        $this->addHook('graphontrackers_load_chart_factories', 'graphontrackers_load_chart_factories', false);
        $this->addHook('artifactType_created', 'copy_graphical_reports', false);
        $this->addHook('artifactType_deleted', 'delete_graphical_reports', false);
        $this->allowedForProject = array();
    }

    /**
     * function to get plugin info
     *
     */
    function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'GraphOnTrackersPluginInfo')) {
            require_once('GraphOnTrackersPluginInfo.class.php');
            $this->pluginInfo = new GraphOnTrackersPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    function tracker_collapsable_sections($params) {
        $params['sections'][] = 'charts';
    }

    /**
     * Return true if current project has the right to use this plugin.
     */
    function isAllowed($group_id) {
        $request = HTTPRequest::instance();
        $group_id = (int) $request->get('group_id');
        if(!isset($this->allowedForProject[$group_id])) {
            $pM = PluginManager::instance();
            $this->allowedForProject[$group_id] = $pM->isPluginAllowedForProject($this, $group_id);
        }
        return $this->allowedForProject[$group_id];
    }
    
    function cssFile($params) {
        // Only show the stylesheet if we're actually in the Docman pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], '/tracker/admin/') === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />'."\n";
        }
    }
    
    function graphontrackers_load_chart_factories($params) {
        require_once('data-access/GraphOnTrackers_Chart_Bar.class.php');
        require_once('data-access/GraphOnTrackers_Chart_Pie.class.php');
        require_once('data-access/GraphOnTrackers_Chart_Gantt.class.php');
        $params['factories']['pie'] = array(
            'chart_type'      => 'pie',
            'chart_classname' => 'GraphOnTrackers_Chart_Pie',
            'icon'            => $this->getThemePath().'/images/chart_pie.png',
            'title'           => $GLOBALS['Language']->getText('plugin_graphontrackers_include_report','pie'),
        );
        $params['factories']['bar'] = array(
            'chart_type'      => 'bar',
            'chart_classname' => 'GraphOnTrackers_Chart_Bar',
            'icon'            => $this->getThemePath().'/images/chart_bar.png',
            'title'           => $GLOBALS['Language']->getText('plugin_graphontrackers_include_report','bar'),
        );
        $params['factories']['gantt'] = array(
            'chart_type'      => 'gantt',
            'chart_classname' => 'GraphOnTrackers_Chart_Gantt',
            'icon'            => $this->getThemePath().'/images/chart_gantt.png',
            'title'           => $GLOBALS['Language']->getText('plugin_graphontrackers_include_report','gantt'),
        );
    }

    /**
     *     Hook to add watch and ganttscale  preferences to  variable url who
     *   belong to  displayReport method .
     *       Used in www/tracker/include/ArtifactReportHtml.class.php
     *
     *     @param params:hook parameters
     */
    public function tracker_urlparam_processing($params){
        if($this->isAllowed($params['group_id'])) {
            if ($this->report_graphic_id!="0"){
                $params['url'] .= "&report_graphic_id=".$this->report_graphic_id;
            }
        }
    }

    /**
     *     Hook to set all  user preferences  .
     *
     *     @param params:hook parameters
     */
    public function tracker_user_pref($params){
        if($this->isAllowed($params['group_id'])) {
            $this->group_id  = $params['group_id'];
            $this->atid      = $params['atid'];
            $this->report_id = $params['report_id'];
            $this->prefs     = $params['prefs'];
            $this->morder    = $params['morder'];
            $this->chunksz   = $params['chunksz'];
            $this->advsrch   = $params['advsrch'];
            $this->msort     = $params['msort'];
            $this->offset    = $params['offset'];
            $this->set       = $params['set'];
            $request = HTTPRequest::instance();

            if ($request->get('report_graphic_id') !== false && $request->valid(new Valid_UInt('report_graphic_id'))) {
                $this->report_graphic_id = $request->get('report_graphic_id');
                $trackerGraphsPrefs = "&report_graphic_id=".$this->report_graphic_id;
                if ($trackerGraphsPrefs != user_get_preference('tracker_graph_brow_cust'.$request->get('atid'))){
                    user_set_preference('tracker_graph_brow_cust'.$request->get('atid'),$trackerGraphsPrefs);
                }
            } else {
                if (user_isloggedin()) {
                    $custom_pref=user_get_preference('tracker_graph_brow_cust'.$this->atid);
                    if ($custom_pref) {
                        $pref_arr = explode('&',substr($custom_pref,1));
                        while (list(,$expr) = each($pref_arr)) {
                            list($field,$value_id) = explode('=',$expr);
                                $field = str_replace('[]','',$field);
                            if($field == 'report_graphic_id') {
                                $this->report_graphic_id = $value_id;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Hook to display the charts.
     * Used in www/tracker/browse.php
     * 
     * @param params:hook parameters
     */
    public function tracker_after_report($params){
       if($this->isAllowed($params['group_id'])) {
           require_once('html-generators/GraphicEngineHtml.class.php');
           $eng = new graphicEngineHtml($this->atid,user_getid(),$this->getThemePath());
           $eng->displayReportGraphic($this->report_graphic_id, $params['group_id'], $params['atid'], $params['url']);
       }
    }
    
    /**
     *  Hook to admin graphic reports
     *  Used in www/tracker/admin/index.php
     * 
     * @param params:hook parameters
     */
    function tracker_graphic_report_admin($params){
        
        $request = HTTPRequest::instance();
        if ($request->valid(new Valid_WhiteList('func', array('reportgraphic'))) && $request->valid(new Valid_UInt('atid'))) {
            $func = $request->get('func');
            $atid = $request->get('atid');  
            if ($func == 'reportgraphic') {
                require_once('html-generators/GraphicEngineHtml.class.php');
                require_once('data-access/GraphOnTrackers_Report.class.php');
                
                if ( !user_isloggedin() ) {
                    exit_not_logged_in();
                    return;
                }
                
                $user_id = UserManager::instance()->getCurrentUser()->getId();
                $geh = new graphicEngineHtml($atid, $user_id, $this->getThemePath());
                if ($request->exist('create_report_graphic') && $request->get('rep_name')) {
                    if ($GLOBALS['ath']->userIsAdmin() && $request->valid(new Valid_WhiteList('rep_scope', array('P', 'I')))) {
                        $rep_scope = $request->get('rep_scope');
                    } else {
                        $rep_scope = 'I';
                    }
                    if ($report = GraphOnTrackers_Report::create($atid, 
                                    $user_id, 
                                    $request->get('rep_name'), 
                                    $request->get('rep_desc'), 
                                    $rep_scope)) {
                        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_graphontrackers_include_report','new_created_report'));
                        $GLOBALS['Response']->redirect('/tracker/admin/?func=reportgraphic&group_id='.$report->getGroupId().'&atid='.$report->getAtid().'&report_graphic_id='.$report->getId());
                    }
                } else {
                    $report_graphic_id = $request->getValidated('report_graphic_id', 'uint', 0);
                    
                    $gr  = new GraphOnTrackers_Report($report_graphic_id);
                    
                    if ($gr->getScope() != 'P' || $GLOBALS['ath']->userIsAdmin()) {
                        if ($request->exist('update_report')) {
                            if ($request->valid(new Valid_String('rep_name'))
                                && $request->valid(new Valid_String('rep_desc'))
                            && $request->valid(new Valid_WhiteList('rep_scope', array('I', 'P')))
                            ) {
                                $rep_name  = $request->get('rep_name');
                                $rep_desc  = $request->get('rep_desc');
                                $rep_scope = $request->get('rep_scope');
                                
                                if ($rep_name != $gr->getName() || $rep_desc != $gr->getDescription() || $rep_scope != $gr->getScope()) {
                                    $gr->setName($rep_name);
                                    $gr->setDescription($rep_desc);
                                    $gr->setScope($rep_scope);
                                    $gr->setUserId(UserManager::instance()->getCurrentUser()->getId());
                                    if ($gr->update()) {
                                        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_graphontrackers_include_report','updated_report'));
                                    } else {
                                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_graphontrackers_include_report','not_updated_report'));
                                    }
                                }
                            }
                        } else if (is_array($request->get('delete_chart'))) {
                            $chart_id_to_delete = (int)key($request->get('delete_chart'));
                            $gr->deleteChart($chart_id_to_delete);
                            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_graphontrackers_include_report','updated_report'));
                            $GLOBALS['Response']->redirect('/tracker/admin/?func=reportgraphic&group_id='.$gr->getGroupId().'&atid='.$gr->getAtid().'&report_graphic_id='.$gr->getId());
                        } else if ($request->exist('update_chart') && is_array($request->get('chart'))) {
                            $row = $request->get('chart');
                            if (isset($row['id'])) {
                                $chart_to_edit = $gr->getChart($row['id']);
                                if ($chart_to_edit->update($row)) {
                                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_graphontrackers_include_report','updated_report'));
                                }
                            }
                        } else if ($request->exist('edit_chart')) {
                            $chart_to_edit = $gr->getChart((int)($request->get('edit_chart')));
                        } else if ($request->exist('add_chart')) {
                            if ($chart = $gr->createChart($request->get('add_chart'))) {
                                $GLOBALS['Response']->redirect('/tracker/admin/?func=reportgraphic&group_id='.$gr->getGroupId().'&atid='.$gr->getAtid().'&report_graphic_id='.$gr->getId().'&edit_chart='. (int)$chart->getId());
                            }
                        } else if ($request->exist('delete_report_graphic')) {
                            $gr->delete();
                            $report_graphic_id = null;
                            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_graphontrackers_include_report','report_deleted'));
                        }
                    }
                }
                
                $GLOBALS['ath']->adminHeader(array ('title'=> $GLOBALS['Language']->getText('plugin_graphontrackers_include_report','report_mgmt'),
                    'help' => 'tracker-v3.html#tracker-graphical-report-setting'));
                
                if ($request->exist('new_report_graphic')) {
                    $geh->createReportForm();
                } else if ($report_graphic_id) {
                    if (isset($chart_to_edit)){
                        $geh->showChartForm($chart_to_edit);
                    } else {
                        $geh->showReportForm($report_graphic_id);
                    }
                } else {
                    // Front page
                    $reports = $geh->grf->getReportsAvailable($atid, user_getid());
                    $geh->showAvailableReports($reports);
                }
                $GLOBALS['ath']->footer(null);
                exit;
            }
        }
    }
    
    /**
     *  Hook to add graphic reports administration link
     *  Used in www/tracker/admin/index.php
     * 
     * @param params:hook parameters
     */
    function tracker_graphic_report_add_link($params) {
        $request = HTTPRequest::instance();
        if ($request->valid(new Valid_GroupId())) {
            echo '<H3><A href="/tracker/admin/?func=reportgraphic&group_id='.$request->get('group_id').'&atid='.$request->get('atid').'">'.$GLOBALS['Language']->getText('plugin_graphontrackers_admin_menu','manage_graphic').'</A></H3>';
            echo $GLOBALS['Language']->getText('plugin_graphontrackers_admin_menu','manage_graphic_desc');
        }
    }
    
    /**
     *  Hook to add graphic reports administration administration
     *  Used in www/tracker/include/ArtifactTypeHtml.class.php
     * 
     * @param params:hook parameters
     */
    function tracker_graphic_report_admin_header($params) {
        $request = HTTPRequest::instance();
        if ($request->valid(new Valid_GroupId())) { 
            echo ' | <a href="/tracker/admin/?func=reportgraphic&group_id='.$request->get('group_id').'&atid='.$request->get('atid').'">'.$GLOBALS['Language']->getText('plugin_graphontrackers_admin_menu','graphic_report').'</a>';
        }
    }
    /**
     *  Hook to copy graphic reports afer trackers reports are copied, when trackers are created.
     *  Used in src/common/tracker/ArtifactTypeFactory.class.php
     * 
     * @param params:hook parameters
     */
    function copy_graphical_reports($params){
        require_once('data-access/GraphOnTrackers_ReportFactory.class.php');
    
        $atid_source=$params['atid_source'];
        $atid_dest=$params['atid_dest'];

        $rf = new GraphOnTrackers_ReportFactory();
        $rf->copyReports($atid_source, $atid_dest);
    }
    
    /**
     *  Hook to delete graphic reports afer tracker reports are deleted, when trackers are deleted.
     *  Used in src/common/tracker/ArtifactTypeFactory.class.php
     * 
     * @param params:hook parameters
     */
    function delete_graphical_reports($params){
        require_once('data-access/GraphOnTrackers_Report.class.php');
        $atid=$params['atid'];
        $sql = "SELECT report_graphic_id FROM plugin_graphontrackers_report_graphic WHERE group_artifact_id='".db_ei($atid)."'";
        $res = db_query($sql);
        while ($report_array = db_fetch_array($res)) {
            $report_graphic_id = db_ei($report_array["report_graphic_id"]);
            $gr  = new GraphOnTrackers_Report($report_graphic_id);
            $gr->delete();        
        }
    }
}
