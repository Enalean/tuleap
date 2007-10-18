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
 * 
 */
require_once('common/dao/CodexDataAccess.class.php');

require_once('DocmanConstants.class.php');
require_once('Docman_Item.class.php');
require_once('Docman_ItemDao.class.php');
require_once('Docman_Folder.class.php');
require_once('Docman_File.class.php');
require_once('Docman_Link.class.php');
require_once('Docman_EmbeddedFile.class.php');
require_once('Docman_Wiki.class.php');
require_once('Docman_Empty.class.php');
require_once('Docman_Version.class.php');
require_once('Docman_CloneItemsVisitor.class.php');
require_once('Docman_SubItemsRemovalVisitor.class.php');
require_once('Docman_PermissionsManager.class.php');

/**
 * 
 */
class Docman_ItemFactory {
    var $rootItems;
    var $onlyOneChildForRoot;
    var $copiedItem;
    var $groupId;

    function Docman_ItemFactory($groupId=null) {
        // Cache highly used info
        $this->rootItems[] = array();
        $this->onlyOneChildForRoot[] = array();
        $this->copiedItem = array();

        // Parameter
        $this->groupId = $groupId;
    }

    function &instance($group_id) {
        static $_instance_Docman_ItemFactory;
        if(!isset($_instance_Docman_ItemFactory[$group_id])) {
            $_instance_Docman_ItemFactory[$group_id] = new Docman_ItemFactory();
        }
        return $_instance_Docman_ItemFactory[$group_id];
    }

    function setGroupId($id) {
        $this->groupId = $id;
    }
    function getGroupId() {
        return $this->groupId;
    }

    function &getItemFromRow(&$row) {
        $item = null;
        switch($row['item_type']) {
        case PLUGIN_DOCMAN_ITEM_TYPE_FOLDER:
            $item = new Docman_Folder($row);
            break;
        case PLUGIN_DOCMAN_ITEM_TYPE_FILE:
            $item = new Docman_File($row);
            break;
        case PLUGIN_DOCMAN_ITEM_TYPE_LINK:
            $item = new Docman_Link($row);
            break;
        case PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE:
            $item = new Docman_EmbeddedFile($row);
            break;
        case PLUGIN_DOCMAN_ITEM_TYPE_WIKI:
            $item = new Docman_Wiki($row);
            break;
        case PLUGIN_DOCMAN_ITEM_TYPE_EMPTY:
            $item = new Docman_Empty($row);
            break;
        default:
            return;
        }
        if ($row['item_type'] == PLUGIN_DOCMAN_ITEM_TYPE_FILE || $row['item_type'] == PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE) {
            if (isset($row['version_id'])) {
                $version = array(
                    'id'        => $row['version_id'],
                    'user_id'   => $row['version_user_id'],
                    'item_id'   => $item->getId(),
                    'number'    => $row['version_number'],
                    'label'     => $row['version_label'],
                    'changelog' => $row['version_changelog'],
                    'date'      => $row['version_date'],
                    'filename'  => $row['version_filename'],
                    'filesize'  => $row['version_filesize'],
                    'filetype'  => $row['version_filetype'],
                    'path'      => $row['version_path']
                );
                $item->setCurrentVersion(new Docman_Version($version));
            }
        }
        return $item;
    }

    function getItemTypeForItem(&$item) {
        $type = false;
        switch(strtolower(get_class($item))) {
            case 'docman_folder':
                $type = PLUGIN_DOCMAN_ITEM_TYPE_FOLDER;
                break;
            case 'docman_link':
                $type = PLUGIN_DOCMAN_ITEM_TYPE_LINK;
                break;
            case 'docman_wiki':
               $type = PLUGIN_DOCMAN_ITEM_TYPE_WIKI;
                break;
            case 'docman_file':
                $type = PLUGIN_DOCMAN_ITEM_TYPE_FILE;
                break;
            case 'docman_embeddedfile':
                $type = PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE;
                break;
            case 'docman_empty':
                $type = PLUGIN_DOCMAN_ITEM_TYPE_EMPTY;
                break;
            default:
                break;
        }
        return $type;
    }

    function &getItemFromDb($id, $params = array()) {
        $_id = (int) $id;
        $dao =& $this->_getItemDao();
        $dar = $dao->searchById($id, $params);

        if($dar->isError()){
            return;
        }
        
        if(!$dar->valid()) {
            return;
        }

        $row =& $dar->current();

        return(Docman_ItemFactory::getItemFromRow($row));
    }

