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

require_once('DocmanConstants.class.php');
require_once('Docman_MetadataListOfValuesElement.class.php');
require_once('Docman_MetadataListOfValuesElementDao.class.php');

/**
 * High level class to manipulate elements of ListOfValues. 
 */
class Docman_MetadataListOfValuesElementFactory {
    var $metadataId;

    function Docman_MetadataListOfValuesElementFactory($metadataId=null) {
        $this->metadataId = $metadataId;
    }

    /**
     * Return Docman_MetadataListOfValuesElementDao object.
     */
    function &getDao() {
        static $_plugin_docman_metadata_love_dao_instance;
        if(!$_plugin_docman_metadata_love_dao_instance) {
            $_plugin_docman_metadata_love_dao_instance =& new Docman_MetadataListOfValuesElementDao(CodexDataAccess::instance());
        }
        return $_plugin_docman_metadata_love_dao_instance;
    }

    /**
     * Delete the the ListOfValueElement.
     * Then keep metadata_value consistent: if there is no entry for a given
     * (item, $this->metadataId), create a default entry (NONE value).
     */
    function delete(&$love) {
        $dao =& $this->getDao();
        $deleted = $dao->delete($love->getId());
        if($deleted) {
            $mdvFactory = new Docman_MetadataValueFactory(null);
            $deleted = $mdvFactory->deleteLove($this->metadataId, $love->getId());
        }
        return $deleted;
    }

    function deleteByMetadataId() {
        $deleted = false;
        if($this->metadataId !== null ) {
            $dao =& $this->getDao();
            $deleted = $dao->deleteByMetadataId($this->metadataId);
        }
        return $deleted;
    }

    function create(&$love) {
        $dao =& $this->getDao();

        $status = $love->getStatus();
        if($status == null) {
            $status = 'A';
        }

        return $dao->create($this->metadataId,
                            $love->getName(),
                            $love->getDescription(),
                            $love->getRank(),
                            $status);
    }

    function update($love) {
        $dao =& $this->getDao();
        return $dao->updateElement($this->metadataId,
                                   $love->getId(),
                                   $love->getName(),
                                   $love->getDescription(),
                                   $love->getRank(),
                                   $love->getStatus());
    }

    /**
     * Add 'None' value as a value of the list for metadata $this->metadataId.
     */
    function createNoneValue() {
        $dao =& $this->getDao();
        return $dao->createMetadataElementBond($this->metadataId, PLUGIN_DOCMAN_ITEM_STATUS_NONE);
    }
    
    function &instanciateLove(&$row) {
        $e = new Docman_MetadataListOfValuesElement();
        $e->initFromRow($row);        
        return $e;
    }

    /**
     * Return the list of Elements for a given Metadata id.
     */
    function &getListByFieldId($id, $mdLabel, $onlyActive) {
        if($mdLabel == 'status') {
            $lst = $this->getStatusList();
            return $lst;
        }
        else {
            $dao =& $this->getDao();
            $dar = $dao->serachByFieldId($id, $onlyActive);
            $res = array();
            while($dar->valid()) {
                $row = $dar->current();
                                
                $res[] =& $this->instanciateLove($row);
                
                $dar->next();
            }
            return $res;
        }
    }

    function &getIteratorByFieldId($id, $mdLabel, $onlyActive) {
        $loveArray = $this->getListByFieldId($id, $mdLabel, $onlyActive);
        $loveIter  = new ArrayIterator($loveArray);
        return $loveIter;
    }

    /**
     * Return the Element from its Id.
     */
    function &getByElementId($id, $mdLabel = null) {
        $e = null;
        switch($mdLabel) {
        case 'status':
            $ea = $this->getStatusList();
            $e =& $ea[$id];
            break;
            
        default:
            $dao =& $this->getDao();
            $dar = $dao->serachByValueId($id);
            if($dar && !$dar->isError() && $dar->rowCount() == 1) {
                $e = $this->instanciateLove($dar->current());
            }
        }
        return $e;
    }

