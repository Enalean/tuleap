<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
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
 * Walk through an item tree (top -> bottom) and for each node:
 * - build the list of its children.
 * - look for those children in the destination project (constructor
 *   paramerter).
 * - Compare the list of chilren found with the DB lookup with the one of the
 *   current item.
 *   + if it matches, fill the tree mapping hashmap and continue the
 *     walk-through with the child.
 *   + if it doesn't match, don't recurse on this child.
 *
 * It takes into account:
 * - permissions
 * - Folder that contains several items with the same title.
 *
 * The item comparison is one only on the title.
 *
 * Once the journey is complete, the full mapping is available with
 * getItemMapping() method.
 *
 * For instance:
 *      ______________________________
 *     _|________                    _|_________
 * 140 Project doc                35 Project doc
 * |-- 150 Folder 1               |-- 36 Folder 1
 * |   |-- 112 Folder 1.1         |   |-- 37 Folder 1.1
 * |   |   `-- 113 Folder 1.1.1   |   |   `-- 38 Folder 1.1.1
 * |   |       `-- *              |   |       `-- *
 * |   `-- 115 Folder 1.2         |   `-- 39 Toto
 * |       `-- *                  |       `-- *
 * `-- 135 Folder 2               `-- 40 Folder 2
 *     `-- *                          `-- *
 *
 * Gives:
 * 140 -> 35
 * 150 -> 36
 * 112 -> 37
 * 113 -> 38
 * 135 -> 40
 */
class Docman_BuildItemMappingVisitor
{
    public $groupId;

    public function __construct($groupId)
    {
        $this->groupId = $groupId;
        $this->itemMapping = array();
        $this->dao = null;
    }

    /**
     * Build itemMapping for the given folder and recurse.
     *
     * This is the only visit method the should be called as we deal with
     * folder children for comparison.
     */
    public function visitFolder($item, $params)
    {
        $nodesToInspect = array();

        // Initial case of the recursion
        // If there is not yet a mapping between the current item id and his
        // equivalent in the destination project, find it.
        // If not found, stop the job.
        if (!isset($this->itemMapping[$item->getId()])) {
            $res = $this->findMatchingItem($item);
            if ($res !== true) {
                return false;
            }
        }

        // Build the mapping
        $this->findMatchingChildren($item);

        // Recurse on children
        $items = $item->getAllItems();
        if ($items && $items->size()) {
            $iter = $items->iterator();
            $iter->rewind();
            while ($iter->valid()) {
                $child = $iter->current();
                // We only need to visit child that have equivalent in the
                // destination project.
                if (isset($this->itemMapping[$child->getId()])) {
                    $child->accept($this, $params);
                }
                $iter->next();
            }
        }
    }

