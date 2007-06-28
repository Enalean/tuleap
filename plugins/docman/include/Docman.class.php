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
require_once('DocmanController.class.php');
require_once('DocmanActions.class.php');
class Docman extends DocmanController {

    function Docman(&$plugin, $pluginPath, $themePath) {
        $this->DocmanController($plugin, $pluginPath, $themePath, HTTPRequest::instance());
    }


    /* protected */ function _checkBrowserCompliance() {
        if($this->request->browserIsNetscape4()) {
            $this->feedback->log('warning', $GLOBALS['Language']->getText('plugin_docman', 'docman_browserns4'));
        }
    }

    /* protected */ function _includeView() {
        $className = 'Docman_View_'. $this->view;
        require_once('view/'. $className .'.class.php');
        return $className;
    }
    /* protected */ function _set_deleteView_errorPerms() {
        $this->view = 'Details';
    }
    /* protected */ function _set_redirectView() {
        if ($redirect_to = Docman_Token::retrieveUrl($this->request->get('token'))) {
            $this->_viewParams['redirect_to'] = $redirect_to;
        }
        $this->view = 'RedirectAfterCrud';
    }
    /* protected */ function _setView($view) {
    	   $this->view = $view;
    }
    /* protected */ function _set_moveView_errorPerms() {
        $this->view = 'Details';
    }
    /* protected */ function _set_createItemView_errorParentDoesNotExist(&$item, $get_show_view) {
    	   $this->view = $item->accept($get_show_view, $this->request->get('report'));
    }
    /* protected */ function _set_createItemView_afterCreate($view) {
        if ($view == 'createFolder') {
            $this->view = 'NewFolder';
        } else {
            $this->view = 'NewDocument';
        }
    }
}

?>