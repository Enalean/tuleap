<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 * Copyright (c) Enalean, 2015-2017. All rights reserved
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

require_once('Docman_Controller.class.php');
require_once('Docman_SOAPActions.class.php');
class Docman_SOAPController extends Docman_Controller
{

    public function __construct(&$plugin, $pluginPath, $themePath, &$request)
    {
        parent::__construct($plugin, $pluginPath, $themePath, $request);
    }

    /* protected */ public function _includeView()
    {
        $className = 'Docman_View_SOAP_' . $this->view;
        require_once('view/soap/' . $className . '.class.php');
        return $className;
    }

    /* protected */ public function _set_deleteView_errorPerms()
    {
        $this->_setView('SOAP');
    }
    /* protected */ public function _set_redirectView()
    {
        $this->_setView('SOAP');
    }

    /* protected */ public function _setView($view)
    {
        switch ($view) {
            default:
                $this->view = 'SOAP';
                break;
        }
    }
    /* protected */ public function _set_moveView_errorPerms()
    {
        $this->_setView('SOAP');
    }
    /* protected */ public function _set_createItemView_errorParentDoesNotExist(&$item, $get_show_view)
    {
        $this->_setView('SOAP');
    }
    /* protected */ public function _set_createItemView_afterCreate($view)
    {
        $this->_setView('SOAP');
    }
    /* protected */ public function _set_doesnot_belong_to_project_error($item, $group)
    {
        $this->feedback->log('error', sprintf(dgettext('tuleap-docman', 'The item %1$s doesn\'t exist or doesn\'t belong to project %2$s.'), $item->getId(), $group->getPublicName()));
        $this->_setView('SOAP');
    }

    public function _dispatch($view, $item, $root, $get_show_view)
    {
        switch ($view) {
            case 'permissions':
                if (!$this->userCanManage($item->getId())) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to set permissions for this item.'));
                } else {
                    $this->action = $view;
                    $this->_setView('');
                }
                break;
            case 'appendFileChunk':
            case 'new_version':
            case 'update':
                if (!$this->userCanWrite($item->getId())) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to edit this item.'));
                } else {
                    $this->action = $view;
                    $this->_setView('');
                }
                break;
            case 'getFileMD5sum':
            case 'getMetadataListOfValues':
            case 'getProjectMetadata':
            case 'getTreeInfo':
            case 'getFileContents':
            case 'getFileChunk':
                if (!$this->userCanRead($item->getId())) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to view this item.'));
                } else {
                    $this->action = $view;
                    $this->_setView('');
                }
                break;
            case 'search':
                $this->view = 'Search';
                break;
            default:
                parent::_dispatch($view, $item, $root, $get_show_view);
        }
    }
}
