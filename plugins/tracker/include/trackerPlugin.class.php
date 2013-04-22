<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('common/plugin/Plugin.class.php');
require_once 'constants.php';
require_once 'autoload.php';
require_once 'common/XmlValidator/XmlValidator.class.php';

/**
 * trackerPlugin
 */
class trackerPlugin extends Plugin {
    
    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        
        $this->_addHook('cssfile',                             'cssFile',                           false);
        $this->_addHook('javascript_file',                     'javascript_file',                   false);
        $this->_addHook(Event::GET_AVAILABLE_REFERENCE_NATURE, 'get_available_reference_natures',   false);
        $this->_addHook(Event::GET_ARTIFACT_REFERENCE_GROUP_ID,'get_artifact_reference_group_id',   false);
        $this->_addHook(Event::BUILD_REFERENCE,                'build_reference',                   false);
        $this->_addHook('ajax_reference_tooltip',              'ajax_reference_tooltip',            false);
        $this->_addHook(Event::SERVICE_CLASSNAMES,             'service_classnames',                false);
        $this->_addHook(Event::COMBINED_SCRIPTS,               'combined_scripts',                  false);
        $this->_addHook(Event::JAVASCRIPT,                     'javascript',                        false);
        $this->_addHook(Event::TOGGLE,                         'toggle',                            false);
        $this->_addHook(Event::SERVICE_PUBLIC_AREAS,           'service_public_areas',              false);
        $this->_addHook('permission_get_name',                 'permission_get_name',               false);
        $this->_addHook('permission_get_object_type',          'permission_get_object_type',        false);
        $this->_addHook('permission_get_object_name',          'permission_get_object_name',        false);
        $this->_addHook('permission_get_object_fullname',      'permission_get_object_fullname',    false);
        $this->_addHook('permission_user_allowed_to_change',   'permission_user_allowed_to_change', false);
        $this->_addHook('permissions_for_ugroup',              'permissions_for_ugroup',            false);

        $this->_addHook('url_verification_instance',           'url_verification_instance',         false);
        
