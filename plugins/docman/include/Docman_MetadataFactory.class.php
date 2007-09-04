<?php
/**
 * Copyright � STMicroelectronics, 2006. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2006.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * 
 * 
 */

require_once('Docman_Metadata.class.php');
require_once('Docman_MetadataDao.class.php');
require_once('Docman_SettingsBo.class.php');
require_once('Docman_MetadataListOfValuesElementFactory.class.php');

require_once('common/collection/ArrayIterator.class.php');

/**
 * MetadataFactory give access to metadata fields
 *
 * 'Metadata fields' means 'list of metadata associated to a project'. The
 * target of this class is to handle the fields (at project level) and not the
 * fields values.
 *
 * There is 2 kind of metadata: 
 * * HardCoded metadata: stored as columns of docman tables.
 * * Real metadata: stored as entry of docman_field table.
 */
class Docman_MetadataFactory {
    var $hardCodedMetadata;
    var $modifiableMetadata;
    var $groupId;
    
    function Docman_MetadataFactory($groupId) {
        // Metadata hard coded as table columns
        $this->hardCodedMetadata = array('title', 'description', 'owner'
                                         , 'create_date', 'update_date'
                                         , 'status', 'obsolescence_date');

        // Metadata hard coded as table columns but with some user-defined
        // states such as 'useIt' in a dedicated table       
        $this->modifiableMetadata = array('obsolescence_date', 'status');

        $this->scalarMetadata     = array(PLUGIN_DOCMAN_METADATA_TYPE_TEXT,
                                          PLUGIN_DOCMAN_METADATA_TYPE_STRING,
                                          PLUGIN_DOCMAN_METADATA_TYPE_DATE);

        $this->groupId = $groupId;
    }
    
    /**
     * Return Docman_MetadataDao object
     *
     */
    function &getDao() {
        static $_plugin_docman_metadata_dao_instance;
        if(!$_plugin_docman_metadata_dao_instance) {
            $_plugin_docman_metadata_dao_instance =& new Docman_MetadataDao(CodexDataAccess::instance());
        }
        return $_plugin_docman_metadata_dao_instance;
    }

    /**
     * For a real metadata, the field name is based on the id
     */
    function getLabelFromId($id) {
        return 'field_'.$id;
    }

    /**
     * Factory method. Create a Docman_Metadata object based on the type of the
     * medatadata. Object is created from a row from the DB.
     */
    function &_createFromRow(&$row) {
        switch($row['data_type']) {
        case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
            $md = new Docman_ListMetadata();
            break;
            
        default:
            $md = new Docman_Metadata();
        }
        $md->initFromRow($row);

        return $md;
    }

    /**
     * Create a Metadata object based on DB a entry.
     */
    function &getRealMetadata($id) {
        $md = null;

        $dao =& $this->getDao();
        $dar = $dao->searchById($id);
        if($dar->rowCount() === 1) {            
            $md =& $this->_createFromRow($dar->current());
        
            $md->setCanChangeName(true);
            $md->setCanChangeIsEmptyAllowed(true);
            $md->setCanChangeIsMultipleValuesAllowed(true);
            $md->setCanChangeDescription(true);
            $md->setCanChangeDefaultValue(true);
        }

        return $md;
    }

    /**
     * Create the list of Real metadata associated with a project.
     *
     * @param boolean $onlyUsed Return only metadata enabled by the project.
     */
    function &getRealMetadataList($onlyUsed = false, $type = array()) {
        $mda = array();

        $dao =& $this->getDao();
        $dar = $dao->searchByGroupId($this->groupId, $onlyUsed, $type);
        while($dar->valid()) {
            $row =& $dar->current();                        
            
            $mda[] =& $this->_createFromRow($row);

            $dar->next();
        }

        return $mda;
    }

    function &getRealMetadataIterator($onlyUsed = false, $type = array()) {
        $mda =& $this->getRealMetadataList($onlyUsed, $type);
        $mdi = new ArrayIterator($mda);
        return $mdi;
    }

    /**
     * Fetch and append HardCoded metadata variable parameters.
     *
     * Some HardCoded are customizable at project level.
     */
    function appendHardCodedMetadataParams(&$md) {
        $sBo =& Docman_SettingsBo::instance($this->groupId);
        $md->setUseIt($sBo->getMetadataUsage($md->getLabel()));
    }

