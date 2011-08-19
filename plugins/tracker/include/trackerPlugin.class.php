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

define('TRACKER_BASE_URL', '/plugins/tracker');

/**
 * trackerPlugin
 */
class trackerPlugin extends Plugin {
    
    /**
     * @var bool True if the plugin should be disabled for all projects on installation
     *
     * Usefull only for plugins with scope == SCOPE_PROJECT
     */
    public $isRestrictedByDefault = true; //until the plugin becomes stable
    
    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        $this->_addHook('cssfile', 'cssFile', false);
        $this->_addHook(Event::GET_AVAILABLE_REFERENCE_NATURE, 'get_available_reference_natures', false);
        $this->_addHook('ajax_reference_tooltip', 'ajax_reference_tooltip', false);
        $this->_addHook(Event::SERVICE_CLASSNAMES, 'service_classnames', false);
        $this->_addHook(Event::COMBINED_SCRIPTS,   'combined_scripts',   false);
        $this->_addHook(Event::JAVASCRIPT,         'javascript',         false);
        $this->_addHook(Event::TOGGLE,             'toggle',             false);
        $this->_addHook('permission_get_name',               'permission_get_name',               false);
        $this->_addHook('permission_get_object_type',        'permission_get_object_type',        false);
        $this->_addHook('permission_get_object_name',        'permission_get_object_name',        false);
        $this->_addHook('permission_get_object_fullname',    'permission_get_object_fullname',    false);
        $this->_addHook('permission_user_allowed_to_change', 'permission_user_allowed_to_change', false);
        $this->_addHook('permissions_for_ugroup',            'permissions_for_ugroup',            false);
    }
    
    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'trackerPluginInfo')) {
            include_once('trackerPluginInfo.class.php');
            $this->pluginInfo = new trackerPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function cssFile($params) {
        // Only show the stylesheet if we're actually in the tracker pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/print.css" media="print" />';
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
                '/plugins/tracker/scripts/TrackerFormElementFieldPermissions.js',
                '/plugins/tracker/scripts/TrackerFieldDependencies.js',
            )
        );
    }
    
    public function javascript($params) {
        include $GLOBALS['Language']->getContent('script_locale', null, 'tracker');
    }
    
    public function toggle($params) {
        if (strpos($params['id'], 'tracker_report_query_') === 0) {
            require_once('Tracker/Report/Tracker_ReportFactory.class.php');
            $report_id = (int)substr($params['id'], strlen('tracker_report_query_'));
            $report_factory = Tracker_ReportFactory::instance();
            if (($report = $report_factory->getReportById($report_id, $params['user']->getid())) && $report->userCanUpdate($params['user'])) {
                $report->toggleQueryDisplay();
                $report_factory->save($report);
            }
            $params['done'] = true;
        }
    }
    
    function permission_get_name($params) {
        if (!$params['name']) {
            switch($params['permission_type']) {
            case 'PLUGIN_TRACKER_FIELD_SUBMIT':
                $params['name'] = $GLOBALS['Language']->getText('project_admin_permissions','tracker_field_submit');
                break;
            case 'PLUGIN_TRACKER_FIELD_READ':
                $params['name'] = $GLOBALS['Language']->getText('project_admin_permissions','tracker_field_read');
                break;
            case 'PLUGIN_TRACKER_FIELD_UPDATE':
                $params['name'] = $GLOBALS['Language']->getText('project_admin_permissions','tracker_field_update');
                break;
            case 'PLUGIN_TRACKER_ACCESS_SUBMITTER':
                $params['name'] = $GLOBALS['Language']->getText('project_admin_permissions','tracker_submitter_access');
                break;
            case 'PLUGIN_TRACKER_ACCESS_ASSIGNEE':
                $params['name'] = $GLOBALS['Language']->getText('project_admin_permissions','tracker_assignee_access');
                break;
            case 'PLUGIN_TRACKER_ACCESS_FULL':
                $params['name'] = $GLOBALS['Language']->getText('project_admin_permissions','tracker_full_access');
                break;
            case 'PLUGIN_TRACKER_ADMIN':
                $params['name'] = $GLOBALS['Language']->getText('project_admin_permissions','tracker_admin');
                break;
            case 'PLUGIN_TRACKER_ARTIFACT_ACCESS':
                $params['name'] = $GLOBALS['Language']->getText('project_admin_permissions','tracker_artifact_access');
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
                    echo '<TD>'.$GLOBALS['Language']->getText('project_admin_editugroup','tracker') 
                    .' <a href="'.TRACKER_BASE_URL.'/?tracker='.$atid.'&func=admin-perms-tracker">'
                    .$objname.'</a></TD>';
                    
                } else if (strpos($params['permission_type'], 'PLUGIN_TRACKER_FIELD') === 0) {
                    $field = Tracker_FormElementFactory::instance()->getFormElementById($atid);
                    $tracker_id = $field->getTrackerId();
                    
                    echo '<TD>'.$GLOBALS['Language']->getText('project_admin_editugroup','tracker') 
                    .' <a href="'.TRACKER_BASE_URL.'/?tracker='.$tracker_id.'&func=admin-perms-fields">'
                    .$objname.'</a></TD>';
                    
                } else if ($params['permission_type'] == 'PLUGIN_TRACKER_ARTIFACT_ACCESS') {
                    echo '<td>'. $hp->purify($objname, CODENDI_PURIFIER_BASIC) .'</td>';
                    
                } else if ($params['permission_type'] == 'PLUGIN_TRACKER_WORKFLOW_TRANSITION') {
                    $transition = TransitionFactory::instance()->getTransition($atid);
                    $tracker_id = $transition->getWorkflow()->getTrackerId();
                    $edit_transition = $transition->getFieldValueFrom().'_'.$transition->getFieldValueTo();
                    echo '<TD><a href="'.TRACKER_BASE_URL.'/?tracker='.$tracker_id.'&func=admin-workflow&edit_transition='.$edit_transition.'">'.$objname.'</a></TD>';
                }
            }
        }
    }
    
    var $_cached_permission_user_allowed_to_change;
    function permission_user_allowed_to_change($params) {
        if (!$params['allowed']) {
            if (in_array($params['permission_type'], array('PLUGIN_TRACKER_ADMIN', 'PLUGIN_TRACKER_ACCESS_FULL', 'PLUGIN_TRACKER_ACCESS_SUBMITTER', 'PLUGIN_TRACKER_ACCESS_ASSIGNEE', 'PLUGIN_TRACKER_FIELD_SUBMIT', 'PLUGIN_TRACKER_FIELD_READ', 'PLUGIN_TRACKER_FIELD_UPDATE', 'PLUGIN_TRACKER_ARTIFACT_ACCESS'))) {
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
                            break;
                    }
                }
            }
            if (isset($this->_cached_permission_user_allowed_to_change[$type][$object_id])) {
                $params['allowed'] = $this->_cached_permission_user_allowed_to_change[$type][$object_id];
            }
        }
    }
    
    public function get_available_reference_natures($params) {
        require_once('Tracker/Artifact/Tracker_Artifact.class.php');
        $natures = array(Tracker_Artifact::REFERENCE_NATURE => array('keyword' => 'artifact',
                                                                     'label'   => 'Artifact Tracker v5'));
        $params['natures'] = array_merge($params['natures'], $natures);
    }
    
    public function ajax_reference_tooltip($params) {
        if ($params['reference']->getServiceShortName() == 'plugin_tracker') {
            if ($params['reference']->getNature() == Tracker_Artifact::REFERENCE_NATURE) {
                $user = UserManager::instance()->getCurrentUser();
                $aid = $params['val'];
                require_once('Tracker/Artifact/Tracker_ArtifactFactory.class.php');
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
}

?>
