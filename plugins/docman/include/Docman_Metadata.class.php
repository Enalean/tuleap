<?php
/**
 * Copyright © STMicroelectronics, 2006. All Rights Reserved.
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

define('PLUGIN_DOCMAN_METADATA_TYPE_TEXT', 1);
define('PLUGIN_DOCMAN_METADATA_TYPE_STRING', 6);
define('PLUGIN_DOCMAN_METADATA_TYPE_DATE', 4);
define('PLUGIN_DOCMAN_METADATA_TYPE_LIST', 5);

define('PLUGIN_DOCMAN_METADATA_UNUSED', 0);
define('PLUGIN_DOCMAN_METADATA_USED', 1);

/**
 * Metadata container
 *
 * This class aims to give all informations about docman's metadata, even if
 * the metadata are hard coded (such as title, create_date, ...).
 * There is:
 * * a set of accessors for values (useful for DB update);
 * * a set of convenient accessors for code lisibility improvment.
 *   eg. if($metadata->isSpecial()) vs. if($metadata->getSpecial() ===
 *   IS_SPECIAL)
 * * a set of functions to distinguish metadata parameters that may change. For
 *   real metadata all parameters may change. But for HardCoded metadata, only
 *   some parmaeters of some metadata may change (eg. 'use_it' for 'status').
 */
class Docman_Metadata {
    var $id;
    var $groupId;
    var $name;
    var $type;
    var $label;
    var $description;
    var $isRequired;
    var $isEmptyAllowed;
    var $isMultipleValuesAllowed;
    var $keepHistory;
    var $special;
    var $useIt;

    var $value;
    var $defaultValue;

    function Docman_Metadata() {
        $this->id = null;
        $this->groupId = null;
        $this->name = null;
        $this->type = null;
        $this->label = null;
        $this->description = null;
        $this->isRequired = null;
        $this->isEmptyAllowed = null;
        $this->isMultipleValuesAllowed = null;
        $this->keepHistory = null;
        $this->special = null;
        $this->useIt = null;

        $this->value = null;
        $this->defaultValue = null;
    }

    //{{{ Accessors
    function setId($v) {
        $this->id = $v;
    }
    function getId() {
        return $this->id;
    }

    function setGroupId($v) {
        $this->groupId = $v;
    }
    function getGroupId() {
        return $this->groupId;
    }

    function setName($v) {
        $this->name = $v;
    }
    function getName() {
        return $this->name;
    }

    function setType($v) {
        $this->type = $v;
    }
    function getType() {
        return $this->type;
    }

    function setLabel($v) {
        $this->label = $v;
    }
    function getLabel() {
        return $this->label;
    }

    function setDescription($v) {
        $this->description = $v;
    }
    function getDescription() {
        return $this->description;
    }

    function setIsRequired($v) {
        $this->isRequired = $v;
    }
    function getIsRequired() {
        return $this->isRequired;
    }

    function setIsEmptyAllowed($v) {
        $this->isEmptyAllowed = $v;
    }
    function getIsEmptyAllowed() {
        return $this->isEmptyAllowed;
    }

    function setIsMultipleValuesAllowed($v) {
        $this->isMultipleValuesAllowed = $v;
    }
    function getIsMultipleValuesAllowed() {
        return $this->isMultipleValuesAllowed;
    }

    function setKeepHistory($v) {
        $this->keepHistory = $v;
    }
    function getKeepHistory() {
        return $this->keepHistory;
    }

    function setSpecial($v) {
        $this->special = $v;
    }
    function getSpecial() {
        return $this->special;
    }

    function setDefaultValue($v) {
        $this->defaultValue = $v;
    }
    function getDefaultValue() {
        return $this->defaultValue;
    }

    function setUseIt($v) {
        $this->useIt = $v;
    }
    function getUseIt() {
        return $this->useIt;
    }
    ///}}} Accessors

    //{{{ Convenient accessors
    function isEmptyAllowed() {
        if($this->isEmptyAllowed == PLUGIN_DOCMAN_DB_TRUE) {
            return true;
        }
        else {
            return false;
        }
    }

    function isMultipleValuesAllowed() {
        if($this->isMultipleValuesAllowed == PLUGIN_DOCMAN_DB_TRUE) {
            return true;
        }
        else {
            return false;
        }
    }

    function isRequired() {
        if($this->isRequired == PLUGIN_DOCMAN_DB_TRUE) {
            return true;
        }
        else {
            return false;
        }
    }
    function isUsed() {
        if($this->useIt == PLUGIN_DOCMAN_METADATA_USED) {
            return true;
        }
        else {
            return false;
        }
    }

    function isSpecial() {
        if($this->special > 0) {
            return true;
        }
        else {
            return false;
        }
    }
    //}}} Convenient accessors

    //{{{ Changable parameters
    var $canChangeName;
    function setCanChangeName($v) {
        $this->canChangeName = $v;
    }
    function canChangeName() {
        return $this->canChangeName;
    }
    
    var $canChangeType;
    function setCanChangeType($v) {
        $this->canChangeType = $v;
    }
    function canChangeType() {
        return $this->canChangeType;
    }

