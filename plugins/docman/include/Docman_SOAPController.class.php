<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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
require_once('Docman_Controller.class.php');
require_once('Docman_SOAPActions.class.php');
class Docman_SOAPController extends Docman_Controller {

    function Docman_SOAPController(&$plugin, $pluginPath, $themePath, &$request) {
        $this->Docman_Controller($plugin, $pluginPath, $themePath, $request);
    }


    /* protected */ function _checkBrowserCompliance() {
    }

    /* protected */ function _includeView() {
        $className = 'Docman_View_SOAP_'. $this->view;
        require_once('view/soap/'. $className .'.class.php');
        return $className;
    }
    
    /* protected */ function _set_deleteView_errorPerms() {
        $this->_setView('SOAP');
    }
    /* protected */ function _set_redirectView() {
        $this->_setView('SOAP');
    }
    
    /* protected */ function _setView($view) {
        switch($view) {
            default:
               $this->view = 'SOAP';
               break;
        }
    }
    /* protected */ function _set_moveView_errorPerms() {
        $this->_setView('SOAP');
    }
    /* protected */ function _set_createItemView_errorParentDoesNotExist(&$item, $get_show_view) {
        $this->_setView('SOAP');
    }
    /* protected */ function _set_createItemView_afterCreate($view) {
        $this->_setView('SOAP');
    }
    /* protected */ function _set_doesnot_belong_to_project_error($item, $group) {
        $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'item_does_not_belong', array($item->getId(), util_unconvert_htmlspecialchars($group->getPublicName()))));
        $this->_setView('SOAP');
    }
    
    function _dispatch($view, $item, $root, $get_show_view) {
           
        switch ($view) {
            case 'appendFileChunk':
            case 'new_version':
            case 'update':
                if (!$this->userCanWrite($item->getId())) {
                    $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_edit'));
                } else {
                    $this->action = $view;
                    $this->_setView('');
                }
                break;
            case 'permissions':
                if (!$this->userCanManage($item->getId())) {
                    $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_perms'));
                } else {
                    $this->action = $view;
                    $this->_setView('');
                }
                break;
            case 'getFileMD5sum':
            case 'getMetadataListOfValues':
                if (!$this->userCanRead($item->getId())) {
                    $this->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_view'));
                } else {
                    $this->action = $view;
                    $this->_setView('');
                }
                break;
            case 'getProjectMetadata':
                $this->action = $view;
                $this->_setView('');
                break;
            default: parent::_dispatch($view, $item, $root, $get_show_view);
        }
   }
   
    function _getEventManager() {
        return new EventManager();
    }
}

