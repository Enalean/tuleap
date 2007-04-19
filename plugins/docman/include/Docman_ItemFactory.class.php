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
require_once('DocmanConstants.class.php');
//require_once('Docman.class.php');
require_once('Docman_Item.class.php');
require_once('Docman_ItemDao.class.php');
require_once('Docman_Folder.class.php');
require_once('Docman_File.class.php');
require_once('Docman_Link.class.php');
require_once('Docman_EmbeddedFile.class.php');
require_once('Docman_Wiki.class.php');
require_once('Docman_Empty.class.php');
require_once('Docman_Version.class.php');
require_once('Docman_ItemBo.class.php');
require_once('Docman_CloneItemsVisitor.class.php');

/**
 * 
 */
class Docman_ItemFactory {
    var $rootItems;
    var $onlyOneChildForRoot;
    var $copiedItem;

    function Docman_ItemFactory() {
        // Cache highly used info
        $this->rootItems[] = array();
        $this->onlyOneChildForRoot[] = array();
        $this->copiedItem = array();
    }

    function &instance($group_id) {
        static $_instance_Docman_ItemFactory;
        if(!isset($_instance_Docman_ItemFactory[$group_id])) {
            $_instance_Docman_ItemFactory[$group_id] = new Docman_ItemFactory();
        }
        return $_instance_Docman_ItemFactory[$group_id];
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
    function create($row, $ordering) {
        $dao =& $this->_getItemDao();
        $id = $dao->createFromRow($row);
        if ($id) {
            $this->setNewParent($id, $row['parent_id'], $ordering);
        }
        return $id;
    }

    function isItemTheOnlyChildOfRoot($item) {
        if(!isset($this->onlyOneChildForRoot[$item->getGroupId()])) {
            $dao = $this->_getItemDao();
            $this->onlyOneChildForRoot[$item->getGroupId()] = $dao->isItemTheOnlyChildOfRoot($item->getGroupId(), $item->getId());
        }
        return $this->onlyOneChildForRoot[$item->getGroupId()];
    }

    function isMoveable(&$item) {
        return $item->getParentId() != 0 && !$this->isItemTheOnlyChildOfRoot($item);
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
        $itemBo = new Docman_ItemBo($srcGroupId);
        $params = array('ignore_collapse' => true,
                        'user'            => $user);
        $itemTree = $itemBo->getItemTree($srcItemId, $params);
        
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
