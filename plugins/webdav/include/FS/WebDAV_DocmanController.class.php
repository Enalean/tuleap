<?php
/**
 * Copyright (c) Enalean, 2011 - 2017. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * WebDav / Docman interactions aims to be done through the standard MVC pattern.
 *
 * WebDav plugin issue a new "WebDav" kind of request to Docman controller.
 * Then Docman controller dispatch to the right action depending of the 'action' request
 * parameter. And finally, we use default docman actions defined ind DocmanActions class.
 *
 * Using this pattern, we ensure a perfect alignment between WebDav access code and Docman
 * access code.
 */
class WebDAV_DocmanController extends Docman_Controller
{

    public function __construct(DocmanPlugin $plugin, WebDAV_Request $request)
    {
        parent::__construct($plugin, $plugin->getPluginPath(), $plugin->getThemePath(), $request);
    }

    public function actionsManagement()
    {
        $action = new Docman_Actions($this);
        $action->process($this->action, $this->_actionParams);
    }

    public function viewsManagement()
    {
    }

    public function _includeView()
    {
    }

    public function _set_deleteView_errorPerms()
    {
    }

    public function _set_redirectView()
    {
    }

    public function _setView($view)
    {
    }

    public function _set_moveView_errorPerms()
    {
    }

    public function _set_createItemView_errorParentDoesNotExist(&$item, $get_show_view)
    {
    }

    public function _set_createItemView_afterCreate($view)
    {
    }

    public function _set_doesnot_belong_to_project_error($item, $group)
    {
    }
}
