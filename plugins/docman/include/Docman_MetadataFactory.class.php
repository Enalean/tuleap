<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

use Tuleap\Docman\Metadata\CustomMetadataException;

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
class Docman_MetadataFactory
{
    public const HARDCODED_METADATA_OWNER_LABEL = 'owner';
    public const HARDCODED_METADATA_LABELS      = [
        'title',
        'description',
        self::HARDCODED_METADATA_OWNER_LABEL,
        'create_date',
        'update_date',
        'status',
        'obsolescence_date'
    ];


    public $modifiableMetadata;
    public $groupId;

    public function __construct($groupId)
    {
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
    private function getDao()
    {
        static $_plugin_docman_metadata_dao_instance;
        if (!$_plugin_docman_metadata_dao_instance) {
            $_plugin_docman_metadata_dao_instance = new Docman_MetadataDao(CodendiDataAccess::instance());
        }
        return $_plugin_docman_metadata_dao_instance;
    }

    /**
     * For a real metadata, the field name is based on the id
     */
    public function getLabelFromId($id)
    {
        return 'field_' . $id;
    }

    /**
     * Factory method. Create a Docman_Metadata object based on the type of the
     * medatadata. Object is created from a row from the DB.
     */
    public function _createFromRow($row)
    {
        switch ($row['data_type']) {
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
    private function getRealMetadata($id)
    {
        $md = null;

        $dao = $this->getDao();
        $dar = $dao->searchById($id);
        if ($dar->rowCount() === 1) {
            $md = $this->_createFromRow($dar->current());

            $md->setCanChangeName(true);
            $md->setCanChangeIsEmptyAllowed(true);
            $md->setCanChangeIsMultipleValuesAllowed(true);
            $md->setCanChangeDescription(true);
        }

        return $md;
    }

    /**
     * Create the list of Real metadata associated with a project.
     *
     * @param bool $onlyUsed Return only metadata enabled by the project.
     */
    public function getRealMetadataList($onlyUsed = false, $type = array())
    {
        $mda = array();

        $dao = $this->getDao();
        $dar = $dao->searchByGroupId($this->groupId, $onlyUsed, $type);
        while ($dar->valid()) {
            $row = $dar->current();

            $mda[] = $this->_createFromRow($row);

            $dar->next();
        }

        return $mda;
    }

    private function getRealMetadataIterator($onlyUsed = false, $type = array())
    {
        $mda = $this->getRealMetadataList($onlyUsed, $type);
        $mdi = new ArrayIterator($mda);
        return $mdi;
    }

    /**
     * Fetch and append HardCoded metadata variable parameters.
     *
     * Some HardCoded are customizable at project level.
     */
    public function appendHardCodedMetadataParams(&$md)
    {
        $sBo = Docman_SettingsBo::instance($this->groupId);
        $md->setUseIt($sBo->getMetadataUsage($md->getLabel()));
    }

    /**
     * Build a list of HardCoded metadata.
     *
     * @param bool $onlyUsed Return only metadata enabled by the project.
     */
    public function getHardCodedMetadataList($onlyUsed = false)
    {
        $mda = array();
        foreach (self::HARDCODED_METADATA_LABELS as $mdLabel) {
            $md = $this->getHardCodedMetadataFromLabel($mdLabel);
            if (in_array($md->getLabel(), $this->modifiableMetadata)) {
                $this->appendHardCodedMetadataParams($md);
            }

            if ($onlyUsed) {
                if ($md->isUsed()) {
                    $mda[] = $md;
                }
            } else {
                $mda[] = $md;
            }
        }
        return $mda;
    }

    /**
     * Get an array of metadata label for all inheritable metadata.
     *
     * - All Real metadata are inheritable.
     * - Only 'Status' static metadata is inheritable.
     */
    public function getInheritableMdLabelArray()
    {
        $mdla = array();

        // Status
        $md = $this->getHardCodedMetadataFromLabel('status');
        $this->appendHardCodedMetadataParams($md);
        if ($md->isUsed()) {
            $mdla['status'] = 'status';
        }

        // Real metadata
        $dao = $this->getDao();
        $dar = $dao->searchByGroupId($this->groupId, true, array());
        while ($dar->valid()) {
            $row = $dar->current();
            $mdla[$row['label']] = $row['label'];
            $dar->next();
        }
        return $mdla;
    }

    /**
     * Return all metadata for current project.
     *
     * @param bool $onlyUsed Return only metadata enabled by the project.
     */
    public function &getMetadataForGroup($onlyUsed = false)
    {
        $mda = array_merge(
            $this->getHardCodedMetadataList($onlyUsed),
            $this->getRealMetadataList($onlyUsed)
        );

        $i = new ArrayIterator($mda);
        return $i;
    }

    /**
     * Append elements of ListOfValues metadata.
     *
     * @param Docman_ListMetadata The metadata.
     * @param Boolean             Return only active values if true.
     */
    public function appendMetadataValueList(&$md, $onlyActive = true)
    {
        if (is_a($md, 'Docman_ListMetadata')) {
            $mdLoveFactory = new Docman_MetadataListOfValuesElementFactory();
            $mdLoveArray   = $mdLoveFactory->getListByFieldId($md->getId(), $md->getLabel(), $onlyActive);
            $md->setListOfValueElements($mdLoveArray);
        }
    }

    /**
     * Add ListOfValues to each 'ListMetadata' in given Metadata iterator
     *
     * @param ArrayIterator Metadata iterator.
     */
    public function appendAllListOfValues(&$mdIter)
    {
        $mdIter->rewind();
        while ($mdIter->valid()) {
            $md = $mdIter->current();

            if ($md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                $this->appendMetadataValueList($md, true);
            }

            $mdIter->next();
        }
    }

    /**
     * For given item, appends all its ListOfValue metadata.
     */
    public function appendAllListOfValuesToItem(&$item)
    {
        $iter = $item->getMetadataIterator();
        $this->appendAllListOfValues($iter);
    }

    /**
     * Add all the metadata (with their values) to the given item
     */
    public function appendItemMetadataList(Docman_Item &$item)
    {
        // Static metadata
        $hardcoded_metadata = $this->getHardCodedMetadataList(true);
        $this->appendHardcodedMetadataToItem($item, $hardcoded_metadata);

        $this->appendDynamicMetadataToItem($item);
    }

    /**
     * Applies metadata values of item1 to item2.
     *
     * @param $item1 Docman_Item Reference item.
     * @param $item2 Docman_Item Item to modify.
     * @param $mdLabelArray Array List of metadata labels to copy.
     */
    public function appliesItem1MetadataToItem2($item1, &$item2, $mdLabelArray)
    {
        $i1Iter = $item1->getMetadataIterator();
        $i1Iter->rewind();
        while ($i1Iter->valid()) {
            $srcMd = $i1Iter->current();

            if (isset($mdLabelArray[$srcMd->getLabel()])) {
                $dstMd = $item2->getMetadataFromLabel($srcMd->getLabel());
                $dstMd->setDefaultValue($srcMd->getValue());
                $item2->addMetadata($dstMd);
                unset($dstMd);
            }

            $i1Iter->next();
        }
    }

    /**
     * For a given Item, add the default metadata values.
     */
    public function appendDefaultValuesToItem(&$item)
    {
        // Get parent
        $itemFactory = new Docman_ItemFactory();
        $parentItem = $itemFactory->getItemFromDb($item->getParentId());
        $this->appendItemMetadataList($parentItem);

        // Get inheritables metadata
        $inheritableMdla = $this->getInheritableMdLabelArray();

        $this->appliesItem1MetadataToItem2($parentItem, $item, $inheritableMdla);
    }

    /**
     * Return the metadata value for a given metadata and item.
     *
     * @return either a scalar (date, string, ...) or a LoveIterator for the
     * list of values.
     */
    public function getMetadataValue($item, $md)
    {
        $value = null;
        if ($md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
            $loveFactory = new Docman_MetadataListOfValuesElementFactory();
            $value = $loveFactory->getLoveValuesForItem($item, $md);
        } else {
            $dao = $this->getDao();
            $dar = $dao->searchValueById($md->getId(), $item->getId());
            if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
                $value = $this->_getMetadataValueFromRow($md, $dar->current());
            }
        }
        return $value;
    }

    /**
     * add to given item the metadata value of the given metadata.
     */
    public function addMetadataValueToItem(&$item, $md)
    {
        $value = $this->getMetadataValue($item, $md);
        $md->setValue($value);
        $item->addMetadata($md);
    }

    /**
     * @access: private
     */
    public function _getMetadataValueFromRow($md, $row)
    {
        $value = null;
        switch ($md->getType()) {
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
     * @deprecated use getMetadataFromLabel instead (throw exception instead of breaking code)
     */
    public function getFromLabel($label)
    {
        try {
            return $this->getMetadataFromLabel($label);
        } catch (CustomMetadataException $exception) {
            trigger_error(sprintf(dgettext('tuleap-docman', 'Unknown property label \'%1$s\'.'), $label), E_USER_ERROR);
        }
    }

    public function isHardCodedMetadata($label)
    {
        return in_array($label, self::HARDCODED_METADATA_LABELS);
    }

    public function isRealMetadata($label)
    {
        if (preg_match('/^field_([0-9]+)$/', $label)) {
            return true;
        } else {
            return false;
        }
    }

    public function isValidLabel($label)
    {
        $valid = false;
        if (Docman_MetadataFactory::isHardCodedMetadata($label)) {
            $valid = true;
        } else {
            $valid = Docman_MetadataFactory::isRealMetadata($label);
        }
        return $valid;
    }

    public function updateRealMetadata($md)
    {
        $dao = $this->getDao();
        return $dao->updateById(
            $md->getId(),
            $md->getName(),
            $md->getDescription(),
            $md->getIsEmptyAllowed(),
            $md->getIsMultipleValuesAllowed(),
            $md->getUseIt()
        );
    }

    // Today only usage configuration supported
    public function updateHardCodedMetadata($md)
    {
        if (in_array($md->getLabel(), $this->modifiableMetadata)) {
            $sBo = Docman_SettingsBo::instance($this->groupId);
            return $sBo->updateMetadataUsage($md->getLabel(), $md->getUseIt());
        }

        return false;
    }

    public function update($md)
    {
        if ($this->isRealMetadata($md->getLabel())) {
            return $this->updateRealMetadata($md);
        } else {
            return $this->updateHardCodedMetadata($md);
        }
        return false;
    }

    public function create(&$md)
    {
        $md->setGroupId($this->groupId);

        $dao  = $this->getDao();
        $mdId = $dao->create(
            $this->groupId,
            $md->getName(),
            $md->getType(),
            $md->getDescription(),
            $md->getIsRequired(),
            $md->getIsEmptyAllowed(),
            $md->getIsMultipleValuesAllowed(),
            $md->getSpecial(),
            $md->getUseIt()
        );

        if ($mdId !== false) {
            if ($md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                // Insert 'none' value in the list (first value).
                $loveFactory = new Docman_MetadataListOfValuesElementFactory($mdId);
                $inserted = $loveFactory->createNoneValue();
                if ($inserted === false) {
                    $mdId = false;
                }
            }

            if ($mdId !== false) {
                // Update existing items, and give them the default
                // value of the metadata.
                // We only need to do that with list of value element for
                // beeing able to easily manipulate 'None' value (esp. for
                // reports).
                $mdvFactory = new Docman_MetadataValueFactory($this->groupId);
                $mdvFactory->updateOrphansLoveItem($mdId);
            }
        }

        return $mdId;
    }

    public function delete($md)
    {
        $deleted = false;

        // Delete Md
        $dao   = $this->getDao();
        $delMd = $dao->delete($md->getId());

        if ($delMd) {
            // Delete LoveElements if needed
            $delLove = false;
            if ($md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                $loveFactory = new Docman_MetadataListOfValuesElementFactory($md->getId());
                $delLove = $loveFactory->deleteByMetadataId();
            } else {
                $delLove = true;
            }

            if ($delLove) {
                $deleted = true;
                // Delete corresponding values
                //$mdvFactory = new Docman_MetadataValueFactory($this->groupId);
                //$deleted = $mdvFactory->deleteByMetadata($md);
            }
        }

        return $deleted;
    }

    public function getHardCodedMetadataFromLabel($label, $value = null)
    {
        $md = null;
        switch ($label) {
            case 'title':
                $md = new Docman_Metadata();
                $md->setName(dgettext('tuleap-docman', 'Title'));
                $md->setLabel('title');
                $md->setDescription(dgettext('tuleap-docman', 'Title of the document.'));
                $md->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);
                $md->setIsRequired(true);
                $md->setIsEmptyAllowed(false);
                $md->setKeepHistory(false);
                $md->setUseIt(true);
                $md->setCanChangeValue(true);
                break;

            case 'description':
                $md = new Docman_Metadata();
                $md->setName(dgettext('tuleap-docman', 'Description'));
                $md->setLabel('description');
                $md->setDescription(dgettext('tuleap-docman', 'Description of the document.'));
                $md->setType(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
                $md->setIsRequired(true);
                $md->setIsEmptyAllowed(true);
                $md->setKeepHistory(false);
                $md->setUseIt(true);
                $md->setCanChangeValue(true);
                break;

            case self::HARDCODED_METADATA_OWNER_LABEL:
                $md = new Docman_Metadata();
                $md->setName(dgettext('tuleap-docman', 'Owner'));
                $md->setLabel('owner');
                $md->setDescription(dgettext('tuleap-docman', 'Default value: submitter. Correspond to official owner of the document.'));
                $md->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);
                $md->setIsRequired(true);
                $md->setIsEmptyAllowed(true);
                $md->setKeepHistory(true);
                $md->setUseIt(true);
                $md->setCanChangeValue(true);
                break;

            case 'create_date':
                $md = new Docman_Metadata();
                $md->setName(dgettext('tuleap-docman', 'Create Date'));
                $md->setLabel('create_date');
                $md->setDescription(dgettext('tuleap-docman', 'When the document was added into the document manager.'));
                $md->setType(PLUGIN_DOCMAN_METADATA_TYPE_DATE);
                $md->setIsRequired(true);
                $md->setIsEmptyAllowed(false);
                $md->setKeepHistory(true);
                $md->setUseIt(true);
                $md->setCanChangeValue(false);
                break;

            case 'update_date':
                $md = new Docman_Metadata();
                $md->setName(dgettext('tuleap-docman', 'Update Date'));
                $md->setLabel('update_date');
                $md->setDescription(dgettext('tuleap-docman', 'Last update of the document content or properties.'));
                $md->setType(PLUGIN_DOCMAN_METADATA_TYPE_DATE);
                $md->setIsRequired(true);
                $md->setIsEmptyAllowed(false);
                $md->setKeepHistory(true);
                $md->setUseIt(true);
                $md->setCanChangeValue(false);
                break;

            case 'status':
                $md = new Docman_ListMetadata();
                $md->setName(dgettext('tuleap-docman', 'Status'));
                $md->setLabel('status');
                $md->setDescription(dgettext('tuleap-docman', 'Document status.'));
                $md->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
                $md->setIsRequired(false);
                $md->setIsEmptyAllowed(true);
                $md->setKeepHistory(true);
                $md->setCanChangeValue(true);
                $md->setDefaultValue(PLUGIN_DOCMAN_ITEM_STATUS_NONE);
                break;

            case 'obsolescence_date':
                $md = new Docman_Metadata();
                $md->setName(dgettext('tuleap-docman', 'Obsolescence Date'));
                $md->setLabel('obsolescence_date');
                $md->setDescription(dgettext('tuleap-docman', 'Define a period of validity for your document. It will automatically be archived at the end of the period.'));
                $md->setType(PLUGIN_DOCMAN_METADATA_TYPE_DATE);
                $md->setIsRequired(false);
                $md->setIsEmptyAllowed(true);
                $md->setKeepHistory(false);
                $md->setCanChangeValue(true);
                $md->setDefaultValue(0);
                break;
        }

        if ($md !== null) {
            $md->setValue($value);
            $md->setSpecial(true);
            $md->setCanChangeName(false);
            $md->setCanChangeIsEmptyAllowed(false);
            $md->setCanChangeDescription(false);
            $md->setGroupId($this->groupId);
        }

        return $md;
    }

    /**
     * Create a new metadata based on an existing one in another project.
     *
     * @see cloneMetadata
     *
     * @param int $dstGroupId Project where the md is created
     * @param Docman_Metadata $md              Metadata to clone
     * @param Array           $metadataMapping Map between src and dst metadata id
     */
    public function _cloneOneMetadata($dstGroupId, $md, &$metadataMapping)
    {
        $dstMdFactory = $this->_getMetadataFactory($dstGroupId);

        $dstMdIter = $dstMdFactory->findByName($md->getName());
        if ($dstMdIter->count() == 0) {
            $newMd   = clone $md;
            $newMdId = $dstMdFactory->create($newMd);
            $newMd->setId($newMdId);

            $metadataMapping['md'][$md->getId()] = $newMdId;

            // If current metadata is a list of values, clone values
            if ($newMdId > 0 && $md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                $srcLoveFactory = $this->_getListOfValuesElementFactory($md->getId());
                $values = $srcLoveFactory->cloneValues($md, $newMd);
                foreach ($values as $srcId => $dstId) {
                    $metadataMapping['love'][$srcId] = $dstId;
                }
            }
        }
    }

    /**
     * Copy all real metadata defined in this project in the destination project
     *
     * @param int $dstGroupId Project where the metadata will be created
     * @param Array   $metadataMapping Map between src and dst metadata id
     */
    public function _cloneRealMetadata($dstGroupId, &$metadataMapping)
    {
        foreach ($this->getRealMetadataList(false) as $md) {
            $this->_cloneOneMetadata($dstGroupId, $md, $metadataMapping);
        }
    }

    /**
     * Copy current metadata structure in the destination project
     *
     * This method will create the same metadata (definitions and values if
     * needed) in the target project. A maps keeps the link between the id in
     * the source and the destination project.
     *
     * @param int $dstGroupId Project where to copy the metadata
     * @param Array   $metadataMapping  Map between source and target metadata definitions
     */
    public function cloneMetadata($dstGroupId, &$metadataMapping)
    {
        // Clone hardcoded metadata prefs
        $sBo = Docman_SettingsBo::instance($this->groupId);
        $sBo->cloneMetadataSettings($dstGroupId);

        // Clone metadata
        $this->_cloneRealMetadata($dstGroupId, $metadataMapping);
    }

    /**
     * Try to find the matching metadata between 2 projects
     * The matching is made on the name and type
     */
    public function getMetadataMapping($dstGroupId, &$metadataMapping)
    {
        $dstMdFactory = $this->_getMetadataFactory($dstGroupId);

        $metadataMapping = array();
        $metadataMapping['md'] = array();
        $metadataMapping['love'] = array();

        $mda = $this->getRealMetadataList(false);
        $mdIter = new ArrayIterator($mda);
        $mdIter->rewind();
        while ($mdIter->valid()) {
            $md = $mdIter->current();

            $dstMdi = $dstMdFactory->findByName($md->getName());
            if ($dstMdi->count() == 1) {
                // Found exactly one name that match
                $dstMdi->rewind();
                $dstMd = $dstMdi->current();

                if ($md->equivalent($dstMd)) {
                    // Mapping in both sense to make the usage of the map
                    // easier
                    $metadataMapping['md'][$md->getId()] = $dstMd->getId();
                    $metadataMapping['md'][$dstMd->getId()] = $md->getId();

                    if ($md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                        $loveFactory = $this->_getListOfValuesElementFactory($md->getId());
                        $loveFactory->getLoveMapping($md, $dstMd->getId(), $metadataMapping);
                    }
                }
            }

            $mdIter->next();
        }
    }


    public function _findRealMetadataByName($name, &$mda)
    {
        $dao = $this->getDao();

        $dar = $dao->searchByName($this->groupId, $name);
        $dar->rewind();
        while ($dar->valid()) {
            $md = $this->_createFromRow($dar->current());
            $md->setCanChangeName(true);
            $md->setCanChangeIsEmptyAllowed(true);
            $md->setCanChangeIsMultipleValuesAllowed(true);
            $md->setCanChangeDescription(true);

            $mda[] = $md;

            $dar->next();
        }
    }

    public function findByName($name)
    {
        $mda = array();

        // Hardcoded
        $hcmda = $this->getHardCodedMetadataList(true);
        foreach ($hcmda as $md) {
            if ($md->getName() == $name) {
                $mda[] = $md;
            }
        }
        // Real
        $this->_findRealMetadataByName($name, $mda);
        $ai = new ArrayIterator($mda);
        return $ai;
    }

    /**
     * Export metadata settings of current project into $dstGroupId
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
     * This function just 'add' things, it's not intend to synchronize two
     * projects (ie. properties defined in destination project but not in source
     * project are not deleted).
     *
     * @access: public
     */
    public function exportMetadata($dstGroupId)
    {
        // Import hardcoded metadata prefs
        $sBo = Docman_SettingsBo::instance($this->groupId);
        $sBo->exportMetadataUsage($dstGroupId);

        // Import metadata
        $this->_exportMetadata($dstGroupId);
    }

    public function _exportMetadata($dstGroupId)
    {
        // Get the properties mapping between the 2 projects
        $mdMap = array();
        $this->getMetadataMapping($dstGroupId, $mdMap);

        $dstMdFactory = $this->_getMetadataFactory($dstGroupId);

        // Get used metadata in source project
        foreach ($this->getRealMetadataList(true) as $srcMd) {
            // Get corresponding metadata in current project (if any)
            if (isset($mdMap['md'][$srcMd->getId()])) {
                $dstMd = $dstMdFactory->getFromLabel($dstMdFactory->getLabelFromId($mdMap['md'][$srcMd->getId()]));
                $dstMd->update($srcMd);
                $dstMdFactory->updateRealMetadata($dstMd);
                if ($srcMd->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                    $srcLoveFactory = $this->_getListOfValuesElementFactory($srcMd->getId());
                    $srcLoveFactory->exportValues($srcMd, $dstMd, $mdMap['love']);
                }
            } else {
                $_dummyMap = array();
                $this->_cloneOneMetadata($dstGroupId, $srcMd, $_dummyMap);
            }
        }
    }

    // Accessors for mock
    public function &_getMetadataFactory($groupId)
    {
        $mdf = new Docman_MetadataFactory($groupId);
        return $mdf;
    }

    public function &_getListOfValuesElementFactory($mdId)
    {
        $mdLoveF = new Docman_MetadataListOfValuesElementFactory($mdId);
        return $mdLoveF;
    }

    private function appendDynamicMetadataToItem(Docman_Item $item): void
    {
        $metadata_iterator = $this->getRealMetadataIterator(true);
        $metadata_iterator->rewind();

        while ($metadata_iterator->valid()) {
            $current_metadata = $metadata_iterator->current();
            $this->addMetadataValueToItem($item, $current_metadata);
            $metadata_iterator->next();
        }
    }

    private function appendHardcodedMetadataToItem(Docman_Item $item, array $modifiable_metadata): void
    {
        foreach ($modifiable_metadata as $metadata) {
            $metadata->setValue($item->getHardCodedMetadataValue($metadata->getLabel()));
            $item->addMetadata($metadata);
            unset($metadata);
        }
    }

    /**
     * @return Docman_ListMetadata|Docman_Metadata|null
     * @throws CustomMetadataException
     */
    public function getMetadataFromLabel($label)
    {
        if (in_array($label, self::HARDCODED_METADATA_LABELS)) {
            $md = $this->getHardCodedMetadataFromLabel($label);

            if ($this->groupId !== null) {
                $md->setGroupId($this->groupId);
            }

            if (in_array($md->getLabel(), $this->modifiableMetadata)) {
                $this->appendHardCodedMetadataParams($md);
            }

            return $md;
        }

        if (preg_match('/^field_([0-9]+)$/', $label, $match)) {
            return $this->getRealMetadata($match[1]);
        }

        throw CustomMetadataException::metadataNotFound($label);
    }
}
