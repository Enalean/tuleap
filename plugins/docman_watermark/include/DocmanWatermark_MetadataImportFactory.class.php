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


class DocmanWatermark_MetadataImportFactory  {
    
    var $srcProjectId;
    var $targetProjectId;
    
    /**
    * Constructs the DocmanWatermark_MetadataImportFactory
    * @param void
    */
    public function __construct() {
    }

    public function getSrcProjectId() {
        return $this->srcProjectId;
    }
        
    public function setSrcProjectId($srcProjectId) {
        $this->srcProjectId = $srcProjectId;
    }

    public function getTargetProjectId() {
        return $this->targetProjectId;
    }
        
    public function setTargetProjectId($targetProjectId) {
        $this->targetProjectId = $targetProjectId;
    }
    
    /**
     * Private Method to copy the metadata setup from the src project to target project
     * @param void
     * @return int: the target metadata id or field_id
     */
    private function copyMetadata(){
        require_once(dirname(__FILE__).'/../../docman/include/Docman_MetadataDao.class.php');
        require_once('DocmanWatermark_MetadataFactory.class.php');
        $dwmf = new DocmanWatermark_MetadataFactory();
        $md_id = $dwmf->getMetadataIdFromGroupId($this->srcProjectId);
        $mdd = new Docman_MetadataDao(CodexDataAccess::instance());
        $dar = $mdd->searchById($md_id);
        $dar->rewind();
        if ($dar->valid()) {
            $md = $dar->current();
            return $mdd->create($this->targetProjectId, $md['name'], $md['type'], $md['description'],
                                $md['isRequired'], $md['isEmptyAllowed'], $md['mulValuesAllowed'], $md['isSpecial'], $md['isUsed']);
        } else {
            return false;
        }
    }

    /**
     * Private Method to copy the watermarkmetadata setup from the src project to target project
     * @param  int md_id: the metadata id to insert in the table plugin_docman_watermark_md_extension
     * @return void
     */    
    private function copyWatermarkMetadata($md_id){
        require_once('DocmanWatermark_MetadataFactory.class.php');
        require_once('DocmanWatermark_Metadata.class.php');
        $dwmd = new DocmanWatermark_Metadata();
        $dwmd->setGroupId($this->targetProjectId);
        $dwmd->setId($md_id);
        $dwmf = new DocmanWatermark_MetadataFactory();
        $dwmf->setField($dwmd);
    }
    
    private function copyMetadataValues($md_id){
        require_once(dirname(__FILE__).'/../../docman/include/Docman_MetadataListOfValuesElementFactory.class.php');
        require_once(dirname(__FILE__).'/../../docman/include/Docman_MetadataFactory.class.php');
        $dmlovef = new Docman_MetadataListOfValuesElementFactory();
        $dmf = new Docman_MetadataFactory($this->srcProjectId);
        $mdLabel = $dmf->getLabelFromId($md_id);
        $dar = $dmlovef->getIteratorByFieldId($md_id,$mdLabel,true);
        $dar->rewind();
        $dwmvs = array();
        while($dar->valid()) {
            require_once('DocmanWatermark_MetadataValue.class.php');
            require_once('DocmanWatermark_MetadataValueFactory.class.php');
            $row = $dar->current();
            $id = $dmloved->createElement($row['name'], $row['description'], $row['rank'], $row['status']);
            $dwmv = new DocmanWatermark_MetadataValue();
            $dwmv->setId($id);
            $dwmvf = new DocmanWatermark_MetadataValueFactory();
            $dwmv->setWatermark($dwmvf->isWatermarked());
            $dwmvs [] = $dwmv; 
            $dar->next();
        }
        return new ArrayIterator($dwmvs);
    }

    /**
     * Private Method to copy the metadata values setup from the src project to target project
     * @param  ArrayIterator(int) $dwmvs : iterator of metadata values  to insert in the table plugin_docman_watermark_love_md_extension
     * @return void
     */     
    
    private function copyWatermarkMetadataValues($dwmvs){
        require_once(dirname(__FILE__).'/../../docman/include/Docman_MetadataListOfValuesElementFactory.class.php');
        require_once(dirname(__FILE__).'/../../docman/include/Docman_MetadataFactory.class.php');
        require_once('DocmanWatermark_MetadataValue.class.php');
        $mdf   = new Docman_MetadataFactory($this->srcProjectId);
        $dwmf = new DocmanWatermark_MetadataFactory();
        $md_id = $dwmf->getMetadataIdFromGroupId($this->srcProjectId);
        $mdLabel = $mdf->getLabelFromId($md_id);
        $mlvef = new Docman_MetadataListOfValuesElementFactory($md_id);
        $mlveIter = $mlvef->getIteratorByFieldId($md_id, $mdLabel, true);
        while($dwmvs->valid()) {
            $dwmv = $dwmvs->current();
            $dwmv->updateMetadataValues();
            $dwmvs->next();
        }        
    }
    
    public function importSettings() {
        $md_id = $this->copyMetadata();
        $this->copyWatermarkMetadata($md_id);
        $dwmvs = $this->copyMetadataValues($md_id);
        $this->copyWatermarkMetadataValues($dwmvs);
    }
}


?>