<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

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
class Docman_Metadata
{
    public $id;
    public $groupId;
    public $name;
    public $type;
    public $label;
    public $description;
    public $isRequired;
    public $isEmptyAllowed;
    public $isMultipleValuesAllowed;
    public $keepHistory;
    public $special;
    public $useIt;

    public $value;
    public $defaultValue;

    public function __construct()
    {
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
    public function setId($v)
    {
        $this->id = $v;
    }
    public function getId()
    {
        return $this->id;
    }

    public function setGroupId($v)
    {
        $this->groupId = $v;
    }
    public function getGroupId()
    {
        return $this->groupId;
    }

    public function setName($v)
    {
        $this->name = $v;
    }
    public function getName()
    {
        return $this->name;
    }

    public function setType($v)
    {
        $this->type = $v;
    }
    public function getType()
    {
        return $this->type;
    }

    public function setLabel($v)
    {
        $this->label = $v;
    }
    public function getLabel()
    {
        return $this->label;
    }

    public function setDescription($v)
    {
        $this->description = $v;
    }
    public function getDescription()
    {
        return $this->description;
    }

    public function setIsRequired($v)
    {
        $this->isRequired = $v;
    }
    public function getIsRequired()
    {
        return $this->isRequired;
    }

    public function setIsEmptyAllowed($v)
    {
        $this->isEmptyAllowed = $v;
    }
    public function getIsEmptyAllowed()
    {
        return $this->isEmptyAllowed;
    }

    public function setIsMultipleValuesAllowed($v)
    {
        $this->isMultipleValuesAllowed = $v;
    }
    public function getIsMultipleValuesAllowed()
    {
        return $this->isMultipleValuesAllowed;
    }

    public function setKeepHistory($v)
    {
        $this->keepHistory = $v;
    }
    public function getKeepHistory()
    {
        return $this->keepHistory;
    }

    public function setSpecial($v)
    {
        $this->special = $v;
    }
    public function getSpecial()
    {
        return $this->special;
    }

    public function setDefaultValue($v)
    {
        $this->defaultValue = $v;
    }
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function setUseIt($v)
    {
        $this->useIt = $v;
    }
    public function getUseIt()
    {
        return $this->useIt;
    }
    ///}}} Accessors

    //{{{ Convenient accessors
    public function isEmptyAllowed()
    {
        if ($this->isEmptyAllowed == PLUGIN_DOCMAN_DB_TRUE) {
            return true;
        } else {
            return false;
        }
    }

    public function isMultipleValuesAllowed()
    {
        if ($this->isMultipleValuesAllowed == PLUGIN_DOCMAN_DB_TRUE) {
            return true;
        } else {
            return false;
        }
    }

    public function isRequired()
    {
        if ($this->isRequired == PLUGIN_DOCMAN_DB_TRUE) {
            return true;
        } else {
            return false;
        }
    }
    public function isUsed()
    {
        if ($this->useIt == PLUGIN_DOCMAN_METADATA_USED) {
            return true;
        } else {
            return false;
        }
    }

    public function isSpecial()
    {
        if ($this->special > 0) {
            return true;
        } else {
            return false;
        }
    }
    //}}} Convenient accessors

    //{{{ Changable parameters
    public $canChangeName;
    public function setCanChangeName($v)
    {
        $this->canChangeName = $v;
    }
    public function canChangeName()
    {
        return $this->canChangeName;
    }

    public $canChangeType;
    public function setCanChangeType($v)
    {
        $this->canChangeType = $v;
    }
    public function canChangeType()
    {
        return $this->canChangeType;
    }

    public $canChangeDescription;
    public function setCanChangeDescription($v)
    {
        $this->canChangeDescription = $v;
    }
    public function canChangeDescription()
    {
        return $this->canChangeDescription;
    }

    public $canChangeIsEmptyAllowed;
    public function setCanChangeIsEmptyAllowed($v)
    {
        $this->canChangeIsEmptyAllowed = $v;
    }
    public function canChangeIsEmptyAllowed()
    {
        return $this->canChangeIsEmptyAllowed;
    }

    public $canChangeIsMultipleValuesAllowed;
    public function setCanChangeIsMultipleValuesAllowed($v)
    {
        $this->canChangeIsMultipleValuesAllowed = $v;
    }
    public function canChangeIsMultipleValuesAllowed()
    {
        return $this->canChangeIsMultipleValuesAllowed;
    }

    public $canChangeValue;
    public function setCanChangeValue($v)
    {
        $this->canChangeValue = $v;
    }
    public function canChangeValue()
    {
        return $this->canChangeValue;
    }

    //}}}  Changable parameters

    public function setValue($v)
    {
        $this->value = $v;
    }
    public function getValue()
    {
        return $this->value;
    }

    public function initFromRow($row)
    {
        if (isset($row['field_id'])) {
            $this->id = $row['field_id'];
        }
        if (isset($row['group_id'])) {
            $this->groupId = $row['group_id'];
        }
        if (isset($row['name'])) {
            $this->name = $row['name'];
        }
        if (isset($row['data_type'])) {
            $this->type = $row['data_type'];
        }
        if (isset($row['label'])) {
            $this->label = $row['label'];
        }
        if (isset($row['description'])) {
            $this->description = $row['description'];
        }
        if (isset($row['required'])) {
            $this->isRequired = $row['required'];
        }
        if (isset($row['empty_ok'])) {
            $this->isEmptyAllowed = $row['empty_ok'];
        }
        if (isset($row['mul_val_ok'])) {
            $this->isMultipleValuesAllowed = $row['mul_val_ok'];
        }
        if (isset($row['special'])) {
            $this->special = $row['special'];
        }
        if (isset($row['use_it'])) {
            $this->useIt = $row['use_it'];
        }

        $this->setCanChangeValue(true);
    }

    /**
     * Check if the metadata in argument can be the same.
     */
    public function equivalent($md)
    {
        return ($md->getName() == $this->getName() &&
                $md->getType() == $this->getType());
    }

    /**
     * Check if 2 metadata are the same.
     * This check neither the 'label' nor the 'group_id'
     */
    public function equals($md)
    {
        return ($this->equivalent($md) &&
                $this->sameDescription($md) &&
                $this->sameIsEmptyAllowed($md) &&
                $this->sameIsMultipleValuesAllowed($md) &&
                $this->sameUseIt($md));
    }

    public function sameDescription($md)
    {
        return ($md->getDescription() == $this->getDescription());
    }

    public function sameIsEmptyAllowed($md)
    {
        return ($md->getIsEmptyAllowed() == $this->getIsEmptyAllowed());
    }

    public function sameIsMultipleValuesAllowed($md)
    {
        return ($md->getIsMultipleValuesAllowed() == $this->getIsMultipleValuesAllowed());
    }

    public function sameUseIt($md)
    {
        return ($md->getUseIt() == $this->getUseIt());
    }

    /**
     * Update current metadata based on the one passed in param
     */
    public function update($md)
    {
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
class Docman_ListMetadata extends Docman_Metadata
{
    public $listOfValue;

    public function __construct()
    {
        parent::__construct();
        $this->defaultValue = array();
    }

    /**
     * @param array of Docman_MetadataListOfValuesElements
     */
    public function setListOfValueElements(&$l)
    {
        $this->listOfValue = $l;
    }

    /**
     * @return iterator of Docman_MetadataListOfValuesElements
     */
    public function &getListOfValueIterator()
    {
        $i = new ArrayIterator($this->listOfValue);
        return $i;
    }

    public function setDefaultValue($v)
    {
        if (is_a($v, 'Iterator')) {
            $v->rewind();
            //if(is_a($love, 'Docman_MetadataListOfValuesElement')) {
            while ($v->valid()) {
                $love = $v->current();
                $this->defaultValue[] = $love->getId();
                $v->next();
            }
        } else {
            $this->defaultValue[] = $v;
        }
    }
}