    /**
     * Build a list of HardCoded metadata.
     *
     * @param boolean $onlyUsed Return only metadata enabled by the project.
     */
    /*private*/ function &_buildHardCodedMetadataList(&$mdLabelArray, $onlyUsed = false) {
        $mda = array();
        foreach($mdLabelArray as $mdLabel) {
            $md =& $this->getHardCodedMetadataFromLabel($mdLabel);
            
            if(in_array($md->getLabel(), $this->modifiableMetadata)) {
                $this->appendHardCodedMetadataParams($md);
            }

            if($onlyUsed) {
                if($md->isUsed()) {
                    $mda[] =& $md;
                }
            }
            else {            
                $mda[] =& $md;
            }
        }
        return $mda;
    }
    
    /**
     * Return an array of HardCoded metadata
     *
     * @param boolean $onlyUsed Return only metadata enabled by the project.
     */
    function &getHardCodedMetadataListForDocuments($onlyUsed = false) {
        $mda =& $this->_buildHardCodedMetadataList($this->hardCodedMetadata, $onlyUsed);
        return $mda;
    }

    function getHardCodedMetadataListForFolders($onlyUsed = false) {
        $mdOnSubmit = array('title', 'description',// 'owner'
                            'create_date', 'update_date');
        $mda =& $this->_buildHardCodedMetadataList($mdOnSubmit, $onlyUsed);
        return $mda;
    }

    function getMetadataLabelToSkipCreation() {
        $labels = array('owner', 'create_date', 'update_date');
        return $labels;
    }
   
    /**
     * Return all metadata for current project.
     *
     * @param boolean $onlyUsed Return only metadata enabled by the project.
     */
    function &getMetadataForGroup($onlyUsed = false) {        
        $mda = array_merge($this->getHardCodedMetadataListForDocuments($onlyUsed),
                           $this->getRealMetadataList($onlyUsed));
        
        $i = new ArrayIterator($mda);
        return $i;
    }    

    /**
     * Append elements of ListOfValues metadata.
     *
     * @param Docman_ListMetadata The metadata.
     * @param Boolean             Return only active values if true.
     */
    function appendMetadataValueList(&$md, $onlyActive = true) {
        if(is_a($md, 'Docman_ListMetadata')) {
            $mdLoveFactory = new Docman_MetadataListOfValuesElementFactory();
            $mdLoveArray =& $mdLoveFactory->getListByFieldId($md->getId(), $md->getLabel(), $onlyActive);        
            $md->setListOfValueElements($mdLoveArray);
        }
    }

    /**
     * Add ListOfValues to each 'ListMetadata' in given Metadata iterator
     *
     * @param ArrayIterator Metadata iterator.
     */
    function appendAllListOfValues(&$mdIter) {
        $mdIter->rewind();
        while($mdIter->valid()) {
            $md =& $mdIter->current();
            
            if($md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                $this->appendMetadataValueList($md, true);
            }
            
            $mdIter->next();
        }
    }

    /**
     * For given item, appends all its ListOfValue metadata.
     */
    function appendAllListOfValuesToItem(&$item) {
        $iter =& $item->getMetadataIterator();
        $this->appendAllListOfValues($iter);
    }

    /**
     * Add all the metadata (with their values) to the given item
     */
    function appendItemMetadataList(&$item) {
        $mda = array();
        if(Docman_ItemFactory::getItemTypeForItem($item) == PLUGIN_DOCMAN_ITEM_TYPE_FOLDER) {
            $isFolder = true;
            $mda = $this->getHardCodedMetadataListForFolders(true);
        }
        else {
            $isFolder = false;
            $mda =& $this->getHardCodedMetadataListForDocuments(true);
        }

        foreach($mda as $md) {
            $md->setValue($item->getHardCodedMetadataValue($md->getLabel()));
            $item->addMetadata($md);
            unset($md);
        }
        
        if(!$isFolder) {
            $mdIter =& $this->getRealMetadataIterator(true);
            $mdIter->rewind();
            while($mdIter->valid()) {
                $md = $mdIter->current();
                $this->addMetadataValueToItem($item, $md);
                $mdIter->next();
            }
        }        
    }