    /**
     * Return the all elements that match given name.
     */
    function getByName($name, $mdLabel) {
        $ea = array();
        $ei = null;
        switch($mdLabel) {
        case 'status':
            $stElmtArray = $this->getStatusList();
            foreach($stElmtArray as $elmt) {
                if($elmt->getName() == $name) {
                    $ea[] = $elmt;
                }
            }
            $ei = new ArrayIterator($ea);
            break;
            
        default:
            $dao = $this->getDao();
            $dar = $dao->searchByName($this->metadataId, $name, true);
            $ei  =& $this->_returnLoveIteratorFromDar($dar);
        }
        
        return $ei;
    }

    function &getLoveValuesForItem($item, $md) {
        $dao = $this->getDao();
        $dar = $dao->searchListValuesById($md->getId(), $item->getId());
        $i =& $this->_returnLoveIteratorFromDar($dar);
        return $i;
    }

    /**
     * Copy values of source metadata in destination metadata
     *
     * Returns the mapping between Ids in source project and id in target one.
     * This mapping is indexed by source metadata values ids.
     * 
     * @param Docman_Metadata $srcMd         Source metadata
     * @param Docman_Metadata $dstMd         Destination metadata
     * 
     * @return Array Map between source and destination
     */
    function cloneValues(Docman_Metadata $srcMd, Docman_Metadata $dstMd) {
        $valuesMapping = array();
        $dstLoveFactory = $this->getMetadataListOfValuesElementFactory($dstMd->getId());
        $loveArray = $this->getListByFieldId($srcMd->getId(), $srcMd->getLabel(), false);
        foreach($loveArray as $love) {
            if($love->getId() != 100) {
                $valuesMapping[$love->getId()] = $dstLoveFactory->create($love);
            }
        }
        return $valuesMapping;
    }
    
    /**
     * Export values in destination metadata.
     *
     * This method perform 2 things:
     * - import the missing values in the current list.
     * - re-order the values with the following paradigm:
     *   - the values defined in the source metadata first (whereever they
     *     where in the current list).
     *   - keep the values that only exists in current list with the same order
     *     but at the end of the list.
     *
     * To achieve the last point (ordering) we use a trick:
     * - reverse the list and add each element of this list at the beginning of
     * the current one.
     * With reverse, the last element of the source list will
     * be the first to be treated. We insert it at the beginning so the last
     * element of the source list will appears before all the existing elements.
     * Then each elements will be inserted at the beginning too so they will 
     * appears in the right order.
     * 
     * @param Docman_Metadata $srcMd   Source metadata
     * @param Docman_Metadata $dstMd   Destination metadata
     * @param Array           $loveMap Map between elements of $srcMd and $dstMd
     */
    function exportValues($srcMd, $dstMd, $loveMap) {
        $dstLoveFactory = $this->getMetadataListOfValuesElementFactory($dstMd->getId());
        
        $srcLoveArray = $this->getListByFieldId($srcMd->getId(), $srcMd->getLabel(), true);

        // \o/ trick \o/
        $reverseLoveArray = array_reverse($srcLoveArray);

        foreach($reverseLoveArray as $srcLove) {
            if($srcLove->getId() > PLUGIN_DOCMAN_ITEM_STATUS_NONE) {
                if(!isset($loveMap[$srcLove->getId()])) {
                    $newLove = clone $srcLove;
                    $newLove->setRank('beg');
                    $dstLoveFactory->create($newLove);
                } else {
                    // Update
                    $updLove = clone $srcLove;
                    $updLove->setId($loveMap[$srcLove->getId()]);
                    $updLove->setRank('beg');
                    $dstLoveFactory->update($updLove);
                }
            }
        }
    }
    
