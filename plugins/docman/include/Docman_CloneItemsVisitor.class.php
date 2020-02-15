<?php
/**
 * Copyright (c) Enalean, 2018-Present. All rights reserved
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

use Tuleap\Docman\Item\ItemVisitor;
use Tuleap\Docman\Metadata\DocmanMetadataTypeValueFactory;

/**
 * @template-implements ItemVisitor<void>
 */
class Docman_CloneItemsVisitor implements ItemVisitor
{
    public $dstGroupId;
    public $_cacheMetadataUsage;
    public $itemMapping;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var Docman_LinkVersionFactory
     */
    private $link_version_factory;

    public function __construct(
        $dstGroupId,
        ProjectManager $project_manager,
        Docman_LinkVersionFactory $link_version_factory
    ) {
        $this->dstGroupId           = $dstGroupId;
        $this->_cacheMetadataUsage  = array();
        $this->itemMapping          = array();
        $this->project_manager      = $project_manager;
        $this->link_version_factory = $link_version_factory;
    }

    public function visitFolder($item, $params = array())
    {
        // Clone folder
        $newItemId = $this->_cloneItem($item, $params);
        if ($newItemId > 0) {
            $params['parentId'] = $newItemId;

            // Recurse
            $items = $item->getAllItems();
            if ($items) {
                $nb = $items->size();
                if ($nb) {
                    $iter = $items->iterator();
                    $iter->rewind();
                    while ($iter->valid()) {
                        $child = $iter->current();
                        $child->accept($this, $params);
                        $iter->next();
                    }
                }
            }
        }
    }

    public function visitDocument(&$item, $params = array())
    {
        die('never happen');
    }

    public function visitWiki(Docman_Wiki $item, $params = array())
    {
        $this->_cloneItem($item, $params);
    }

    public function visitLink(Docman_Link $item, $params = array())
    {
        $copied_item_id = $this->_cloneItem($item, $params);

        $copied_item = $this->_getItemFactory()->getItemFromDb($copied_item_id);
        if ($copied_item !== null) {
            assert($copied_item instanceof Docman_Link);
            $this->link_version_factory->create(
                $copied_item,
                dgettext('tuleap-docman', 'Copy from template'),
                $this->getChangelogForCopiedItem($copied_item),
                (new DateTimeImmutable())->getTimestamp()
            );
        }
    }

    public function visitFile(Docman_File $item, $params = array())
    {
        $this->_cloneFile($item, $params);
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, $params = array())
    {
        $this->_cloneFile($item, $params);
    }

    public function visitEmpty(Docman_Empty $item, $params = array())
    {
        $this->_cloneItem($item, $params);
    }

    public function visitItem(Docman_Item $item, array $params = [])
    {
    }

    public function _cloneFile($item, $params)
    {
        $newItemId = $this->_cloneItem($item, $params);
        if ($newItemId > 0) {
            // Clone physical file of the last version in the template item
            $srcVersion = $item->getCurrentVersion();
            $srcPath = $srcVersion->getPath();
            $dstName = basename($srcPath);
            //print $srcPath.'-'.$dstName."-<br>";
            $fs = $this->_getFileStorage($params['data_root']);
            $dstPath = $fs->copy(
                $srcPath,
                $dstName,
                $this->dstGroupId,
                $newItemId,
                0
            );

            // Register a new file
            $versionFactory = $this->_getVersionFactory();
            $user = $params['user'];

            $newVersionArray = array('item_id'   => $newItemId,
                                     'number'    => 0,
                                     'user_id'   => $user->getId(),
                                     'label'     => dgettext('tuleap-docman', 'Copy from template'),
                                     'changelog' => $this->getChangelogForCopiedItem($item),
                                     'filename'  => $srcVersion->getFilename(),
                                     'filesize'  => $srcVersion->getFilesize(),
                                     'filetype'  => $srcVersion->getFiletype(),
                                     'path'      => $dstPath);

            $versionId = $versionFactory->create($newVersionArray);
        }
    }

    /**
     * @param Docman_File|Docman_Link $original_item
     */
    private function getChangelogForCopiedItem($original_item) : string
    {
        $project_id = $original_item->getGroupId();
        $project    = $this->project_manager->getProject($project_id);
        if ($project === null) {
            throw new RuntimeException(
                sprintf(
                    'The project #%d of item #%d does not exist',
                    $original_item->getId(),
                    $original_item->getGroupId()
                )
            );
        }

        $current_version = $original_item->getCurrentVersion();

        return sprintf(
            dgettext('tuleap-docman', 'Copy of %s in %s at version %d.'),
            $original_item->getTitle(),
            $project->getPublicName(),
            $current_version === null ? 0 : $current_version->getNumber()
        );
    }