    /**
     * Return the metadata value for a given metadata and item.
     *
     * @return either a scalar (date, string, ...) or a LoveIterator for the
     * list of values.
     */
    function &getMetadataValue($item, $md) {
        $value = null;
        if($md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
            $loveFactory = new Docman_MetadataListOfValuesElementFactory();
            $value = $loveFactory->getLoveValuesForItem($item, $md);
        }
        else {
            $dao =& $this->getDao();
            $dar = $dao->searchValueById($md->getId(), $item->getId());
            if($dar && !$dar->isError() && $dar->rowCount() == 1) {
                $value = $this->_getMetadataValueFromRow($md, $dar->current());
            }
        }
        return $value;
    }

    /**
     * add to given item the metadata value of the given metadata.
     */
    function addMetadataValueToItem(&$item, $md) {
        $value = $this->getMetadataValue($item, $md);
        $md->setValue($value);
        $item->addMetadata($md);
    }

    /**
     * @access: private
     */
    function _getMetadataValueFromRow($md, $row) {
        $value = null;
        switch($md->getType()) {
        case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
            $value = $row['valueText'];
            break;
        case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
             $value = $row['valueString'];
            break;
        case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
            $value = $row['valueDate'];
            break;
        }
        return $value;
    }

    /**
     * Return the Metadata corresponding to the given label.
     */
    function &getFromLabel($label) {
        if(in_array($label, $this->hardCodedMetadata)) {
            $md =& $this->getHardCodedMetadataFromLabel($label);

            if($this->groupId !== null) {
                $md->setGroupId($this->groupId);
            }

            if(in_array($md->getLabel(), $this->modifiableMetadata)) {
                $this->appendHardCodedMetadataParams($md);
            }

            return $md;
        }
        else {
            if(preg_match('/^field_([0-9]+)$/', $label, $match)) {
                return $this->getRealMetadata($match[1]);
            }
            else {
                trigger_error($GLOBALS['Language']->getText('plugin_docman',
                                                            'md_bo_badlabel',
                                                            array($label)), 
                              E_USER_ERROR);
                return null;
            }
        }
    }

    function isHardCodedMetadata($label) {
        return in_array($label, $this->hardCodedMetadata);
    }

    function isRealMetadata($label) {
        if(preg_match('/^field_([0-9]+)$/', $label)) {
            return true;
        }
        else {
            return false;
        }
    }

    function isValidLabel($label) {
        $valid = false;
        if(Docman_MetadataFactory::isHardCodedMetadata($label)) {
            $valid = true;
        }
        else {
            $valid = Docman_MetadataFactory::isRealMetadata($label);
        }
        return $valid;
    }

    function updateRealMetadata($md) {
        $dao =& $this->getDao();
        return $dao->updateById($md->getId(),
                                $md->getName(),
                                $md->getDescription(),
                                $md->getIsEmptyAllowed(),
                                $md->getIsMultipleValuesAllowed(),
                                $md->getUseIt(),
                                $md->getDefaultValue());
    }

    // Today only usage configuration supported
    function updateHardCodedMetadata($md) {        
        if(in_array($md->getLabel(), $this->modifiableMetadata)) {
            $sBo =& Docman_SettingsBo::instance($this->groupId);
            return $sBo->updateMetadataUsage($md->getLabel(), $md->getUseIt());
        }

        return false;
    }

    function update($md) {
        if($this->isRealMetadata($md->getLabel())) {
            return $this->updateRealMetadata($md);
        }
        else {
            return $this->updateHardCodedMetadata($md);
        }
        return false;
    }

    function create(&$md) {
        $md->setGroupId($this->groupId);

        if($md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
            $dfltValue = $md->getDefaultValue();
            if(!is_numeric($dfltValue) || $dfltValue < 100) {
                $md->setDefaultValue(100);
            }
            // @todo: check that default value is in the value list.
        }

        $dao =& $this->getDao();
        $mdId = $dao->create($this->groupId, 
                             $md->getName(),
                             $md->getType(),
                             $md->getDescription(),
                             $md->getIsRequired(),
                             $md->getIsEmptyAllowed(),
                             $md->getIsMultipleValuesAllowed(),
                             $md->getSpecial(),
                             $md->getDefaultValue(),
                             $md->getUseIt());

        if($mdId !== false) {
            if($md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                // Insert 'none' value in the list (first value).
                $loveFactory = new Docman_MetadataListOfValuesElementFactory($mdId);
                $inserted = $loveFactory->createNoneValue();
                if($inserted === false) {
                    $mdId = false;
                }
            }
            
            if($mdId !== false) {
                // Update existing items, and give them the default
                // value of the metadata.
                // *WARNING*: Only lists are fully implemented (be carful of
                // default value for other fields).
                $mdvFactory = new Docman_MetadataValueFactory($this->groupId);
                
                $itemFactory = new Docman_ItemFactory($this->groupId);
                $itemIter =& $itemFactory->getDocumentsIterator(); 

                while($itemIter->valid()) {
                    $item =& $itemIter->current();

                    $mdv =& $mdvFactory->newMetadataValue($item->getId(), $mdId, $md->getType(), $md->getDefaultValue());
                    $inserted = $mdvFactory->create($mdv);
                    //@todo: we should catch an error here. But actually, the
                    //best thing to do is to rollback the transaction and since
                    //we do not use tables that support transactions...

                    $itemIter->next();
                }
            }
        }

        return $mdId;
    }

