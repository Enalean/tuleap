<?php
/* 
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
require_once('common/include/HTTPRequest.class.php');
require_once('common/plugin/Plugin.class.php');
require_once('GraphOnTrackersV5_Renderer.class.php');



class GraphOnTrackersV5Plugin extends Plugin {

    const RENDERER_TYPE = 'plugin_graphontrackersv5';
    
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
    function GraphOnTrackersV5Plugin($id) {
        $this->Plugin($id);
        $this->setScope(Plugin::SCOPE_PROJECT);
        
        $this->_addHook('cssfile',                           'cssFile',                           false);
        
        //Tracker report renderer
        $this->_addHook('tracker_report_renderer_instance',  'tracker_report_renderer_instance',  false);
        $this->_addHook('tracker_report_renderer_from_xml',  'tracker_report_renderer_from_xml', false);
        $this->_addHook('tracker_report_add_renderer' ,      'tracker_report_add_renderer',       false);
        $this->_addHook('tracker_report_create_renderer' ,      'tracker_report_create_renderer',       false);
        $this->_addHook('tracker_report_renderer_types' ,    'tracker_report_renderer_types',     false);
        $this->_addHook('trackers_get_renderers' ,    'trackers_get_renderers',     false);
        
        //Widgets
        $this->_addHook('widget_instance',                   'widget_instance',                   false);
        $this->_addHook('widgets',                           'widgets',                           false);
        $this->_addHook('default_widgets_for_new_owner',     'default_widgets_for_new_owner',     false);
        
        $this->_addHook('graphontrackersv5_load_chart_factories', 'graphontrackersv5_load_chart_factories', false);
        
        $this->_addHook('ArtifactTracker_created', 'copy_graphical_reports', false);
        $this->_addHook('ArtifactTracker_deleted', 'delete_graphical_reports', false);
        $this->allowedForProject = array();
    }

    
    /**
     * This hook ask to create a new instance of a renderer
     *
     * @param mixed instance Output parameter. must contain the new instance
     * @param string type the type of the new renderer
     * @param array row the base properties identifying the renderer (id, name, description, rank)
     * @param Report report the report
     *
     * @return void
     */
    public function tracker_report_renderer_instance($params) {
        if ($params['type'] == self::RENDERER_TYPE) {
            require_once('GraphOnTrackersV5_Renderer.class.php');
            $params['instance'] = new GraphOnTrackersV5_Renderer(
                $params['row']['id'],
                $params['report'],
                $params['row']['name'],
                $params['row']['description'],
                $params['row']['rank'],
                $this
            );
            if ($params['store_in_session']) {
                $params['instance']->initiateSession();
            }
            $f = GraphOnTrackersV5_ChartFactory::instance();
            if (isset($params['row']['charts']) && isset($params['row']['mapping'])) {                
                $charts = array();
                foreach ($params['row']['charts']->chart as $chart) {
                   $charts[] = $f->getInstanceFromXML($chart, $params['instance'], $params['row']['mapping'], $params['store_in_session']);
                }            
            } else {
                $charts = $f->getCharts($params['instance'], $params['store_in_session']);
            }
            $params['instance']->setCharts($charts);
        }
    }
    
    /**
     * This hook ask to create a new instance of a renderer from XML
     *
     * @param mixed instance Output row. must contain the new instance
     * @param string type the type of the new renderer
     * @param array xml describing the renderer
     * @param Report report the report
     *
     * @return void
     */
    public function tracker_report_renderer_from_xml($params) {
        if ($params['type'] == self::RENDERER_TYPE) {
            require_once('GraphOnTrackersV5_Renderer.class.php');
            $params['row']['id'] = 0;
            $params['row']['name'] = (string)$params['xml']->name;
            $params['row']['description'] = (string)$params['xml']->description;
            $params['row']['rank'] = (int)$params['xml']->rank;   
            $params['row']['charts'] = $params['xml']->charts; 
            $params['row']['mapping'] = $params['mapping'];  
        }
    }
    
    
    /**
     * This hook says that a new renderer has been added to a report session
     * Maybe it is time to set default specific parameters of the renderer?
     *
     * @param int renderer_id the id of the new renderer
     * @param string type the type of the new renderer
     * @param Report report the report
     */
    public function tracker_report_add_renderer($params) {
        if ($params['type'] == self::RENDERER_TYPE) {
            //Nothing to do for now
        }
    }

    /**
     * This hook says that a new renderer has been added to a report and therefore must be created into db
     * Maybe it is time to set default specific parameters of the renderer?
     *
     * @param int renderer_id the id of the new renderer
     * @param string type the type of the new renderer
     * @param Report report the report
     */
    public function tracker_report_create_renderer($params) {
        if ($params['type'] == self::RENDERER_TYPE) {
            //Nothing to do for now
        }
    }

    /**
     * This hook ask for types of report renderer provided by the listener
     * 
     * @param array types Output parameter. Expected format: $types['my_type'] => 'Label of the type'
     */
    public function tracker_report_renderer_types($params) {
        $params['types'][self::RENDERER_TYPE] = $GLOBALS['Language']->getText('plugin_tracker_report','charts');
    }
    
     /**
     * This hook adds a  GraphOnTrackersV5_Renderer in a renderers array
     * 
     * @param array types Output parameter. Expected format: $types['my_type'] => 'Label of the type'
     */
    public function trackers_get_renderers($params) {
        if ($params['renderer_type'] == 'plugin_graphontrackersv5') {
                    
            $params['renderers'][$params['renderer_key']] = new GraphOnTrackersV5_Renderer(
                    $params['renderer_key'],
                    $params['report'],
                    $params['name'],
                    $params['description'],
                    $params['rank'],
                    $this
            );
            $params['renderers'][$params['renderer_key']]->initiateSession();
        }
    }    
    

    /**
     * Search for an instance of a specific widget
     * @param (in) string 'widget' => the name of the widget, eg: 'mydocman'
     * @param (out) Widget 'instance' => the instance of the widget
     */
    public function widget_instance($params) {
        switch ($params['widget']) {
            case 'my_plugin_graphontrackersv5_chart':
                require_once('GraphOnTrackersV5_Widget_MyChart.class.php');
                $params['instance'] = new GraphOnTrackersV5_Widget_MyChart();
                break;
            case 'project_plugin_graphontrackersv5_chart':
                require_once('GraphOnTrackersV5_Widget_ProjectChart.class.php');
                $params['instance'] = new GraphOnTrackersV5_Widget_ProjectChart();
                break;
            default:
                break;
        }
    }
    
    /**
     * Ask for provided widgets.
     * @param (in) string 'owner_type' => the type of the "owner" (user, project, ...)
     * @param (in/out) array 'codendi_widgets' => a collection of 'internal' widget names
     * @param (in/out) array 'external_widgets' => the same but external
     */
    public function widgets($params) {
        require_once('common/widget/WidgetLayoutManager.class.php');
        switch ($params['owner_type']) {
            case WidgetLayoutManager::OWNER_TYPE_USER:
                $params['codendi_widgets'][] = 'my_plugin_graphontrackersv5_chart';
                break;
            case WidgetLayoutManager::OWNER_TYPE_GROUP:
                $params['codendi_widgets'][] = 'project_plugin_graphontrackersv5_chart';
                break;
            default:
                break;
        }
    }
    
    /**
     * Ask for default widgets instanciated for new users or new projects or new... etc.
     * @param (in) string 'owner_type' => the type of the "owner" (user, project, ...)
     * @param (in/out) array 'widgets' => a collection of widget descriptions provided
     *                                    by the listeners. The description of a widget
     *                                    is : {name: 'mydocman', column: 1, rank: 2}
     *                                    Where name is the name of the widget,
     *                                    And column is the default column position
     *                                    And rank is the default rank of the widget.
     */     
    public function default_widgets_for_new_owner($params) {
        //no default widget
    }
    
    
    /**
     * function to get plugin info
     *
     */
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'GraphOnTrackersV5PluginInfo')) {
            require_once('GraphOnTrackersV5PluginInfo.class.php');
            $this->pluginInfo =& new GraphOnTrackersV5PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /**
     * Return true if current project has the right to use this plugin.
     */
    function isAllowed() {
        $request =& HTTPRequest::instance();
        $group_id = (int) $request->get('group_id');
        if(!isset($this->allowedForProject[$group_id])) {
            $pM =& PluginManager::instance();
            $this->allowedForProject[$group_id] = $pM->isPluginAllowedForProject($this, $group_id);
        }
        return $this->allowedForProject[$group_id];
    }
    
    function cssFile($params) {
        // Only show the stylesheet if we're actually in the Docman pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], TRACKER_BASE_URL.'/') === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />'."\n";
        }
    }
    
    function graphontrackersv5_load_chart_factories($params) {
        require_once('data-access/GraphOnTrackersV5_Chart_Bar.class.php');
        require_once('data-access/GraphOnTrackersV5_Chart_Pie.class.php');
        require_once('data-access/GraphOnTrackersV5_Chart_Gantt.class.php');
        $params['factories']['pie'] = array(
            'chart_type'      => 'pie',
            'chart_classname' => 'GraphOnTrackersV5_Chart_Pie',
            'icon'            => $this->getThemePath().'/images/chart_pie.png',
            'title'           => $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report','pie'),
        );
        $params['factories']['bar'] = array(
            'chart_type'      => 'bar',
            'chart_classname' => 'GraphOnTrackersV5_Chart_Bar',
            'icon'            => $this->getThemePath().'/images/chart_bar.png',
            'title'           => $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report','bar'),
        );
        $params['factories']['gantt'] = array(
            'chart_type'      => 'gantt',
            'chart_classname' => 'GraphOnTrackersV5_Chart_Gantt',
            'icon'            => $this->getThemePath().'/images/chart_gantt.png',
            'title'           => $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report','gantt'),
        );
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
                require_once('html-generators/GraphicEngineHtmlV5.class.php');
                require_once('data-access/GraphOnTrackersV5_Report.class.php');
                
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
                    if ($report = GraphOnTrackersV5_Report::create($atid, 
                                    $user_id, 
                                    $request->get('rep_name'), 
                                    $request->get('rep_desc'), 
                                    $rep_scope)) {
                        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report','new_created_report'));
                        $GLOBALS['Response']->redirect('/tracker/admin/?func=reportgraphic&group_id='.$report->getGroupId().'&atid='.$report->getAtid().'&report_graphic_id='.$report->getId());
                    }
                } else {
                    $report_graphic_id = $request->getValidated('report_graphic_id', 'uint', 0);
                    
                    $gr  = new GraphOnTrackersV5_Report($report_graphic_id);
                    
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
                                        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report','updated_report'));
                                    } else {
                                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report','not_updated_report').': '.$gr->getErrorMessage());
                                    }
                                }
                            }
                        } else if (is_array($request->get('delete_chart'))) {
                            $chart_id_to_delete = (int)key($request->get('delete_chart'));
                            $gr->deleteChart($chart_id_to_delete);
                            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report','updated_report'));
                            $GLOBALS['Response']->redirect('/tracker/admin/?func=reportgraphic&group_id='.$gr->getGroupId().'&atid='.$gr->getAtid().'&report_graphic_id='.$gr->getId());
                        } else if ($request->exist('update_chart') && is_array($request->get('chart'))) {
                            $row = $request->get('chart');
                            if (isset($row['id'])) {
                                $chart_to_edit = $gr->getChart($row['id']);
                                if ($chart_to_edit->update($row)) {
                                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report','updated_report'));
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
                            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report','report_deleted'));
                        }
                    }
                }
                
                $GLOBALS['ath']->adminHeader(array ('title'=> $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report','report_mgmt'),
                    'help' => 'TrackerAdministration.html#GraphTrackerReportSetting'));
                
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
     *  Hook to copy graphic reports afer trackers reports are copied, when trackers are created.
     *  Used in src/common/tracker/ArtifactTrackerFactory.class.php
     * 
     * @param params:hook parameters
     */
    function copy_graphical_reports($params){
        require_once('data-access/GraphOnTrackersV5_ReportFactory.class.php');
    
        $atid_source=$params['atid_source'];
        $atid_dest=$params['atid_dest'];

        $rf = new GraphOnTrackersV5_ReportFactory();
        $rf->copyReports($atid_source, $atid_dest);
    }
    
    /**
     *  Hook to delete graphic reports afer tracker reports are deleted, when trackers are deleted.
     *  Used in src/common/tracker/ArtifactTrackerFactory.class.php
     * 
     * @param params:hook parameters
     */
    function delete_graphical_reports($params){
        require_once('data-access/GraphOnTrackersV5_Report.class.php');
        $atid=$params['atid'];
        $sql = "SELECT report_graphic_id FROM plugin_graphontrackersv5_report_graphic WHERE group_artifact_id='".db_ei($atid)."'";
        $res = db_query($sql);
        while ($report_array = db_fetch_array($res)) {
            $report_graphic_id = db_ei($report_array["report_graphic_id"]);
            $gr  = new GraphOnTrackersV5_Report($report_graphic_id);
            $gr->delete();        
        }
    }
}

?>
