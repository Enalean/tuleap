<?php
/*
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 */

require_once('Docman_View_Browse.class.php');

class Docman_View_Empty extends Docman_View_Display
{

    public function _content($params)
    {
        $item = $params['item'];

        $dPm = Docman_PermissionsManager::instance($item->getGroupId());

        $html  = '';

        $html .= '<h3>' . dgettext('tuleap-docman', 'Empty document') . '</h3>';
        $html .= '<p>' . dgettext('tuleap-docman', 'There is no data attached to this item.') . '</p>';
        if ($dPm->userCanWrite($params['user'], $item->getId())) {
            $upurl = $params['default_url'] . '&amp;action=action_update&amp;id=' . $item->getId();
            $html .= '<p><a href="' . $upurl . '">' . dgettext('tuleap-docman', 'Update this document') . '</a></p>';
        }

        $html .= '<h3>' . dgettext('tuleap-docman', 'Document properties') . '</h3>';
        $html .= '<table>';
        $html .= '<tr><td class="label">';
        $get_fields = new Docman_View_GetFieldsVisitor();
        $fields = $item->accept($get_fields, $params);
        foreach ($fields as $field) {
            $html .= '<tr>';
            $html .= '<td class="label">' . $field->getLabel() . '</td>';
            $html .= '<td class="value">' . $field->getValue() . '</span></td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        if ($dPm->userCanWrite($params['user'], $item->getId())) {
            $editurl = $params['default_url'] . '&amp;action=edit&amp;id=' . $item->getId();
            $html .= '<p><a href="' . $editurl . '">' . dgettext('tuleap-docman', 'Edit properties') . '</a></p>';
        }

        print $html;
    }
}
