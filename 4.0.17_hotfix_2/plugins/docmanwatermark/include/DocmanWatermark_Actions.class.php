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

require_once 'common/mvc/Actions.class.php';
require_once 'common/include/HTTPRequest.class.php';

require_once 'DocmanWatermark_MetadataFactory.class.php';
require_once 'DocmanWatermark_MetadataValueFactory.class.php';
require_once 'DocmanWatermark_MetadataImportFactory.class.php';
require_once 'DocmanWatermark_ItemFactory.class.php';
require_once 'DocmanWatermark_Log.class.php';

class DocmanWatermark_Actions extends Actions {

    public function __construct($controler, $view=null) {
        parent::__construct($controler);
    }
    
    public function setup_metadata() {
        $mf = new DocmanWatermark_MetadataFactory();
        $md_id    = $this->_controler->_actionParams['md_id'];
        $group_id = $this->_controler->_actionParams['group_id'];
        $wmd      = new DocmanWatermark_Metadata();
        $wmd->setGroupId($group_id);
        $wmd->setId($md_id);
        $mf->setField($wmd);
    }
    
    public function setup_metadata_values() {
        $wmdv     = $this->_controler->_actionParams['md_values'];
        $group_id = $this->_controler->_actionParams['group_id'];
        $mvf      = new DocmanWatermark_MetadataValueFactory();
        $mvf->updateMetadataValues($wmdv, $group_id);
    }
    
    public function import_from_project() {
        $dwmi = new DocmanWatermark_MetadataImportFactory();
        $src_group_id     = $this->_controler->_actionParams['src_group_id'];
        $target_group_id  = $this->_controler->_actionParams['target_group_id'];
        $md               = $this->_controler->_actionParams['md'];
        $dwmi->setSrcProjectId($src_group_id);
        $dwmi->setTargetProjectId($target_group_id);
        $dwmi->importSettings($md);
    }
    
    public function docmanwatermark_toggle_item() {
        $itemId  = $this->_controler->request->getValidated('item_id', 'UInt');
        if($itemId != null &&
           $this->_controler->userCanManage($itemId) &&
           $this->_controler->request->isPost()) {
               
            $itemFactory   = new Docman_ItemFactory();
            $dwItemFactory = new DocmanWatermark_ItemFactory();
            $dwLog         = new DocmanWatermark_Log();

            $item = $itemFactory->getItemFromDb($itemId);
            $user = $this->_controler->getUser();
            if ($this->_controler->request->existAndNonEmpty('disable_watermarking')) {
                if ($dwItemFactory->disableWatermarking($itemId)) {
                    $dwLog->disableWatermarking($item, $user);
                    $dwItemFactory->notifyOnDisable($item, $user, $this->_controler->getDefaultUrl());
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_docmanwatermark', 'action_watermarking_disabled'));
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_docmanwatermark', 'action_watermarking_disable_error'));
                }
            } else {
                if ($dwItemFactory->enableWatermarking($itemId)) {
                    $dwLog->enableWatermarking($item, $user);
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_docmanwatermark', 'action_watermarking_enabled'));
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_docmanwatermark', 'action_watermarking_enable_error'));
                }
            }
            
            $this->_controler->view   = 'RedirectAfterCrud';
            $this->_controler->_viewParams['redirect_to'] = $this->_controler->getDefaultUrl().'&action=details&id='.$itemId.'&section=watermarking';
        } else {
            // Bad Item Id or attempt to fake the server, redirect to root
            // @todo: log those kind of attempt.
            $this->_controler->view   = 'RedirectAfterCrud';
            $this->_controler->_viewParams['redirect_to'] = $this->_controler->getDefaultUrl();
        }
    }
}

?>
