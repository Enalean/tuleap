<?php

/*
 * Copyright (c) Xerox, 2011. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2011. Xerox Codendi Team.
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('common/plugin/Plugin.class.php');

define('TRACKER_BASE_URL', '/plugins/tracker');

/**
 * trackerPlugin
 */
class trackerPlugin extends Plugin {
	
	public function __construct($id) {
		parent::__construct($id);
		$this->setScope(self::SCOPE_PROJECT);
		$this->_addHook('cssfile', 'cssFile', false);
		$this->_addHook(Event::SERVICE_CLASSNAMES, 'service_classnames', false);
		$this->_addHook(Event::COMBINED_SCRIPTS, 'combined_scripts', false);
		$this->_addHook(Event::JAVASCRIPT,         'javascript',         false);
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
    
    function permission_get_name($params) {
        if (!$params['name']) {
            switch($params['permission_type']) {
                case 'PLUGIN_TRACKER_FIELD_SUBMIT':
		    $params['name'] = $Language->getText('project_admin_permissions','tracker_field_submit');
                    break;
	        case 'PLUGIN_TRACKER_FIELD_READ':
		    $params['name'] = $Language->getText('project_admin_permissions','tracker_field_read');
                    break;
	        case 'PLUGIN_TRACKER_FIELD_UPDATE':
		    $params['name'] = $Language->getText('project_admin_permissions','tracker_field_update');
                    break;
	        case 'PLUGIN_TRACKER_ACCESS_SUBMITTER':
		    $params['name'] = $Language->getText('project_admin_permissions','tracker_submitter_access');
                    break;
	        case 'PLUGIN_TRACKER_ACCESS_ASSIGNEE':
		    $params['name'] = $Language->getText('project_admin_permissions','tracker_assignee_access');
                    break;
	        case 'PLUGIN_TRACKER_ACCESS_FULL':
		    $params['name'] = $Language->getText('project_admin_permissions','tracker_full_access');
                    break;
	        case 'PLUGIN_TRACKER_ARTIFACT_ACCESS':
		    $params['name'] = $Language->getText('project_admin_permissions','tracker_artifact_access');
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
    	if (!$params['object_type']) {
    	    switch($params['permission_type']) {
    	    case 'PLUGIN_TRACKER_FIELD_SUBMIT':
    	    case 'PLUGIN_TRACKER_FIELD_READ':
    	    case 'PLUGIN_TRACKER_FIELD_UPDATE':
	    	    return 'field';
	    	case 'PLUGIN_TRACKER_ACCESS_SUBMITTER':
	    	case 'PLUGIN_TRACKER_ACCESS_ASSIGNEE':
	    	case 'PLUGIN_TRACKER_ACCESS_FULL':
	    	    return 'tracker';
	    	case 'PLUGIN_TRACKER_ARTIFACT_ACCESS':
	    	    return 'artifact';
	    	}
    	}
    	return false;
    }
    
    function permission_get_object_name($params) {
        if (!$params['object_name']) {
            $type = $this->getObjectTypeFromPermissions($params);
            if (in_array($params['permission_type'], array('PLUGIN_TRACKER_ACCESS_FULL', 'PLUGIN_TRACKER_ACCESS_SUBMITTER', 'PLUGIN_TRACKER_ACCESS_ASSIGNEE', 'PLUGIN_TRACKER_FIELD_SUBMIT', 'PLUGIN_TRACKER_FIELD_READ', 'PLUGIN_TRACKER_FIELD_UPDATE', 'PLUGIN_TRACKER_ARTIFACT_ACCESS'))) {
            	    if ($type == 'tracker') {
            	    	    $tracker = new Tracker();
            	    	    $tracker->setId($params['object_id']);
            	    	    $params['object_name'] = $tracker->getItemName();
            	    } else if ($type == 'field') {
            	    	    $field = new Tracker_FormElement_Field();
            	    	    $field->setId(permission_extract_field_id($params['object_id']));
            	    	    $params['object_name'] = $field->getName();
            	    } else if ($type == 'artifact') {
            	    	    $artifact = new Tracker_Artifact();
            	    	    $artifact->setId($params['object_id']);
            	    	    $params['object_name'] = ($artifact->getTitle() != null) ? $artifact->getTitle() : 'art #'.$params['object_id'];
            	    }
            }
        }
    }
    
    function permission_get_object_fullname($params) {
        if (!$params['object_fullname']) {
            $type = $this->getObjectTypeFromPermissions($params);
            if (in_array($params['permission_type'], array('PLUGIN_TRACKER_ACCESS_FULL', 'PLUGIN_TRACKER_ACCESS_SUBMITTER', 'PLUGIN_TRACKER_ACCESS_ASSIGNEE', 'PLUGIN_TRACKER_FIELD_SUBMIT', 'PLUGIN_TRACKER_FIELD_READ', 'PLUGIN_TRACKER_FIELD_UPDATE', 'PLUGIN_TRACKER_ARTIFACT_ACCESS'))) {
            	    if ($type == 'tracker') {
            	    	    $tracker = new Tracker();
            	    	    $tracker->setId($params['object_id']);
            	    	    $params['object_fullname'] = $tracker->getName();
            	    } else if ($type == 'field') {
            	    	    $field = new Tracker_FormElement_Field();
            	    	    $field->setId(permission_extract_field_id($params['object_id']));
            	    	    $params['object_fullname'] = $field->getLabel();
            	    } else if ($type == 'artifact') {
            	    	    $artifact = new Tracker_Artifact();
            	    	    $artifact->setId($params['object_id']);
            	    	    $params['object_fullname'] = ($artifact->getTitle() != null) ? $artifact->getTitle() : 'art #'.$params['object_id'];
            	    }
            }
        }
    }
    
    function permissions_for_ugroup($params) {
        if (!$params['results']) {
            if (in_array($params['permission_type'], array('PLUGIN_TRACKER_ACCESS_FULL', 'PLUGIN_TRACKER_ACCESS_SUBMITTER', 'PLUGIN_TRACKER_ACCESS_ASSIGNEE', 'PLUGIN_TRACKER_FIELD_SUBMIT', 'PLUGIN_TRACKER_FIELD_READ', 'PLUGIN_TRACKER_FIELD_UPDATE', 'PLUGIN_TRACKER_ARTIFACT_ACCESS'))) {
            	if (strpos($row['permission_type'], 'PLUGIN_TRACKER_ACCESS') === 0) {
            	    echo '<TD>'.$Language->getText('project_admin_editugroup','tracker') 
            	        .' <a href="plugins/tracker/admin/?func=permissions&perm_type=tracker&group_id='.$group_id.'&atid='.$row['object_id'].'">'
            	        .$objname.'</a></TD>';
            	} else if (strpos($row['permission_type'], 'PLUGIN_TRACKER_FIELD') === 0) {
            	    $tracker_field_displayed[$atid]=1;
            	    $atid = permission_extract_atid($row['object_id']);
            	    echo '<TD>'.$Language->getText('project_admin_editugroup','tracker_field')
            	        .' <a href="plugins/tracker/admin/?group_id='.$group_id.'&atid='.$atid.'&func=permissions&perm_type=fields&group_first=1&selected_id='.$ugroup_id.'">' 
            	        .$objname.'</a></TD>';
            	} else if ($row['permission_type'] == 'PLUGIN_TRACKER_ARTIFACT_ACCESS') {
            	    echo '<td>'. $hp->purify($objname, CODENDI_PURIFIER_BASIC) .'</td>';
            	}
            }
        }
    }
    
    var $_cached_permission_user_allowed_to_change;
    function permission_user_allowed_to_change($params) {
    	//TODO: manage permissions related to field "permission on artifact"
        if (!$params['allowed']) {
            if (!$this->_cached_permission_user_allowed_to_change) {
                if (in_array($params['permission_type'], array('PLUGIN_TRACKER_ACCESS_FULL', 'PLUGIN_TRACKER_ACCESS_SUBMITTER', 'PLUGIN_TRACKER_ACCESS_ASSIGNEE', 'PLUGIN_TRACKER_FIELD_SUBMIT', 'PLUGIN_TRACKER_FIELD_READ', 'PLUGIN_TRACKER_FIELD_UPDATE', 'PLUGIN_TRACKER_ARTIFACT_ACCESS'))) {
                    $tracker = new Tracker();
                    $tracker->setId($params['object_id']);
                    try {
                        //Only tracker admin can update perms
                        $this->_cached_permission_user_allowed_to_change = $tracker->userIsAdmin(UserManager::instance()->getCurrentUser());
                    } catch (Exception $e) {
                        // do nothing
                    }
                }
            }
            $params['allowed'] = $this->_cached_permission_user_allowed_to_change;
        }
    }
}

?>
