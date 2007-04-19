<?php
/* 
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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
 * $Id$
 */

require_once('Docman_View_Browse.class.php');

class Docman_View_Empty extends Docman_View_Display {

    function _content($params) {
        $item = $params['item'];
        
        $dPm =& Docman_PermissionsManager::instance($item->getGroupId());

        $html  = '';

        $html .= '<fieldset>';
        $html .= '<legend>'.Docman::txt('view_empty_emptydoc').'</legend>';
        $html .= '<p>'.Docman::txt('view_empty_docisempty').'</p>';
        if($dPm->userCanWrite($params['user'], $item->getId())) {
            $upurl = $params['default_url'].'&amp;action=action_update&amp;id='.$item->getId();
            $html .= '<p><a href="'.$upurl.'">'.Docman::txt('view_empty_update').'</a></p>';
        }
        $html .= '</fieldset>';

        $html .= '<fieldset>';
        $html .= '<legend>view_empty_docmd</legend>';
        $html .= '<table>';
        $html .= '<tr><td class="label">';
        $get_fields = new Docman_View_GetFieldsVisitor();
        $fields = $item->accept($get_fields, $params);
        foreach($fields as $field) {
            $html .= '<tr>';
            $html .= '<td class="label">'. $field->getLabel() .'</td>';
            $html .= '<td class="value">'. $field->getValue() .'</span></td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';

        if($dPm->userCanWrite($params['user'], $item->getId())) {
            $editurl = $params['default_url'].'&amp;action=edit&amp;id='.$item->getId();
            $html .= '<p><a href="'.$editurl.'">'.Docman::txt('details_properties_edit').'</a></p>';
        }

        $html .= '</fieldset>';

        print $html;
    }

}

?>