    function &getChildrenFromParent($item) {
        $dao =& $this->_getItemDao();
        
        $itemArray = array();

        $dar = $dao->searchByParentsId(array($item->getId()));
        if ($dar && !$dar->isError()) {
            while($dar->valid()) {
                $row = $dar->current();

                $itemArray[] = Docman_ItemFactory::getItemFromRow($row);

                $dar->next();
            }
        }

        $iIter = new ArrayIterator($itemArray);
        return $iIter;
    }
    
    /**
     * Retreive list of collapsed items for given user
     *
     * This function retreive collapsed folders from user preferences 
     *
     * @param $parentId Id of the "current" root node (cannot be excluded).
     * @param $userId Id of current user.
     * @return Array List of items to exclude for a search
     **/
    function &_getExpandedUserPrefs($parentId, $userId) {           
        $collapsedItems = array();     
        // Retreive the list of collapsed folders in prefs
        $dao =& $this->_getItemDao();
        $dar = $dao->searchExpandedUserPrefs($this->groupId, 
                                                   $userId);
        while($dar->valid()) {
            $row =& $dar->current();
            $tmp = explode('_', $row['preference_name']);
            if ($tmp[4] != $parentId) {
                $collapsedItems[] = (int) $tmp[4];
            }
            $dar->next();
        }               
        
        return $collapsedItems;
    }

    /**
     *
     */
    function userHasPermission(&$user, &$item, $ignorePerms = false) {
        $userCanRead = false;
        if($ignorePerms === true) {
            $userCanRead = true;
        } else {
            $dPm =& Docman_PermissionsManager::instance($this->groupId);
            $userCanRead = $dPm->userCanRead($user, $item->getId());
        }
        return $userCanRead;
    }
    
    /**
     * Preload item perms from a item result set
     */
    function preloadItemPerms($dar, $user) {
        // Preload perms
        $objectsIds = array();
        while($dar->valid()) {
            $row = $dar->current();
            $objectsIds[] = $row['item_id'];
            
            $dar->next();
        }

        $dpm =& Docman_PermissionsManager::instance($this->groupId);
        $dpm->retreiveReadPermissionsForItems($objectsIds, $user);
    }

