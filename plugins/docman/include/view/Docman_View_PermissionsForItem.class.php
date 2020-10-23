<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

class Docman_View_PermissionsForItem extends Docman_View_View  /* implements Visitor*/
{

    /* protected */ public function _content($params)
    {
        echo $this->fetch($params['item']->getId(), $params);
    }

    public function fetch($id, $params)
    {
        $html = '';
        if ($params['user_can_manage']) {
            $titles = [];
            $titles[] = dgettext('tuleap-docman', 'User groups');
            $titles[] = dgettext('tuleap-docman', 'Access Permissions');
            $html .= html_build_list_table_top($titles, false, false, false);
            $odd_even = ['boxitem', 'boxitemalt'];
            $i = 0;
            $ugroups = permission_get_ugroups_permissions($params['group_id'], $id, ['PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE'], false);
            $purifier = Codendi_HTMLPurifier::instance();
            ksort($ugroups);
            foreach ($ugroups as $ugroup) {
                $html .= '<tr class="' . $purifier->purify($odd_even[$i++ % count($odd_even)]) . '">';
                $html .= '<td>' . $purifier->purify($ugroup['ugroup']['name']) . '</td>';
                $html .= '<td style="text-align:center;"><select name="permissions[' . $purifier->purify($ugroup['ugroup']['id']) . ']">';
                $html .= '<option value="100">-</option>';
                $perms = ['PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE'];
                $i = 1;
                foreach ($perms as $perm) {
                    if (isset($params['force_permissions'][$ugroup['ugroup']['id']])) {
                        $selected = $params['force_permissions'][$ugroup['ugroup']['id']] == $i ? 'selected="selected"' : '';
                    } else {
                        $selected = isset($ugroup['permissions'][$perm])  ? 'selected="selected"' : '';
                    }
                    $html .= '<option value="' . $purifier->purify($i++) . '" ' . $selected . '>' . $purifier->purify(permission_get_name($perm)) . '</option>';
                }
                $html .= '</select></td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        } else {
            $html .= dgettext('tuleap-docman', 'Will be created with the same permissions than its parent.'); // Will be created with the same permissions than its parent.
            $html .= dgettext('tuleap-docman', '<br />You need Manage permission to define permissions.'); // <br />You need Manage permission to define permissions.
        }
        return $html;
    }
}
