<?php
/**
 * Copyright © Enalean, 2011 - 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
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

require_once __DIR__ . '/../../../../src/www/project/admin/permissions.php';

class Docman_View_ItemDetailsSectionPermissions extends Docman_View_ItemDetailsSection
{
    public function __construct($item, $url)
    {
        parent::__construct($item, $url, 'permissions', dgettext('tuleap-docman', 'Permissions'));
    }
    public function getContent($params = [])
    {
        $content  = '';
        $content .= '<form action="' . $this->url . '" method="post">';

        //{{{ Explanations
        /* => in the doc
        $content .= '<div>';
        $content .= 'Readers can:<ul>
                        <li>access to document/folder,</li>
                        <li>access to properties of the document/folder.</li>
                     </ul>';
        $content .= 'Writers have the same rights than readers plus:<ul>
                        <li>update the document (create a new version),</li>
                        <li>update the properties of the document/folder,</li>
                        <li>move the document/folder,</li>
                        <li>create a sub-item for the folder.</li>
                     </ul>';
        $content .= 'Managers have the same rights than writers plus:<ul>
                        <li>delete the document/folder,</li>
                        <li>change permissions of the document/folder.</li>
                     </ul>';
        $content .= '</div>';
        */
        //}}}

        //{{{ Permissions
        $content .= '<div>';
        $titles = array();
        $titles[] = dgettext('tuleap-docman', 'User groups');
        $titles[] = dgettext('tuleap-docman', 'Access Permissions');
        $content .= html_build_list_table_top($titles, false, false, false);
        $odd_even = array('boxitem', 'boxitemalt');
        $i = 0;
        $ugroups = permission_get_ugroups_permissions($this->item->getGroupId(), $this->item->getId(), array('PLUGIN_DOCMAN_READ','PLUGIN_DOCMAN_WRITE','PLUGIN_DOCMAN_MANAGE'), false);
        ksort($ugroups);
        foreach ($ugroups as $ugroup) {
            $content .= '<tr class="' . $odd_even[$i++ % count($odd_even)] . '">';
            $content .= '<td>' . $ugroup['ugroup']['name'] . '</td>';
            $content .= '<td style="text-align:center;"><select name="permissions[' . $ugroup['ugroup']['id'] . ']">';
            $content .= '<option value="100">-</option>';
            $perms = array('PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE');
            $i = 1;
            foreach ($perms as $perm) {
                $content .= '<option value="' . $i++ . '" ' . (isset($ugroup['permissions'][$perm])  ? 'selected="selected"' : '') . '>' . permission_get_name($perm) . '</option>';
            }
            $content .= '</select></td>';
            $content .= '</tr>';
        }
        $content .= '</table>';

        if (is_a($this->item, 'Docman_Folder')) {
            $content .= '<div>';
            $content .= '<input type="checkbox" name="recursive" id="docman_recusrsive_permissions" value="1" /><label for="docman_recusrsive_permissions">' . dgettext('tuleap-docman', 'recursive (apply same permissions to all sub-items of this folder)') . '</label>';
            $content .= '</div>';
        }
        $content .= '<div>';
        $content .= '<input type="hidden" name="action" value="permissions" />';
        $content .= '<input type="hidden" name="id"     value="' . $this->item->getId() . '" />';
        $content .= '<input type="submit" name="update" value="' . $GLOBALS['Language']->getText('project_admin_permissions', 'submit_perm') . '" />';
        $content .= '</div>';
        $content .= '</div>';
        //}}}

        $content .= '</form>';
        return $content;
    }
}
