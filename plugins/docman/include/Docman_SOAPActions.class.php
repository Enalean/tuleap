<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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
require_once('Docman_Actions.class.php');

class Docman_SOAPActions extends Docman_Actions {

    /**
     * Append a chunk of data to a file
     */
    function appendFileChunk() {
        $request =& $this->_controler->request;

        if ($request->exist('item_id')) {
            $item_id = $request->get('item_id');
            $item_factory = $this->_getItemFactory();
            $item = $item_factory->getItemFromDb($item_id);
            $itemType = $item_factory->getItemTypeForItem($item);
            
            if($itemType == PLUGIN_DOCMAN_ITEM_TYPE_FILE) {
                $this->_storeFileChunk($item);
            } else {
                $this->_controler->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_not_a_file'));
            }
            
        } else {
            $this->_controler->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_append_filechunk'));
        }
    }
    
    /**
     * Adds a chunk to the last version of an existing file
     */
    function _storeFileChunk($item) {
        $fs       = $this->_getFileStorage();
        $request  = $this->_controler->request;
        if ($request->exist('chunk_offset') && $request->exist('chunk_size')) {
            $path = $fs->store($request->get('upload_content'), $request->get('group_id'), $item->getId(), $item->getCurrentVersion()->getNumber(), $request->get('chunk_offset'), $request->get('chunk_size'));
            if (!$path) {
                //TODO i18n
                $this->_controler->feedback->log('error', "Error while storing file chunk ");
            }
        }
    }
    
    /**
     * Returns the MD5 checksum of a file
     */
    function getFileMD5sum() {
        $request =& $this->_controler->request;

        if ($request->exist('item_id')) {
            $item_id = $request->get('item_id');
            $item_factory = $this->_getItemFactory();
            $item = $item_factory->getItemFromDb($item_id);
            $itemType = $item_factory->getItemTypeForItem($item);
            if($itemType == PLUGIN_DOCMAN_ITEM_TYPE_FILE) {
                $fs = $this->_getFileStorage();
                $md5sum = $fs->getFileMD5sum($request->get('group_id'), $item->getId(), $item->getCurrentVersion()->getNumber());
                $this->_controler->_viewParams['action_result'] = $md5sum;
                if (!$md5sum) {
                    $this->_controler->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_get_checksum'));
                }
            } else {
                $this->_controler->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_not_a_file'));
            }
        } else {
            $this->_controler->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_get_checksum'));
        }
    }
    
    /**
     * Returns the (used) metadata of the given project 
     */
    function getProjectMetadata() {
        $request =& $this->_controler->request;
        $groupId = $request->get('group_id');
        $metadataFactory = new Docman_MetadataFactory($groupId);
        $metadataList = $metadataFactory->getRealMetadataList(true);
        $this->_controler->_viewParams['action_result'] = $metadataList;
    }
    
    /**
     * Returns the list of values for the given list metadata.
     */
    function getMetadataListOfValues() {
        $request =& $this->_controler->request;
        $groupId = $request->get('group_id');
        $metadataFactory = new Docman_MetadataFactory($groupId);
        $metadataLovFactory = new Docman_MetadataListOfValuesElementFactory();
        
        $label = $request->get('label');
        
        $md = $metadataFactory->getFromLabel($label);
        
        $res = array();
        if($md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
           foreach ($metadataLovFactory->getListByFieldId($md->id, $md->label, false) as $val) {
               $res[] = $val;
           }
        }
        
        $this->_controler->_viewParams['action_result'] = $res;
    }
}

?>
