<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2008
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */

require_once('Group.class.php');
require_once('common/mvc/Controler.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('common/user/UserManager.class.php');
require_once('common/include/Feedback.class.php');
require_once('common/event/EventManager.class.php');

$GLOBALS['Language']->loadLanguageMsg('docman_watermark', 'docman_watermark');

class DocmanWatermarkController extends Controler {
    // variables
    var $request;
    var $user;
    var $groupId;
    var $themePath;
    var $plugin;
    var $feedback;
    var $user_can_admin;
    var $docmanPath;
    
    function DocmanWatermarkController(&$plugin, $docmanPath,$pluginPath, $themePath, &$request) {
        $this->request        =& $request;
        $this->user           = null;
        $this->groupId        = null;
        $this->user_can_admin = null;
        $this->pluginPath     = $pluginPath;
        $this->docmanPath     = $docmanPath;
        $this->themePath      = $themePath;
        $this->plugin         = $plugin;
        $this->view           = null;
        $this->feedback       = false;
        
        $event_manager =& $this->_getEventManager();
        $this->feedback =& $GLOBALS['Response']->_feedback;
    }

    /**
     * Wrapper to i18n string call for docmanWatermark.
     * static
     */
    function txt($key, $vars = array()) {
        return $GLOBALS['Language']->getText('plugin_docmanwatermark', $key, $vars);
    }

    // Franlky, this is not at all the best place to do this.
    function installDocmanWatermark($ugroupsMapping, $group_id = false) {
        
    }

    function _cloneDocmanWatermark($srcGroupId, $dstGroupId, $ugroupsMapping) {
        
    }

    function &_getEventManager() {
        return EventManager::instance();
    }
    
    function &getUser() {
        if($this->user === null) {
            $um =& UserManager::instance();
            $this->user = $um->getCurrentUser();
        }
        return $this->user;
    }
    
    function userCanManage($item_id) {
        $dPm  =& Docman_PermissionsManager::instance($this->getGroupId());
        $user =& $this->getUser();
        return $dPm->userCanManage($user, $item_id);
    }
    function userCanAdmin() {
        $dPm  =& Docman_PermissionsManager::instance($this->getGroupId());
        $user =& $this->getUser();
        return $dPm->userCanAdmin($user);
    }
    
    function getGroupId() {
        if($this->groupId === null) {
            $_gid = (int) $this->request->get('group_id');
            if($_gid > 0) {
                $this->groupId = $_gid;
            }
        }
        return $this->groupId;
    }

    function getDefaultUrl() {
        $_gid = $this->getGroupId();
        return $this->docmanPath.'/?group_id='.$_gid;
    }

    function getAdminUrl() {
        $_gid = $this->getGroupId();
        return $this->docmanPath.'/admin/?group_id='.$_gid;
    }

    function getAdminWatermarkUrl() {
        $_gid = $this->getGroupId();
        return $this->pluginPath.'/admin/?group_id='.$_gid;
    }
    
    
    function getThemePath() {
        return $this->themePath;
    }
    

    /*private*/ function _checkBrowserCompliance() {
        if($this->request_type == 'http' && $this->request->browserIsNetscape4()) {
            $this->feedback->log('warning', $GLOBALS['Language']->getText('plugin_docmanwatermark', 'docman_browserns4'));
        }
    }


    function request() {
        if (!$this->request->exist('group_id')) {
            $this->feedback->log('error', 'Project is missing.');
            $this->_setView('Error');
        } else {
            $_groupId = (int) $this->request->get('group_id');
            $project = group_get_object($_groupId);
            if($project == false) {
                $this->feedback->log('error', 'Project is missing.');
                $this->_setView('Error');
                return;
            }
            
            // Browser alert
            $this->_checkBrowserCompliance();
            
            $this->_viewParams['docmanwatermark']     =& $this;
            $this->_viewParams['user']                =& $this->getUser();
            $this->_viewParams['default_url']         =  $this->getDefaultUrl();
            $this->_viewParams['watermark_admin_url'] =  $this->getAdminWatermarkUrl();
            $this->_viewParams['theme_path']          =  $this->getThemePath();
            $this->_viewParams['group_id']            = (int) $this->request->get('group_id');                
           
            $view = $this->request->exist('action') ? $this->request->get('action') : 'admin_watermark';
            $this->_viewParams['action'] = $view;
            $this->_dispatch($view);
        }
    }

    function _dispatch($view) {
        $user =& $this->getUser();
        $dpm =& Docman_PermissionsManager::instance($this->getGroupId());

        switch ($view) {
        case 'admin_watermark':
            $this->action = $view;
            $group_id = $this->request->get('group_id');
            $dwmdf = new DocmanWatermark_MetadataFacory();
            $md_id = $dwmdf->getMetadataIdFromGroupId($group_id);
            $this->_viewParams['md_id']    = $md_id;
            $this->_viewParams['group_id'] = $group_id;
            $this->view   = 'Admin_Watermark';
            break;
        case 'admin_set_watermark_metadata':
            require('DocmanWatermark_Metadata.class.php');
            $group_id = $this->request->get('group_id');
            $id       = $this->request->get('md_id');
            $this->_actionParams['group_id'] = $group_id;
            $this->_actionParams['md_id']    = $id;
            $dwm = new DocmanWatermark_Metadata();
            $dwm->setId($id);
            $dwm->setGroupId($group_id);
            $this->action = 'setup_metadata';
            
            $this->feedback->log('info', $GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_update_metadata'));
            $this->_viewParams['md_id'] = $this->request->get('md_id');
            $this->view   = 'Admin_Watermark';
            break;
        case 'admin_set_watermark_metadata_values':
            $this->_actionParams['group_id'] = $this->request->get('group_id');
            $this->_actionParams['md_id']    = $this->request->get('md_id');
            $this->action = 'setup_metadata_values';
            
            $this->feedback->log('info', $GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_update_metadata_values'));
            $this->_viewParams['md_id'] = $this->request->get('md_id');
            $this->view   = 'Admin_Watermark';            
            break;
        case 'admin_import_from_project':
            $this->_actionParams['group_id'] = $this->request->get('group_id');
            $this->_actionParams['md_id']    = $this->request->get('md_id');
            $this->action = 'import_from_project';
            
            $this->feedback->log('info', $GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_import_from_project'));
            $this->_viewParams['md_id'] = $this->request->get('md_id');
            $this->view   = 'Admin_Watermark';
            break;
        default:
            die(htmlspecialchars($view) .' is not supported');
            break;
        }
    }

    function getProperty($name) {
        $info =& $this->plugin->getPluginInfo();
        return $info->getPropertyValueForName($name);
    }
    
    function viewsManagement() {
        if ($this->view !== null) {
            $className = $this->_includeView();
            if (class_exists($className)) {
                $wv = new $className($this);
                return $wv->display($this->_viewParams);
            } else {
                die($className .' does not exist.');
            }
        }
    }
    
}
?>