    /**
     * Try to find matching values between 2 metadata
     */
    function getLoveMapping($md, $dstMdId, &$metadataMapping) {
        // Special treatement for value 'Any' that is not recorded in the DB but
        // that is always 0.
        $metadataMapping['love'][0] = 0;

        $loveArray = $this->getListByFieldId($md->getId(), $md->getLabel(), true);
        $loveIter = new ArrayIterator($loveArray);
        $loveIter->rewind();
        while($loveIter->valid()) {
            $love = $loveIter->current();

            $dstLoveFactory = new Docman_MetadataListOfValuesElementFactory($dstMdId);
            $ei = $dstLoveFactory->getByName($love->getName(), $md->getLabel());
            if($ei->count() == 1) {
                // Found exactly one name that match
                $ei->rewind();
                $dstLove = $ei->current();

                // Mapping in both sense to make the usage of the map
                // easier
                $metadataMapping['love'][$love->getId()] = $dstLove->getId();
                $metadataMapping['love'][$dstLove->getId()] = $love->getId();
            }

            $loveIter->next();
        }        
    }

    /**
     * Return static list of status (hardcoded metadata with hardcoded values)
     */
    function &getStatusList($status=null) {
        $ea = array();

        $e = new Docman_MetadataListOfValuesElement();
        $e->setId(PLUGIN_DOCMAN_ITEM_STATUS_NONE);
        $e->setName($GLOBALS['Language']->getText('plugin_docman','md_love_status_none_name'));
        //$e->setDescription($GLOBALS['Language']->getText('plugin_docman','md_love_status_none_desc'));
        $e->setRank(PLUGIN_DOCMAN_ITEM_STATUS_NONE);
        $e->setStatus('P');
        $ea[PLUGIN_DOCMAN_ITEM_STATUS_NONE] =& $e;
        unset($e);

        $e = new Docman_MetadataListOfValuesElement();
        $e->setId(PLUGIN_DOCMAN_ITEM_STATUS_DRAFT);
        $e->setName($GLOBALS['Language']->getText('plugin_docman','md_love_status_draft_name'));
        //$e->setDescription('md_love_status_draft_desc');
        $e->setRank(PLUGIN_DOCMAN_ITEM_STATUS_DRAFT);
        $e->setStatus('P');
        $ea[PLUGIN_DOCMAN_ITEM_STATUS_DRAFT] =& $e;
        unset($e);

        $e = new Docman_MetadataListOfValuesElement();
        $e->setId(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $e->setName($GLOBALS['Language']->getText('plugin_docman','md_love_status_approved_name'));
        //$e->setDescription('md_love_status_approved_desc');
        $e->setRank(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $e->setStatus('P');
        $ea[PLUGIN_DOCMAN_ITEM_STATUS_APPROVED] =& $e;
        unset($e);

        $e = new Docman_MetadataListOfValuesElement();
        $e->setId(PLUGIN_DOCMAN_ITEM_STATUS_REJECTED);
        $e->setName($GLOBALS['Language']->getText('plugin_docman','md_love_status_rejected_name'));
        //$e->setDescription('md_love_status_rejected_desc');
        $e->setRank(PLUGIN_DOCMAN_ITEM_STATUS_REJECTED);
        $e->setStatus('P');
        $ea[PLUGIN_DOCMAN_ITEM_STATUS_REJECTED] =& $e;
        unset($e);
        
        if($status === null) {
            return $ea;
        } else {
            return $ea[$status];
        }
    }

    function &_returnLoveIteratorFromDar($dar) {
        $ea = array();
        if($dar && !$dar->isError()) {
            $dar->rewind();
            while($dar->valid()) {
                $ea[] =& $this->instanciateLove($dar->current());
                $dar->next();
            }
        }
        $ei = new ArrayIterator($ea);
        return $ei;
    }
    
    function getMetadataListOfValuesElementFactory($metadataId=null) {
        return new Docman_MetadataListOfValuesElementFactory($metadataId);
    }
}

?>