    /**
     * Find in the destination project an item that match the one in parameter.
     * This works only for root item.
     */
    public function findMatchingItem($item)
    {
        if ($item->getParentId() == 0) {
            $dar = $this->searchMatchingItem($item, 0);
            if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
                $row = $dar->getRow();
                if ($this->checkItemPermissions($row['item_id'])) {
                    $this->itemMapping[$item->getId()] = $row['item_id'];
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Search $item children in the destination project and check if the
     * resulting list of item is equivalent to the children of $item.
     */
    public function findMatchingChildren($item)
    {
        if (isset($this->itemMapping[$item->getId()])) {
            $parentId = $this->itemMapping[$item->getId()];
            $dar = $this->searchMatchingChildren($item, $parentId);
            if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
                // When there are several items that match, we need to build a fake node
                $node = new Docman_Folder();
                while ($row = $dar->getRow()) {
                    $itemFactory = $this->getItemFactory();
                    $i = $itemFactory->getItemFromRow($row);
                    if ($i !== null && $this->checkItemPermissions($row['item_id'])) {
                        $node->addItem($i);
                    }
                    unset($i);
                }
                $this->compareFolderChildren($item, $node);
            }
        }
    }

    /**
     * Compare content of 2 folders.
     * Takes 2 folders and compare there children side by side. If an item
     * match, it is added into global $itemMapping variable and is returned as
     * a node to inspect.
     * When a child of the source item is not found in the destination item,
     * the search stops, the mapping is (obviously) not done and the source
     * child and its future brothers are discared.
     */
    public function compareFolderChildren($srcItem, $dstItem)
    {
        $nodesToInspect = array();
        $srcList = $srcItem->getAllItems();
        $dstList = $dstItem->getAllItems();
        if (
            $srcList && $srcList->size() &&
            $dstList && $dstList->size()
        ) {
            $srcIter = $srcList->iterator();
            $dstIter = $dstList->iterator();
            $srcIter->rewind();
            $dstIter->rewind();
            $identical = true;
            while ($srcIter->valid() && $dstIter->valid() && $identical) {
                $srcChild = $srcIter->current();
                $dstChild = $dstIter->current();
                if ($this->compareItem($srcChild, $dstChild)) {
                    $this->itemMapping[$srcChild->getId()] = $dstChild->getId();
                    $nodesToInspect[$srcChild->getId()] = true;
                } else {
                    $identical = false;
                }
                $srcIter->next();
                $dstIter->next();
            }
        }
        return $nodesToInspect;
    }

    /**
     * Compare 2 items.
     */
    public function compareItem($srcItem, $dstItem)
    {
        return ($srcItem->getTitle() == $dstItem->getTitle());
    }

    /**
     * Check if item can be read by current user
     */
    public function checkItemPermissions($itemId)
    {
        $user = $this->getCurrentUser();
        $dPm  = $this->getPermissionsManager($this->groupId);
        return $dPm->userCanRead($user, $itemId);
    }

    /**
     * Search if there is an equivalent of $item in $parentId.
     */
    public function searchMatchingItem($item, $parentId)
    {
        $dao = $this->getItemDao();
        $itemTitles = $this->getTitleStrings($item);
        $dar = $dao->searchByTitle($itemTitles, $this->groupId, $parentId);
        return $dar;
    }

    /**
     * Build the list of $item children and search for matching items in
     * $parentId
     */
    public function searchMatchingChildren($item, $parentId)
    {
        $dao = $this->getItemDao();
        $itemTitles = $this->getChildrenTitles($item);
        $dar = $dao->searchByTitle($itemTitles, $this->groupId, $parentId);
        return $dar;
    }

    /**
     * Build the list of title that we will look for.
     */
    public function getChildrenTitles($item)
    {
        $title = array();
        $childList = $item->getAllItems();
        if ($childList && $childList->size()) {
            $childIter = $childList->iterator();
            $childIter->rewind();
            while ($childIter->valid()) {
                $i = $childIter->current();
                $title = array_merge($title, $this->getTitleStrings($i));
                $childIter->next();
            }
        }
        return $title;
    }

    /**
     * Due to the mess between usage of 'roottitle_lbl_key' and translation for
     * item title, we need to take this into account in search. So when we find
     * an item with 'roottitle_lbl_key' as title, we need to look for the key
     * and all possible translations.
     */
    public function getTitleStrings($item)
    {
        $title = array();
        if ($item->titlekey != null) {
            // Hardcoded for all languages. There is no simple and testable
            // ways to do it
            $title[] = 'roottitle_lbl_key';
            $title[] = 'Project Documentation';
            $title[] = 'Documentation du projet';
        } else {
            $title[] = $item->getTitle();
        }
        return $title;
    }

    public function visitDocument($item, $params)
    {
    }

    public function visitWiki($item, $params)
    {
    }

    public function visitLink($item, $params)
    {
    }

    public function visitFile($item, $params)
    {
    }

    public function visitEmbeddedFile($item, $params)
    {
    }

    public function visitEmpty($item, $params)
    {
    }

    public function getItemMapping()
    {
        return $this->itemMapping;
    }

    // Object accessors
    protected function getItemDao()
    {
        if ($this->dao === null) {
            $this->dao = new Docman_ItemDao(CodendiDataAccess::instance());
        }
        return $this->dao;
    }

    protected function getPermissionsManager($groupId)
    {
        $dPm = Docman_PermissionsManager::instance($groupId);
        return $dPm;
    }

    protected function getCurrentUser()
    {
        $um   = UserManager::instance();
        $user = $um->getCurrentUser();
        return $user;
    }

    private function getItemFactory()
    {
        $if = new Docman_ItemFactory();
        return $if;
    }
}
