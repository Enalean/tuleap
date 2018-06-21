<?php
/*
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
require_once('Docman_MetadataValue.class.php');
require_once('Docman_MetadataValueDao.class.php');
require_once('common/dao/CodendiDataAccess.class.php');

/**
 * High level object for Metadata Values management.
 */
class Docman_MetadataValueFactory {
    var $groupId;
    /**
     * @var string
     */
    private $error_message = '';
    /**
     * @var bool
     */
    private $error_state = false;

    /**
     * Constructor
     */
    function __construct($groupId) {
        $this->groupId = $groupId;
    }

    /**
     * Return Docman_MetadataValueDao reference.
     */
    function getDao() {
        static $_plugin_docman_metadata_value_dao_instance;
        if(!$_plugin_docman_metadata_value_dao_instance) {
            $_plugin_docman_metadata_value_dao_instance = new Docman_MetadataValueDao(CodendiDataAccess::instance());
        }
        return $_plugin_docman_metadata_value_dao_instance;
    }

    /**
     * Factory. Create a MetadataValue object based on the metadata type.
     */
    function &createFromType($type) {
        $mdv = null;
        switch($type) {
        case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
            $mdv = new Docman_MetadataValueList();
            break;

        case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
        case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
        case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
            $mdv = new Docman_MetadataValueScalar();
            break;
        }
        $mdv->setType($type);
        return $mdv;
    }

    /**
     * Create and set-up a MetadataValue object.
     */
    function &newMetadataValue($itemId, $fieldId, $type, $value) {
        $mdv = $this->createFromType($type);
        
        $mdv->setFieldId($fieldId);
        $mdv->setItemId($itemId);
        $mdv->setType($type);
        if($type == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
            $ea = array();
            if(is_array($value)) {
                foreach($value as $val) {
                    $e = new Docman_MetadataListOfValuesElement();
                    $e->setId($val);
                    $ea[] = $e;
                }
            }
            else {
                $e = new Docman_MetadataListOfValuesElement();
                $e->setId($value);
                $ea[] = $e;
            }
            $mdv->setValue($ea);
        }
        else {
            $mdv->setValue($value);
        }
          
        return $mdv;
    }

    /**
     * Insert new metadata value(s) in database.
     */
    function create(&$mdv) {
        $dao = $this->getDao();
        switch($mdv->getType()) {
        case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
            $eIter = $mdv->getValue();
            $eIter->rewind();
            $ret = true;
            while($eIter->valid()) {
                $e = $eIter->current();

                $pret = $dao->create($mdv->getItemId(),
                                     $mdv->getFieldId(),
                                     $mdv->getType(),
                                     $e->getId());
                if($pret === false) {
                    //$this->setError('Unable to bind this item to the value "'.$val.'" for metadata "'.$mdv->getName().'"');
                    $ret = false;
                }

                $eIter->next();
            }
            break;
            
        case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
        case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
        case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
            $ret = $dao->create($mdv->getItemId(),
                                $mdv->getFieldId(),
                                $mdv->getType(),
                                $mdv->getValue());
            // extract cross references
            $reference_manager = ReferenceManager::instance();
            $reference_manager->extractCrossRef($mdv->getValue(), $mdv->getItemId(), ReferenceManager::REFERENCE_NATURE_DOCUMENT, $this->groupId);
            break;

        default:
            $this->setError($GLOBALS['Language']->getText('plugin_docman',
                                                          'mdv_bo_errbadtype'));
            $ret = false;
        }
        return $ret;
    }

    /**
     * Create new MetadataValue record.
     */
    function createFromRow($id, $row) {
        $mdFactory = new Docman_MetadataFactory($this->groupId);

        foreach($row as $md_name => $md_v) {
            $md = $mdFactory->getFromLabel($md_name);
            
            if($md !== null) {
                $this->validateInput($md, $md_v);

                $mdv = $this->newMetadataValue($id
                                                ,$md->getId()
                                                ,$md->getType()
                                                ,$md_v);
                
                $created = $this->create($mdv);
                if(!$created) {
                    $this->setError($GLOBALS['Language']->getText('plugin_docman',
                                                                  'mdv_bo_createerror',
                                                                  array($md->getName())));
                }
            }
            else {
                $this->setError($GLOBALS['Language']->getText('plugin_docman',
                                                              'mdv_bo_createunknown',
                                                              array($md_name)));
            }
        }
    }

    /**
     * Update MetadataValue in database.
     */
    function update($mdv) {
        $dao = $this->getDao();
        switch($mdv->getType()) {
        case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
            // First delete all previous values
            $dao->delete($mdv->getFieldId(), $mdv->getItemId());
            
            // Now create new one
            $pret = $this->create($mdv);
            if($pret === false) {
                //$this->setError('Unable to bind this item to the value "'.$val.'" for metadata "'.$mdv->getName().'"');
                $ret = false;
            } else {
                $ret = true;
            }
            break;
            
        case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
        case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
        case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
            $ret = $dao->updateValue($mdv->getItemId(),
                                     $mdv->getFieldId(),
                                     $mdv->getType(),
                                     $mdv->getValue());
            // extract cross references
            $reference_manager = ReferenceManager::instance();
            $reference_manager->extractCrossRef($mdv->getValue(), $mdv->getItemId(), ReferenceManager::REFERENCE_NATURE_DOCUMENT, $this->groupId);
            break;

        default:
            $this->setError($GLOBALS['Language']->getText('plugin_docman',
                                                          'mdv_bo_errbadtype'));
            $ret = false;
        }

        return $ret;
    }

