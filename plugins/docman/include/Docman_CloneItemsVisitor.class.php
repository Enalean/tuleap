<?php
/**
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
 * $Id$
 */

require_once('Docman_ItemFactory.class.php');
require_once('Docman_PermissionsManager.class.php');
require_once('Docman_MetadataValueFactory.class.php');
require_once('Docman_MetadataFactory.class.php');

class Docman_CloneItemsVisitor {
    var $dstGroupId;
    var $_cacheMetadataUsage;
    
    function Docman_CloneItemsVisitor($dstGroupId) {
        $this->dstGroupId = $dstGroupId;
        $this->_cacheMetadataUsage = array();
    }

    function visitFolder($item, $params = array()) {
        // Clone folder
        $newItemId = $this->_cloneItem($item, $params);
        if($newItemId > 0) {
            $params['parentId'] = $newItemId;
            
            // Recurse
            $items =& $item->getAllItems();
            if($items) {
                $nb = $items->size();
                if($nb) {
                    $iter =& $items->iterator();
                    $iter->rewind();
                    while($iter->valid()) {
                        $child =& $iter->current();
                        $child->accept($this, $params);
                        $iter->next();
                    }
                }
            }
        }
    }

    function visitDocument(&$item, $params = array()) {
        die('never happen');
    }

    function visitWiki(&$item, $params = array()) {
        $this->_cloneItem($item, $params);
    }

    function visitLink(&$item, $params = array()) {
        $this->_cloneItem($item, $params);
    }

    function visitFile(&$item, $params = array()) {
        $this->_cloneFile($item, $params);
    }

    function visitEmbeddedFile(&$item, $params = array()) {
        $this->_cloneFile($item, $params);
    }

    function visitEmpty(&$item, $params = array()) {
        $this->_cloneItem($item, $params);
    }

    function _cloneFile($item, $params) {
        $newItemId = $this->_cloneItem($item, $params);
        if($newItemId > 0) {
            // Clone physical file of the last version in the template item
            $srcVersion = $item->getCurrentVersion();
            $srcPath = $srcVersion->getPath();
            $dstName = basename($srcPath);
            //print $srcPath.'-'.$dstName."-<br>";
            $fs = $this->_getFileStorage($params['data_root']);
            $dstPath = $fs->clone($srcPath,
                                      $dstName, $this->dstGroupId, $newItemId, 0);

            // Register a new file
            $versionFactory = $this->_getVersionFactory();
            $user = $params['user'];
            $label = $GLOBALS['Language']->getText('plugin_docman', 'clone_file_label');
            $project = group_get_object($item->getGroupId());
            $changelog = $GLOBALS['Language']->getText('plugin_docman', 'clone_file_changelog', array($item->getTitle(),
                                                                                                      $project->getPublicName(),
                                                                                                      $srcVersion->getNumber()));
            $newVersionArray = array('item_id'   => $newItemId,
                                     'number'    => 0,
                                     'user_id'   => $user->getId(),
                                     'label'     => $label,
                                     'changelog' => $changelog,
                                     'filename'  => $srcVersion->getFilename(),
                                     'filesize'  => $srcVersion->getFilesize(),
                                     'filetype'  => $srcVersion->getFiletype(),
                                     'path'      => $dstPath);
            
            $versionId = $versionFactory->create($newVersionArray);
            
        }
    }

    function _cloneItem($item, $params) {
        $parentId = $params['parentId'];
        $metadataMapping = $params['metadataMapping'];
        $ugroupsMapping = $params['ugroupsMapping'];

        // Clone Item
        $itemFactory = $this->_getItemFactory();
        $newItem = $item;
        $newItem->setGroupId($this->dstGroupId);
        $newItem->setParentId($parentId);
        // Change rank if specified
        if($item->getId() === $params['srcRootId']) {
            if(isset($params['newRank']) && $params['newRank'] !== null) {
                $newItem->setRank($params['newRank']);
            }
        }
        // Check for special metadata
        if(!$this->_metadataEnabled($item->getGroupId(), 'status')) {
            $newItem->setStatus(PLUGIN_DOCMAN_ITEM_STATUS_NONE);
        }
        if(!$this->_metadataEnabled($item->getGroupId(), 'obsolescence_date')) {
            $newItem->setObsolescenceDate(PLUGIN_DOCMAN_ITEM_VALIDITY_PERMANENT);
        }

        $newItemId = $itemFactory->rawCreate($newItem);
        if($newItemId > 0) {
            // Clone Permissions
            $this->_clonePermissions($item, $newItemId, $ugroupsMapping);

            // Clone Metadata values
            $this->_cloneMetadataValues($item, $newItemId, $metadataMapping);
        }
        return $newItemId;
    }