    /**
     * Build a subtree from with the list of items
     *
     * @param $parentId int Id of tree root.
     * @param $params['user']
     * @param $params['filter']          (Optional)
     * @param $params['ignore_collapse'] (Optional)
     * @param $params['ignore_perms']    (Optional)
     * @return Item
     */
    function &getItemSubTree($parentId, $params = null) {
        //
        // Parameters
        //
        $_parentId = (int) $parentId;
        $user =& $params['user'];

        // {{1}} Exclude collapsed items
        $expandedFolders = array();
        if(!isset($params['ignore_collapse']) || !$params['ignore_collapse']) {
            $expandedFolders =& $this->_getExpandedUserPrefs($_parentId,
                                                             user_getid());
        }

        // Prepare filters if any
        $filter = null;
        if(isset($params['filter'])) {
            $filter =& $params['filter'];
        }

        $ignorePerms = false;
        if(isset($params['ignore_perms']) && $params['ignore_perms'] === true) {
            $ignorePerms = true;
        }

        $searchItemsParams = array();
        if(isset($params['ignore_obsolete'])) {
            $searchItemsParams['ignore_obsolete'] = $params['ignore_obsolete'];
        }

        //
        // Treatment
        //
        $dao =& $this->_getItemDao();
        $dar = $dao->searchByGroupId($this->groupId, $filter, $searchItemsParams);

        $this->preloadItemPerms($dar, $user);

        $parentIdList = array();
        $itemList = array();
        $first = true;
        $dar->rewind();
        while(count($parentIdList) > 0 || $first) {
            if(!$first) {
                $dar = $dao->searchByIdList($parentIdList);
            }
            else {
                $first = false;
            }
               
            $tmpParentIdList = array();

            while($dar->valid()) {
                $row =& $dar->current();

                $item =& $this->getItemFromRow($row);
                if($item && $this->userHasPermission($user, $item, $ignorePerms)) {
                    $insert = false;
                    $type = $this->getItemTypeForItem($item);
                    if ($type == PLUGIN_DOCMAN_ITEM_TYPE_FILE 
                        || $type == PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE) {
                        // For items with history, we retreive all versions
                        // of the item. The following keep only the more
                        // recent version of the item
                        if(isset($itemList[$item->getId()])) {
                            $oldVer =& $itemList[$item->getId()]->getCurrentVersion();
                            $newVer =& $item->getCurrentVersion();
                            if($oldVer->getDate() < $newVer->getDate()) {
                                $insert = true;
                            }
                        }
                        else {
                            $insert = true;
                        }
                    }
                    else {
                        $insert = true;
                    }

                    if($insert) {
                        $itemList[$item->getId()] =& $item;
                        if($item->getId() != $_parentId) {
                            $tmpParentIdList[] = $item->getParentId();
                        }
                    }
                }
                
                $dar->next();
            }
            
            $parentIdList = array();
            foreach($tmpParentIdList as $id) {
                if(!array_key_exists($id, $itemList) 
                   && !in_array($id, $parentIdList)) {
                    $parentIdList[] = $id;
                }
            }
         }
     
        // Note: use foreach with keys to ensure we only deal with
        // references (foreach $itemList loose references)

        // Build hierarchie
        $keys = array_keys($itemList);
        foreach($keys as $i) {
            if(isset($itemList[$itemList[$i]->getParentId()])) {
                $itemList[$itemList[$i]->getParentId()]->addItem($itemList[$i]);
            }
        }        
        
        // @todo: Tailor empty folders      

        // Tailor expanded folders
        if (!isset($params['ignore_collapse']) || !$params['ignore_collapse']) {
            $keys = array_keys($itemList);
            $remove_subitems =& new Docman_SubItemsRemovalVisitor();
            foreach($keys as $i) {
                $item =& $itemList[$i];
                if(!in_array($item->getId(), $expandedFolders) && $item->getParentId() && $item->getId() != $parentId) {
                    // @todo: Delete all childrens
                    $item->accept($remove_subitems, array());
                }
                unset($item);
            }
        }

        // If nothing to output, output root (?)
        if(!isset($itemList[$_parentId])) {
            $item =& $this->findById($_parentId);
            if($item && $this->userHasPermission($user, $item)) {
                $itemList[$_parentId] =& $item;
            }
        }

        return $itemList[$_parentId];     
    }

