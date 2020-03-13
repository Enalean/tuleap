<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\Docman\View\DocmanViewURLBuilder;

class Docman_View_Docman extends Docman_View_ProjectHeader
{
    protected function getToolbar(array $params)
    {
        $tools = array();

        $this->addDocmanTool($params, $tools);
        $this->addNewDocumentEntry($params, $tools);

        if ($this->_controller->userCanAdmin()) {
            $tools[] = array(
                'title' => dgettext('tuleap-docman', 'Admin'),
                'url'   => $params['default_url'] . '&amp;action=admin'
            );
        }

        $tools[] = array(
            'title' => $GLOBALS['Language']->getText('global', 'help'),
            'url'   => "javascript:help_window('/doc/" . $this->_controller->getUser()->getShortLocale() . "/user-guide/documents-and-files/doc.html')"
        );

        return $tools;
    }

    protected function addDocmanTool(array $params, array &$toolbar)
    {
    }

    private function addNewDocumentEntry(array $params, &$tools)
    {
        $permission_manager      = Docman_PermissionsManager::instance($params['group_id']);
        $user                    = $this->_controller->getUser();
        $has_one_folder_writable = $permission_manager->oneFolderIsWritable($user);
        if ($has_one_folder_writable) {
            $url_params = array('action' => 'newGlobalDocument');
            if (isset($params['item'])) {
                $url_params['id'] = $params['item']->accept(new Docman_View_ToolbarNewDocumentVisitor());
            }
            $tools[] = array(
                'title' => dgettext('tuleap-docman', 'New document'),
                'url'   => DocmanViewURLBuilder::buildUrl($params['default_url'], $url_params)
            );
        }
    }
}
