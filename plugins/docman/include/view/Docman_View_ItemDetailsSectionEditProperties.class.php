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
require_once('Docman_View_ItemDetailsSectionProperties.class.php');
require_once(dirname(__FILE__).'/../Docman_PermissionsManager.class.php');

class Docman_View_ItemDetailsSectionEditProperties extends Docman_View_ItemDetailsSectionProperties {
    var $token;
    var $subItemsWritable;
    var $recursionConfirmed;
    var $recurse;
    var $recurseOnDocs;

    var $nbDocsImpacted;
    var $nbFoldersImpacted;

    function Docman_View_ItemDetailsSectionEditProperties(&$item, $url, $theme_path, $force, $token, $recursionConfirmed, $recurse, $recurseOnDocs) {
        parent::Docman_View_ItemDetailsSectionProperties($item, $url, $theme_path, true, $force);
        $this->token = $token;
        $this->formName = 'update_metadata';
        $this->subItemsWritable = null;
        $this->recursionConfirmed = $recursionConfirmed;
        $this->recurse = $recurse;
        $this->recurseOnDocs = $recurseOnDocs;

        $this->nbDocsImpacted = null;
        $this->nbFoldersImpacted = null;
    }

    function _getDirectLinkField() {
        return '';
    }

    function getContent() {
        $html = '';
        $params = array('form_name' => $this->formName);
        $html  .= '<form name="'.$params['form_name'].'" action="'. $this->url .'" method="post" class="docman_form">';
        if(!$this->recursionConfirmed && $this->_subItemsAreWritable()) {
            $html .= '<div class="docman_confirm_delete">';
            $nbDocs = 0;
            if($this->recurseOnDocs) {
                $nbDocs = $this->nbDocsImpacted;
            }
            $html .= $GLOBALS['Language']->getText('plugin_docman', 'details_update_recursion_warning', array($nbDocs, $this->nbFoldersImpacted));
            $html .= '</div>';
        }
        $html .= parent::getContent($params);
        $html .= '</form>';
        return $html;
    }

    function _showField($field) {
        return $field->getField();
    }

    function _getFieldLabel(&$field) {
        return $field->getLabel(true);
    }

    function _subItemsAreWritable() {
        if($this->subItemsWritable === null) {
            $dPm =& Docman_PermissionsManager::instance($this->item->getGroupId());
            $this->subItemsWritable = $dPm->currentUserCanWriteSubItems($this->item->getId());

            // Cache some info.
            $subItemsWritableVisitor =& $dPm->getSubItemsWritableVisitor();
            $this->nbDocsImpacted = $subItemsWritableVisitor->getDocumentCounter();
            $this->nbFoldersImpacted = $subItemsWritableVisitor->getFolderCounter();
        }
        return $this->subItemsWritable;
    }

    function _getDefaultValuePropertyField($field) {
        $html = '';

        $html .= '<tr style="vertical-align:top;">';
        $checked = '';
        if($this->_subItemsAreWritable()) {
            if(in_array($field->md->getLabel(), $this->recurse)) {
                $checked = ' checked="checked"';
            }
            $html .= '<td style="text-align: center;"><input type="checkbox" name="recurse[]" value="'.$field->md->getLabel().'"'.$checked.' /></td>';
        }
        $html .= '<td class="label">';
        $fieldHtml = $this->_getFieldLabel($field);
        if($checked != '') {
            $html .= '<strong>'.$fieldHtml.'</strong>';
        } else {
            $html .= $fieldHtml;
        }
        $html .= '</td>';
        $html .= '<td class="value">'.$this->_showField($field).'</td>';
        $html .= '</tr>';
        return $html;
    }

    function _getDefaultValuesTableHeader() {
       $html = '';
       if($this->_subItemsAreWritable()) {
           $html .= '<tr>';
           $html .= '<th>'.$GLOBALS['Language']->getText('plugin_docman', 'details_properties_dfltv_rec').'</th>';
           $html .= '<th>'.$GLOBALS['Language']->getText('plugin_docman', 'details_properties_dfltv_md').'</td>';
           $html .= '<th>'.$GLOBALS['Language']->getText('plugin_docman', 'details_properties_dfltv_val').'</th>';
           $html .= '</tr>';
       }
       return $html;
    }

    function _getDefaultValues() {
        $html = '';
        if($this->_subItemsAreWritable()) {
            if(!$this->recursionConfirmed) {
                $html .= '<input type="hidden" name="validate_recurse" value="true" />';
            }
        }

        // Table
        $html .= parent::_getDefaultValues();

        // Apply on Folder/Doc selection
        $docChecked = '';
        $fldChecked = ' selected="selected"';
        if($this->recurseOnDocs) {
            $docChecked = ' selected="selected"';
            $fldChecked = '';
        }

        $html .= '<h4>'.$GLOBALS['Language']->getText('plugin_docman', 'details_properties_dfltv_include_title').'</h4>';
        $html .= '<p>'.$GLOBALS['Language']->getText('plugin_docman', 'details_properties_dfltv_include_docs_desc').'</p>';
        $html .= '<p>';
        $html .= '<select name="recurse_on_doc">';
        $html .= '<option value="0"'.$fldChecked.'>'.$GLOBALS['Language']->getText('plugin_docman', 'details_properties_dfltv_include_fold').'</option>';
        $html .= '<option value="1"'.$docChecked.'>'.$GLOBALS['Language']->getText('plugin_docman', 'details_properties_dfltv_include_docs').'</option>';
        $html .= '</select>';
        $html .= '</p>';

        return $html;
    }

    function _getAdditionalRows() {
        $html  = '<p>';
        if ($this->token) {
            $html .= '<input type="hidden" name="token" value="'. $this->token .'" />';
        }
        $html .= '<input type="hidden" name="item[id]" value="'. $this->item->getId() .'" />';
        $html .= '<input type="hidden" name="action" value="update" />';

        if($this->recursionConfirmed){
            $confirmStr = $GLOBALS['Language']->getText('global','btn_submit');
        } else {
            $confirmStr = $GLOBALS['Language']->getText('global','btn_apply');
        }
        $html .= '<input type="submit" name="confirm" value="'. $confirmStr .'" />';
        $html .= ' ';
        $html .= '<input type="submit" name="cancel" value="'. $GLOBALS['Language']->getText('global','btn_cancel') .'" />';
        $html .= '</p>';
        return $html;
    }


}
?>