    public function _cloneItem($item, $params)
    {
        $parentId = $params['parentId'];
        $metadataMapping = $params['metadataMapping'];
        $ugroupsMapping = $params['ugroupsMapping'];

        // Clone Item
        $itemFactory = $this->_getItemFactory();
        // @php5: clone
        $newItem = clone $item;
        $newItem->setGroupId($this->dstGroupId);
        $newItem->setParentId($parentId);
        // Change rank if specified
        if ($item->getId() === $params['srcRootId']) {
            if (isset($params['newRank']) && $params['newRank'] !== null) {
                $newItem->setRank($params['newRank']);
            }
        }
        // Check for special metadata
        if (!$this->_metadataEnabled($item->getGroupId(), 'status')) {
            $newItem->setStatus(PLUGIN_DOCMAN_ITEM_STATUS_NONE);
        }
        if (!$this->_metadataEnabled($item->getGroupId(), 'obsolescence_date')) {
            $newItem->setObsolescenceDate(PLUGIN_DOCMAN_ITEM_VALIDITY_PERMANENT);
        }

        $newItemId = $itemFactory->rawCreate($newItem);
        if ($newItemId > 0) {
            // Keep track of which item id in the new tree correspond the source item id
            // This is needed for reports that applies on specific folders.
            $this->itemMapping[$item->getId()] = $newItemId;

            // Clone Permissions
            $this->_clonePermissions($item, $newItemId, $ugroupsMapping);

            // Clone Metadata values
            $this->_cloneMetadataValues($item, $newItemId, $metadataMapping);
        }
        return $newItemId;
    }

    public function _clonePermissions($item, $newItemId, $ugroupsMapping)
    {
        $dpm = $this->_getPermissionsManager($item->getGroupId());
        if ($ugroupsMapping === false) {
            // ugroups mapping is not available.
            // use default values.
            $dpm->setDefaultItemPermissions($newItemId, true);
        } else {
            $dpm->cloneItemPermissions($item->getId(), $newItemId, $this->dstGroupId);
        }
    }

    public function _cloneMetadataValues($item, $newItemId, $metadataMapping)
    {
        // List for current item all its metadata and
        // * change the itemId
        // * change the fieldId (use mapping between template metadata and
        //   project metadata)
        // * for list of values change the values (use mapping as behind).
        $newMdvFactory = $this->_getMetadataValueFactory($this->dstGroupId);
        $type_value_factory = new DocmanMetadataTypeValueFactory();

        $oldMdFactory = $this->_getMetadataFactory($item->getGroupId());
        $oldMdFactory->appendItemMetadataList($item);

        $oldMdIter = $item->getMetadataIterator();
        $oldMdIter->rewind();
        while ($oldMdIter->valid()) {
            $oldMd = $oldMdIter->current();

            if ($oldMdFactory->isRealMetadata($oldMd->getLabel())) {
                $oldValue = $oldMdFactory->getMetadataValue($item, $oldMd);

                if (isset($metadataMapping['md'][$oldMd->getId()])) {
                    $newMdv = $type_value_factory->createFromType($oldMd->getType());
                    $newMdv->setItemId($newItemId);
                    $newMdv->setFieldId($metadataMapping['md'][$oldMd->getId()]);
                    if ($oldMd->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                        $ea = array();
                        $oldValue->rewind();
                        while ($oldValue->valid()) {
                            $e = $oldValue->current();

                            // no maping for value `100` (shared by all lists).
                            if (($e->getId() != 100) && isset($metadataMapping['love'][$e->getId()])) {
                                $newE = clone $e;
                                $newE->setId($metadataMapping['love'][$e->getId()]);
                                $ea[] = $newE;
                            }

                            $oldValue->next();
                        }
                        // No match found: set None value.
                        if (count($ea) == 0) {
                            $e = new Docman_MetadataListOfValuesElement();
                            $e->setId(PLUGIN_DOCMAN_ITEM_STATUS_NONE);
                            $ea[] = $e;
                        }
                        $newMdv->setValue($ea);
                    } else {
                        $newMdv->setValue($oldValue);
                    }
                    $newMdvFactory->create($newMdv);
                }
            }

            $oldMdIter->next();
        }
    }

    public function _metadataEnabled($srcGroupId, $mdLabel)
    {
        if (!isset($this->_cacheMetadataUsage[$mdLabel])) {
            $srcSettingsBo = $this->_getSettingsBo($srcGroupId);
            $dstSettingsBo = $this->_getSettingsBo($this->dstGroupId);
            $this->_cacheMetadataUsage[$mdLabel] = ($srcSettingsBo->getMetadataUsage($mdLabel)
                                                    && $dstSettingsBo->getMetadataUsage($mdLabel));
        }
        return $this->_cacheMetadataUsage[$mdLabel];
    }

    /**
     * Return the mapping between item_id in the original tree (src) and the new one (dst).
     * Src item id it the key of the hash map.
     */
    public function getItemMapping()
    {
        return $this->itemMapping;
    }

    // Factory methods mandatate by tests.
    public function _getItemFactory()
    {
        return new Docman_ItemFactory();
    }

    public function _getPermissionsManager($groupId)
    {
        return Docman_PermissionsManager::instance($groupId);
    }

    public function _getFileStorage($dataRoot)
    {
        return new Docman_FileStorage($dataRoot);
    }

    public function _getVersionFactory()
    {
        return new Docman_VersionFactory();
    }

    public function _getMetadataValueFactory($groupId)
    {
        return new Docman_MetadataValueFactory($groupId);
    }

    public function _getMetadataFactory($groupId)
    {
        return new Docman_MetadataFactory($groupId);
    }

    public function _getSettingsBo($groupId)
    {
        return Docman_SettingsBo::instance($groupId);
    }
}