    /**
     * This function return an iterator on a list of documents (no folders).
     *
     * How it works:
     * 1. Get the list of all documents that match the criteria (if
     *    any!). (permissions apply).
     *    Note: the final list of documents is a subset of this result.
     * 2. Get the list of folders behind $parentId (permissions apply).
     * 3. Check that each document in list 1. is in a folder of list 2.
     * 5. Apply limits ($start, $offset) is only a subset of the list is required.
     * 6. If needed, add the metadata to the items. 
     */
    function &getItemSubTreeAsList($parentId, &$nbItemsFound, $params = null) {
        $_parentId = (int) $parentId;
               
        $user =& $params['user'];

        // Prepare filters if any
        $filter = null;
        if(isset($params['filter'])) {
            $filter =& $params['filter'];
        }

        // Obsolescence
        $searchItemsParams = array();
        if(isset($params['ignore_obsolete'])) {
            $searchItemsParams['ignore_obsolete'] = $params['ignore_obsolete'];
        }

        // List doesn't really care about folders
        $searchItemsParams['ignore_folders'] =  true;

        // Range of documents to return
        $start = 0;
        if(isset($params['start'])) {
            $start = $params['start'];
        }
        $end = 25;
        if(isset($params['offset'])) {
            $end = $start + $params['offset'];
        }

        $dao =& $this->_getItemDao();

        //
        // Build Folder List
        //
        $parentItem = $this->findById($parentId);
        $folderList = array($parentId => &$parentItem);
        $pathIdArray = array($parentId => array());
        $pathTitleArray = array($parentId => array());
        $parentIds = array($parentId);
        $folderIds = array($parentId);
        $i = 0;
        do {
            $i++;
            $dar = $dao->searchSubFolders($parentIds);
            $parentIds = array();
            if($dar && !$dar->isError()) {
                $dar->rewind();
                while($dar->valid()) {
                    $row = $dar->current();
                    $item = $this->getItemFromRow($row);

                    if($this->userHasPermission($user, $item)) {
                        $folderList[$item->getId()] = $item;
                        $parentIds[] = $item->getId();
                        $folderIds[] = $item->getId();
                        
                        // Update path
                        $pathIdArray[$item->getId()] = array_merge($pathIdArray[$item->getParentId()], array($item->getId()));
                        $pathTitleArray[$item->getId()] = array_merge($pathTitleArray[$item->getParentId()], array($item->getTitle()));
                    }

                    $dar->next();
                }
            }
        } while(count($parentIds) > 0);

        //
        // Keep only documents in allowed subfolders
        //
        $mdFactory  = new Docman_MetadataFactory($this->groupId);
        $ci = null;
        if($filter !== null) {
            $ci = $filter->getColumnIterator();
        }

        //
        // Build Document list
        //
        $documentList = array();
        if(isset($params['obsolete_only']) && $params['obsolete_only']) {
            $dar = $dao->searchObsoleteByGroupId($this->groupId);
        } else {
            $dar = $dao->searchByGroupId($this->groupId, $filter, $searchItemsParams);
        }
        $documentList =& $this->_getItemArrayFromDar($dar, $user);

        // Compute the number of document found.
        $nbItemsFound = count($documentList);

        $itemIter =& new ArrayIterator($documentList);
        $itemIter->rewind();
        $docFoundIdx = 0;
        while($itemIter->valid()) {
            $item =& $itemIter->current();
            if(!isset($folderList[$item->getParentId()])) {
                // The document is not is one of the allowed subfolder so we
                // can delete it. As a side effect decrease the number of
                // document found.
                unset($documentList[$item->getId()]);
                $nbItemsFound--;
            } else {
                if($docFoundIdx >= $start && $docFoundIdx < $end) {
                    // Append Path
                    $item->setPathTitle($pathTitleArray[$item->getParentId()]);
                    $item->setPathId($pathIdArray[$item->getParentId()]);
                    
                    // Append metadata
                    if($ci !== null) {
                        $ci->rewind();
                        while($ci->valid()) {
                            $c = $ci->current();
                            if($c->md !== null && $mdFactory->isRealMetadata($c->md->getLabel())) {
                                $mdFactory->addMetadataValueToItem($item, $c->md);
                            }
                            $ci->next();
                        }
                    }
                } else {
                    // Item out of the range asked by user, no need to return
                    // it. However, since we want to display the total number
                    // of documents found, do not decrease $nbItemsFound.
                    unset($documentList[$item->getId()]);
                }
                $docFoundIdx++;
            }
            $itemIter->next();
        }

        $docIter = new ArrayIterator($documentList);
        return $docIter;
    }

    /**
     *
     */
    function &_getItemArrayFromDar($dar, $user) {
        $itemArray = array();
        if($dar && !$dar->isError()) {
            $this->preloadItemPerms($dar, $user);
            $dar->rewind();
            while($dar->valid()) {
                $row = $dar->current();
                $item =& $this->getItemFromRow($row);
                if($this->userHasPermission($user, $item)) {
                    $itemArray[$item->getId()] =& $item;
                }
                unset($item);
                $dar->next();
            }
        }
        return $itemArray;
    }

    /**
     * Build a tree from with the list of items
     *
     * @return ItemNode
     */
    function &getItemTree($id = 0, $params = null) {
        if (!$id) {
            $dao =& $this->_getItemDao();
            $id = $dao->searchRootIdForGroupId($this->groupId);
        }
        return $this->getItemSubTree($id, $params);
    }

    /**
     * Build a list of items
     *
     * @return ItemNode
     */
    function &getItemList($id = 0, &$nbItemsFound, $params = null) {
        if (!$id) {
            $dao =& $this->_getItemDao();
            $id = $dao->searchRootIdForGroupId($this->groupId);
        }
        return $this->getItemSubTreeAsList($id, $nbItemsFound, $params);
    } 

    /**
     *
     */
    function &getDocumentsIterator() {
        $dao =& $this->_getItemDao();
        $filters = null;
        $dar = $dao->searchByGroupId($this->groupId, $filters, array());
        $itemList = array();
        while($dar->valid()) {
            $row = $dar->current();

            $item =& $this->getItemFromRow($row);
            $type = $this->getItemTypeForItem($item);
            if($type != PLUGIN_DOCMAN_ITEM_TYPE_FOLDER) {
                if(!isset($itemList[$item->getId()])) {
                    $itemList[$item->getId()] =& $item;
                }
            }

            $dar->next();
        }

        $i = new ArrayIterator($itemList);
        return $i;
    }