    /**
     * Update an existing MetadataValue record.
     */
    function updateFromRow($id, $row) {
        $mdFactory = new Docman_MetadataFactory($this->groupId);

        foreach($row as $md_name => $md_v) {
            $md = $mdFactory->getFromLabel($md_name);

            if($md !== null) {
                $this->validateInput($md, $md_v);

                $mdv = $this->newMetadataValue($id
                                                ,$md->getId()
                                                ,$md->getType()
                                                ,$md_v);

                if($this->exist($mdv->getItemId(), $mdv->getFieldId())) {
                    $success = $this->update($mdv);
                }
                else {
                    $success = $this->create($mdv);
                }
                if($success === false) {
                    $this->setError($GLOBALS['Language']->getText('plugin_docman',
                                                                  'mdv_bo_updateerror',
                                                                  array($mdv->getName())));
                }
            }
            else {
                $this->setError($GLOBALS['Language']->getText('plugin_docman',
                                                              'mdv_bo_updateunknown',
                                                              array($md_name)));
            }
        }
    }

    /**
     * For each metadata in '$recurseArray', apply the metadata value of
     * '$srcItemId' item on items in '$itemIdArray'.
     */
    function massUpdateFromRow($srcItemId, $recurseArray, $itemIdArray) {
        foreach($recurseArray as $mdLabel) {
            $this->massUpdate($srcItemId, $mdLabel, $itemIdArray);
        }
    }

    function massUpdate($srcItemId, $mdLabel, $itemIdArray) {
        $mdFactory = new Docman_MetadataFactory($this->groupId);
        if($mdFactory->isRealMetadata($mdLabel)) {
            $md  = $mdFactory->getFromLabel($mdLabel);
            $dao = $this->getDao();
            $dao->massUpdate($srcItemId, $md->getId(), $md->getType(), $itemIdArray);   
        } else {
            $itemFactory = new Docman_ItemFactory($this->groupId);
            $itemFactory->massUpdate($srcItemId, $mdLabel, $itemIdArray);
        }
    }

    /**
     * Delete usage of $loveId as a metadata value.
     * If an item is only assigned to the deleted value, it is automaticaly
     * defaulted to '100'
     */
    function deleteLove($mdId, $loveId) {
        $dao = $this->getDao();
        $deleted = $dao->deleteLove($loveId);
        if($deleted) {
            return $this->updateOrphansLoveItem($mdId);
        }
        return $deleted;
    }

    /**
     * Ensure there is no item w/o a value for '$mdId' metadata
     */
    function updateOrphansLoveItem($mdId) {
        $dao = $this->getDao();
        return $dao->updateOrphansLoveItem($mdId);
    }

    /**
     * Return true if a value already exist for a given (itme, field).
     */
    function exist($itemId, $fieldId) {
        $exist = false;
        $dao   = $this->getDao();
        $dar   = $dao->exist($itemId, $fieldId);
        if($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->current();
            if($row['nb'] > 0) {
                $exist = true;
            }
        }
        return $exist;
    }

    /**
     * Convert user input to internal storage form.
     *
     * Warning: Unfortunatly, due to a bad design I don't really now the parm
     * type! Gosh! Well, the only real problem is with list of values because
     * sometime we are dealing with array (input from user) and sometimes with
     * iterators.
     */
    function validateInput(&$md, &$value) {
        switch($md->getType()) {
        case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
            if($md->isMultipleValuesAllowed()) {
                if(!is_array($value) && !is_numeric($value)) {
                    //$value = 100; // Set to default
                    // Maybe a warning ?
                }
            } else if (is_array($value) && count($value) > 1) {
                $value = $value[0]; // If only one value is allowed, the first is taken
            }
            break;
        case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
            break;
        case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
            break;
        case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
            if(preg_match('/^([0-9]+)-([0-9]+)-([0-9]+)$/', $value, $d)) {
                $value = mktime(0, 0, 0, $d[2], $d[3], $d[1]);
            } else if (!preg_match('/\d+/', $value)) { // Allow timestamps as supplied value
                $value = 0;
            }
            break;
        }
    }

    /**
     * @param $string
     */
    public function setError($string) {
        $this->error_state = true;
        $this->error_message = $string;
    }

    /**
     * @return string
     */
    public function getErrorMessage() {
        if ($this->error_state) {
            return $this->error_message;
        } else {
            return $GLOBALS['Language']->getText('include_common_error', 'no_err');
        }
    }

    /**
     * @return bool
     */
    public function isError() {
        return $this->error_state;
    }
}

?>
