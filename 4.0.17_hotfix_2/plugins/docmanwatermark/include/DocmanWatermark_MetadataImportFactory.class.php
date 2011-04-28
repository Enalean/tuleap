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

require_once 'DocmanWatermark_MetadataValueFactory.class.php';
require_once 'DocmanWatermark_MetadataFactory.class.php';
require_once 'DocmanWatermark_Metadata.class.php';
require_once dirname(__FILE__).'/../../docman/include/Docman_MetadataDao.class.php';
        
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
     * Public Method to create the watermark metadata map in the target project (customized for the import purpose)
     * @param  Docman_Metadata object dm: the metadata object to be checked
     * @return array ('md' => int matched metadataId, 'love' => related loves)
     */    
    public function getWatermarkMetadataMap($md) {
        // get the metadata iterator from target project
        $dmd = new Docman_MetadataDao(CodendiDataAccess::instance());
        $dar = $dmd->searchByName($this->targetProjectId,$md->getName());
        // if the name doesn't match then return md = 0
        $dar->rewind();
        if (!$dar->valid()) {
            return array('md' => 0, 'love' => array ());
        } else {
            // check metadata properties type, useIt, AllowMultipleValue if it doesn't much then return md = 0
            while($dar->valid()) {
                $row = $dar->current();
                if(($row['use_it'] == $md->isUsed()) && ($row['data_type'] == $md->getType()) && ($row['mul_val_ok'] == $md->isMultipleValuesAllowed())) {
                    // it seams that the metadata is ok
                    // check now if source Metadata values already exist
                    $dmlovef = new Docman_MetadataListOfValuesElementFactory($md->getId());
                    $loveIter = $dmlovef->getIteratorByFieldId($md->getId(), $md->getLabel(), true);
                    
                    $dmloveft = new Docman_MetadataListOfValuesElementFactory($row['field_id']);

                    $loveIter->rewind();
                    $i = 0;
                    $loves = array();
                    while($loveIter->valid()){
                        $love = $loveIter->current();
                        $lovetIter = $dmloveft->getByName($love->getName(), $row['field_id'], true);
                        $lovetIter->rewind();
                        if (!$lovetIter->valid()) {
                            return array('md' => 0, 'love' => array ());;
                        }
                        $lovet = $lovetIter->current();
                        $dwmdvf = new DocmanWatermark_MetadataValueFactory();
                        $loves[$i]['value_id']  = $lovet->getId();
                        $loves[$i]['watermark'] = $dwmdvf->isWatermarkedOnValue($love->getId());
                        $i++;
                        $loveIter->next();
                    }
                    return array('md' => $row['field_id'], 'love' => $loves);
                }
                $dar->next();
            }
            return array('md' => 0, 'love' => array ());
        }
    }

    /**
     * Private Method to copy the watermarkmetadata setup from the src project to target project
     * @param  int md_id: the metadata id to insert in the table plugin_docman_watermark_md_extension
     * @return void
     */    
    private function copyWatermarkMetadata($md_id){
        $dwmd = new DocmanWatermark_Metadata();
        $dwmd->setGroupId($this->targetProjectId);
        $dwmd->setId($md_id);
        $dwmf = new DocmanWatermark_MetadataFactory();
        $dwmf->setField($dwmd);
    }
    

    /**
     * Private Method to copy the metadata values setup from the src project to target project
     * @param  ArrayIterator(int) $dwmvs : iterator of metadata values  to insert in the table plugin_docman_watermark_love_md_extension
     * @return void
     */     
    
    private function copyWatermarkMetadataValues($loves){
        $arrdwmv = array();
        for($i=0;$i<count($loves);$i++) {
            $dwmv = new DocmanWatermark_MetadataValue();
            $dwmv->setValueId($loves[$i]['value_id']);
            $dwmv->setWatermark($loves[$i]['watermark']);
            $arrdwmv[] = $dwmv;
        }
        // update watermarking settings related to the source project
        $dwmvf = new DocmanWatermark_MetadataValueFactory();
        $dwmvf->updateMetadataValues(new ArrayIterator($arrdwmv),$this->targetProjectId);     
    }

    /**
     * Public Method to import all watermark setting from the src project to target project
     * @param  void
     * @return void
     */        
    public function importSettings($md) {
        $mdMap = $this->getWatermarkMetadataMap($md);
        if($mdMap['md'] != 0) {
            $this->copyWatermarkMetadata($mdMap['md']);
            $this->copyWatermarkMetadataValues($mdMap['love']);
        }
    }
}


?>