    /**
     * @return Item
     */
    function &findById($id, $params = array()) {
        $item_factory =& $this->_getItemFactory();
        $item =& $item_factory->getItemFromDb($id);
        if (is_a($item, 'Docman_Folder') && isset($params['recursive']) && $params['recursive']) {
            $item =& $this->getItemSubTree($item->getId(), $params);
        }
        return $item;
    }

    /**
     *
     */
    var $item_factory;
    function &_getItemFactory() {
        if (!$this->item_factory) {
            $this->item_factory =& new Docman_ItemFactory();
        }
        return $this->item_factory;
    }

    /**
     *
     */
    function findByTitle($user, $title) {
        $ia = array();

        $dao =& $this->_getItemDao();
        $dar = $dao->searchByTitle($title);
        $dar->rewind();
        while($dar->valid()) {
            $row = $dar->current();

            $item = $this->getItemFromRow($row);
            if($this->userHasPermission($user, $item)) {
                $parentItem = $this->findById($item->getParentId());
                if($this->userHasPermission($user, $parentItem)) {
                    $ia[] = $item;
                }
            }

            $dar->next();
        }

        $ii = new ArrayIterator($ia);

        return $ii;
    }

    /*
     * Give the list of documents obsolete that have an obsolescence date in
     * one month.
     * It means that the obso date of the document is between 00:00:00 and
     * 23:59:59 in on month from today.
     */
    function findFuturObsoleteItems() {
        // Compute the timescale for the day in one month
        $today = getdate();
        $tsStart = mktime(0,0,0, $today['mon']+1, $today['mday'], $today['year']);
        $tsEnd   = mktime(23,59,59, $today['mon']+1, $today['mday'], $today['year']);

        $ia = array();
        $dao =& $this->_getItemDao();
        $dar = $dao->searchObsoleteAcrossProjects($tsStart, $tsEnd);
        while($dar->valid()) {
            $row = $dar->current();
            $ia[] = $this->getItemFromRow($row);
            $dar->next();
        }

        $ii = new ArrayIterator($ia);
        return $ii;
    }

    var $dao;
    function &_getItemDao() {
        if (!$this->dao) {
            $this->dao =& new Docman_ItemDao(CodexDataAccess::instance());
        }
        return $this->dao;
    }

    function update($row) {
        $dao =& $this->_getItemDao();
        return $dao->updateFromRow($row);
    }

    function massUpdate($srcItemId, $mdLabel, $itemIdArray) {
        $dao =& $this->_getItemDao();
        $dao->massUpdate($srcItemId, $mdLabel, $itemIdArray);
    }

    function create($row, $ordering) {
        $dao =& $this->_getItemDao();
        $id = $dao->createFromRow($row);
        if ($id) {
            $this->setNewParent($id, $row['parent_id'], $ordering);
        }
        return $id;
    }

    /**
     * Find root unique child if exists.
     *
     * @param $groupId Project id of the docman.
     * @return int/boolean false if there is more than one children for root.
     *                     true if there is no child for root.
     *                     item_id of the unique child of root if any.
     */
    function isItemTheOnlyChildOfRoot($groupId) {
        if(!isset($this->onlyOneChildForRoot[$groupId])) {
            $dao = $this->_getItemDao();
            $dar = $dao->hasRootOnlyOneChild($groupId);
            if($dar && !$dar->isError()) {
                if($dar->rowCount() > 1) {
                    $this->onlyOneChildForRoot[$groupId] = false;
                } elseif($dar->rowCount() == 0) {
                    $this->onlyOneChildForRoot[$groupId] = true;
                } else {
                    $row = $dar->getRow();
                    $this->onlyOneChildForRoot[$groupId] = (int) $row['item_id'];
                }
            }
        }
        return $this->onlyOneChildForRoot[$groupId];
    }