    function delete($md) {
        $deleted = false;

        // Delete Md
        $dao =& $this->getDao();
        $delMd = $dao->delete($md->getId());

        if($delMd) {            
            // Delete LoveElements if needed
            $delLove = false;
            if($md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                $loveFactory = new Docman_MetadataListOfValuesElementFactory($md->getId());
                $delLove = $loveFactory->deleteByMetadataId();
            }
            else {
                $delLove = true;
            }
            
            if($delLove) {
                $deleted = true;
                // Delete corresponding values
                //$mdvFactory = new Docman_MetadataValueFactory($this->groupId);
                //$deleted = $mdvFactory->deleteByMetadata($md);
            }
        }

        return $deleted;
    }

    function &getHardCodedMetadataFromLabel($label, $value=null) {
        $md = null;
        switch($label) {
        case 'title':
            $md = new Docman_Metadata();
            $md->setName($GLOBALS['Language']->getText('plugin_docman', 'md_title_name'));
            $md->setLabel('title');
            $md->setDescription($GLOBALS['Language']->getText('plugin_docman', 'md_title_desc'));
            $md->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);
            $md->setIsRequired(true);
            $md->setIsEmptyAllowed(false);
            $md->setKeepHistory(false);
            $md->setUseIt(true);
            $md->setCanChangeValue(true);
            break;

        case 'description':
            $md = new Docman_Metadata();
            $md->setName($GLOBALS['Language']->getText('plugin_docman', 'md_desc_name'));
            $md->setLabel('description');
            $md->setDescription($GLOBALS['Language']->getText('plugin_docman', 'md_desc_desc'));
            $md->setType(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
            $md->setIsRequired(true);
            $md->setIsEmptyAllowed(true);
            $md->setKeepHistory(false);
            $md->setUseIt(true);
            $md->setCanChangeValue(true);
            break;

        case 'owner':
            $md = new Docman_Metadata();
            $md->setName($GLOBALS['Language']->getText('plugin_docman', 'md_owner_name'));
            $md->setLabel('owner');
            $md->setDescription($GLOBALS['Language']->getText('plugin_docman', 'md_owner_desc'));
            $md->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);
            $md->setIsRequired(true);
            $md->setIsEmptyAllowed(true);
            $md->setKeepHistory(true);
            $md->setUseIt(true);
            $md->setCanChangeValue(true);
            break;

        case 'create_date':
            $md = new Docman_Metadata();
            $md->setName($GLOBALS['Language']->getText('plugin_docman', 'md_cdate_name'));
            $md->setLabel('create_date');
            $md->setDescription($GLOBALS['Language']->getText('plugin_docman', 'md_cdate_desc'));
            $md->setType(PLUGIN_DOCMAN_METADATA_TYPE_DATE);
            $md->setIsRequired(true);
            $md->setIsEmptyAllowed(false);
            $md->setKeepHistory(true);
            $md->setUseIt(true);
            $md->setCanChangeValue(false);
            break;

        case 'update_date':
            $md = new Docman_Metadata();
            $md->setName($GLOBALS['Language']->getText('plugin_docman', 'md_udate_name'));
            $md->setLabel('update_date');
            $md->setDescription($GLOBALS['Language']->getText('plugin_docman', 'md_udate_desc'));
            $md->setType(PLUGIN_DOCMAN_METADATA_TYPE_DATE);
            $md->setIsRequired(true);
            $md->setIsEmptyAllowed(false);
            $md->setKeepHistory(true);
            $md->setUseIt(true);
            $md->setCanChangeValue(false);
            break;

        case 'status': 
            $md = new Docman_ListMetadata();
            $md->setName($GLOBALS['Language']->getText('plugin_docman', 'md_status_name'));
            $md->setLabel('status');
            $md->setDescription($GLOBALS['Language']->getText('plugin_docman', 'md_status_desc'));
            $md->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
            $md->setIsRequired(false);
            $md->setIsEmptyAllowed(true);
            $md->setKeepHistory(true);
            $md->setCanChangeValue(true);
            $md->setDefaultValue(PLUGIN_DOCMAN_ITEM_STATUS_NONE);
            break;

        case 'obsolescence_date':
            $md = new Docman_Metadata();
            $md->setName($GLOBALS['Language']->getText('plugin_docman', 'md_odate_name'));
            $md->setLabel('obsolescence_date');
            $md->setDescription($GLOBALS['Language']->getText('plugin_docman', 'md_odate_desc'));
            $md->setType(PLUGIN_DOCMAN_METADATA_TYPE_DATE);
            $md->setIsRequired(false);
            $md->setIsEmptyAllowed(true);
            $md->setKeepHistory(false);
            $md->setCanChangeValue(true);
            $md->setDefaultValue(0);
            break;
        }

