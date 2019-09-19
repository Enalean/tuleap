<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*
*
*
* Docman_View_PermissionsForItem
*/

require_once('Docman_View_View.class.php');

class Docman_View_PermissionsForItem extends Docman_View_View  /* implements Visitor*/
{

    /* protected */ function _content($params)
    {
        echo $this->fetch($params['item']->getId(), $params);
    }

    function fetch($id, $params)
    {
        $html = '';
        if ($params['user_can_manage']) {
            $titles = array();
            $titles[] = $GLOBALS['Language']->getText('plugin_docman', 'details_permissions_ugroups');
            $titles[] = $GLOBALS['Language']->getText('plugin_docman', 'details_permissions_perms');
            $html .= html_build_list_table_top($titles, false, false, false);
            $odd_even = array('boxitem', 'boxitemalt');
            $i = 0;
            $ugroups = permission_get_ugroups_permissions($params['group_id'], $id, array('PLUGIN_DOCMAN_READ','PLUGIN_DOCMAN_WRITE','PLUGIN_DOCMAN_MANAGE'), false);
            ksort($ugroups);
            foreach ($ugroups as $ugroup) {
                $html .= '<tr class="'. $odd_even[$i++ % count($odd_even)] .'">';
                $html .= '<td>'. $ugroup['ugroup']['name'] .'</td>';
                $html .= '<td style="text-align:center;"><select name="permissions['. $ugroup['ugroup']['id'] .']">';
                $html .= '<option value="100">-</option>';
                $perms = array('PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE');
                $i = 1;
                foreach ($perms as $perm) {
                    if (isset($params['force_permissions'][$ugroup['ugroup']['id']])) {
                        $selected = $params['force_permissions'][$ugroup['ugroup']['id']] == $i ? 'selected="selected"' : '';
                    } else {
                        $selected = isset($ugroup['permissions'][$perm])  ? 'selected="selected"' : '';
                    }
                    $html .= '<option value="'. $i++ .'" '. $selected .'>'. permission_get_name($perm) .'</option>';
                }
                $html .= '</select></td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_docman', 'new_same_perms_as_parent'); // Will be created with the same permissions than its parent.
            $html .= $GLOBALS['Language']->getText('plugin_docman', 'new_need_to_be_manager'); // <br />You need Manage permission to define permissions.
        }
        return $html;
    }
}
