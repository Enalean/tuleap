<?php
/** 
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2008
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

require_once(dirname(__FILE__).'/../../../docman/include/view/Docman_View_Extra.class.php');

class DocmanWatermark_View_Admin_Watermark extends Docman_View_Extra {

    public function DocmanWatermark_View_Admin_Watermark(&$controller) {
    	$GLOBALS['Language']->loadLanguageMsg('docman_watermark', 'docman_watermark');
        $this->_controller = $controller; 
    }

    function _title($params) {
        echo '<h2>'. $this->_getTitle($params) .' - '. $GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_watermark') .'</h2>';
        echo '<p>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_watermark_desc').'</p>';
    }

    function getMetaDataForm($groupId,$mdId) {
        require_once(dirname(__FILE__).'/../../../docman/include/Docman_MetadataFactory.class.php');
        $mdf    = new Docman_MetadataFactory($groupId);
        $mdIter = $mdf->getMetadataForGroup(true);
        $html  = '<h3>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_confidentiality_field').'</h3>';
        $html .= '<p>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_select_field');
        $html .= '<form name="metadata_field" method="post" action="?group_id='.$groupId.'&action=admin_set_watermark_metadata">'; 
        $html .= '<select name="md_id" onchange="javascript:document.metadata_field.submit()">';
        $html .= '<option value="none">'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_none').'</option>';
        $mdIter->rewind();
        while ($mdIter->valid()) {
            $md   = $mdIter->current();
            if ($md->getType()== PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                $id   = $md->getId();
                if ($id == "") {
                    $id = $md->getLabel();
                }
             
                $html .= '<option ';
                if ($mdId == $id) {
                    $html .= 'selected '; 
                }
                $html .= 'value="'.$id.'">'.$md->getName().'</option>';       
            }
            $mdIter->next();
        }
        $html .= '</select>';
        $html .= '<input name="submit_metadatafield" type="submit" value="'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_update').'">';
        $html .= '</form></p>';
        return $html;
    }

    function getMetaDataValuesTable($groupId,$mdId,$vals) {
        require_once(dirname(__FILE__).'/../../../docman/include/Docman_MetadataListOfValuesElementFactory.class.php');
        require_once(dirname(__FILE__).'/../../../docman/include/Docman_MetadataFactory.class.php');
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
        $html .= html_build_list_table_top($titles, false, false, false);
        $mlveIter->rewind();
        $iter_empty = 1;
        while ($mlveIter->valid()) {
            $mdv   = $mlveIter->current();
            $id   = $mdv->getId();
            $posValue = array_search($id, $vals['value_id']);
            if (($id != '') && ($mdv->getName() != 'Status')) {
                $name = $mdv->getName();
                if ($mdv->getName() == 'love_special_none_name_key') {
                    $name = $GLOBALS['Language']->getText('plugin_docman', 'love_special_none_name_key');
                }
                $html .= '<tr><td align="center"><input type="checkbox" name="chk_'.$id.'"';
                if (($vals['watermark'][$posValue] == 1) && ($posValue !== false)) {
                    $html .= ' checked ';    
                }
                $html .= '/></td>';
                $html .= '<td><b>'.$name.'</b></td>';
                $html .= '</tr>';
                $iter_empty = 0;
            }
            $mlveIter->next();
        }
        $html .= '</table>';
        if (!$iter_empty) {
            $html .= '<input name="submit_metadatafield_value" type="submit" value="'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_save_settings').'">';
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
    
    function _content($params) {
        $html = '';
        $html .= $this->getMetaDataForm($params['group_id'], $params['md_id']);
        $html .= $this->getMetaDataValuesTable($params['group_id'],$params['md_id'],$params['md_values']);
        $html .= $this->getImportForm($params['group_id']);
        echo $html;
    }

}

?>
