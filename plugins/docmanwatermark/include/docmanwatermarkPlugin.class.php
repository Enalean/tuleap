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
 
 
 /**
  *  Plugin to watermark PDF and made upon MVC framework
  *  
  */
 
 
require_once('common/plugin/Plugin.class.php');

class DocmanWatermarkPlugin extends Plugin {
    
    
    /**
     *  constructor of DocmanWatermarkPlugin class
     *  @param int id : the plugin id
     *  @return void
     */
    function __construct($id) {
        $this->Plugin($id);
        $this->_addHook('plugin_load_language_file', 'loadPluginLanguageFile', false);
        $this->_addHook('plugin_docman_file_before_download', 'stampFile', false);
        $this->_addHook('plugin_docman_after_admin_menu', 'addAdminMenuWatermark', false);
        $this->_addHook('plugin_docman_after_dispacher', 'dispachToController', false);
        $this->_addHook('plugin_docman_after_metadata_clone', 'importWatermarkMetadataSettings', false);
    }
    
    /**
     *  method to get the plugin info to be displayed in the plugin administration
     *  @param void
     *  @return void
     */
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'DocmanWatermarkPluginInfo')) {
            require_once('DocmanWatermarkPluginInfo.class.php');
            $this->pluginInfo = new DocmanWatermarkPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    /**
     *  hook method to load the plugin language file
     *  @param array params
     *  @return void 
     */
    function loadPluginLanguageFile($params) {
        $GLOBALS['Language']->loadLanguageMsg('docmanwatermark', 'docmanwatermark');
    }

    /**
     *  hook method to perform the PDF version stamping
     *  @param array params
     *  @return void
     */
    function stampFile($params) {
        require_once('DocmanWatermark_Stamper.class.php');
        $stamper = new DocmanWatermark_Stamper($params['path'],$params['headers'],$params['group_id'],$params['item'], $params['user']);
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
     *  hook method to add the admin menu to watermarking management
     *  @param array params
     *  @return void
     */
    function addAdminMenuWatermark($params) {
        require_once(dirname(__FILE__).'/../../docman/include/view/Docman_View_Extra.class.php');
        $dve = new Docman_View_Extra($params);
        $params['html'] .= '<h3><a href="'. $dve->buildUrl($params['default_url'], array('action' => 'admin_watermark')) .'">'. $GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_watermark') .'</a></h3>';
        $params['html'] .= '<p>'. $GLOBALS['Language']->getText('plugin_docmanwatermark', 'admin_watermark_descr') .'</p>';
    }
    
    /**
     *  method to process the plugin controller 
     *  @param void
     *  @return void 
     */
    
    function process() {
        require_once('DocmanWatermark_HTTPController.class.php');
        $controler = new DocmanWatermark_HTTPController($this, $this->_getPluginPath().'/../../plugins/docman',$this->_getPluginPath(), $this->_getThemePath());
        $controler->process();
    }
     
     
    /**
     *  hook method to dispach to watermark Controller
     *  @param array params
     *  @return void
     */
    function dispachToController($params) {
        $this->process();
        exit(0);
    }
    
    /**
     *  hook method to import watermark metadata settings
     */
    function importWatermarkMetadataSettings($params) {
        require_once('DocmanWatermark_MetadataFactory.class.php');
        $dwmf = new DocmanWatermark_MetadataFactory();
        $mdId = $dwmf->getMetadataIdFromGroupId($params['srcProjectId']);
        if ($mdId == $params['md']->getId()) {
            require_once('DocmanWatermark_MetadataImportFactory.class.php');
            $dwmif = new DocmanWatermark_MetadataImportFactory();
            $dwmif->setSrcProjectId($params['srcProjectId']);
            $dwmif->setTargetProjectId($params['targetProjectId']);
            $dwmif->importSettings($params['md']);
        }
    }
    
}

?>
