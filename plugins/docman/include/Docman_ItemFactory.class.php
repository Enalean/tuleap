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
require_once('Docman_BuildItemMappingVisitor.class.php');

/**
 * 
 */
class Docman_ItemFactory {
    var $rootItems;
    var $onlyOneChildForRoot;
    var $copiedItem;
    var $groupId;

    private static $instance;
    
    function Docman_ItemFactory($groupId=null) {
        // Cache highly used info
        $this->rootItems[] = array();
        $this->onlyOneChildForRoot[] = array();
        $this->copiedItem = array();

        // Parameter
        $this->groupId = $groupId;
    }

    /**
     * Return a single instance of Docman_ItemFactory per group.
     * 
     * This is useful when you need to cache information across method calls
     * 
     * @param Integer $group_id Project id
     * 
     * @return Docman_ItemFactory
     */
    public static function instance($group_id) {
        if(!isset(self::$instance[$group_id])) {
            self::$instance[$group_id] = new Docman_ItemFactory($group_id);
        }
        return self::$instance[$group_id];
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
	
    /**   
    * This method checks if the item is a wiki page. if true it return its id in wiki, else, it returns null.   
    *   
    * @param int $group_id project id   
    * @param int $item_id  docman item id   
    *   
    * @return wiki page id or null if the page is not yet created in wiki.   
    */   
    function getIdInWikiOfWikiPageItem($pagename, $group_id){   
        // Get wiki id of the pagename   
        $wiki_dao =& $this->_getWikiDao();   
        $id_in_wiki = $wiki_dao->retrieveWikiPageId($pagename, $group_id);   
        if ($id_in_wiki != null){   
            return $id_in_wiki;   
        }   
        else {   
            return null;   
        }   
    } 

    function &getItemFromDb($id, $params = array()) {
        $_id = (int) $id;
        $dao =& $this->_getItemDao();
        $dar = $dao->searchById($id, $params);

        $item = null;
        if(!$dar->isError() && $dar->valid()) {
            $row =& $dar->current();
            $item =& Docman_ItemFactory::getItemFromRow($row);
        }
        return $item;
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
     * Preload item perms from a item result set
     */
    function preloadItemPerms($dar, $user, $groupId) {
        // Preload perms
        $objectsIds = array();
        $dar->rewind();
        while($dar->valid()) {
            $row = $dar->current();
            $objectsIds[] = $row['item_id'];
            $dar->next();
        }
        $dar->rewind();

        $dPm = Docman_PermissionsManager::instance($groupId);
        $dPm->retreiveReadPermissionsForItems($objectsIds, $user);
    }

    /**
     * Build a subtree from the given item id.
     *
     * Build the list in depth, level per level, from root to leaves.
     * 
     * @param Docman_Item $rootItem
     * @param User $user
     * @param boolean $ignorePerms
     * @param boolean $expandAll
     * @param boolean $ignoreObsolete
     * @return Docman_Item
     */
    function &getItemSubTree(&$rootItem, &$user, $ignorePerms=false, $expandAll=false, $ignoreObsolete=true) {
        // {{1}} Exclude collapsed items
        $expandedFolders = array();
        if(!$expandAll) {
            $fld = $this->_getExpandedUserPrefs($rootItem->getId(), user_getid());
            foreach($fld as $v) {
                $expandedFolders[$v] = true;
            }
        }

        $searchItemsParams = array('ignore_obsolete' => $ignoreObsolete);

        //
        // Treatment
        //
        $dao =& $this->_getItemDao();
        $dPm = Docman_PermissionsManager::instance($rootItem->getGroupId());
        
        $itemList = array($rootItem->getId() => &$rootItem);
        $parentIds = array($rootItem->getId());
        do {
            // Fetch all children for the given level.
            $dar = $dao->searchChildren($parentIds, $searchItemsParams);
            $parentIds = array();
            $itemIds = array();
            $itemRows = array();
            if($dar && !$dar->isError()) {
                $dar->rewind();
                while($dar->valid()) {
                    $row = $dar->current();
                    $itemRows[$row['item_id']] = $row;
                    $itemIds[] = $row['item_id'];
                    if($row['item_type'] == PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
                        && ($expandAll || isset($expandedFolders[$row['item_id']]))) {
                        $parentIds[$row['item_id']] = $row['item_id'];
                    }
                    $dar->next();
                }
                
                // Fetch all the permissions at the same time
                $dPm->retreiveReadPermissionsForItems($itemIds, $user);

                // Build hierarchy: only keep displayable items
                foreach($itemIds as $id) {
                    if($ignorePerms || $dPm->userCanRead($user, $id)) {
                        $itemList[$id] =& $this->getItemFromRow($itemRows[$id]);
                        $itemList[$itemList[$id]->getParentId()]->addItem($itemList[$id]);
                    } else {
                       unset($parentIds[$id]);
                    }
                }
            }
        } while(count($parentIds) > 0);

        return $itemList[$rootItem->getId()];
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
        $parentItem = $this->getItemFromDb($parentId);
        $dPm = Docman_PermissionsManager::instance($parentItem->getGroupId());
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
            $itemIds   = array();
            $itemRows = array();
            if($dar && !$dar->isError()) {
                $dar->rewind();
                while($dar->valid()) {
                    $row = $dar->current();
                    $itemRows[$row['item_id']] = $row;
                    $itemIds[] = $row['item_id'];
                    $parentIds[$row['item_id']] = $row['item_id'];
                    $dar->next();
                }

                // Fetch all the permissions at the same time
                $dPm->retreiveReadPermissionsForItems($itemIds, $user);

                // Build hierarchy: only keep displayable items
                foreach($itemIds as $id) {
                    if($dPm->userCanRead($user, $id)) {
                        $folderList[$id] =& $this->getItemFromRow($itemRows[$id]);
                        // Update path
                        $pathIdArray[$id] = array_merge($pathIdArray[$folderList[$id]->getParentId()], array($id));
                        $pathTitleArray[$id] = array_merge($pathTitleArray[$folderList[$id]->getParentId()], array($folderList[$id]->getTitle()));
                    } else {
                        unset($parentIds[$id]);
                    }
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
        $itemArray = array();
        if(isset($params['obsolete_only']) && $params['obsolete_only']) {
            $dar = $dao->searchObsoleteByGroupId($this->groupId);
        } else {
            $dar = $dao->searchByGroupId($this->groupId, $filter, $searchItemsParams);
        }

        $nbItemsFound = 0;
        if($dar && !$dar->isError()) {
            $this->preloadItemPerms($dar, $user, $this->groupId);
            $dar->rewind();
            while($dar->valid()) {
                $row = $dar->current();
                // The document is not is one of the allowed subfolder so we
                // can delete it. As a side effect decrease the number of
                // document found.
                if($dPm->userCanRead($user, $row['item_id']) && isset($folderList[$row['parent_id']])) {
                    if($nbItemsFound >= $start && $nbItemsFound < $end) {
                        $itemArray[$row['item_id']] =& $this->getItemFromRow($row);

                        // Append Path
                        $itemArray[$row['item_id']]->setPathTitle($pathTitleArray[$row['parent_id']]);
                        $itemArray[$row['item_id']]->setPathId($pathIdArray[$row['parent_id']]);

                        // Append metadata
                        if($ci !== null) {
                            $ci->rewind();
                            while($ci->valid()) {
                                $c = $ci->current();
                                if($c->md !== null && $mdFactory->isRealMetadata($c->md->getLabel())) {
                                    $mdFactory->addMetadataValueToItem($itemArray[$row['item_id']], $c->md);
                                }
                                $ci->next();
                            }
                        }
                    }
                    $nbItemsFound++;
                }
                $dar->next();
            }
        }

        $docIter =& new ArrayIterator($itemArray);
        return $docIter;
    }

    /**
     * Build a tree from with the list of items
     *
     * @return Docman_Item
     */
    function &getItemTree(&$rootItem, &$user, $ignorePerms=false, $expandAll=false, $ignoreObsolete=true) {
        return $this->getItemSubTree($rootItem, $user, $ignorePerms, $expandAll, $ignoreObsolete);
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
     *
     */
    function findByTitle($user, $title, $groupId) {
        $ia = array();

        $dao =& $this->_getItemDao();
        $dPm = Docman_PermissionsManager::instance($groupId);
        $dar = $dao->searchByTitle($title);
        $dar->rewind();
        while($dar->valid()) {
            $row = $dar->current();

            $item = $this->getItemFromRow($row);
            if($dPm->userCanRead($user, $item->getId())) {
                $parentItem = $this->getItemFromDb($item->getParentId());
                if($dPm->userCanRead($user, $parentItem->getId())) {
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

    var $wikidao;
    function &_getWikiDao() {
        require_once('common/dao/WikiDao.class.php');
        if (!$this->wikidao) {
            $this->wikidao =& new WikiDao(CodexDataAccess::instance());
        }
        return $this->wikidao;
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
    
    /**
    * Walk through a item hierarchy and for each subitem apply callback method
    * in parameter.
    *
    * The callback method (or function) will be applied for each sub-item of
    * $item_id with following paramters:
    * - A plugin_docman_item table row that correspond to the child node.
    * - $params
    *
    * @see call_user_func_array for details on $callback forms.
    *
    * @param int   $item_id  Id of the parent item.
    * @param mixed $callback Callback function or method.
    * @param array $params   Parameters for the callback function
    * @return void
    */
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

    /**
     * Returns an item tree build from leaves to root ("bottom -> top").
     *
     * @param  Array of items.
     * @return Item_Folder A sub tree or null if root node was not found.
     */
    function &getItemTreeFromLeaves($itemArray, $user) {
        $null = null;
        if(is_array($itemArray)) {
            foreach($itemArray as $item) {
                $itemList[$item->getId()] = $item;
                $orphans[$item->getId()] = $item->getId();
                $itemIds[] = $item->getId();
            }
        } else {
            return $null;
        }

        // Check permissions on submitted item array
        $dpm =& Docman_PermissionsManager::instance($this->groupId);
        $dpm->retreiveReadPermissionsForItems($itemIds, $user);
        foreach($itemArray as $item) {
             if(!$dPm->userCanRead($user, $item->getId())) {
                 unset($itemList[$item->getId()]);
                 unset($orphans[$item->getId()]);
             }
        }

        // Now, here we go
        $paths = array();
        $dao =& $this->_getItemDao();
        $rootId = false;
        do {
            // Try to build the connections between childrens and parents in itemList
            $wantedItems = array();
            $rootInfo = $this->connectOrphansToParents($itemList, $orphans, $wantedItems);
            if($rootInfo !== false) {
                $rootId = $rootInfo;
            }

            // If some items are missing, look for them in the DB.
            if(is_array($wantedItems) && count($wantedItems) > 0) {
                $dar = $dao->searchByIdList($wantedItems);
                if($dar && !$dar->isError()) {
                    $this->preloadItemPerms($dar, $user, $this->groupId);
                    while ($dar->valid()) {
                        $row = $dar->current();
                        $item = $this->getItemFromRow($row);
                        if($dPm->userCanRead($user, $item->getId())) {
                            $itemList[$item->getId()] = $item;
                            $orphans[$item->getId()] = $item->getId();
                        } else {
                            $itemList[$item->getId()] = false;
                        }
                        $dar->next();
                    }
                }
            }
        } while (count($wantedItems) > 0);

        if($rootId !== false) {
            return $itemList[$rootId];
        } else {
            return $null;
        }
    }

    /**
     * Build the connexions between the different nodes in item list and
     * identify the missing nodes.
     *
     * This method iterates on $orphans list that indicates the item in
     * $itemList that are not yet connected to their father node.
     * The function returns the nodes in $itemList that are still orphans and
     * the list of item Ids needed to continue to build the tree
     * ($wantedItems).
     *
     * See UnitTests
     * @param $itemList    Array of Docma_Item.
     * @param $orphan      Hashmap of item ids. Items (in ItemList) without
     *                     parent node
     * @param $wantedItems Items needed to continue to build the tree.
     * @return Integer Id of root item if found, false otherwise.
     */
    function connectOrphansToParents(&$itemList, &$orphans, &$wantedItems) {
        $rootId = false;
        foreach($orphans as $itemId) {
            // Check if orphan belong to the item list and is available.
            // As orphans should always be parts of $itemList, it means that
            // this orphan is not readable by user.
            if(isset($itemList[$itemId]) && $itemList[$itemId] !== false) {
                // Check if current item parents is in the list
                $pid = $itemList[$itemId]->getParentId();
                if($pid != 0) {
                    if(isset($itemList[$pid])) {
                        if($itemList[$pid] !== false) {
                            $itemList[$pid]->addItem($itemList[$itemId]);
                            unset($orphans[$itemId]);
                        }
                    } else {
                        if(!isset($orphans[$itemId])) {
                            $orphans[$itemId] = $itemId;
                        }
                        $wantedItems[] = $pid;
                    }
                } else {
                    $rootId = $itemId;
                    unset($orphans[$itemId]);
                }
            }
        }
        return $rootId;
    }

    /**
     * Returns a hashmap with the mapping between items in $item tree and items
     * that belongs to this group.
     */
    function getItemMapping($item) {
        $v =& new Docman_BuildItemMappingVisitor($this->groupId);
        $item->accept($v);
        return $v->getItemMapping();
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

    /**
     * Copy a subtree.
     */
    function cloneItems($srcGroupId, $dstGroupId, $user, $metadataMapping, $ugroupsMapping, $dataRoot, $srcItemId = 0, $dstItemId = 0, $ordering = null) {
        $itemMapping = array();

        $itemFactory = new Docman_ItemFactory($srcGroupId);
        if($srcItemId == 0) {
            $srcItem = $this->getRoot($srcGroupId);
        } else {
            $srcItem = $this->getItemFromDb($srcItemId);
        }
        $itemTree = $itemFactory->getItemTree($srcItem, $user, false, true);
        
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
            $itemMapping = $cloneItemsVisitor->getItemMapping();
        }
        return $itemMapping;
    }
    
    function preferrencesExist($group_id, $user_id) {
        $dao =& $this->_getItemDao();
        $dar = $dao->searchExpandedUserPrefs($group_id, $user_id);
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
        $wiki_dao =& $this->_getWikiDao();
        if($this->getItemTypeForItem($item) == PLUGIN_DOCMAN_ITEM_TYPE_WIKI) {
            $version = $wiki_dao->searchCurrentWikiVersion($item->getGroupId(), $item->getPagename());
        }
        return $version;
    }

}

?>
