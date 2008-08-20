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
        require_once(dirname(__FILE__).'/../../docman/include/Docman_MetadataListOfValuesElementDao.class.php');
        require_once(dirname(__FILE__).'/../../docman/include/Docman_MetadataFactory.class.php');
        require_once('DocmanWatermark_MetadataFactory.class.php');
        
        // get the metadata selected as confidentiality field
        $dwmf = new DocmanWatermark_MetadataFactory();
        $md_id = $dwmf->getMetadataIdFromGroupId($this->srcProjectId);
        // instanciate metadata dao to search metadata from the source and clone it in the target project
        $mdd = new Docman_MetadataDao(CodexDataAccess::instance());
        $dar = $mdd->searchById($md_id);
        $loves = array();
        $dar->rewind();
        if ($dar->valid()) {
            $mdt = $dar->current();
            $dmfSrc = new Docman_MetadataFactory($this->srcProjectId);
            $mdIter = $dmfSrc->findByName($mdt['name']);
            $mdIter->rewind();
            if ($mdIter->valid()) {
                $mdloved = new Docman_MetadataListOfValuesElementDao(CodexDataAccess::instance());
                // $md is the metadata selected as confidentiality field
                $md = $mdIter->current();
                $m_map = $this->searchMetadata($md);
                if($m_map['md'] == 0) { // the metadata does not exist in the target project
                    // create the metadata and get the new metadata ID in the target project
                    $newMdId = $mdd->create($this->targetProjectId, $md->getName(), $md->getType(), $md->getDescription()
                                            , $md->isRequired(), $md->isEmptyAllowed(), $md->isMultipleValuesAllowed(), $md->isSpecial(), $md->getUseIt());
                    
                    // get the list of love of the metadata in the source project
                    $dmlovef = new Docman_MetadataListOfValuesElementFactory($md->getId());
                    $loveIter = $dmlovef->getIteratorByFieldId($md->getId(), $md->getLabel(), true);
                    // create none value for the new metadata id in the target project
                    $dmlovenf = new Docman_MetadataListOfValuesElementFactory($newMdId);
                    $dmlovenf->createNoneValue();
                    
                    $dwmvf = new DocmanWatermark_MetadataValueFactory();
                    
                    $loveIter->rewind();
                    $i = 0;
                    while($loveIter->valid()){
                        $love = $loveIter->current();
                        if ($love->getId() != 100){
                            $loves[$i]['value_id']  = $mdloved->create($newMdId, $love->getName(), $love->getDescription(), $love->getRank(), $love->getStatus());
                            $loves[$i]['watermark'] = $dwmvf->isWatermarked($love->getId());
                            $i++;
                        }
                        $loveIter->next();
                    }
                    return array ('md'   => $newMdId,
                                  'love' => $loves);
                } else {
                    return $m_map;
                }
            }
        } else {
            return array();
        }
    }

    /**
     * Private Method to search a metadata in the target project (customized for the import purpose)
     * @param  Docman_Metadata object dm: the metadata object to be checked
     * @return array ('md' => int matched metadataId, 'love' => related loves)
     */    
    private function searchMetadata($md) {
        // get the metadata iterator from target project
        require_once(dirname(__FILE__).'/../../docman/include/Docman_MetadataDao.class.php');
        $dmd = new Docman_MetadataDao(CodexDataAccess::instance());
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
                        $loves[$i]['watermark'] = $dwmdvf->isWatermarked($love->getId());
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
        require_once('DocmanWatermark_MetadataFactory.class.php');
        require_once('DocmanWatermark_Metadata.class.php');
        $dwmd = new DocmanWatermark_Metadata();
        $dwmd->setGroupId($this->targetProjectId);
        $dwmd->setId($md_id);
        $dwmf = new DocmanWatermark_MetadataFactory();
        $dwmf->createField($dwmd);
    }
    

    /**
     * Private Method to copy the metadata values setup from the src project to target project
     * @param  ArrayIterator(int) $dwmvs : iterator of metadata values  to insert in the table plugin_docman_watermark_love_md_extension
     * @return void
     */     
    
    private function copyWatermarkMetadataValues($loves){
        require_once('DocmanWatermark_MetadataValue.class.php');
        require_once('DocmanWatermark_MetadataValueFactory.class.php');
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
    public function importSettings() {
        $mdMap = $this->copyMetadata();
        $this->copyWatermarkMetadata($mdMap['md']);
        $this->copyWatermarkMetadataValues($mdMap['love']);
    }
}


?>