    /**
     * Check if given item is movable or not.
     *
     * An item is movable if:
     * - it's not root of the project.
     * - there is more than one children for root.
     * - or if the item is not the unique children of root.
     */
    function isMoveable($item) {
        $movable = false;
        if($item->getParentId() != 0) {
            $onlyOneChild = $this->isItemTheOnlyChildOfRoot($item->getGroupId());
            if($onlyOneChild === false || $onlyOneChild !== $item->getId()) {
                $movable = true;
            }
        }
        return $movable;
    }
 
   function setNewParent($item_id, $new_parent_id, $ordering) {
        $item =& $this->getItemFromDb($item_id);
        $dao =& $this->_getItemDao();
        return $item && $this->isMoveable($item) && $dao->setNewParent($item_id, $new_parent_id, $ordering);
    }
    
    function breathFirst($item_id, $callback, $params) {
        $dao =& $this->_getItemDao();
        $parents = array($item_id);
        do {
            $dar = $dao->searchByParentsId($parents);
            if ($dar && !$dar->isError()) {
                $parents = array();
                while ($dar->valid()) {
                    $row = $dar->current();
                    call_user_func_array($callback, array($row, $params));
                    if (PLUGIN_DOCMAN_ITEM_TYPE_FOLDER == $row['item_type']) {
                        $parents[] = $row['item_id'];
                    }
                    $dar->next();
                }
            }
        } while (count($parents) > 0);
    }
    function &getRoot($group_id) {
        if(!isset($this->rootItems[$group_id])) {
            $dao =& $this->_getItemDao();
            $id = $dao->searchRootIdForGroupId($group_id);
            $this->rootItems[$group_id] = $this->getItemFromDb($id);
        }
        return $this->rootItems[$group_id];
    }
    function isRoot(&$item) {
        $root = $this->getRoot($item->getGroupId());
        return $item->getId() == $root->getId();
    }
    function &createRoot($group_id, $title) {
        $dao =& $this->_getItemDao();
        $root =& new Docman_Folder();
        $root->setGroupId($group_id);
        $root->setTitle($title);
        return $dao->createFromRow($root->toRow());
    }

    function rawCreate($item) {
        $dao = $this->_getItemDao();
        return $dao->createFromRow($item->toRow());
    }

    function cloneItems($srcGroupId, $dstGroupId, $user, $metadataMapping, $ugroupsMapping, $dataRoot, $srcItemId = 0, $dstItemId = 0, $ordering = null) {
        $itemFactory = new Docman_ItemFactory($srcGroupId);
        $params = array('ignore_collapse' => true,
                        'user'            => $user);
        $itemTree = $itemFactory->getItemTree($srcItemId, $params);
        
        if ($itemTree) {
            $rank = null;
            if($ordering !== null) {
                $dao  =& $this->_getItemDao();
                $rank = $dao->_changeSiblingRanking($dstItemId, $ordering);
            }

            $cloneItemsVisitor = new Docman_CloneItemsVisitor($dstGroupId);
            $visitorParams = array('parentId' => $dstItemId,
                               'user' => $user,
                               'metadataMapping' => $metadataMapping,
                               'ugroupsMapping'  => $ugroupsMapping,
                               'data_root' => $dataRoot,
                               'newRank' => $rank,
                               'srcRootId' => $srcItemId);
            $itemTree->accept($cloneItemsVisitor, $visitorParams);
        }
    }
    
    function preferrencesExist($group_id, $user_id) {
        $dao =& $this->_getItemDao();
        $dar =& $dao->searchExpandedUserPrefs($group_id, $user_id);
        return $dar->valid();
    }

    function setCopyPreference($item) {
        user_set_preference(PLUGIN_DOCMAN_PREF.'_item_copy',
                            $item->getId());
    }

    function getCopyPreference($user) {
        if(!isset($this->copiedItem[$user->getId()])) {
            $this->copiedItem[$user->getId()] = user_get_preference(PLUGIN_DOCMAN_PREF.'_item_copy');
        }
        return $this->copiedItem[$user->getId()];
    }

    function delCopyPreference() {
        user_del_preference(PLUGIN_DOCMAN_PREF.'_item_copy');
    }

    function getCurrentWikiVersion($item) {
        $version = null;
        $dao =& $this->_getItemDao();
        if($this->getItemTypeForItem($item) == PLUGIN_DOCMAN_ITEM_TYPE_WIKI) {
            $version = $dao->searchCurrentWikiVersion($item->getGroupId(), $item->getPagename());
        }
        return $version;
    }

}

?>
