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
require_once('DocmanWatermark_MetadataValueDao.class.php');

class DocmanWatermark_MetadataValueFactory {
    
    var $dao;
    
    public function __construct() {
        $this->_getWatermarkMetadataValueDao();
    }
    
    private function &_getWatermarkMetadataValueDao() {
        if (!$this->dao) {
            require_once('common/dao/CodexDataAccess.class.php');
            $this->dao = new DocmanWatermark_MetadataValueDao(CodexDataAccess::instance());
        }
        return $this->dao;
    }
    
    public function updateMetadataValues($wmdvIter, $groupId) {
        $this->dao->deleteByGroupId($groupId);
        $wmdvIter->rewind();
        while ($wmdvIter->valid()) {
            $mdv   = $wmdvIter->current();
            // exclude None value
            if ($mdv->getValueId() != 100) {
                $this->dao->update($mdv, $groupId);
            }
            $wmdvIter->next();
        }
    }
    
    public function getMetadataValuesIterator($fieldId) {
        require_once ('DocmanWatermark_MetadataValue.class.php');
        $dar = $this->dao->searchByFieldId($fieldId);
        $valuesArr = array();
        $dar->rewind();
        while($dar->valid()) {
            $row = $dar->current();
            $wmdv = new DocmanWatermark_MetadataValue();
            $wmdv->setValueId($row['value_id']);
            $wmdv->setWatermark($row['watermark']);
            $valuesArr[] = $wmdv;
            $dar->next();
        }
        return new ArrayIterator($valuesArr);
    }
    
    public function cleanFieldValuesByGroupId($groupId) {
        $this->dao->deleteByGroupId($groupId);
    }
    
    public function isWatermarkedOnValue($valueId) {
        $dar = $this->dao->searchByValueId($valueId);
        $dar->rewind();
        if($dar->valid()) {
            $row = $dar->current();
            if ($row['watermark']) {
                return true;
            }
        }
        return false;
    }
    
}

?>
