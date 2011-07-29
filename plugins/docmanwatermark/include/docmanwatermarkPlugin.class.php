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
 
 
 /**
  *  Plugin to watermark PDF and made upon MVC framework
  *  
  */
 
 
require_once 'common/plugin/Plugin.class.php';
require_once('exception/EncryptedPdfException.class.php');

class DocmanWatermarkPlugin extends Plugin {
    
    
    /**
     *  constructor of DocmanWatermarkPlugin class
     *  @param int id : the plugin id
     *  @return void
     */
    function __construct($id) {
        parent::__construct($id);
        $this->_addHook('plugin_load_language_file', 'loadPluginLanguageFile', false);
        $this->_addHook('plugin_docman_file_before_download', 'stampFile', false);
        $this->_addHook('plugin_docman_after_admin_menu', 'addAdminMenuWatermark', false);
        $this->_addHook('plugin_docman_after_dispacher', 'dispachToController', false);
        $this->_addHook('plugin_docman_after_metadata_clone', 'synchronizeWatermarkMetadataSettings', false);
        $this->_addHook('plugin_docman_view_details_after_tabs', 'plugin_docman_view_details_after_tabs', false);
        $this->_addHook('plugin_docman_after_new_document', 'plugin_docman_after_document_upload', false);
        $this->_addHook('plugin_docman_after_new_version', 'plugin_docman_after_document_upload', false);
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
     *  hook method to perform the PDF version stamping
     *  @param array params
     *  @return void
     */
    function stampFile($params) {
        require_once('DocmanWatermark_Stamper.class.php');
        $pInfo    = $this->getPluginInfo();
        $zendPath = $pInfo->getPropertyValueForName("zend_path");
        $stamper  = new DocmanWatermark_Stamper($zendPath, $params['item'], $params['version'], $params['user']);
        $dwmf     = $this->getDocmanWatermark_MetadataFactory();
        $dwif     = $this->getDocmanWatermark_ItemFactory();
        try {
            if ($stamper->check() &&
            !$dwif->isWatermarkingDisabled($params['item']->getId()) &&
            $dwmf->isWatermarkingEnabled($params['item'])) {
                $stamper->load();
                $stamper->stamp($dwmf->getWatermarkingValues($params['item']));
                $stamper->render();
                exit(0);
            }
        } catch (Zend_pdf_exception $e) {
            //the application should not be able to download a file 
            //if some problem occure during stamping process

            // Here is the case when pdf doc is encrypted. We cancel download and redirect to error page.
            if(strpos($e->getMessage(), "Encrypted") !== FALSE){
                throw new EncryptedPdfException($GLOBALS['Language']->getText('plugin_docmanwatermark', 'error_no_controller_found'));
            }
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
     *  hook method to dispach to watermark Controller
     *  @param array params
     *  @return void
     */
    function dispachToController($params) {
        require_once('DocmanWatermark_HTTPController.class.php');
        $controler = new DocmanWatermark_HTTPController($this, $params['docmanPath'], $this->getPluginPath(), $this->getThemePath());
        $controler->process();
        exit(0);
    }
    
    /**
     *  hook method to synchnonize watermark metadata settings
     */
    function synchronizeWatermarkMetadataSettings($params) {
        require_once('DocmanWatermark_MetadataFactory.class.php');
        require_once('DocmanWatermark_MetadataImportFactory.class.php');
        $dwmf = new DocmanWatermark_MetadataFactory();
        $dwmif = new DocmanWatermark_MetadataImportFactory();
        $mdId = $dwmf->getMetadataIdFromGroupId($params['srcProjectId']);
        if ($mdId == $params['md']->getId()) {
            $dwmif->setSrcProjectId($params['srcProjectId']);
            $dwmif->setTargetProjectId($params['targetProjectId']);
            $dwmif->importSettings($params['md']);
        }
    }
    
    /**
     * Hook to add a tab dedicated to watermaring in docman
     */
    function plugin_docman_view_details_after_tabs($params) {
        include_once 'view/DocmanWatermark_View_ItemDetailsSectionWatermarking.class.php';
        $dwmf = $this->getDocmanWatermark_MetadataFactory();
        if ($params['item'] instanceof Docman_File && $dwmf->isWatermarkingEnabled($params['item'])) {
            $params['sections']['watermarking'] = true;
            $params['details']->addSection(new DocmanWatermark_View_ItemDetailsSectionWatermarking($params['item'], $params['default_url']));
        }
    }
    
    /**
    * Hook to check if the uploaded document is a pdf file. If it is the case, user is warned about possible watermarking limitations.
    */
    function plugin_docman_after_document_upload($params) {
        // use docmanwatermark stamper to check watermarking limit for item. 
        // For perfs reasons, it now just checks if it is a pdf doc. Should be extended
        include_once 'DocmanWatermark_Stamper.class.php';
        $pInfo    = $this->getPluginInfo();
        $zendPath = $pInfo->getPropertyValueForName("zend_path");
        $stamper  = new DocmanWatermark_Stamper($zendPath, $params['item'], $params['version'], $params['user']);
        $dwif = $this->getDocmanWatermark_ItemFactory();
        $dwmf = $this->getDocmanWatermark_MetadataFactory();
        if($stamper->check() 
            && !$dwif->isWatermarkingDisabled($params['item']->getId())
            && $dwmf->isWatermarkingEnabled($params['item'])){
            $watermarkingDetailsUrl = $params['docmanControler']->getDefaultUrl().'&action=details&id='.$params['item']->getId().'&section=watermarking';
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_docmanwatermark', 'info_watermarking_management', array($watermarkingDetailsUrl)), CODENDI_PURIFIER_DISABLED);
        }
    }

    /**
     * Wrapper for Docman_PermissionsManager class
     * @param $groupId
     * @return Docman_PermissionsManager
     */
    function getDocman_PermissionsManager($groupId) {
        include_once dirname(__FILE__).'/../../docman/include/Docman_PermissionsManager.class.php';
        return Docman_PermissionsManager::instance($groupId);
    }
    
    /**
     * Wrapper for DocmanWatermark_MetadataFactory object
     * 
     * @return DocmanWatermark_MetadataFactory
     */
    function getDocmanWatermark_MetadataFactory() {
        include_once 'DocmanWatermark_MetadataFactory.class.php';
        return new DocmanWatermark_MetadataFactory();
    }
    
    /**
     * Wrapper for DocmanWatermark_MetadataFactory object
     * 
     * @return DocmanWatermark_MetadataFactory
     */
    function getDocmanWatermark_ItemFactory() {
        include_once 'DocmanWatermark_ItemFactory.class.php';
        return new DocmanWatermark_ItemFactory();
    }
}

?>
