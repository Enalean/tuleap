<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

use Tuleap\Docman\Metadata\DocmanMetadataInputValidator;
use Tuleap\Docman\Metadata\DocmanMetadataTypeValueFactory;
use Tuleap\Docman\Metadata\MetadataDoesNotExistException;
use Tuleap\Docman\Metadata\MetadataValueCreator;
use Tuleap\Docman\Metadata\MetadataValueObjectFactory;
use Tuleap\Docman\Metadata\MetadataValueStore;
use Tuleap\Docman\Metadata\MetadataValueUpdator;

/**
 * High level object for Metadata Values management.
 */
class Docman_MetadataValueFactory
{
    public $groupId;
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
    public function __construct($groupId)
    {
        $this->groupId = $groupId;
    }

    /**
     * Return Docman_MetadataValueDao reference.
     */
    public function getDao()
    {
        static $_plugin_docman_metadata_value_dao_instance;
        if (!$_plugin_docman_metadata_value_dao_instance) {
            $_plugin_docman_metadata_value_dao_instance = new Docman_MetadataValueDao(CodendiDataAccess::instance());
        }
        return $_plugin_docman_metadata_value_dao_instance;
    }

    /**
     * Create and set-up a MetadataValue object.
     * @deprecated use MetadataValueObjectFactory::createNewMetadataValue
     */
    public function newMetadataValue($itemId, $fieldId, $type, $value)
    {
        return $this->getMetadataTypeObjectFactory()->createMetadataValueObjectWithCorrectValue((int) $itemId, (int) $fieldId, (int) $type, $value);
    }

    /**
     * Insert new metadata value(s) in database.
     * @deprecated use MetadataValueCreator::storeMetadata
     */
    public function create(&$mdv)
    {
        try {
            $store = new MetadataValueStore($this->getDao(), ReferenceManager::instance());
            $store->storeMetadata($mdv, $this->groupId);
            return true;
        } catch (MetadataDoesNotExistException $e) {
            $this->setError(
                dgettext('tuleap-docman', 'Bad property type')
            );
        }

        return false;
    }

    /**
     * Create new MetadataValue record.
     * @deprecated use MetadataValueCreator::createMetadataObject
     */
    public function createFromRow($id, $row)
    {
        $mdFactory = new Docman_MetadataFactory($this->groupId);

        try {
            foreach ($row as $md_name => $md_v) {
                $md = $mdFactory->getFromLabel($md_name);
                if ($md !== null) {
                    $this->getMetadataCreator()->createMetadataObject($md, $id, $md_v);
                } else {
                    $this->setError(
                        dgettext('tuleap-docman', 'Try to create an unknown property')
                    );
                }
            }
        } catch (MetadataDoesNotExistException $e) {
            $this->setError(
                dgettext('tuleap-docman', 'Bad property type')
            );
        }
    }

    /**
     * Update an existing MetadataValue record.
     */
    public function updateFromRow($id, $row)
    {
        $mdFactory = new Docman_MetadataFactory($this->groupId);
        $updator   = $this->getMetadataUpdator();

        try {
            foreach ($row as $md_name => $md_v) {
                $md = $mdFactory->getFromLabel($md_name);

                if ($md !== null) {
                    $updator->updateMetadata($md, $id, $md_v);
                } else {
                    $this->setError(
                        sprintf(dgettext('tuleap-docman', 'Try to update an unknown property \'%1$s\''), $md_name)
                    );
                }
            }
        } catch (MetadataDoesNotExistException $e) {
            $this->setError(
                dgettext('tuleap-docman', 'Bad property type')
            );
        }
    }

    /**
     * For each metadata in '$recurseArray', apply the metadata value of
     * '$srcItemId' item on items in '$itemIdArray'.
     */
    public function massUpdateFromRow($srcItemId, $recurseArray, $itemIdArray)
    {
        foreach ($recurseArray as $mdLabel) {
            $this->massUpdate($srcItemId, $mdLabel, $itemIdArray);
        }
    }

    public function massUpdate($srcItemId, $mdLabel, $itemIdArray)
    {
        $mdFactory = new Docman_MetadataFactory($this->groupId);
        if ($mdFactory->isRealMetadata($mdLabel)) {
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
    public function deleteLove($mdId, $loveId)
    {
        $dao = $this->getDao();
        $deleted = $dao->deleteLove($loveId);
        if ($deleted) {
            return $this->updateOrphansLoveItem($mdId);
        }
        return $deleted;
    }

    /**
     * Ensure there is no item w/o a value for '$mdId' metadata
     */
    public function updateOrphansLoveItem($mdId)
    {
        $dao = $this->getDao();
        return $dao->updateOrphansLoveItem($mdId);
    }

    /**
     * Return true if a value already exist for a given (itme, field).
     */
    public function exist($itemId, $fieldId)
    {
        $exist = false;
        $dao   = $this->getDao();
        $dar   = $dao->exist($itemId, $fieldId);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->current();
            if ($row['nb'] > 0) {
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
     *
     * @deprecated use DocmanMetadataInputValidator::validateInput
     */
    public function validateInput(&$md, &$value)
    {
        $validator = new DocmanMetadataInputValidator();
        $value = $validator->validateInput($md, $value);
    }

    /**
     * @param $string
     */
    public function setError($string)
    {
        $this->error_state = true;
        $this->error_message = $string;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        if ($this->error_state) {
            return $this->error_message;
        } else {
            return $GLOBALS['Language']->getText('include_common_error', 'no_err');
        }
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return $this->error_state;
    }

    private function getMetadataCreator(): MetadataValueCreator
    {
        return new Tuleap\Docman\Metadata\MetadataValueCreator(
            new DocmanMetadataInputValidator(),
            new MetadataValueObjectFactory(new DocmanMetadataTypeValueFactory()),
            new MetadataValueStore(
                $this->getDao(),
                ReferenceManager::instance()
            )
        );
    }

    private function getMetadataUpdator(): MetadataValueUpdator
    {
        return new Tuleap\Docman\Metadata\MetadataValueUpdator(
            new DocmanMetadataInputValidator(),
            new MetadataValueObjectFactory(new DocmanMetadataTypeValueFactory()),
            $this->getDao(),
            new MetadataValueStore(
                $this->getDao(),
                ReferenceManager::instance()
            )
        );
    }

    private function getMetadataTypeObjectFactory(): MetadataValueObjectFactory
    {
        return new MetadataValueObjectFactory(new DocmanMetadataTypeValueFactory());
    }
}