        if($md !== null) {
            $md->setValue($value);
            $md->setSpecial(true);
            $md->setCanChangeName(false);
            $md->setCanChangeIsEmptyAllowed(false);
            $md->setCanChangeDescription(false);
            $md->setGroupId($this->groupId);
        }

        return $md;
    }

    // Create new metadata
    function _cloneOneMetadata($dstGroupId, $md, &$metadataMapping) {
        $dstMdFactory = new Docman_MetadataFactory($dstGroupId);

        $newMd = $md;
        $newMdId = $dstMdFactory->create($newMd);
        $newMd->setId($newMdId);
        
        $metadataMapping['md'][$md->getId()] = $newMdId;
        
        // If current metadata is a list of values, clone values
        if($newMdId > 0 && $md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
            $oldLoveFactory = new Docman_MetadataListOfValuesElementFactory($md->getId());
            $newLoveFactory = new Docman_MetadataListOfValuesElementFactory($newMdId);
            
            $loveArray = $oldLoveFactory->getListByFieldId($md->getId(), $md->getLabel(), false);
            $loveIter = new ArrayIterator($loveArray);
            $loveIter->rewind();
            while($loveIter->valid()) {
                $love = $loveIter->current();
                
                // Do not clone value 100 (already created on md creation)
                if($love->getId() != 100) {
                    $newLoveId = $newLoveFactory->create($love);
                    $metadataMapping['love'][$love->getId()] = $newLoveId;
                    
                    // If we found the loveId that is the default value in
                    // template: we have to update the metadata field.
                    if($md->getDefaultValue() == $love->getId()) {
                        $newMd->setDefaultValue($newLoveId);
                        $this->update($newMd);
                    }
                }
                
                $loveIter->next();
            }
        }
    }

    // Clone metadata defs and list of values
    function _cloneMetadata($dstGroupId, &$metadataMapping) {
        $mda = $this->getRealMetadataList(false);
        $mdIter = new ArrayIterator($mda);
        $mdIter->rewind();
        while($mdIter->valid()) {
            $md = $mdIter->current();
            
            $this->_cloneOneMetadata($dstGroupId, $md, $metadataMapping);

            $mdIter->next();
        }
    }

    function cloneMetadata($dstGroupId, &$metadataMapping) {
        // Clone hardcoded metadata prefs
        $sBo =& Docman_SettingsBo::instance($this->groupId);
        $sBo->cloneMetadataSettings($dstGroupId);

        // Clone metadata
        $this->_cloneMetadata($dstGroupId, $metadataMapping);
    }
    
    /**
     * Try to find the matching metadata between 2 projects
     * The matching is made on the name and type
     */
    function getMetadataMapping($dstGroupId, &$metadataMapping) {
        $dstMdFactory =& $this->_getMetadataFactory($dstGroupId);

        $metadataMapping = array();
        $metadataMapping['md'] = array();
        $metadataMapping['love'] = array();

        $mda = $this->getRealMetadataList(false);
        $mdIter = new ArrayIterator($mda);
        $mdIter->rewind();
        while($mdIter->valid()) {
            $md = $mdIter->current();

            $dstMdi = $dstMdFactory->findByName($md->getName());
            if($dstMdi->count() == 1) {
                // Found exactly one name that match
                $dstMdi->rewind();
                $dstMd = $dstMdi->current();

                if($md->equivalent($dstMd)) {
                    // Mapping in both sense to make the usage of the map
                    // easier
                    $metadataMapping['md'][$md->getId()] = $dstMd->getId();
                    $metadataMapping['md'][$dstMd->getId()] = $md->getId();

                    if($md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                        $loveFactory = $this->_getListOfValuesElementFactory($md->getId());
                        $loveFactory->getLoveMapping($md, $dstMd->getId(), $metadataMapping);
                    }
                }
            }

            $mdIter->next();
        }
    }


    /**
     */
    function _findRealMetadataByName($name, &$mda) {
        $dao =& $this->getDao();

        $dar = $dao->searchByName($this->groupId, $name);
        $dar->rewind();
        while($dar->valid()) {
            $md = $this->_createFromRow($dar->current());
            $md->setCanChangeName(true);
            $md->setCanChangeIsEmptyAllowed(true);
            $md->setCanChangeIsMultipleValuesAllowed(true);
            $md->setCanChangeDescription(true);
            $md->setCanChangeDefaultValue(true);

            $mda[] = $md;

            $dar->next();
        }
    }

    function findByName($name) {
        $mda = array();

        // Hardcoded
        $hcmda = $this->_buildHardCodedMetadataList($this->hardCodedMetadata, true);
        foreach($hcmda as $md) {
            if($md->getName() == $name) {
                $mda[] = $md;
            }
        }

        // Real
        $this->_findRealMetadataByName($name, $mda);
        $ai = new ArrayIterator($mda);
        return $ai;
    }
    
    /**
     * Import metadata settings from $srcGroupId into current project.
     *
     * For metadata that are equivalent (@see Docman_Metadata::equivalent) the
     *   settings are just updated.
     * For metadata that are equal (@see Docman_Metadata::equal) there is
     *   nothing to do (but for ListOfValues we should have a look on them
     *   though).
     * For metadata that are missing in this project, they are just created
     *   with the very same settings than the one in the source project (like
     *   clone).
     *
     * This function just 'import' things, it's not intend to synchronize two
     * projects (ie. properties defined in current project but not in source
     * project are not deleted).
     *
     * @access: public
     */
    function importMetadataFrom($srcGroupId) {
        // Import hardcoded metadata prefs
        $sBo =& Docman_SettingsBo::instance($this->groupId);
        $sBo->importMetadataUsageFrom($srcGroupId);

        // Import metadata
        $this->_importMetadataFrom($srcGroupId);
    }

    /**
     * Only import real metadata settings since hardcoded metadata cannot change.
     */
    function _importMetadataFrom($srcGroupId) {
        // Get used metadata in source project
        $srcMdFactory = new Docman_MetadataFactory($srcGroupId);
        $mda = $srcMdFactory->getRealMetadataList(true);
        $srcMdIter = new ArrayIterator($mda);

        // Get the properties mapping between the 2 projects
        $mdMap = array();
        $srcMdFactory->getMetadataMapping($this->groupId, $mdMap);

        $srcMdIter->rewind();
        while($srcMdIter->valid()) {
            $srcMd = $srcMdIter->current();

            // Get corresponding metadata in current project (if any)
            if(isset($mdMap['md'][$srcMd->getId()])) {
                $dstMd = $srcMdFactory->getFromLabel($srcMdFactory->getLabelFromId($mdMap['md'][$srcMd->getId()]));
                $dstMd->update($srcMd, $mdMap['love']);
                $this->updateRealMetadata($dstMd);
                //print "Update MD: ".$srcMd->getName()."<br>";
                if($srcMd->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                    $oldLoveFactory = new Docman_MetadataListOfValuesElementFactory($dstMd->getId());
                    $oldLoveFactory->importFrom($srcMd, $mdMap['love']);
                }
            } else {
                // Otherwise Create new metadata
                $_dummyMap = array();
                //print "Clone MD: ".$srcMd->getName()."<br>";
                $srcMdFactory->_cloneOneMetadata($this->groupId, $srcMd, $_dummyMap);
            }

            $srcMdIter->next();
        }
    }

    //
    // Accessors for mock
    //
    
    function &_getMetadataFactory($groupId) {
        $mdf = new Docman_MetadataFactory($groupId);
        return $mdf;
    }

    function &_getListOfValuesElementFactory($mdId) {
        $mdLoveF = new Docman_MetadataListOfValuesElementFactory($mdId);
        return $mdLoveF;
    }

}

?>