        $this->_addHook('widget_instance',                     'widget_instance',                   false);
        $this->_addHook('widgets',                             'widgets',                           false);
        $this->_addHook('project_is_deleted',                  'project_is_deleted',                false);
        $this->_addHook('register_project_creation',           'register_project_creation',         false);
        $this->_addHook('codendi_daily_start',                 'codendi_daily_start',               false);
        $this->_addHook('fill_project_history_sub_events',     'fillProjectHistorySubEvents',       false);
        $this->_addHook(Event::SOAP_DESCRIPTION,               'soap_description',                  false);
        $this->_addHook(Event::EXPORT_XML_PROJECT);
        $this->_addHook(Event::IMPORT_XML_PROJECT,              'importTrackersFromXml',            false);
    }
    
    public function getHooksAndCallbacks() {
        if (defined('AGILEDASHBOARD_BASE_DIR')) {
            $this->_addHook(AGILEDASHBOARD_EVENT_ADDITIONAL_PANES_ON_MILESTONE, 'agiledashboard_event_additional_panes_on_milestone', false);
        }
        return parent::getHooksAndCallbacks();
    }
    
    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'trackerPluginInfo')) {
            include_once('trackerPluginInfo.class.php');
            $this->pluginInfo = new trackerPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    public function javascript_file() {        
        if (strpos($_SERVER['REQUEST_URI'], TRACKER_BASE_URL.'/') === 0) {
            echo '<script type="text/javascript" src="/plugins/tracker/scripts/TrackerSearchTreeView.js"></script>'."\n";
            // Cannot be moved in combined (it conflicts with same implementation in tracker v3)
            echo '<script type="text/javascript" src="/plugins/tracker/scripts/TrackerFieldDependencies.js"></script>'."\n";
            echo '<script type="text/javascript" src="/plugins/tracker/scripts/TrackerRichTextEditor.js"></script>'."\n";
            echo '<script type="text/javascript" src="/plugins/tracker/scripts/artifactChildren.js"></script>'."\n";
            echo '<script type="text/javascript" src="/plugins/tracker/scripts/load-artifactChildren.js"></script>'."\n";
        }
    }
    
    public function cssFile($params) {
        $include_tracker_css_file = false;
        EventManager::instance()->processEvent(TRACKER_EVENT_INCLUDE_CSS_FILE, array('include_tracker_css_file' => &$include_tracker_css_file));
        // Only show the stylesheet if we're actually in the tracker pages.
        // This stops styles inadvertently clashing with the main site.
        if ($include_tracker_css_file ||
            strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/my/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/projects/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0 ) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/print.css" media="print" />';
            echo '<!--[if lte IE 8]><link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/ieStyle.css" /><![endif]-->';
        }
    }
    
    public function service_classnames($params) {
        include_once 'ServiceTracker.class.php';
        $params['classnames']['plugin_tracker'] = 'ServiceTracker';
    }
    
    public function combined_scripts($params) {
        $params['scripts'] = array_merge(
            $params['scripts'],
            array(
                '/plugins/tracker/scripts/TrackerReports.js',
                '/plugins/tracker/scripts/TrackerBinds.js',
                '/plugins/tracker/scripts/ReorderColumns.js',
                '/plugins/tracker/scripts/TrackerTextboxLists.js',
                '/plugins/tracker/scripts/TrackerAdminFields.js',
                '/plugins/tracker/scripts/TrackerArtifact.js',
                '/plugins/tracker/scripts/TrackerArtifactLink.js',
                '/plugins/tracker/scripts/TrackerCreate.js',
                '/plugins/tracker/scripts/TrackerFormElementFieldPermissions.js',
                '/plugins/tracker/scripts/TrackerDateReminderForms.js',
            )
        );
    }
    
    public function javascript($params) {
        // TODO: Move this in ServiceTracker::displayHeader()
        include $GLOBALS['Language']->getContent('script_locale', null, 'tracker');
        echo PHP_EOL;
        echo "codendi.tracker = codendi.tracker || { };".PHP_EOL;
        echo "codendi.tracker.base_url = '". TRACKER_BASE_URL ."/';".PHP_EOL;
    }
    
    public function toggle($params) {
        if ($params['id'] === 'tracker_report_query_0') {
            Toggler::togglePreference($params['user'], $params['id']);
            $params['done'] = true;
        } else if (strpos($params['id'], 'tracker_report_query_') === 0) {
            $report_id = (int)substr($params['id'], strlen('tracker_report_query_'));
            $report_factory = Tracker_ReportFactory::instance();
            if (($report = $report_factory->getReportById($report_id, $params['user']->getid())) && $report->userCanUpdate($params['user'])) {
                $report->toggleQueryDisplay();
                $report_factory->save($report);
            }
            $params['done'] = true;
        }
    }
    
    public function agiledashboard_event_additional_panes_on_milestone($params) {
        $artifact = $params['milestone']->getArtifact();
        $user     = $params['user'];
        $burndown_field = $artifact->getABurndownField($user);
        if ($burndown_field) {
            $pane_info = new Tracker_Artifact_Burndown_PaneInfo($params['milestone']);
            if ($params['request']->get('pane') == Tracker_Artifact_Burndown_PaneInfo::IDENTIFIER) {
                $pane_info->setActive(true);
                $params['active_pane'] = new Tracker_Artifact_Burndown_Pane(
                        $pane_info,
                        $artifact,
                        $burndown_field,
                        $params['user']
                );
            }
            $params['panes'][] = $pane_info;
        }
    }
    
   /**
    * Project creation hook
    *
    * @param Array $params
    */
    function register_project_creation($params) {
        $tm = new TrackerManager();
        $tm->duplicate($params['template_id'], $params['group_id'], $params['ugroupsMapping']);

    }
    
    function permission_get_name($params) {
        if (!$params['name']) {
            switch($params['permission_type']) {
            case 'PLUGIN_TRACKER_FIELD_SUBMIT':
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_field_submit');
                break;
            case 'PLUGIN_TRACKER_FIELD_READ':
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_field_read');
                break;
            case 'PLUGIN_TRACKER_FIELD_UPDATE':
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_field_update');
                break;
            case 'PLUGIN_TRACKER_ACCESS_SUBMITTER':
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_submitter_access');
                break;
            case 'PLUGIN_TRACKER_ACCESS_ASSIGNEE':
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_assignee_access');
                break;
            case 'PLUGIN_TRACKER_ACCESS_FULL':
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_full_access');
                break;
            case 'PLUGIN_TRACKER_ADMIN':
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_admin');
                break;
            case 'PLUGIN_TRACKER_ARTIFACT_ACCESS':
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_artifact_access');
                break;
            case 'PLUGIN_TRACKER_WORKFLOW_TRANSITION':
                $params['name'] = $GLOBALS['Language']->getText('workflow_admin','permissions_transition');
                break;
            default:
                break;
            }
        }
    }
    
    function permission_get_object_type($params) {
        $type = $this->getObjectTypeFromPermissions($params);
        if ($type != false) {
            $params['object_type'] = $type;
        }
    }
    
    function getObjectTypeFromPermissions($params) {
        switch($params['permission_type']) {
            case 'PLUGIN_TRACKER_FIELD_SUBMIT':
            case 'PLUGIN_TRACKER_FIELD_READ':
            case 'PLUGIN_TRACKER_FIELD_UPDATE':
                return 'field';
            case 'PLUGIN_TRACKER_ACCESS_SUBMITTER':
            case 'PLUGIN_TRACKER_ACCESS_ASSIGNEE':
            case 'PLUGIN_TRACKER_ACCESS_FULL':
            case 'PLUGIN_TRACKER_ADMIN':
                return 'tracker';
            case 'PLUGIN_TRACKER_ARTIFACT_ACCESS':
                return 'artifact';
            case 'PLUGIN_TRACKER_WORKFLOW_TRANSITION':
                return 'workflow transition';
        }
        return false;
    }
    
    function permission_get_object_name($params) {
        if (!$params['object_name']) {
            $type = $this->getObjectTypeFromPermissions($params);
            if (in_array($params['permission_type'], array('PLUGIN_TRACKER_ADMIN', 'PLUGIN_TRACKER_ACCESS_FULL', 'PLUGIN_TRACKER_ACCESS_SUBMITTER', 'PLUGIN_TRACKER_ACCESS_ASSIGNEE', 'PLUGIN_TRACKER_FIELD_SUBMIT', 'PLUGIN_TRACKER_FIELD_READ', 'PLUGIN_TRACKER_FIELD_UPDATE', 'PLUGIN_TRACKER_ARTIFACT_ACCESS'))) {
                $object_id = $params['object_id'];
                if ($type == 'tracker') {
                    $ret = (string)$object_id;
                    if ($tracker = TrackerFactory::instance()->getTrackerById($object_id)) {
                        $params['object_name'] = $tracker->getName();
                    }
                } else if ($type == 'field') {
                    $ret = (string)$object_id;
                    if ($field = Tracker_FormElementFactory::instance()->getFormElementById($object_id)) {
                        $ret = $field->getLabel() .' ('. $field->getTracker()->getName() .')';
                    }
                    $params['object_name'] =  $ret;
                } else if ($type == 'artifact') {
                    $ret = (string)$object_id;
                    if ($a  = Tracker_ArtifactFactory::instance()->getArtifactById($object_id)) {
                        $ret = 'art #'. $a->getId();
                        $semantics = $a->getTracker()
                                       ->getTrackerSemanticManager()
                                       ->getSemantics();
                        if (isset($semantics['title'])) {
                            if ($field = Tracker_FormElementFactory::instance()->getFormElementById($semantics['title']->getFieldId())) {
                                $ret .= ' - '. $a->getValue($field)->getText();
                            }
                        }
                    }
                    $params['object_name'] =  $ret;
                }
            }
        }
    }
    
    function permission_get_object_fullname($params) {
        $this->permission_get_object_name($params);
    }
    
    function permissions_for_ugroup($params) {
        if (!$params['results']) {
            
            $group_id = $params['group_id'];
            $hp = Codendi_HTMLPurifier::instance();
            $atid = $params['object_id'];
            $objname = $params['objname'];
            
            if (in_array($params['permission_type'], array('PLUGIN_TRACKER_ADMIN', 'PLUGIN_TRACKER_ACCESS_FULL', 'PLUGIN_TRACKER_ACCESS_SUBMITTER', 'PLUGIN_TRACKER_ACCESS_ASSIGNEE', 'PLUGIN_TRACKER_FIELD_SUBMIT', 'PLUGIN_TRACKER_FIELD_READ', 'PLUGIN_TRACKER_FIELD_UPDATE', 'PLUGIN_TRACKER_ARTIFACT_ACCESS', 'PLUGIN_TRACKER_WORKFLOW_TRANSITION'))) {
                if (strpos($params['permission_type'], 'PLUGIN_TRACKER_ACCESS') === 0 || $params['permission_type'] === 'PLUGIN_TRACKER_ADMIN') {
                    $params['results'] = $GLOBALS['Language']->getText('project_admin_editugroup','tracker') 
                    .' <a href="'.TRACKER_BASE_URL.'/?tracker='.$atid.'&func=admin-perms-tracker">'
                    .$objname.'</a>';
                    
                } else if (strpos($params['permission_type'], 'PLUGIN_TRACKER_FIELD') === 0) {
                    $field = Tracker_FormElementFactory::instance()->getFormElementById($atid);
                    $tracker_id = $field->getTrackerId();
                    
                    $params['results'] = $GLOBALS['Language']->getText('project_admin_editugroup','tracker') 
                    .' <a href="'.TRACKER_BASE_URL.'/?tracker='.$tracker_id.'&func=admin-perms-fields">'
                    .$objname.'</a>';
                    
                } else if ($params['permission_type'] == 'PLUGIN_TRACKER_ARTIFACT_ACCESS') {
                    $params['results'] = $hp->purify($objname, CODENDI_PURIFIER_BASIC);
                    
                } else if ($params['permission_type'] == 'PLUGIN_TRACKER_WORKFLOW_TRANSITION') {
                    $transition = TransitionFactory::instance()->getTransition($atid);
                    $tracker_id = $transition->getWorkflow()->getTrackerId();
                    $edit_transition = $transition->getFieldValueFrom().'_'.$transition->getFieldValueTo();
                    $params['results'] = '<a href="'.TRACKER_BASE_URL.'/?'. http_build_query(
                        array(
                            'tracker'         => $tracker_id,
                            'func'            => Workflow::FUNC_ADMIN_TRANSITIONS,
                            'edit_transition' => $edit_transition
                        )
                    ).'">'.$objname.'</a>';
                }
            }
        }
    }
    
    var $_cached_permission_user_allowed_to_change;
    function permission_user_allowed_to_change($params) {
        if (!$params['allowed']) {
            $allowed = array(
                'PLUGIN_TRACKER_ADMIN', 
                'PLUGIN_TRACKER_ACCESS_FULL', 
                'PLUGIN_TRACKER_ACCESS_SUBMITTER', 
                'PLUGIN_TRACKER_ACCESS_ASSIGNEE', 
                'PLUGIN_TRACKER_FIELD_SUBMIT', 
                'PLUGIN_TRACKER_FIELD_READ', 
                'PLUGIN_TRACKER_FIELD_UPDATE', 
                'PLUGIN_TRACKER_ARTIFACT_ACCESS',
                'PLUGIN_TRACKER_WORKFLOW_TRANSITION',
            );
            if (in_array($params['permission_type'], $allowed)) {
                $group_id  = $params['group_id'];
                $object_id = $params['object_id'];
                $type      = $this->getObjectTypeFromPermissions($params);
                if (!isset($this->_cached_permission_user_allowed_to_change[$type][$object_id])) {
                    switch ($type) {
                        case 'tracker':
                            if ($tracker = TrackerFactory::instance()->getTrackerById($object_id)) {
                                $this->_cached_permission_user_allowed_to_change[$type][$object_id] = $tracker->userIsAdmin();
                            }
                            break;
                        case 'field':
                            if ($field = Tracker_FormElementFactory::instance()->getFormElementById($object_id)) {
                                $this->_cached_permission_user_allowed_to_change[$type][$object_id] = $field->getTracker()->userIsAdmin();
                            }
                            break;
                        case 'artifact':
                            if ($a  = Tracker_ArtifactFactory::instance()->getArtifactById($object_id)) {
                                //TODO: manage permissions related to field "permission on artifact"
                                $this->_cached_permission_user_allowed_to_change[$type][$object_id] = $a->getTracker()->userIsAdmin();
                            }
                        case 'workflow transition':
                            if ($transition = TransitionFactory::instance()->getTransition($object_id)) {
                                $this->_cached_permission_user_allowed_to_change[$type][$object_id] = $transition->getWorkflow()->getTracker()->userIsAdmin();
                            }
                            break;
                    }
                }
                if (isset($this->_cached_permission_user_allowed_to_change[$type][$object_id])) {
                    $params['allowed'] = $this->_cached_permission_user_allowed_to_change[$type][$object_id];
                }
            }
        }
    }
    
    public function get_available_reference_natures($params) {
        $natures = array(Tracker_Artifact::REFERENCE_NATURE => array('keyword' => 'artifact',
                                                                     'label'   => 'Artifact Tracker v5'));
        $params['natures'] = array_merge($params['natures'], $natures);
    }
    
    public function get_artifact_reference_group_id($params) {        
        $artifact = Tracker_ArtifactFactory::instance()->getArtifactByid($params['artifact_id']);
        if ($artifact) {
            $tracker = $artifact->getTracker();
            $params['group_id'] = $tracker->getGroupId();
        }
    }
    
    public function build_reference($params) {
        $row = $params['row'];
        $params['ref'] = new Reference($params['ref_id'],$row['keyword'],$row['description'],'/plugins'.$row['link'],
                                    $row['scope'],'plugin_tracker', Tracker_Artifact::REFERENCE_NATURE, $row['is_active'],$row['group_id']);
    }
    
    public function ajax_reference_tooltip($params) {
        if ($params['reference']->getServiceShortName() == 'plugin_tracker') {
            if ($params['reference']->getNature() == Tracker_Artifact::REFERENCE_NATURE) {
                $user = UserManager::instance()->getCurrentUser();
                $aid = $params['val'];
                if ($artifact = Tracker_ArtifactFactory::instance()->getArtifactByid($aid)) {
                    if ($artifact && $artifact->getTracker()->isActive()) {
                        echo $artifact->fetchTooltip($user);
                    } else {
                        echo $GLOBALS['Language']->getText('plugin_tracker_common_type', 'artifact_not_exist');
                    }
                }
            }
        }
    }
    
    public function url_verification_instance($params) {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            include_once 'Tracker/Tracker_URLVerification.class.php';
            $params['url_verification'] = new Tracker_URLVerification();
        }
    }

    /**
     * Hook: event raised when widget are instanciated
     * 
     * @param Array $params
     */
    public function widget_instance($params) {
        include_once 'Tracker/Widget/Tracker_Widget_MyArtifacts.class.php';
        include_once 'Tracker/Widget/Tracker_Widget_MyRenderer.class.php';
        include_once 'Tracker/Widget/Tracker_Widget_ProjectRenderer.class.php';
        
        switch ($params['widget']) {
            case Tracker_Widget_MyArtifacts::ID:
                $params['instance'] = new Tracker_Widget_MyArtifacts();
                break;
            case Tracker_Widget_MyRenderer::ID:
                $params['instance'] = new Tracker_Widget_MyRenderer();
                break;
            case Tracker_Widget_ProjectRenderer::ID:
                $params['instance'] = new Tracker_Widget_ProjectRenderer();
                break;
        }
    }

    /**
     * Hook: event raised when user lists all available widget
     *
     * @param Array $params
     */
    public function widgets($params) {
        include_once 'common/widget/WidgetLayoutManager.class.php';
        include_once 'Tracker/Widget/Tracker_Widget_MyArtifacts.class.php';
        include_once 'Tracker/Widget/Tracker_Widget_MyRenderer.class.php';
        include_once 'Tracker/Widget/Tracker_Widget_ProjectRenderer.class.php';
        
        switch ($params['owner_type']) {
            case WidgetLayoutManager::OWNER_TYPE_USER:
                $params['codendi_widgets'][] = Tracker_Widget_MyArtifacts::ID;
                $params['codendi_widgets'][] = Tracker_Widget_MyRenderer::ID;
                break;
            
            case WidgetLayoutManager::OWNER_TYPE_GROUP:
                $params['codendi_widgets'][] = Tracker_Widget_ProjectRenderer::ID;
                break;
        }
    }
    
    function service_public_areas($params) {
        if ($params['project']->usesService('plugin_tracker')) {
            $tf = TrackerFactory::instance();
            
            // Get the artfact type list
            $trackers = $tf->getTrackersByGroupId($params['project']->getGroupId());
            
            if ($trackers) {
                $entries = array();
                foreach($trackers as $t) {
                    if ($t->userCanView()) {
                        $entries[] = '<a href="'. TRACKER_BASE_URL .'/?tracker='. $t->id .'">'. $t->name .'</a>';
                    }
                }
                if ($entries) {
                    $area = '';
                    $area .= '<a href="'. TRACKER_BASE_URL .'/?group_id='. $params['project']->getGroupId() .'">';
                    $area .= $GLOBALS['HTML']->getImage('ic/clipboard-list.png');
                    $area .= ' '. $GLOBALS['Language']->getText('plugin_tracker', 'service_lbl_key');
                    $area .= '</a>';
                    
                    $area .= '<ul><li>'. implode('</li><li>', $entries) .'</li></ul>';
                    $params['areas'][] = $area;
                }
            }
        }
    }

    /**
     * When a project is deleted, we delete all its trackers
     *
     * @param mixed $params ($param['group_id'] the ID of the deleted project)
     *
     * @return void
     */
    function project_is_deleted($params) {
        $groupId = $params['group_id'];
        if ($groupId) {
            include_once 'Tracker/TrackerManager.class.php';
            $trackerManager = new TrackerManager();
            $trackerManager->deleteProjectTrackers($groupId);
        }
    }

   /**
     * Process the nightly job to send reminder on artifact correponding to given criteria
     *
     * @param Array $params Hook params
     *
     * @return Void
     */
    public function codendi_daily_start($params) {
        include_once 'Tracker/TrackerManager.class.php';
        $trackerManager = new TrackerManager();
        return $trackerManager->sendDateReminder();
    }

    /**
     * Fill the list of subEvents related to tracker in the project history interface
     *
     * @param Array $params Hook params
     *
     * @return Void
     */
    public function fillProjectHistorySubEvents($params) {
        array_push($params['subEvents']['event_others'], 'tracker_date_reminder_add',
                                                         'tracker_date_reminder_edit',
                                                         'tracker_date_reminder_delete',
                                                         'tracker_date_reminder_sent'
        );
    }

    public function soap_description($params) {
        $params['end_points'][] = array(
            'title'       => 'Tracker',
            'wsdl'        => $this->getPluginPath().'/soap/?wsdl',
            'wsdl_viewer' => $this->getPluginPath().'/soap/view-wsdl',
            'changelog'   => $this->getPluginPath().'/soap/ChangeLog',
            'version'     => file_get_contents(dirname(__FILE__).'/../www/soap/VERSION'),
            'description' => 'Query and modify Trackers.',
        );
    }

    /**
     * @see Event::EXPORT_XML_PROJECT
     * @param array $params
     */
    public function export_xml_project($params) {
        $xml_content     = $params['into_xml']->addChild('trackers');
        $tracker_manager = new TrackerManager();
        $tracker_manager->exportToXml($params['project']->getID(), $xml_content);
    }

    /**
     *
     * @param array $params
     * @see Event::IMPORT_XML_PROJECT
     */
    public function importTrackersFromXml($params) {
        if (! isset($params['project']) ||
            ! isset($params['xml_content']) ||
            ! $params['project'] instanceof Project ||
            ! $params['xml_content'] instanceof SimpleXMLElement
            ) {
            throw new Exception('bad params for method');
        }

        $tracker_xml_import = new TrackerXmlImport(
            $params['project']->getId(),
            $params['xml_content'],
            TrackerFactory::instance(),
            EventManager::instance(),
            new Tracker_Hierarchy_Dao(),
            new XmlValidator()
        );

        $tracker_xml_import->import();
        
    }
}

?>
