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

require_once('DocmanWatermark_MetadataDao.class.php');

class DocmanWatermark_MetadataFactory {
    
    var $dao;
    
    public function __construct() {
        $this->dao = $this->_getWatermarkMetadataDao();
    }
    
    public function &_getWatermarkMetadataDao() {
        if (!$this->dao) {
            $this->dao = new DocmanWatermark_MetadataDao(CodexDataAccess::instance());
        }
        return $this->dao;
    }
    
    public function setField($wmd) {
        
        // remove the old field related watermarking values if any
        require_once('DocmanWatermark_MetadataValueFactory.class.php');
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
    
    public function createField($wmd) {
        // remove the old field related watermarking values if any
        require_once('DocmanWatermark_MetadataValueFactory.class.php');
        $dwmvf = new DocmanWatermark_MetadataValueFactory();
        $dwmvf->cleanFieldValuesByGroupId($wmd->getGroupId());
        
        $this->dao->deleteByGroupId($wmd->getGroupId());
        $this->dao->createByGroupId($wmd->getGroupId(), $wmd->getId());
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
    
}

?>