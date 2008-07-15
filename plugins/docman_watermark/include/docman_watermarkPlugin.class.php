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
require_once('common/plugin/Plugin.class.php');

class Docman_watermarkPlugin extends Plugin {
    
    
    /**
     *  constructor of Docman_watermarkPlugin class
     *  @param int id : the plugin id
     *  @return void
     */
    function Docman_watermarkPlugin($id) {
        $this->Plugin($id);
        $this->_addHook('plugin_load_language_file', 'loadPluginLanguageFile', false);
        $this->_addHook('docman_file_before_download', 'stampFile', false);
        $this->_addHook('docman_file_after_display_property', 'fileWatermark', false);
        $this->_addHook('docman_file_add_version_property_table_header', 'addWatermarkTableHeader', false);
        $this->_addHook('docman_file_add_version_property_table_cell', 'addWatermarkTableCell', false);
    }

    /**
     *  method to get the plugin info to be displayed in the plugin administration
     *  @param void
     *  @return void
     */
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'Docman_watermarkPluginInfo')) {
            require_once('Docman_watermarkPluginInfo.class.php');
            $this->pluginInfo =& new DocmanWatermarkPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    /**
     *  hook method to load the plugin language file
     *  @param array params
     *  @return void 
     */
    function loadPluginLanguageFile($params) {
        $GLOBALS['Language']->loadLanguageMsg('docman_watermark', 'docman_watermark');
    }

    /**
     *  hook method to perform the PDF version stamping
     *  @param array params
     *  @return void
     */
    function stampFile($params) {
        require_once('Docman_watermark_Stamper.class.php');
        $stamper = new Docman_watermark_Stamper($params['path'],$params['headers'],$params['group_id'],$params['item'], $params['user']);
        try {
            if ($stamper->check()) {
                $stamper->load();
                $stamper->stamp();
                $stamper->render();
                exit(0);
            }
        } catch (Zend_pdf_exception $e) {
            //the application should not be able to download a file 
            //if some problem occure during stamping process
            exit(0);
        }
    }
    
    /**
     *  hook method to add extra property (watermark on new docment creation)
     */
     function fileWatermark($params) {
        if ($params['type'] == PLUGIN_DOCMAN_ITEM_TYPE_FILE) {
            $params['html'] .= '<br><input type="checkbox"> <b>Use watermark</b> <i>(available only for PDF files)</i>';
        }
     }
     
     function addWatermarkTableHeader($params){
     	if ($params['type'] == PLUGIN_DOCMAN_ITEM_TYPE_FILE) {
            $params['titles'][] = 'Watermark';
        }
     }
     
     function addWatermarkTableCell($params){
        if ($params['type'] == PLUGIN_DOCMAN_ITEM_TYPE_FILE) {
            $params['html'] .= '<td align="center"><a href="">Enabled</a></td>';
        }
     }     
    
}

?>
