<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

/* abstract */ class Docman_View_ProjectHeader extends Docman_View_Header
{
    /* protected */ public function _scripts($params)
    {
        $project = ProjectManager::instance()->getProject((int) $params['group_id']);
        echo '<script type="text/javascript"> var docman = new com.xerox.codendi.Docman(' . $params['group_id'] . ', ';
        $di = $this->_getDocmanIcons($params);
        echo json_encode(array_merge(
            [
                'folderSpinner' => $di->getFolderSpinner(),
                'spinner'       => $di->getSpinner(),
                'pluginPath'    => $this->_controller->pluginPath,
                'themePath'     => $this->_controller->themePath,
                'document_path' => "/plugins/document/" . urlencode($project->getUnixNameLowerCase()),
                'language'      => [
                    'btn_close'                => $GLOBALS['Language']->getText('global', 'btn_close'),
                    'new_in'                   => dgettext('tuleap-docman', 'In:&nbsp;'),
                    'new_other_folders'        => dgettext('tuleap-docman', 'other folders:'),
                    'new_same_perms_as_parent' => dgettext('tuleap-docman', 'Will be created with the same permissions than its parent.'),
                    'new_view_change'          => dgettext('tuleap-docman', 'view/change'),
                    'new_news_explaination'    => dgettext('tuleap-docman', 'You can post news about your new item if you are admin of this project.'),
                    'new_news_displayform'     => dgettext('tuleap-docman', 'Display news form'),
                    'report_save_opt'          => dgettext('tuleap-docman', 'Save options'),
                    'report_custom_fltr'       => dgettext('tuleap-docman', 'Customize filters'),
                    'report_name_new'          => dgettext('tuleap-docman', 'Please enter a new search name:'),
                    'report_name_upd'          => dgettext('tuleap-docman', 'Update search:'),
                    'action_doc_id'            => dgettext('tuleap-docman', 'Document id:'),
                    'action_newfolder'         => dgettext('tuleap-docman', 'New folder'),
                    'action_newdocument'       => dgettext('tuleap-docman', 'New document'),
                    'action_details'           => dgettext('tuleap-docman', 'Properties'),
                    'action_newversion'        => dgettext('tuleap-docman', 'New version'),
                    'action_move'              => dgettext('tuleap-docman', 'Move'),
                    'action_permissions'       => dgettext('tuleap-docman', 'Permissions'),
                    'action_history'           => dgettext('tuleap-docman', 'History'),
                    'action_notifications'     => dgettext('tuleap-docman', 'Notifications'),
                    'action_delete'            => dgettext('tuleap-docman', 'Delete'),
                    'action_update'            => dgettext('tuleap-docman', 'Update'),
                    'action_cut'               => dgettext('tuleap-docman', 'Cut'),
                    'action_copy'              => dgettext('tuleap-docman', 'Copy'),
                    'action_paste'             => dgettext('tuleap-docman', 'Paste'),
                    'action_lock_add'          => dgettext('tuleap-docman', 'Lock for edition'),
                    'action_lock_del'          => dgettext('tuleap-docman', 'Release lock'),
                    'action_lock_info'         => dgettext('tuleap-docman', 'locked'),
                    'action_approval'          => dgettext('tuleap-docman', 'Approval table'),
                    'feedback_cut'             => dgettext('tuleap-docman', 'cut. You can now paste it wherever you want with \'Paste\' action in popup menu.'),
                    'feedback_copy'            => dgettext('tuleap-docman', 'copied. you can now paste it wherever you want (even across projects) with \'Paste\' action in popup menu.<br />Note that copy keeps <strong>neither approval tables nor notifications</strong> while cut does. <br />Note that only the link of the <strong>wiki pages</strong> is copied, not the <strong>content</strong>.'),
                    'new_approvaltable'        => dgettext('tuleap-docman', 'Please choose option for creating approval table'),
                    'event_lock_add'           => dgettext('tuleap-docman', 'Locked document'),
                ],
            ],
            $this->_getJSDocmanParameters($params)
        ));
        echo '); </script>';
    }

    /* protected */ public function _getJSDocmanParameters($params)
    {
        return [];
    }
}
