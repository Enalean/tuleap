<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2008
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
 * 
 */

require_once 'common/project/ProjectManager.class.php';
require_once 'common/mvc/Controler.class.php';
require_once 'common/include/HTTPRequest.class.php';
require_once 'common/user/UserManager.class.php';
require_once 'common/include/Feedback.class.php';
require_once 'common/event/EventManager.class.php';

require_once dirname(__FILE__).'/../../docman/include/Docman_MetadataFactory.class.php';
require_once dirname(__FILE__).'/../../docman/include/Docman_MetadataListOfValuesElementFactory.class.php';

require_once 'DocmanWatermark_MetadataFactory.class.php';
require_once 'DocmanWatermark_Metadata.class.php';
require_once 'DocmanWatermark_MetadataValueFactory.class.php';
require_once 'DocmanWatermark_MetadataImportFactory.class.php';

            
class DocmanWatermark_Controller extends Controler {
    // variables
    var $request;
    var $user;
    var $groupId;
    var $themePath;
    var $plugin;
    var $feedback;
    var $user_can_admin;
    var $docmanPath;
    
    function __construct($plugin, $docmanPath,$pluginPath, $themePath, $request) {
        $this->request        = $request;
        $this->user           = null;
        $this->groupId        = null;
        $this->user_can_admin = null;
        $this->pluginPath     = $pluginPath;
        $this->docmanPath     = $docmanPath;
        $this->themePath      = $themePath;
        $this->plugin         = $plugin;
        $this->view           = null;
        $this->feedback       = false;
        
        $event_manager  = $this->_getEventManager();
        $this->feedback = $GLOBALS['Response']->_feedback;
    }

    function &_getEventManager() {
        return EventManager::instance();
    }
    
    function &getUser() {
        if($this->user === null) {
            $um = UserManager::instance();
            $this->user = $um->getCurrentUser();
        }
        return $this->user;
    }
    