    var $canChangeDescription;
    function setCanChangeDescription($v) {
        $this->canChangeDescription = $v;
    }
    function canChangeDescription() {
        return $this->canChangeDescription;
    }

    var $canChangeIsEmptyAllowed;
    function setCanChangeIsEmptyAllowed($v) {
        $this->canChangeIsEmptyAllowed = $v;
    }
    function canChangeIsEmptyAllowed() {
        return $this->canChangeIsEmptyAllowed;
    }

    var $canChangeIsMultipleValuesAllowed;
    function setCanChangeIsMultipleValuesAllowed($v) {
        $this->canChangeIsMultipleValuesAllowed = $v;
    }
    function canChangeIsMultipleValuesAllowed() {
        return $this->canChangeIsMultipleValuesAllowed;
    }

    var $canChangeValue;
    function setCanChangeValue($v) {
        $this->canChangeValue = $v;
    }
    function canChangeValue() {
        return $this->canChangeValue;
    }

    //}}}  Changable parameters

    function setValue($v) {
        $this->value = $v;
    }
    function getValue() {
        return $this->value;
    }

    function initFromRow($row) {
        if(isset($row['field_id'])) $this->id = $row['field_id'];
        if(isset($row['group_id'])) $this->groupId = $row['group_id'];
        if(isset($row['name'])) $this->name = $row['name'];
        if(isset($row['data_type'])) $this->type = $row['data_type'];
        if(isset($row['label'])) $this->label = $row['label'];
        if(isset($row['description'])) $this->description = $row['description'];
        if(isset($row['required'])) $this->isRequired = $row['required'];
        if(isset($row['empty_ok'])) $this->isEmptyAllowed = $row['empty_ok'];
        if(isset($row['mul_val_ok'])) $this->isMultipleValuesAllowed = $row['mul_val_ok'];
        if(isset($row['special'])) $this->special = $row['special'];
        if(isset($row['use_it'])) $this->useIt = $row['use_it'];

        $this->setCanChangeValue(true);
    }

    /**
     * Check if the metadata in argument can be the same.
     */
    function equivalent($md) {
        return ($md->getName() == $this->getName() &&
                $md->getType() == $this->getType());
    }

    /**
     * Check if 2 metadata are the same.
     * This check neither the 'label' nor the 'group_id'
     */
    function equals($md) {
        return ($this->equivalent($md) &&
                $this->sameDescription($md) &&
                $this->sameIsEmptyAllowed($md) &&
                $this->sameIsMultipleValuesAllowed($md) &&
                $this->sameUseIt($md));
    }

    function sameDescription($md) {
        return ($md->getDescription() == $this->getDescription());
    }

    function sameIsEmptyAllowed($md) {
        return ($md->getIsEmptyAllowed() == $this->getIsEmptyAllowed());
    }

    function sameIsMultipleValuesAllowed($md) {
        return ($md->getIsMultipleValuesAllowed() == $this->getIsMultipleValuesAllowed());
    }

    function sameUseIt($md) {
        return ($md->getUseIt() == $this->getUseIt());
    }

    /**
     * Update current metadata based on the one passed in param
     */
    function update($md) {
        $this->setName($md->getName());
        $this->setType($md->getType());
        $this->setDescription($md->getDescription());
        $this->setIsRequired($md->getIsRequired());
        $this->setIsEmptyAllowed($md->getIsEmptyAllowed());
        $this->setIsMultipleValuesAllowed($md->getIsMultipleValuesAllowed());
        $this->setKeepHistory($md->getKeepHistory());
        $this->setSpecial($md->getSpecial());
        $this->setUseIt($md->getUseIt());
    }
}

/**
 * For metadata that aims to provide a list of values to use we add two special
 * methods that store and restore all the values provided by the metadata
 * (ie. the select box).
 *
 * Actually, Docman_ListMetadata objects are quite complex because they provide
 * - a list of values the user can select (this is the purpose of the two
 *   function bellow)
 * - a list of values the user selected, accessible by regular setValue() and
 *   getValue().
 */
class Docman_ListMetadata extends Docman_Metadata {
    var $listOfValue;

    function Docman_ListMetadata() {
        parent::Docman_Metadata();
        $this->defaultValue = array();
    }

    /**
     * @param array of Docman_MetadataListOfValuesElements
     */
    function setListOfValueElements(&$l) {
        $this->listOfValue =& $l;
    }

    /**
     * @return iterator of Docman_MetadataListOfValuesElements
     */
    function &getListOfValueIterator() {
        $i = new ArrayIterator($this->listOfValue);
        return $i;
    }

    function setDefaultValue($v) {
        if(is_a($v, 'Iterator')) {
            $v->rewind();
            //if(is_a($love, 'Docman_MetadataListOfValuesElement')) {
            while($v->valid()) {
                $love = $v->current();
                $this->defaultValue[] = $love->getId();
                $v->next();
            }
        } else {
            $this->defaultValue[] = $v;
        }

    }
}

?>
