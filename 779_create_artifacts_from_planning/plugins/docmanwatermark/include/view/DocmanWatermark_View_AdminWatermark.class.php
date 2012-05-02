<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2008
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 * 
 */

require_once dirname(__FILE__).'/../../../docman/include/view/Docman_View_Extra.class.php';
require_once dirname(__FILE__).'/../../../docman/include/Docman_MetadataFactory.class.php';
require_once dirname(__FILE__).'/../../../docman/include/Docman_MetadataListOfValuesElementFactory.class.php';
        
class DocmanWatermark_View_AdminWatermark extends Docman_View_Extra {

    public function __construct($controller) {
        $this->_controller = $controller; 
    }

    function _title($params) {
        echo '<h2>'. $this->_getTitle($params) .' - '. $GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_watermark') .'</h2>';
        echo '<p>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_watermark_desc').'</p>';
    }

    function getMetaDataForm($groupId,$mdId) {
        $mdf    = new Docman_MetadataFactory($groupId);
        $mdIter = $mdf->getMetadataForGroup(true);
        $html  = '<h3>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_confidentiality_field').'</h3>';
        $html .= '<p>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_select_field');
        $html .= '<form name="metadata_field" method="post" action="?group_id='.$groupId.'&action=admin_set_watermark_metadata">'; 
        $html .= '<select name="md_id" onchange="javascript:document.metadata_field.submit()">';
        $html .= '<option value="none">'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_none').'</option>';
        $mdIter->rewind();
        $md_arr = array();
        while ($mdIter->valid()) {
            $md   = $mdIter->current();
            if ($md->getType()== PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                $id   = $md->getId();
                $md_arr[] = $id;
                if (( $md->getLabel() != 'status') && ($id != 100)) {
                    $html .= '<option ';
                    if ($mdId == $id) {
                        $html .= 'selected '; 
                    }
                    $html .= 'value="'.$id.'">'.$md->getName().'</option>';
                }       
            }
            $mdIter->next();
        }
        $html .= '</select>';
        $html .= '<input name="submit_metadatafield" type="submit" value="'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_update').'">';
        $html .= '</form></p>';
        return $html;
    }

    function getMetaDataValuesTable($groupId,$mdId,$vals) {
        $mdf   = new Docman_MetadataFactory($groupId);
        $mdLabel = $mdf->getLabelFromId($mdId);
        $mlvef = new Docman_MetadataListOfValuesElementFactory($mdId);
        $mlveIter = $mlvef->getIteratorByFieldId($mdId, $mdLabel, true);
        $html  = '<h3>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_confidentiality_field_values').'</h3>';
        $html .= '<p>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_select_field_values');
        $html .= '<form name="metadata_field_values" method="post" action="?group_id='.$groupId.'&action=admin_set_watermark_metadata_values">';
        
        $titles = array();
        $titles[] = $GLOBALS['Language']->getText('plugin_docmanwatermark','admin_use_watermark');
        $titles[] = $GLOBALS['Language']->getText('plugin_docmanwatermark','admin_values');
        $mlveIter->rewind();
        $iter_empty = 1;
        if ($mlveIter->valid()) {
            $iter_empty = 0;
            $html .= html_build_list_table_top($titles, false, false, false);
        }
        $i = 1;
        while ($mlveIter->valid()) {
            $mdv   = $mlveIter->current();
            $id   = $mdv->getId();
            if (isset($vals['value_id'])) {
                $posValue = array_search($id, $vals['value_id']);
            } else {
                $posValue = false;
            }
            // exclude status field and None Value
            if (($id != 100) && ($mdv->getName() != 'Status')) {
                $name = $mdv->getName();
                $html .= '<tr class="'.html_get_alt_row_color($i).'"><td align="center"><input type="checkbox" name="chk_'.$id.'"';
                if (($vals['watermark'][$posValue] == 1) && ($posValue !== false)) {
                    $html .= ' checked ';    
                }
                $html .= '/></td>';
                $html .= '<td><b>'.$name.'</b></td>';
                $html .= '</tr>';
                $i++;
            }
            $mlveIter->next();
        }
        
        if (!$iter_empty) {
            $html .= '</table>';
            $html .= '<input name="submit_metadatafield_value" type="submit" value="'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_save_settings').'">';
        } else {
            $html .= '<b>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_disabled').'</b>';
        }
        $html .= '</form>';
        return $html;
    }

    function getImportForm($groupId){
        $html  = '<h3>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_import_settings').'</h3>';
        $html .= $GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_import_from_project');
        $html .= '<form name="metadata_import" method="post" action="?group_id='.$groupId.'&action=admin_import_from_project">';
        $html .= '<input type="text" name="project"/>';
        $html .= '<input name="submit_import" type="submit" value="'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_import').'">';
        $html .= '</form>';
        return $html;        
    }

    function getDisabledTable($groupId) {
        $dwLog = new DocmanWatermark_ItemFactory();
        $dar = $dwLog->getNotWatermarkedByProject($groupId);

        $html = '<h3>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_disabled_title').'</h3>';
        $html .= '<p>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_disabled_desc').'</p>';
        if ($dar && $dar->rowCount() > 0) {
            $titles = array($GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_disabled_document'),
            				$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_disabled_when'),
            				$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_disabled_directaccess'));
            $html .= html_build_list_table_top($titles, false, false, false);
            $altColor = 0;
            $previousItem = null;
            foreach($dar as $row) {
                if ($row['item_id'] != $previousItem) {
                    $html .= '<tr class="'.html_get_alt_row_color($altColor++).'">';
                    $html .= '<td>'.$row['title'].'</td>';
                    $html .= '<td>'.util_timestamp_to_userdateformat($row['time']).'</td>';
                    $html .= '<td><a href="'.$this->_controller->getDefaultUrl().'&action=details&id='.$row['item_id'].'&section=watermarking">'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_disabled_link').'</a></td>';
                    $html .= '<tr>';
                    $previousItem = $row['item_id']; 
                }
            }
            $html .= '</table>';
        }
        return $html;
    }

    function _content($params) {
        $html = '';
        $html .= $this->getMetaDataForm($params['group_id'], $params['md_id']);
        $html .= $this->getMetaDataValuesTable($params['group_id'],$params['md_id'],$params['md_values']);
        $html .= $this->getImportForm($params['group_id']);
        $html .= $this->getDisabledTable($params['group_id']);
        echo $html;
    }

}

?>
