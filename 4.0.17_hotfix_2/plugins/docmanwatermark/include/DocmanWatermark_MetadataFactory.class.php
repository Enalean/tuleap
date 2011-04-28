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

require_once 'common/dao/CodendiDataAccess.class.php';
require_once 'DocmanWatermark_MetadataDao.class.php';
require_once 'DocmanWatermark_MetadataValueFactory.class.php';
                    
class DocmanWatermark_MetadataFactory {
    
    var $dao;
    
    protected $_watermarkedValues;
    
    public function __construct() {
        $this->dao = $this->_getWatermarkMetadataDao();
        // Cache watermarked values per item
        $this->_watermarkedValues = array();
    }
    
    public function &_getWatermarkMetadataDao() {
        if (!$this->dao) {
            $this->dao = new DocmanWatermark_MetadataDao(CodendiDataAccess::instance());
        }
        return $this->dao;
    }
    
    public function setField($wmd) {
        // remove the old field related watermarking values if any
        $dwmvf = new DocmanWatermark_MetadataValueFactory();
        $dwmvf->cleanFieldValuesByGroupId($wmd->getGroupId());
        // set the new metadata as confidentiality field
        $dar = $this->dao->searchByGroupId($wmd->getGroupId());
        if ($dar->valid()) {
            $this->dao->updateByGroupId($wmd->getGroupId(), $wmd->getId());
        } else {
            $this->dao->createByGroupId($wmd->getGroupId(), $wmd->getId());
        }
    }
    
    public function getMetadataIdFromGroupId($group_id) {
        $dar = $this->dao->searchByGroupId($group_id);
        if ($dar->rowCount() >0) {
            $row = $dar->current();
            return $row['field_id'];
        }
        return false;
    }
    
    public function getMetadataNameFromId($field_id) {
        $dar = $this->dao->searchNameByFieldId($field_id);
        if ($dar->rowCount() >0) {
            $row = $dar->current();
            return $row['name'];
        }
        return false;        
    } 

    /**
     * Return the metadata defined for watermarking (if any)
     * 
     * @param Integer $group_id
     * 
     * @return Docman_Metadata
     */
    public function _getWatermarkedMetadata($group_id) {
        $md = null;
        $mdId = $this->getMetadataIdFromGroupId($group_id);
        if($mdId != false) {
            // Check if this metadata if valid
            $mdf = $this->getDocman_MetadataFactory($group_id);
            $mdLabel = $mdf->getLabelFromId($mdId);
            $md = $mdf->getFromLabel($mdLabel);
        }
        return $md;
    }
    
    /**
     * Return all the values that watermark the given item
     * 
     * @param Docman_Item $item
     * @param Docman_Metadata $md
     * 
     * @return Array of Docman_MetadataListOfValuesElement
     */
    public function _getWatermarkingValues($item, $md) {
        // Get metadata values of the item
        $mdlvef = $this->getDocman_MetadataListOfValuesElementFactory();
        $values = $mdlvef->getLoveValuesForItem($item, $md);
        $itemValues = array();
        foreach($values as $value) {
            $itemValues[$value->getId()] = $value;
        }

        // Get Watermarked values
        $dwmvf = $this->getDocmanWatermark_MetadataValueFactory();
        $wmValues = $dwmvf->getMetadataValuesIterator($md->getId());

        $this->_watermarkedValues[$item->getId()] = null;
        foreach($wmValues as $value) {
            if($value->getWatermark() == 1 && isset($itemValues[$value->getValueId()])) {
                $this->_watermarkedValues[$item->getId()][$value->getValueId()] = $itemValues[$value->getValueId()];
            }
        }
        return $this->_watermarkedValues[$item->getId()];
    }
    
    /**
     * Return true if PDF watermarking is possible
     * 
     * Conditions:
     * - Watermarking is enabled for a metadata (!)
     * - The selected metadata is not deleted
     * - The selected at least one selected value is available
     * 
     * @return Boolean
     */
    public function isWatermarkingEnabled($item) {
        // Check if a metadata was selected for watermarking
        $md = $this->_getWatermarkedMetadata($item->getGroupId());
        if ($md != null) {
            // Check if their is at least one valid metadata value
            $values = $this->_getWatermarkingValues($item, $md);
            if ($values && count($values) > 0) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Returns the list of values that watermark the document
     * 
     * @param Docman_Item $item
     * 
     * @return Array of Docman_MetadataListOfValuesElement
     */
    public function getWatermarkingValues($item) {
        if (!isset($this->_watermarkedValues[$item->getId()])) {
            $md = $this->_getWatermarkedMetadata($item->getGroupId());
            $this->_getWatermarkingValues($item, $md);
        }
        return $this->_watermarkedValues[$item->getId()];
    }
    
    /**
     * Wrapper for Docman_MetadataFactory object
     * 
     * @param Integer $group_id
     * @return Docman_MetadataFactory
     */
    function getDocman_MetadataFactory($group_id) {
        return new Docman_MetadataFactory($group_id);
    }
    
    /**
     * Wrapper for Docman_MetadataListOfValuesElementFactory
     * 
     * @param Integer $metadata_id
     * @return Docman_MetadataListOfValuesElementFactory
     */
    function getDocman_MetadataListOfValuesElementFactory($metadata_id=null) {
        return new Docman_MetadataListOfValuesElementFactory($metadata_id);
    }
    
    /**
     * Wrapper for DocmanWatermark_MetadataValueFactory
     * 
     * @return DocmanWatermark_MetadataValueFactory
     */
    function getDocmanWatermark_MetadataValueFactory() {
        return new DocmanWatermark_MetadataValueFactory();
    }
}

?>