<?php
/**
 * Copyright © STMicroelectronics, 2006. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2006.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * 
 * 
 */

class Docman_MetaMetadataHtml {
    var $md;
    var $str_yes;
    var $str_no;
    var $hp;

    function Docman_MetaMetadataHtml(&$md) {
        $this->md =& $md;
        $this->hp =& CodeX_HTMLPurifier::instance();

        $this->str_yes = $GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_param_yes');
        $this->str_no = $GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_param_no');
    }

    function getName(&$sthCanChange) {
        $mdContent = '';
        $mdContent .= '<tr>';
        $mdContent .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_param_name').'</td>';
        $mdContent .= '<td>';
        if($this->md->canChangeName()) {
            $sthCanChange = true;
            $mdContent .= '<input type="text" name="name" value="'.$this->hp->purify($this->md->getName()).'" class="text_field" />';
        }
        else {
            $mdContent .= $this->hp->purify($this->md->getName());
        }
        $mdContent .= '</td>';
        $mdContent .= '</tr>';

        return $mdContent;
    }

    function getDescription(&$sthCanChange) {
        $mdContent = '';
        $mdContent .= '<tr>';
        $mdContent .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_param_desc').'</td>';
        $mdContent .= '<td>';
        if($this->md->canChangeDescription()) {
            $sthCanChange = true;
            $mdContent .= '<textarea name="descr">'.$this->hp->purify($this->md->getDescription()).'</textarea>';
        }
        else {
            $mdContent .= $this->hp->purify($this->md->getDescription());
        }
        $mdContent .= '</td>';
        $mdContent .= '</tr>';

        return $mdContent;
    }

    function getEmptyAllowed(&$sthCanChange) {
        $mdContent = '';
        $mdContent .= '<tr>';
        $mdContent .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_param_allowempty').'</td>';
        $mdContent .= '<td>';
        if($this->md->canChangeIsEmptyAllowed()) {
            $sthCanChange = true;
            $selected = '';
            if($this->md->isEmptyAllowed()) {
                $selected = 'checked="checked"';
            }          
            $mdContent .= '<input type="checkbox" name="empty_allowed" value="1" '.$selected.' />';
        }
        else {
            if($this->md->isEmptyAllowed()) {
                $mdContent .= $this->str_yes;
            }
            else {
                $mdContent .= $this->str_no;
            }
        }
        $mdContent .= '</td>';
        $mdContent .= '</tr>';

        return $mdContent;
    }

    function getMultipleValuesAllowed(&$sthCanChange) {
        $mdContent = '';
        $mdContent .= '<tr>';
        $mdContent .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_param_allowmultiplevalue').'</td>';
        $mdContent .= '<td>';
        if($this->md->canChangeIsMultipleValuesAllowed()) {
            $sthCanChange = true;
            $selected = '';
            if($this->md->isMultipleValuesAllowed()) {
                $selected = 'checked="checked"';
            }          
            $mdContent .= '<input type="checkbox" name="multiplevalues_allowed" value="1" '.$selected.' />';
        }
        else {
            if($this->md->isMultipleValuesAllowed()) {
                $mdContent .= $this->str_yes;
            }
            else {
                $mdContent .= $this->str_no;
            }
        }
        $mdContent .= '</td>';
        $mdContent .= '</tr>';

        return $mdContent;
    }

    function getUseIt(&$sthCanChange) {
        $mdContent = '';
        $mdContent .= '<tr>';

        $mdContent .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_param_useit').'</td>';
        $mdContent .= '<td>';
        if(!$this->md->isRequired()) {
            $sthCanChange = true;
            $selected = '';
            if($this->md->isUsed()) {
                $selected = 'checked="checked"';
            }          
            $mdContent .= '<input type="checkbox" name="use_it" value="1" '.$selected.' />';
        }
        else {
            if($this->md->isUsed()) {
                $mdContent .= $this->str_yes;
            }
            else {
                $mdContent .= $this->str_no;
            }
        }
        $mdContent .= '</td>';
        $mdContent .= '</tr>';

        return $mdContent;
    }

    function getKeepHistory(&$sthCanChange) {
        $mdContent = '';
        $mdContent .= '<tr>';
        $mdContent .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_param_keephistory').'</td>';
        $mdContent .= '<td>';
        if($this->md->getKeepHistory()) {
            $mdContent .= $this->str_yes;
        }
        else {
            $mdContent .= $this->str_no;
        }
        $mdContent .= '</td>';
        $mdContent .= '</tr>';

        return $mdContent;
    }

    function getType(&$sthCanChange) {
        $mdContent = '';
        $mdContent .= '<tr>';

        $mdContent .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_param_type').'</td>';
        $mdContent .= '<td>';
        if($this->md->canChangeType()) {
            $sthCanChange = true;

            $vals = array(PLUGIN_DOCMAN_METADATA_TYPE_TEXT,
                          PLUGIN_DOCMAN_METADATA_TYPE_STRING,
                          PLUGIN_DOCMAN_METADATA_TYPE_DATE,
                          PLUGIN_DOCMAN_METADATA_TYPE_LIST);

            $texts = array($GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_param_type_text'),
                           $GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_param_type_string'),
                           $GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_param_type_date'),
                           $GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_param_type_list'));
            
            $mdContent .= html_build_select_box_from_arrays($vals, $texts, 'type', '', false, '');            
        }
        else {
            switch($this->md->getType()) {
            case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
                $mdContent .= $GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_param_type_text');
                break;
            case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
                $mdContent .= $GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_param_type_string');
                break;
            case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
                $mdContent .= $GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_param_type_date');
                break;
            case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                $mdContent .= $GLOBALS['Language']->getText('plugin_docman', 'admin_md_detail_param_type_list');
                break;
            }
        }
        $mdContent .= '</td>';
        $mdContent .= '</tr>';

        return $mdContent;
    }

}

?>
