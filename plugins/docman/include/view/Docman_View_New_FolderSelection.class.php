<?php
/**
 * Copyright © STMicroelectronics, 2007. All Rights Reserved.
 * Copyright © Enalean, 2018. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('Docman_View_ParentsTree.class.php');

class Docman_View_New_FolderSelection extends Docman_View_Docman
{

    function _title($params)
    {
        // No title in printer version
        if (isset($params['pv']) && $params['pv'] > 0) {
            return;
        }
        echo '<h2>'.$GLOBALS['Language']->getText('plugin_docman', 'new_fldsel_title').'</h2>';
    }

    function _content($params)
    {
        $html = '';

        $html .= '<div class="docman_new_item">'."\n";

        $html .= '<form action="'.$params['default_url'].'" method="post">';

        // Location
        $html .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'new_fldsel_location').'</h3>';
        $html .= '<p>'.$GLOBALS['Language']->getText('plugin_docman', 'new_fldsel_location_desc').'</p>';
        $parentsTree = new Docman_View_ParentsTree($this->_controller);
        $html .= $parentsTree->fetch(array(
            'docman_icons' => $this->_getDocmanIcons($params),
            'current'      => $params['item']->getId(),
            'hierarchy'    => $params['hierarchy'],
            'input_name'   => 'id'
        ));
        $html .= '<div class="docman_help">'.$GLOBALS['Language']->getText('plugin_docman', 'new_fldsel_location_help').'</div>';

        // Type
        $html .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'new_fldsel_type').'</h3>';
        $html .= '<p>'.$GLOBALS['Language']->getText('plugin_docman', 'new_fldsel_typehelp').'</p>';
        $html .= '<p>';
        $html .= '<select name="item_type" data-test="document_type">';
        $html .= '<option value="-1">'.$GLOBALS['Language']->getText('plugin_docman', 'new_fldsel_createdoc').'</option>';
        $html .= '<option value="'.PLUGIN_DOCMAN_ITEM_TYPE_FOLDER.'">'.$GLOBALS['Language']->getText('plugin_docman', 'new_fldsel_createfolder').'</option>';
        $html .= '</select>';
        $html .= '</p>';

        // Form params
        $html .= '<p>';
        $html .= '<input type="hidden" name="action" value="newDocument" />';
        $html .= '<input type="submit" tabindex="2" name="confirm" data-test="create_document_next" value="'. $GLOBALS['Language']->getText('global', 'next') .'" />';
        $html .= ' ';
        $html .= '<input type="submit" tabindex="1" name="cancel" value="'. $GLOBALS['Language']->getText('global', 'btn_cancel') .'" />';
        $html .= '</p>';
        $html .= '</form>';

        $html .= '</div>';

        echo $html;
    }
}
