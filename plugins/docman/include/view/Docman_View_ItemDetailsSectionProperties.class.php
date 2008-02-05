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
    var $formName;
    var $inheritableMetadataArray;

    function Docman_View_ItemDetailsSectionProperties(&$item, $url, $theme_path, $user_can_write = false, $force = null) {
        $this->user_can_write = $user_can_write;
        $this->force = $force;
        $this->theme_path = $theme_path;
        $this->formName = '';
        $this->inheritableMetadataArray = null;
        $id = 'properties';
        $title = $GLOBALS['Language']->getText('plugin_docman','details_properties');
        parent::Docman_View_ItemDetailsSection($item, $url, $id, $title);
    }

    function _getPropertyRow($label, $value) {
        $html = '';
        $html .= '<tr style="vertical-align:top;">';
        $html .= '<td class="label">'.$label.'</td>';
        $html .= '<td class="value">'.$value.'</td>';
        $html .= '</tr>';
        return $html;
    }

    function _getPropertyField($field) {
        return $this->_getPropertyRow($this->_getFieldLabel($field),
                                      $this->_showField($field));
    }

    function _getDefaultValuePropertyField($field) {
        return $this->_getPropertyRow($this->_getFieldLabel($field),
                                      $this->_showField($field));
    }

    function _getItemIdField() {
        return $this->_getPropertyRow('Id:',
                                      $this->item->getId());
    }

    function _getDirectLinkField() {
        $html = '';
        $itemFactory = new Docman_ItemFactory();
        if($itemFactory->getItemTypeForItem($this->item) != PLUGIN_DOCMAN_ITEM_TYPE_FOLDER) {
            $dpm =& Docman_PermissionsManager::instance($this->item->getGroupId());
            $um =& UserManager::instance();
            $user = $um->getCurrentUser();
            if(!$this->item->isObsolete() || ($this->item->isObsolete() && $dpm->userCanAdmin($user))) {
                $label = $GLOBALS['Language']->getText('plugin_docman','details_properties_view_doc_lbl');
                $url = $this->url.'&action=show&id='.$this->item->getId();
                $href = '<a href="'.$url.'">'.$GLOBALS['Language']->getText('plugin_docman','details_properties_view_doc_val').'</a>';
            }
            $html = $this->_getPropertyRow($GLOBALS['Language']->getText('plugin_docman','details_properties_view_doc_lbl'),
                                              $href);
        }
        return $html;
    }

    function _getPropertiesFields($params) {
        $html = '';

        $params['theme_path'] = $this->theme_path;
        $get_fields =& new Docman_View_GetFieldsVisitor();
        $fields = $this->item->accept($get_fields, $params);

        $html .= '<table class="docman_item_details_properties">';

        // Item Id
        $html .= $this->_getItemIdField();

        // Item properties
        foreach($fields as $field) {
            $html .= $this->_getPropertyField($field);
        }

        // Item link
        $html .= $this->_getDirectLinkField();

        $html .= '</table>';
        return $html;
    }

    function getContent($params = array()) {
        $html  = '';

        $defaultValuesToManage = false;
        if(is_a($this->item, 'Docman_Folder') && count($this->_getInheritableMetadata()) > 0) {
            $defaultValuesToManage = true;
        }

        if($defaultValuesToManage) {
            $html .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'details_properties_folder').'</h3>';
        }
        $html .= $this->_getPropertiesFields($params);
        if($defaultValuesToManage) {
            $html .= $this->_getDefaultValuesFields();
        }

        $html .= $this->_getAdditionalRows();
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

        if ($this->user_can_write) {
            $html .= '<p><a href="'. $this->url .'&amp;action=edit&amp;id='. $this->item->getid() .'">'. $GLOBALS['Language']->getText('plugin_docman','details_properties_edit') .'</a></p>';
        }
        return $html;
    }

    function _getInheritableMetadata() {
        if($this->inheritableMetadataArray === null) {
            $mdFactory = new Docman_MetadataFactory($this->item->getGroupId());
            $inheritableMda = $mdFactory->getInheritableMdLabelArray(true);

            $mdIter = $this->item->getMetadataIterator();

            $mdHtmlFactory = new Docman_MetadataHtmlFactory();
            $this->inheritableMetadataArray = $mdHtmlFactory->buildFieldArray($mdIter, $inheritableMda, true, $this->formName, $this->theme_path);
        }
        return $this->inheritableMetadataArray;
    }

    function _getDefaultValuesTableHeader() {
        return '';
    }

    function _getDefaultValues() {
        $html = '';
        $fields = $this->_getInheritableMetadata();
        $html .= '<table class="docman_item_details_properties">';
        $html .= $this->_getDefaultValuesTableHeader();
        foreach($fields as $field) {
            $html .= $this->_getDefaultValuePropertyField($field);
        }
        $html .= '</table>';
        return $html;
    }

    function _getDefaultValuesFields() {
        $html = '';
        $html .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'details_properties_dfltv').'</h3>';
        $html .= '<p>'.$GLOBALS['Language']->getText('plugin_docman', 'details_properties_dfltv_desc').'</p>';
        $html .= $this->_getDefaultValues();
        return $html;
    }
}

?>