    function userCanManage($item_id) {
        $dPm  = Docman_PermissionsManager::instance($this->getGroupId());
        $user = $this->getUser();
        return $dPm->userCanManage($user, $item_id);
    }
    function userCanAdmin() {
        $dPm  = Docman_PermissionsManager::instance($this->getGroupId());
        $user = $this->getUser();
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
    
    function request() {
        if (!$this->request->exist('group_id')) {
            $this->feedback->log('error', 'Project is missing.');
            $this->_setView('Error');
        } else {
            $_groupId = (int) $this->request->get('group_id');
            $pM = ProjectManager::instance();
            $project = $pM->getProject($_groupId);
            if($project == false) {
                $this->feedback->log('error', 'Project is missing.');
                $this->_setView('Error');
                return;
            }
                        
            $this->_viewParams['docmanwatermark']     = $this;
            $this->_viewParams['user']                = $this->getUser();
            $this->_viewParams['default_url']         = $this->getDefaultUrl();
            $this->_viewParams['watermark_admin_url'] = $this->getAdminWatermarkUrl();
            $this->_viewParams['theme_path']          = $this->getThemePath();
            $this->_viewParams['group_id']            = (int) $this->request->get('group_id');

            $dwmdf = new DocmanWatermark_MetadataFactory();
            $md_id = $dwmdf->getMetadataIdFromGroupId((int) $this->request->get('group_id'));
            $this->_viewParams['md_id']    = $md_id;
            
            $dwmdvf = new DocmanWatermark_MetadataValueFactory();
            $iterValues = $dwmdvf->getMetadataValuesIterator($md_id);
            $arrVals = array();
            $iterValues->rewind();
            while($iterValues->valid()) {
                $dwmv = $iterValues->current();
                $arrVals['value_id'][] = $dwmv->getValueId();
                $arrVals['watermark'][] = $dwmv->getWatermark();
                $iterValues->next();
            }
            $this->_viewParams['md_values'] = $arrVals;
            $view = $this->request->exist('action') ? $this->request->get('action') : 'admin_watermark';
            if ($this->request->exist('project') && ($this->request->exist('action') == 'admin_import_from_project')) {
                $_targetGroupId = (int) $this->request->get('project');
                $targetProject = $pM->getProject($_targetGroupId);
                if($targetProject == false) {
                    $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_project_import_error'));
                    $this->view= 'Admin_Watermark';
                    return;
                } else if ($_targetGroupId == $_groupId) {
                    $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_project_import_same'));
                    $this->view= 'AdminWatermark';
                    return;
                }
            }
            $this->_viewParams['action'] = $view;
            $this->_dispatch($view);
        }
    }

    function _dispatch($view) {
        $user = $this->getUser();
        $dpm  = Docman_PermissionsManager::instance($this->getGroupId());

        switch ($view) {
        case 'admin_watermark':
            $group_id = $this->request->get('group_id');
            $dwmdf = new DocmanWatermark_MetadataFactory();
            $md_id = $dwmdf->getMetadataIdFromGroupId($group_id);
            $this->_viewParams['md_id']    = $md_id;
            $this->_viewParams['group_id'] = $group_id;
            $md_arr = array();
            
            $dmf = new Docman_MetadataFactory($group_id);
            $mdIter = $dmf->getMetadataForGroup(true);
            $mdIter->rewind();
            while ($mdIter->valid()){
                $md = $mdIter->current();
                $md_arr[] = $md->getId();
                $mdIter->next();
            }
            if ($md_id != 0 && !in_array($md_id, $md_arr)) {
                $this->feedback->log('warning', $GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_metadata_deleted'));
            }
            $this->view   = 'AdminWatermark';
            break;
        case 'admin_set_watermark_metadata':
            $group_id = $this->request->get('group_id');
            $id       = $this->request->get('md_id');
            $this->_actionParams['group_id'] = $group_id;
            $this->_actionParams['md_id']    = $id;
            $dwm = new DocmanWatermark_Metadata();
            $dwm->setId($id);
            $dwm->setGroupId($group_id);
            $this->action = 'setup_metadata';
            
            $this->feedback->log('info', $GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_update_metadata'));

            $this->_viewParams['redirect_to'] = '?group_id='.$group_id.'&action=admin_watermark';
            $this->view   = 'RedirectAfterCrud';
            break;
        case 'admin_set_watermark_metadata_values':
            $mdf   = new Docman_MetadataFactory($this->request->get('group_id'));
            $dwmf  = new DocmanWatermark_MetadataFactory();
            $md_id = $dwmf->getMetadataIdFromGroupId($this->request->get('group_id'));
            $mdLabel = $mdf->getLabelFromId($md_id);
            $mlvef = new Docman_MetadataListOfValuesElementFactory($md_id);
            $mlveIter = $mlvef->getIteratorByFieldId($md_id, $mdLabel, true);
            $mlveIter->rewind();
            $arrValues = array();
            $arrVals   = array();
            while($mlveIter->valid()) {
                $dmv = $mlveIter->current();
                $dwmv = new DocmanWatermark_MetadataValue();
                if ($this->request->exist('chk_'.$dmv->getId())) {
                    $watermark = 1;
                } else {
                    $watermark = 0;
                }
                $dwmv->setValueId($dmv->getId());
                $dwmv->setWatermark($watermark);
                $arrValues[] = $dwmv;
                $arrVals ['value_id'][]  = $dmv->getId();
                $arrVals ['watermark'][] = $watermark;
                $mlveIter->next();
            }
            $iterValues = new ArrayIterator($arrValues);
            $this->_actionParams['group_id'] = $this->request->get('group_id');
            $this->_actionParams['md_values'] = $iterValues;            
            $this->action = 'setup_metadata_values';

            $this->feedback->log('info', $GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_update_metadata_values'));
            $this->_viewParams['redirect_to'] = '?group_id='.$this->request->get('group_id').'&action=admin_watermark';
            $this->view   = 'RedirectAfterCrud';            
            break;
        case 'admin_import_from_project':
            $this->_actionParams['src_group_id']    = $this->request->get('project');
            $this->_actionParams['target_group_id'] = $this->request->get('group_id');
                        
            $dwmif = new DocmanWatermark_MetadataImportFactory();
            $dwmif->setSrcProjectId($this->request->get('project'));
            $dwmif->setTargetProjectId($this->request->get('group_id'));
            
            $dmf = new Docman_MetadataFactory($this->request->get('project'));

            $dwmf = new DocmanWatermark_MetadataFactory();
            $md_id = $dwmf->getMetadataIdFromGroupId($dwmif->getSrcProjectId());
            $mdIter = $dmf->findByName($dwmf->getMetadataNameFromId($md_id));
            $mdIter->rewind();
            $md = $mdIter->current();
            $this->_actionParams['md'] = $md; 
            $mdMap = $dwmif->getWatermarkMetadataMap($md);
            if ($mdMap['md'] != 0) {
                $this->action = 'import_from_project';
                $this->feedback->log('info', $GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_imported_from_project'));
                $this->_viewParams['md_id'] = $md_id;
                $this->_viewParams['redirect_to'] = '?group_id='.$this->request->get('group_id').'&action=admin_watermark';                
            } else {
                $this->feedback->log('info', $GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_import_from_project_not_match'));
                $this->_viewParams['redirect_to'] = '?group_id='.$this->request->get('group_id').'&action=admin_import_metadata_check&import_group_id='.$this->request->get('project');
            }
            $this->view   = 'RedirectAfterCrud';
            break;

        case 'docmanwatermark_toggle_item':
            $this->action = 'docmanwatermark_toggle_item';
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
    
    
    function actionsManagement() {
        // Redefine actions classes names building.
        $className = get_class($this);
        $class = substr($className, 0, -(strlen("HTTPController"))) . 'Actions';
        require_once($class.'.class.php');
        $wa = new $class($this, $this->gid);
        $wa->process($this->action, $this->_actionParams);
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
