<?php
/* 
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
require_once('Docman_ItemAction.class.php');

/**
 * Item is a transport object (aka container) used to share data between
 * Model/Controler and View layer of the application
 */
class Docman_Item {
    
    function Docman_Item($data = null) {
        $this->id          = null;
        $this->title       = null;
        $this->description = null;
        $this->createDate  = null;
        $this->updateDate  = null;
        $this->deleteDate  = null;
        $this->rank        = null;
        $this->parentId    = null;
        $this->groupId     = null;
        $this->ownerId     = null;
        $this->status      = null;
        $this->obsolescenceDate = null;
        
        // Cache
        $this->isObsolete = null;

        $this->_actions    = array();
        $this->_metadata   = array();
        $this->pathId      = array();
        $this->pathTitle   = array();

        $this->titlekey = null;

        if ($data) {
            $this->initFromRow($data);
        }
    }

    //{{{
    var $id;
    function setId($id) {
        $this->id = (int) $id;
    }

    function getId() {
        return $this->id;
    }

    var $title;
    var $titlekey;
    function setTitle($title) { 
        if(strpos($title, '_lbl_key') !== FALSE) {
            $this->title = $GLOBALS['Language']->getText('plugin_docman', $title);
            $this->titlekey = $title;
        }
        else {
            $this->title = $title;
        }
    }
    function getTitle($key=false) {
        if($key && $this->titlekey !== null) {
            return $this->titlekey;
        }
        return $this->title; 
    }

    var $description;
    function setDescription($description) { 
        $this->description = $description;
    }

    function getDescription() {
        return $this->description;
    }

    var $createDate;
    function setCreateDate($date) {
        $this->createDate = (int) $date;
    }

    function getCreateDate() {
        return $this->createDate;
    }

    var $updateDate;
    function setUpdateDate($date) {
        $this->updateDate = (int) $date;
    }

    function getUpdateDate() {
        return $this->updateDate;
    }

    var $deleteDate;
    function setDeleteDate($date) {
        $this->deleteDate = (int) $date;
    }

    function getDeleteDate() {
        return $this->deleteDate;
    }

    var $rank;
    function setRank($rank) {
        $this->rank = (int) $rank;
    }

    function getRank() {
        return $this->rank;
    }

    var $parentId;
    function setParentId($id) {
        $this->parentId = (int) $id;
    }

    function getParentId() {
        return $this->parentId;
    }

    var $groupId;
    function setGroupId($id) {
        $this->groupId = (int) $id;
    }

    function getGroupId() {
        return $this->groupId;
    }

    var $ownerId;
    function setOwnerId($id) {
        $this->ownerId = (int) $id;
    }

    function getOwnerId() {
        return $this->ownerId;
    }

    var $status;
    function setStatus($v) {
        $this->status = (int) $v;
    }
    function getStatus() {
        return $this->status;
    }

    var $obsolescenceDate;
    function setObsolescenceDate($v) {
        $this->obsolescenceDate = (int) $v;
        $this->isObsolete = null; // Clear cache
    }
    function getObsolescenceDate() {
        return $this->obsolescenceDate;
    }

    /*
     * Convenient accessors
     */
    var $isObsolete;
    function isObsolete() {
        if($this->isObsolete == null) {
            $date = $this->getObsolescenceDate();
            if($date > 0) {
                $today = getdate();
                $time = mktime(0,0,1,$today['mon'], $today['mday'], $today['year']);
                if($date < $time) {
                    $this->isObsolete = true;
                } else {
                    $this->isObsolete = false;
                }
            } else {
                $this->isObsolete = false;
            }
        }
        return $this->isObsolete;
    }

    function initFromRow(&$row) {
        if (isset($row['item_id']))     $this->setId($row['item_id']);
        if (isset($row['title']))       $this->setTitle($row['title']);
        if (isset($row['description'])) $this->setDescription($row['description']);
        if (isset($row['create_date'])) $this->setCreateDate($row['create_date']);
        if (isset($row['update_date'])) $this->setUpdateDate($row['update_date']);
        if (isset($row['delete_date'])) $this->setDeleteDate($row['delete_date']);
        if (isset($row['rank']))        $this->setRank($row['rank']);
        if (isset($row['parent_id']))   $this->setParentId($row['parent_id']);
        if (isset($row['group_id']))    $this->setGroupId($row['group_id']);
        if (isset($row['user_id']))     $this->setOwnerId($row['user_id']);
        if (isset($row['status']))      $this->setStatus($row['status']);
        if (isset($row['obsolescence_date'])) $this->setObsolescenceDate($row['obsolescence_date']);
    }
    
    function toRow() {
        $row = array();
        $row['item_id']     = $this->getId();
        $row['title']       = $this->getTitle(true);
        $row['description'] = $this->getDescription();
        $row['create_date'] = $this->getCreateDate();
        $row['update_date'] = $this->getUpdateDate();
        $row['delete_date'] = $this->getDeleteDate();
        $row['rank']        = $this->getRank();
        $row['parent_id']   = $this->getParentId();
        $row['group_id']    = $this->getGroupId();
        $row['user_id']     = $this->getOwnerId();
        $row['status']      = $this->getStatus();
        $row['obsolescence_date'] = $this->getObsolescenceDate();
        return $row;
    }
    //}}}
    
    /* abstract */function accept(&$visitor, $params = array()) {
    }

    var $_metadata;
    function addMetadata(&$md) {
        $this->_metadata[$md->getLabel()] =& $md;
    }
    function setMetadata(&$mda) {
        $this->_metadata =& $mda;
    }
    function &getMetadata() {
        return $this->_metadata;
    }
   
    function &getMetadataIterator() {        
        $i = new ArrayIterator($this->_metadata);
        return $i;
    }

    function getHardCodedMetadataValue($label) {
        $value = null;

        switch($label) {
        case 'title':
            $value = $this->getTitle();
            break;

        case 'description':
            $value = $this->getDescription();
            break;

        case 'owner':
            $value = $this->getOwnerId();
            break;

        case 'create_date':
            $value = $this->getCreateDate();
            break;

        case 'update_date':
            $value = $this->getUpdateDate();
            break;

        case 'status':
            $st = $this->getStatus();
            if($st === null) {
                $ea = array();
            } else {
                $status = Docman_MetadataListOfValuesElementFactory::getStatusList($st);
                $ea = array($status);
            }
            $value = new ArrayIterator($ea);
            break;

        case 'obsolescence_date':
            $value = $this->getObsolescenceDate();
            break;

        case 'rank':
            $value = $this->getRank();
            break;
        }

        return $value;
    }

    /**
     * 
     */
    function &getMetadataFromLabel($label) {
        $md = null;
        $mdv = $this->getHardCodedMetadataValue($label);
        if($mdv !== null) {
            $md = Docman_MetadataFactory::getHardCodedMetadataFromLabel($label, $mdv);
        } else {
            if(isset($this->_metadata[$label])) {
                $md = $this->_metadata[$label];
            }
        }
        return $md;
    }

    var $pathId;
    function setPathId(&$p) {
        $this->pathId =& $p;
    }
    function &getPathId() {
        return $this->pathId;
    }

    var $pathTitle;
    function setPathTitle(&$p) {
        $this->pathTitle =& $p;
    }
    function &getPathTitle() {
        return $this->pathTitle;
    }
}

?>
