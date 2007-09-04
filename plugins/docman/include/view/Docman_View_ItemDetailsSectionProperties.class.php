<?php
/* 
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
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
 * 
 */
require_once('Docman_View_ItemDetailsSection.class.php');
require_once('Docman_View_GetFieldsVisitor.class.php');

class Docman_View_ItemDetailsSectionProperties extends Docman_View_ItemDetailsSection {
    var $user_can_write;
    var $force;
    var $theme_path;

    function Docman_View_ItemDetailsSectionProperties(&$item, $url, $theme_path, $user_can_write = false, $force = null) {
        $this->user_can_write = $user_can_write;
        $this->force = $force;
        $this->theme_path = $theme_path;
        $id = 'properties';
        $title = $GLOBALS['Language']->getText('plugin_docman','details_properties');
        parent::Docman_View_ItemDetailsSection($item, $url, $id, $title);
    }
    function getContent($params = array()) {
        $html  = '';
        $params['theme_path'] = $this->theme_path;
        $html .= '<table class="docman_item_details_properties">';
        $html .= '<tr style="vertical-align:top;"><td class="label">Id:</td><td>'. $this->item->getId() .'</td></tr>';
        $get_fields =& new Docman_View_GetFieldsVisitor();
        $fields = $this->item->accept($get_fields, $params);
        foreach($fields as $field) {
            $html .= '<tr style="vertical-align:top;">';
            $html .= '<td class="label">'. $this->_getFieldLabel($field) .'</td>';
            $html .= '<td class="value">'. $this->_showField($field)     .'</span></td>';
            $html .= '</tr>';
        }
        $html .= $this->_getAdditionalRows();
        
        $html .= '</table>';
        return $html;
    }
    function _getFieldLabel($field) {
        return $field->getLabel(false);
    }
    function _showField($field) {
        return $field->getValue();
    }
    function _getAdditionalRows() {
        $html = '';

        $itemFactory = new Docman_ItemFactory();
        if($itemFactory->getItemTypeForItem($this->item) != PLUGIN_DOCMAN_ITEM_TYPE_FOLDER) {
            $dpm =& Docman_PermissionsManager::instance($this->item->getGroupId());
            $um =& UserManager::instance();
            $user = $um->getCurrentUser();
            if(!$this->item->isObsolete() || ($this->item->isObsolete() && $dpm->userCanAdmin($user))) {
                $html .= '<td class="label">'.$GLOBALS['Language']->getText('plugin_docman','details_properties_view_doc_lbl').'</td>';
                $url = $this->url.'&action=show&id='.$this->item->getId();
                $href = '<a href="'.$url.'">'.$GLOBALS['Language']->getText('plugin_docman','details_properties_view_doc_val').'</a>';
                $html .= '<td class="value">'.$href.'</span></td>';
            }
        }

        if ($this->user_can_write) {
            $html .= '<tr><td colspan="2">&nbsp;</td></tr>';
            $html .= '<tr style="vertical-align:top;">';
            $html .= '<td colspan="2"><a href="'. $this->url .'&amp;action=edit&amp;id='. $this->item->getid() .'">'. $GLOBALS['Language']->getText('plugin_docman','details_properties_edit') .'</a></td></tr>';
        }
        return $html;
    }
}

?>