    function _clonePermissions($item, $newItemId, $ugroupsMapping) {
        $dpm =& $this->_getPermissionsManager($item->getGroupId());
        if($ugroupsMapping === false) {
            // ugroups mapping is not available.
            // use default values.
            $dpm->setDefaultItemPermissions($newItemId, true);
        }
        else {
            $dpm->cloneItemPermissions($item->getId(), $newItemId, $this->dstGroupId);
        }
    }

    function _cloneMetadataValues($item, $newItemId, $metadataMapping) {
        // List for current item all its metadata and
        // * change the itemId
        // * change the fieldId (use mapping between template metadata and
        //   project metadata)
        // * for list of values change the values (use mapping as behind).
        $newMdvFactory =& $this->_getMetadataValueFactory($this->dstGroupId);
        $oldMdvFactory =& $this->_getMetadataValueFactory($item->getGroupId());
        
        $oldMdFactory =& $this->_getMetadataFactory($item->getGroupId());
        $oldMdFactory->appendItemMetadataList($item);
        
        $oldMdIter =& $item->getMetadataIterator();
        $oldMdIter->rewind();
        while($oldMdIter->valid()) {
            $oldMd = $oldMdIter->current();
            
            if($oldMdFactory->isRealMetadata($oldMd->getLabel())) {
                $oldMdv = $oldMdvFactory->getMetadataValue($item, $oldMd);

                if(isset($metadataMapping['md'][$oldMd->getId()])) {
                    $newMdv = $oldMdv;
                    $newMdv->setItemId($newItemId);
                    $newMdv->setFieldId($metadataMapping['md'][$oldMd->getId()]);
                    if($oldMd->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                        $ea = array();
                        $eIter = $oldMdv->getValue();
                        $eIter->rewind();
                        while($eIter->valid()) {
                            $e = $eIter->current();
                            
                            // no maping for value `100` (shared by all lists).
                            if(($e->getId() != 100) && isset($metadataMapping['love'][$e->getId()])) {
                                $newE = $e;
                                $newE->setId($metadataMapping['love'][$e->getId()]);
                                $ea[] = $newE;
                            }
                            
                            $eIter->next();
                        }
                        // No match found: set default value.
                        if(count($ea) == 0) {
                            $newMdFactory = $this->_getMetadataFactory($this->dstGroupId);
                            $newMdId = $metadataMapping['md'][$oldMd->getId()];
                            $newMd   = $newMdFactory->getFromLabel('field_'.$newMdId);
                            $e = new Docman_MetadataListOfValuesElement();
                            $e->setId($newMd->getDefaultValue());
                            $ea[] = $e;
                        }
                        $newMdv->setValue($ea);
                    }
                    $newMdvFactory->create($newMdv);
                }
            }

            $oldMdIter->next();
        }
    }
    
    function _metadataEnabled($srcGroupId, $mdLabel) {
        if(!isset($this->_cacheMetadataUsage[$mdLabel])) {
            $srcSettingsBo =& Docman_SettingsBo::instance($srcGroupId);
            $dstSettingsBo =& Docman_SettingsBo::instance($this->dstGroupId);
            $this->_cacheMetadataUsage[$mdLabel] = ($srcSettingsBo->getMetadataUsage($mdLabel) 
                                                    && $dstSettingsBo->getMetadataUsage($mdLabel));
        }
        return $this->_cacheMetadataUsage[$mdLabel];
    }

    // Factory methods mandatate by tests.
    function &_getItemFactory() {
        $o = new Docman_ItemFactory();
        return $o;
    }

    function &_getPermissionsManager($groupId) {
        $o =& Docman_PermissionsManager::instance($groupId);
        return $o;
    }

    function &_getFileStorage($dataRoot) {
        $o = new Docman_FileStorage($dataRoot);
        return $o;
    }

    function &_getVersionFactory() {
        $o = new Docman_VersionFactory();
        return $o;
    }

    function &_getMetadataValueFactory($groupId) {
        $o = new Docman_MetadataValueFactory($groupId);
        return $o;
    }

    function &_getMetadataFactory($groupId) {
        $o = new Docman_MetadataFactory($groupId);
        return $o;
    }
}

?>
