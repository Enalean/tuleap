<?php
/* 
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
        echo '<h2>'.' - '. $GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_watermark') .'</h2>';
        echo '<p>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_watermark_desc').'</p>';
    }

    function getMetaDataForm($group_id) {
        require_once(dirname(__FILE__).'/../DocmanWatermark_MetadataFactory.class.php');
        $wmd   = new DocmanWatermark_MetadataFactory();
        //$field = $wmd->searchByGroupId($group_id);
        
        $html  = '<h3>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_confidentiality_field').'</h3>';
        $html .= $GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_select_field'); 
        $html .= '<select name="fields">';
        $html .= '<option></option>';
        $html .= '</select>';
        return $html;
    }

    function getMetaDataValuesTable() {
        $html = '<h3>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_confidentiality_field_values').'</h3>';
        return $html;
    }

    function getImportForm($group_id){
        $html = '<h3>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_import_settings').'</h3>';
        return $html;        
    }
    
    function _content($params) {
        $html = '';
        $html .= $this->getMetaDataForm($params['group_id']);
        $html .= $this->getMetaDataValuesTable();
        $html .= $this->getImportForm($params['group_id']);
        echo $html;
    }

}

?>
