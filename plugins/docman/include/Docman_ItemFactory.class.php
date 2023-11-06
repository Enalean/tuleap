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

use Tuleap\Docman\CannotInstantiateItemWeHaveJustCreatedInDBException;
use Tuleap\Docman\DeleteFailedException;
use Tuleap\Docman\DestinationCloneItem;
use Tuleap\Docman\Notifications\UgroupsToNotifyDao;
use Tuleap\Docman\Notifications\UsersToNotifyDao;
use Tuleap\PHPWiki\WikiPage;

class Docman_ItemFactory
{
    public array $rootItems;
    public array $onlyOneChildForRoot;
    public array $copiedItem;
    public mixed $groupId;
    private array $cutItem;

    private static array $instance;

    public function __construct($groupId = null)
    {
        // Cache highly used info
        $this->rootItems[]           = [];
        $this->onlyOneChildForRoot[] = [];
        $this->copiedItem            = [];
        $this->cutItem               = [];

        // Parameter
        $this->groupId = $groupId;
    }

    /**
     * Return a single instance of Docman_ItemFactory per group.
     *
     * This is useful when you need to cache information across method calls
     *
     * @param int $group_id Project id
     *
     * @return Docman_ItemFactory
     */
    public static function instance($group_id)
    {
        if (! isset(self::$instance[$group_id])) {
            self::$instance[$group_id] = new Docman_ItemFactory($group_id);
        }
        return self::$instance[$group_id];
    }

    /**
     * Return a single instance of Docman_ItemFactory per group.
     *
     * This is useful when you need to cache information across method calls
     *
     * @param int $group_id Project id
     *
     * @return Docman_ItemFactory
     */
    public static function setInstance($group_id, $instance)
    {
        self::$instance[$group_id] = $instance;
    }

    /**
     * Return a single instance of Docman_ItemFactory per group.
     *
     * This is useful when you need to cache information across method calls
     *
     * @param int $group_id Project id
     *
     * @return Docman_ItemFactory
     */
    public static function clearInstance($group_id)
    {
        self::$instance[$group_id] = null;
    }

    public function setGroupId($id)
    {
        $this->groupId = $id;
    }

    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @return Docman_Item|null|void
     */
    public function getItemFromRow(array $row)
    {
        $item = null;
        switch ($row['item_type']) {
            case PLUGIN_DOCMAN_ITEM_TYPE_FOLDER:
                $item = new Docman_Folder($row);
                break;
            case PLUGIN_DOCMAN_ITEM_TYPE_FILE:
                $item = new Docman_File($row);
                break;
            case PLUGIN_DOCMAN_ITEM_TYPE_LINK:
                $item = new Docman_Link($row);
                if (isset($row['link_version_id'])) {
                    $item->setCurrentVersion(
                        new Docman_LinkVersion(
                            [
                                'id'        => $row['link_version_id'],
                                'user_id'   => $row['link_version_user_id'],
                                'item_id'   => $item->getId(),
                                'number'    => $row['link_version_number'],
                                'label'     => $row['link_version_label'],
                                'changelog' => $row['link_version_changelog'],
                                'date'      => $row['link_version_date'],
                                'link_url'      => $row['link_version_link_url'],
                            ]
                        )
                    );
                }
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
                $version = [
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
                    'path'      => $row['version_path'],
                ];
                $item->setCurrentVersion(new Docman_Version($version));
            }
        }
        return $item;
    }

    public function getItemTypeAsText($itemTypeId)
    {
        switch ($itemTypeId) {
            case PLUGIN_DOCMAN_ITEM_TYPE_FOLDER:
                return dgettext('tuleap-docman', 'Folder');
            case PLUGIN_DOCMAN_ITEM_TYPE_FILE:
                return dgettext('tuleap-docman', 'File');
            case PLUGIN_DOCMAN_ITEM_TYPE_LINK:
                return dgettext('tuleap-docman', 'Link');
            case PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE:
                return dgettext('tuleap-docman', 'Embedded file');
            case PLUGIN_DOCMAN_ITEM_TYPE_WIKI:
                return dgettext('tuleap-docman', 'Wiki page');
            case PLUGIN_DOCMAN_ITEM_TYPE_EMPTY:
                return dgettext('tuleap-docman', 'Empty document');
            default:
                return $GLOBALS['Language']->getText('include_html', 'unknown_value');
        }
    }

    public function getItemTypeForItem(&$item)
    {
        $type = false;
        switch (strtolower($item::class)) {
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
    * @return int|null wiki page id or null if the page is not yet created in wiki.
    */
    public function getIdInWikiOfWikiPageItem($pagename, $group_id)
    {
        $wiki_page = $this->getWikiPage($group_id, $pagename);

        if ($wiki_page->exist()) {
            return $wiki_page->getId();
        } else {
            return null;
        }
    }

    /**
    * This looks for possible references of a wiki page from docman
    *
    * @param string $wiki_page
    * @param string $group_id
    *
    * @return Docman_Wiki[] items that reference the same given wiki page.
    */
    public function getWikiPageReferencers($wiki_page, $group_id)
    {
        $items    = [];
        $item_dao = $this->_getItemDao();
        if ($item_dao->isWikiPageReferenced($wiki_page, $group_id)) {
            $items_ids = $item_dao->getItemIdByWikiPageAndGroupId($wiki_page, $group_id);
            if (is_array($items_ids)) {
                foreach ($items_ids as $key => $id) {
                    $item = $this->getItemFromDb($id);
                    if ($item !== null) {
                        assert($item instanceof Docman_Wiki);
                        $items[] = $item;
                    }
                }
            } else {
                $item = $this->getItemFromDb($items_ids);
                if ($item !== null) {
                    assert($item instanceof Docman_Wiki);
                    $items[] = $item;
                }
            }
        }
        return $items;
    }

    /**
    * This deletes an existant wiki page and all its stored data and infos from codendi db.
    *
    * @param string $wiki_page_name name of the wiki page
    * @param int $group_id project id.
    *
    * @return true if there was no error.
    */
    public function deleteWikiPage($wiki_page_name, $group_id)
    {
        $wiki_page = $this->getWikiPage($group_id, $wiki_page_name);

        return $wiki_page->delete();
    }

    private function getWikiPage($project_id, $pagename)
    {
        $wiki_page = null;

        $event_manager = EventManager::instance();
        $event_manager->processEvent(
            PLUGIN_DOCMAN_EVENT_GET_PHPWIKI_PAGE,
            [
                'phpwiki_page_name' => $pagename,
                'project_id'        => $project_id,
                'phpwiki_page'      => $wiki_page,
            ]
        );

        if ($wiki_page === null) {
            $wiki_page = new WikiPage($project_id, $pagename);
        }

        return $wiki_page;
    }

    /**
     * @return Docman_Item | null
     */
    public function getItemFromDb($id, $params = [])
    {
        $dao = $this->_getItemDao();
        $dar = $dao->searchById($id, $params);

        $item = null;
        if (! $dar->isError() && $dar->valid()) {
            $row  = $dar->current();
            $item = $this->getItemFromRow($row);
        }
        return $item;
    }

    public function getChildrenFromParent($item)
    {
        $dao = $this->_getItemDao();

        $itemArray = [];

        $dar = $dao->searchByParentsId([$item->getId()]);
        if ($dar && ! $dar->isError()) {
            while ($dar->valid()) {
                $row = $dar->current();

                $itemArray[] = $this->getItemFromRow($row);

                $dar->next();
            }
        }

        $iIter = new ArrayIterator($itemArray);
        return $iIter;
    }

    public function getAllChildrenFromParent(Docman_Item $item)
    {
        $children      = [];
        $item_iterator = $this->getChildrenFromParent($item);

        foreach ($item_iterator as $child) {
            $item_type = $this->getItemTypeForItem($child);

            if ($item_type === PLUGIN_DOCMAN_ITEM_TYPE_FILE || $item_type === PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE) {
                $docman_version_factory = new Docman_VersionFactory();
                $item_current_version   = $docman_version_factory->getCurrentVersionForItem($child);

                $child->setCurrentVersion($item_current_version);
            }

            $children[] = $child;

            if ($item_type === PLUGIN_DOCMAN_ITEM_TYPE_FOLDER) {
                $children = array_merge($children, $this->getAllChildrenFromParent($child));
            }
        }

        return $children;
    }

    /**
     * Retreive list of collapsed items for given user
     *
     * This function retreive collapsed folders from user preferences
     *
     * @param $parentId Id of the "current" root node (cannot be excluded).
     * @param int $userId Id of current user.
     * @return Array List of items to exclude for a search
     **/
    private function _getExpandedUserPrefs($parentId, $userId)
    {
        $collapsedItems = [];
        // Retreive the list of collapsed folders in prefs
        $dao = $this->_getItemDao();
        $dar = $dao->searchExpandedUserPrefs(
            $this->groupId,
            $userId
        );
        while ($dar->valid()) {
            $row = $dar->current();
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
    public function preloadItemPerms($dar, $user, $groupId)
    {
        // Preload perms
        $objectsIds = [];
        $dar->rewind();
        while ($dar->valid()) {
            $row          = $dar->current();
            $objectsIds[] = $row['item_id'];
            $dar->next();
        }
        $dar->rewind();

        $dPm = Docman_PermissionsManager::instance($groupId);
        $dPm->retreiveReadPermissionsForItems($objectsIds, $user);
    }

    /**
     * Check if a given item is into the subtree of another given item or not.
     *
     * @param int $childId Id of the potential child.
     * @param int $parentId Id of the potential parent.
     *
     * @return bool
     */
    public function isInSubTree($childId, $parentId)
    {
        $child = $this->getItemFromDb($childId);
        if ($child === null || $this->isRoot($child)) {
            return false;
        }
        $directParentId = $child->getParentId();
        if ($parentId == $directParentId) {
            return true;
        } else {
            return $this->isInSubTree($directParentId, $parentId);
        }
    }

    /**
     * Give the list of parents of an item
     *
     * @param int $childId Id of the child.
     *
     * @return Array
     */
    public function getParents($childId)
    {
        $child = $this->getItemFromDb($childId);
        if ($child === null || $this->isRoot($child)) {
            return [];
        }
        $directParentId           = $child->getParentId();
        $parents                  = $this->getParents($directParentId);
        $parents[$directParentId] = true;
        return $parents;
    }

    /**
     * Build a subtree from the given item id.
     *
     * Build the list in depth, level per level, from root to leaves.
     *
     * @param Docman_Item $rootItem
     * @param PFUser $user
     * @param bool $ignorePerms
     * @param bool $expandAll
     * @param bool $ignoreObsolete
     * @return Docman_Item
     */
    public function &getItemSubTree(&$rootItem, &$user, $ignorePerms = false, $expandAll = false, $ignoreObsolete = true)
    {
        // {{1}} Exclude collapsed items
        $expandedFolders = [];
        if (! $expandAll) {
            $fld = $this->_getExpandedUserPrefs($rootItem->getId(), $user->getId());
            foreach ($fld as $v) {
                $expandedFolders[$v] = true;
            }
        }

        $searchItemsParams = ['ignore_obsolete' => $ignoreObsolete];

        // Treatment
        $dao = $this->_getItemDao();
        $dPm = Docman_PermissionsManager::instance($rootItem->getGroupId());

        $itemList  = [$rootItem->getId() => &$rootItem];
        $parentIds = [$rootItem->getId()];
        do {
            // Fetch all children for the given level.
            $dar       = $dao->searchChildren($parentIds, $searchItemsParams);
            $parentIds = [];
            $itemIds   = [];
            $itemRows  = [];
            if ($dar && ! $dar->isError()) {
                $dar->rewind();
                while ($dar->valid()) {
                    $row                       = $dar->current();
                    $itemRows[$row['item_id']] = $row;
                    $itemIds[]                 = $row['item_id'];
                    if (
                        $row['item_type'] == PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
                        && ($expandAll || isset($expandedFolders[$row['item_id']]))
                    ) {
                        $parentIds[$row['item_id']] = $row['item_id'];
                    }
                    $dar->next();
                }

                // Fetch all the permissions at the same time
                $dPm->retreiveReadPermissionsForItems($itemIds, $user);

                // Build hierarchy: only keep displayable items
                foreach ($itemIds as $id) {
                    if ($ignorePerms || $dPm->userCanRead($user, $id)) {
                        $itemList[$id] = $this->getItemFromRow($itemRows[$id]);
                        $itemList[$itemList[$id]->getParentId()]->addItem($itemList[$id]);
                    } else {
                        unset($parentIds[$id]);
                    }
                }
            }
        } while (count($parentIds) > 0);

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
     *
     * @psalm-param array{user: PFUser, filter?: Docman_Report, ignore_obsolete: bool, start?: int, offset?: int, obsolete_only?: bool, getall?: bool, ignore_deleted?: bool, ignore_folders?: bool, api_offset?: int, api_limit?: int} $params
     */
    private function getItemSubTreeAsList($parentId, int &$nbItemsFound, array $params): ArrayIterator
    {
        $user = $params['user'];

        // Prepare filters if any
        $filter = null;
        if (isset($params['filter'])) {
            $filter = $params['filter'];
            assert($filter instanceof Docman_Report);
        }

        // Obsolescence
        $searchItemsParams = [];
        if (isset($params['ignore_obsolete'])) {
            $searchItemsParams['ignore_obsolete'] = $params['ignore_obsolete'];
        }

        // Range of documents to return
        $start = 0;
        if (isset($params['start'])) {
            $start = $params['start'];
        } elseif (isset($params['api_offset'])) {
            $start = $params['api_offset'];
        }
        $end = 25;
        if (isset($params['offset'])) {
            $end = $start + $params['offset'];
        } elseif (isset($params['api_limit'])) {
            $end = $start + $params['api_limit'];
        }

        $dao = $this->_getItemDao();

        // Build Folder List
        $parentItem     = $this->getItemFromDb($parentId);
        $dPm            = Docman_PermissionsManager::instance($parentItem->getGroupId());
        $folderList     = [$parentId => &$parentItem];
        $pathIdArray    = [$parentId => []];
        $pathTitleArray = [$parentId => []];
        $parentIds      = [$parentId];
        do {
            $dar       = $dao->searchSubFolders($parentIds);
            $parentIds = [];
            $itemIds   = [];
            $itemRows  = [];
            if ($dar && ! $dar->isError()) {
                $dar->rewind();
                while ($dar->valid()) {
                    $row                        = $dar->current();
                    $itemRows[$row['item_id']]  = $row;
                    $itemIds[]                  = $row['item_id'];
                    $parentIds[$row['item_id']] = $row['item_id'];
                    $dar->next();
                }

                // Fetch all the permissions at the same time
                $dPm->retreiveReadPermissionsForItems($itemIds, $user);

                // Build hierarchy: only keep displayable items
                foreach ($itemIds as $id) {
                    if ($dPm->userCanRead($user, $id)) {
                        $folderList[$id] = $this->getItemFromRow($itemRows[$id]);
                        // Update path
                        $pathIdArray[$id]    = array_merge($pathIdArray[$folderList[$id]->getParentId()], [$id]);
                        $pathTitleArray[$id] = array_merge($pathTitleArray[$folderList[$id]->getParentId()], [$folderList[$id]->getTitle()]);
                    } else {
                        unset($parentIds[$id]);
                    }
                }
            }
        } while (count($parentIds) > 0);

        // Keep only documents in allowed subfolders
        $mdFactory = new Docman_MetadataFactory($this->groupId);
        $ci        = null;
        if ($filter !== null) {
            $ci = $filter->getColumnIterator();
        }

        // Build Document list
        $itemArray    = [];
        $nbItemsFound = 0;

        //Keep old logical way of retrieving items if legacy param "getall" is provided
        /** @psalm-suppress DeprecatedMethod */
        if ((isset($params['getall']) && $params['getall'])) {
            if (isset($params['obsolete_only']) && $params['obsolete_only']) {
                $dar = $dao->searchObsoleteByGroupId($this->groupId);
            } else {
                $dar = $dao->searchByGroupId($this->groupId, $filter, $searchItemsParams);
            }

            if ($dar && ! $dar->isError()) {
                $this->preloadItemPerms($dar, $user, $this->groupId);
                $dar->rewind();
                while ($dar->valid()) {
                    $row = $dar->current();
                    // The document is not is one of the allowed subfolder so we
                    // can delete it. As a side effect decrease the number of
                    // document found.
                    if ($dPm->userCanRead($user, $row['item_id']) && isset($folderList[$row['parent_id']])) {
                        $itemArray[$row['item_id']] = $this->buildItemWithAllDetails(
                            $row,
                            $pathTitleArray,
                            $pathIdArray,
                            $ci,
                            $mdFactory
                        );
                        $nbItemsFound++;
                    }
                    $dar->next();
                }
            }

            return new ArrayIterator($itemArray);
        }

        //Get all the items with light information to have an accurate count of total value
        if (isset($params['obsolete_only']) && $params['obsolete_only']) {
            $all_dar = $dao->searchObsoleteByGroupId($this->groupId);
        } else {
            $all_dar = $dao->searchByGroupId(
                $this->groupId,
                $filter,
                array_merge(
                    $searchItemsParams,
                    ['light_search' => true],
                )
            );
        }
        $item_ids_to_fetch = [];
        if ($all_dar && ! $all_dar->isError()) {
            $this->preloadItemPerms($all_dar, $user, $this->groupId);
            $all_dar->rewind();
            $i = 0;
            while ($all_dar->valid()) {
                $row = $all_dar->current();
                if ($dPm->userCanRead($user, $row['item_id']) && isset($folderList[$row['parent_id']])) {
                    $nbItemsFound++;
                    if ($i >= $start && $i < $end) {
                        $item_ids_to_fetch[] = (int) $row['item_id'];
                    }
                    $i++;
                }
                $all_dar->next();
            }
        }

        //Free the memory used for this no more used but possibly huge collection
        $all_dar = null;

        if (empty($item_ids_to_fetch)) {
            return new ArrayIterator($itemArray);
        }

        //Get all the information with the sliced items
        if (isset($params['obsolete_only']) && $params['obsolete_only']) {
            $dar = $dao->searchObsoleteByGroupId($this->groupId);
        } else {
            $dar = $dao->searchByGroupId(
                $this->groupId,
                $filter,
                array_merge(
                    $searchItemsParams,
                    ['items_ids_to_fetch' => $item_ids_to_fetch],
                )
            );
        }

        /** @psalm-suppress DeprecatedMethod */
        if ($dar && ! $dar->isError()) {
            $dar->rewind();
            while ($dar->valid()) {
                $row                        = $dar->current();
                $itemArray[$row['item_id']] = $this->buildItemWithAllDetails(
                    $row,
                    $pathTitleArray,
                    $pathIdArray,
                    $ci,
                    $mdFactory
                );
                $dar->next();
            }
        }

        return new ArrayIterator($itemArray);
    }

    private function buildItemWithAllDetails(
        array $row,
        array $pathTitleArray,
        array $pathIdArray,
        ?ArrayIterator $columns_iterator,
        Docman_MetadataFactory $docman_metadata_factory,
    ): Docman_Item {
        $item = $this->getItemFromRow($row);

        // Append Path
        $item->setPathTitle($pathTitleArray[$row['parent_id']]);
        $item->setPathId($pathIdArray[$row['parent_id']]);

        // Append metadata
        if ($columns_iterator !== null) {
            $columns_iterator->rewind();
            while ($columns_iterator->valid()) {
                $c = $columns_iterator->current();
                if ($c->md !== null && Docman_MetadataFactory::isRealMetadata($c->md->getLabel())) {
                    $docman_metadata_factory->addMetadataValueToItem($item, $c->md);
                }
                $columns_iterator->next();
            }
        }

        return $item;
    }

    /**
     * Build a tree from with the list of items
     *
     * @return Docman_Item
     */
    public function getItemTree(&$rootItem, &$user, $ignorePerms = false, $expandAll = false, $ignoreObsolete = true)
    {
        return $this->getItemSubTree($rootItem, $user, $ignorePerms, $expandAll, $ignoreObsolete);
    }

    /**
     * * @psalm-param array{user: PFUser, filter?: Docman_Report, ignore_obsolete: boolean, start?: int, offset?: int, obsolete_only?: bool, getall?: bool } $params
     *
     */
    public function getItemList($id, int &$nbItemsFound, array $params): ArrayIterator
    {
        if (! $id) {
            $dao = $this->_getItemDao();
            $id  = $dao->searchRootIdForGroupId($this->groupId);
        }
        return $this->getItemSubTreeAsList($id, $nbItemsFound, $params);
    }

    public function doesTitleCorrespondToExistingDocument(string $title, int $parent_id)
    {
        return $this->_getItemDao()->doesTitleCorrespondToExistingDocument($title, $parent_id);
    }

    public function doesTitleCorrespondToExistingFolder(string $title, int $parent_id)
    {
        return $this->_getItemDao()->doesTitleCorrespondToExistingFolder($title, $parent_id);
    }

    public function findByTitle($user, $title, $groupId)
    {
        $ia = [];

        $dao = $this->_getItemDao();
        $dPm = Docman_PermissionsManager::instance($groupId);
        $dar = $dao->searchByTitle($title);
        $dar->rewind();
        while ($dar->valid()) {
            $row = $dar->current();

            $item = $this->getItemFromRow($row);
            if ($dPm->userCanRead($user, $item->getId())) {
                $parentItem = $this->getItemFromDb($item->getParentId());
                if ($dPm->userCanRead($user, $parentItem->getId())) {
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
    public function findFuturObsoleteItems()
    {
        // Compute the timescale for the day in one month
        $today   = getdate();
        $tsStart = mktime(0, 0, 0, $today['mon'] + 1, $today['mday'], $today['year']);
        $tsEnd   = mktime(23, 59, 59, $today['mon'] + 1, $today['mday'], $today['year']);

        $ia  = [];
        $dao = $this->_getItemDao();
        $dar = $dao->searchObsoleteAcrossProjects($tsStart, $tsEnd);
        while ($dar->valid()) {
            $row  = $dar->current();
            $ia[] = $this->getItemFromRow($row);
            $dar->next();
        }

        $ii = new ArrayIterator($ia);
        return $ii;
    }

    public Docman_ItemDao|null $dao = null;
    /**
     * @return Docman_ItemDao
     */
    public function _getItemDao()
    {
        if (! $this->dao) {
            $this->dao = new Docman_ItemDao(CodendiDataAccess::instance());
        }
        return $this->dao;
    }

    protected function _getVersionFactory()
    {
        return new Docman_VersionFactory();
    }

    protected function _getUserManager()
    {
        return UserManager::instance();
    }

    protected function _getEventManager()
    {
        return EventManager::instance();
    }

    public function update($row)
    {
        // extract cross references
        $reference_manager = ReferenceManager::instance();
        if (isset($row['title'])) {
            $reference_manager->extractCrossRef($row['title'], $row['id'], ReferenceManager::REFERENCE_NATURE_DOCUMENT, (int) $this->groupId);
        }
        if (isset($row['description'])) {
            $reference_manager->extractCrossRef($row['description'], $row['id'], ReferenceManager::REFERENCE_NATURE_DOCUMENT, (int) $this->groupId);
        }
        $dao = $this->_getItemDao();
        return $dao->updateFromRow($row);
    }

    public function updateLink(Docman_Link $link, array $version_data)
    {
        $update = $this->update(
            [
                'id'        => $link->getId(),
                'group_id'  => $link->getGroupId(),
                'title'     => $link->getTitle(),
                'user_id'   => $link->getOwnerId(),
                'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_LINK,
                'link_url'  => $link->getUrl(),
            ]
        );

        return $update && $this->createNewLinkVersion($link, $version_data);
    }

    public function updateLinkFromVersionData(Docman_Link $link, array $version_data)
    {
        $update = $this->update(
            [
                'id'        => $link->getId(),
                'group_id'  => $link->getGroupId(),
                'title'     => $link->getTitle(),
                'user_id'   => $link->getOwnerId(),
                'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_LINK,
                'link_url'  => $version_data['link_url'],
            ]
        );

        $link->setUrl($version_data['link_url']);

        return $update && $this->createNewLinkVersion($link, $version_data);
    }

    public function updateLinkWithMetadata(Docman_Link $link, array $version_data)
    {
        $update = $this->update(
            [
                'id'                => $link->getId(),
                'group_id'          => $link->getGroupId(),
                'title'             => $version_data['title'],
                'description'       => $version_data['description'],
                'user_id'           => $link->getOwnerId(),
                'item_type'         => PLUGIN_DOCMAN_ITEM_TYPE_LINK,
                'link_url'          => $version_data['link_url'],
                'status'            => $version_data['status'],
                'obsolescence_date' => $version_data['obsolescence_date'],
            ]
        );

        $link->setUrl($version_data['link_url']);

        return $update && $this->createNewLinkVersion($link, $version_data);
    }

    public function massUpdate($srcItemId, $mdLabel, $itemIdArray)
    {
        $dao = $this->_getItemDao();
        $dao->massUpdate($srcItemId, $mdLabel, $itemIdArray);
    }

    public function create($row, $ordering)
    {
        $dao = $this->_getItemDao();
        $id  = $dao->createFromRow($row);
        if ($id) {
            $this->setNewParent($id, $row['parent_id'], $ordering);
        }

        return $id;
    }

    /**
     * @return Docman_Item
     * @throws CannotInstantiateItemWeHaveJustCreatedInDBException
     */
    public function createWithoutOrdering(
        $title,
        $description,
        $parent_id,
        $status_id,
        ?int $obsolescence_date,
        $user_id,
        $item_type_id,
        DateTimeImmutable $create_date,
        DateTimeImmutable $update_date,
        $wiki_page = null,
        $link_url = null,
    ) {
        $row = [
            'title'             => $title,
            'description'       => $description,
            'parent_id'         => $parent_id,
            'group_id'          => $this->groupId,
            'create_date'       => $create_date->getTimestamp(),
            'update_date'       => $update_date->getTimestamp(),
            'user_id'           => $user_id,
            'status'            => $status_id,
            'obsolescence_date' => $obsolescence_date,
            'item_type'         => $item_type_id,
            'wiki_page'         => $wiki_page,
            'link_url'          => $link_url,

        ];
        $id = $this->create($row, null);

        $row['item_id'] = $id;
        $item           = $this->getItemFromRow($row);

        if (! $item) {
            throw new CannotInstantiateItemWeHaveJustCreatedInDBException();
        }
        return $item;
    }

    /**
     * Find root unique child if exists.
     *
     * @param $groupId Project id of the docman.
     * @return int|bool false if there is more than one children for root.
     *                     true if there is no child for root.
     *                     item_id of the unique child of root if any.
     */
    public function isItemTheOnlyChildOfRoot($groupId)
    {
        if (! isset($this->onlyOneChildForRoot[$groupId])) {
            $dao = $this->_getItemDao();
            $dar = $dao->hasRootOnlyOneChild($groupId);
            if ($dar && ! $dar->isError()) {
                if ($dar->rowCount() > 1) {
                    $this->onlyOneChildForRoot[$groupId] = false;
                } elseif ($dar->rowCount() == 0) {
                    $this->onlyOneChildForRoot[$groupId] = true;
                } else {
                    $row                                 = $dar->getRow();
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
    public function isMoveable($item)
    {
        $movable = false;
        if ($item->getParentId() != 0) {
            $onlyOneChild = $this->isItemTheOnlyChildOfRoot($item->getGroupId());
            if ($onlyOneChild === false || $onlyOneChild !== $item->getId()) {
                $movable = true;
            }
        }
        return $movable;
    }

    public function setNewParent($item_id, $new_parent_id, $ordering)
    {
        $item = $this->getItemFromDb($item_id);
        $dao  = $this->_getItemDao();
        return $item && $this->isMoveable($item) && $dao->setNewParent($item_id, $new_parent_id, $ordering);
    }

    /**
     * @return bool
     */
    public function move(Docman_Item $item_to_move, Docman_Folder $destination, PFUser $user_requesting_the_move, $ordering)
    {
        if (! $this->setNewParent($item_to_move->getId(), $destination->getId(), $ordering)) {
            return false;
        }
        $item_to_move->fireEvent('plugin_docman_event_move', $user_requesting_the_move, $destination);
        return true;
    }

    /**
     * @return bool
     */
    public function moveWithDefaultOrdering(Docman_Item $item_to_move, Docman_Folder $destination, PFUser $user_requesting_the_move)
    {
        return $this->move($item_to_move, $destination, $user_requesting_the_move, '');
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
    public function breathFirst($item_id, $callback, $params)
    {
        $dao     = $this->_getItemDao();
        $parents = [$item_id];
        do {
            $dar = $dao->searchByParentsId($parents);
            if ($dar && ! $dar->isError()) {
                $parents = [];
                while ($dar->valid()) {
                    $row = $dar->current();
                    call_user_func_array($callback, [$row, $params]);
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
     * @return Docman_Folder|null A sub tree or null if root node was not found.
     */
    public function &getItemTreeFromLeaves($itemArray, $user)
    {
        $null = null;
        if (is_array($itemArray)) {
            foreach ($itemArray as $item) {
                $itemList[$item->getId()] = $item;
                $orphans[$item->getId()]  = $item->getId();
                $itemIds[]                = $item->getId();
            }
        } else {
            return $null;
        }

        // Check permissions on submitted item array
        $dpm = Docman_PermissionsManager::instance($this->groupId);
        $dpm->retreiveReadPermissionsForItems($itemIds, $user);
        foreach ($itemArray as $item) {
            if (! $dpm->userCanRead($user, $item->getId())) {
                unset($itemList[$item->getId()]);
                unset($orphans[$item->getId()]);
            }
        }

        // Now, here we go
        $paths  = [];
        $dao    = $this->_getItemDao();
        $rootId = false;
        do {
            // Try to build the connections between childrens and parents in itemList
            $wantedItems = [];
            $rootInfo    = $this->connectOrphansToParents($itemList, $orphans, $wantedItems);
            if ($rootInfo !== false) {
                $rootId = $rootInfo;
            }

            // If some items are missing, look for them in the DB.
            if (is_array($wantedItems) && count($wantedItems) > 0) {
                $dar = $dao->searchByIdList($wantedItems);
                if ($dar && ! $dar->isError()) {
                    $this->preloadItemPerms($dar, $user, $this->groupId);
                    while ($dar->valid()) {
                        $row  = $dar->current();
                        $item = $this->getItemFromRow($row);
                        if ($item === null) {
                            continue;
                        }
                        if ($dpm->userCanRead($user, $item->getId())) {
                            $itemList[$item->getId()] = $item;
                            $orphans[$item->getId()]  = $item->getId();
                        } else {
                            $itemList[$item->getId()] = null;
                        }
                        $dar->next();
                    }
                }
            }
        } while (count($wantedItems) > 0);

        if ($rootId !== false) {
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
     * @return int|false Id of root item if found, false otherwise.
     */
    public function connectOrphansToParents(&$itemList, &$orphans, &$wantedItems)
    {
        $rootId = false;
        foreach ($orphans as $itemId) {
            // Check if orphan belong to the item list and is available.
            // As orphans should always be parts of $itemList, it means that
            // this orphan is not readable by user.
            if (isset($itemList[$itemId]) && $itemList[$itemId] !== false) {
                // Check if current item parents is in the list
                $pid = $itemList[$itemId]->getParentId();
                if ($pid != 0) {
                    if (isset($itemList[$pid])) {
                        if ($itemList[$pid] !== false) {
                            $itemList[$pid]->addItem($itemList[$itemId]);
                            unset($orphans[$itemId]);
                        }
                    } else {
                        if (! isset($orphans[$itemId])) {
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
    public function getItemMapping($item)
    {
        $v = new Docman_BuildItemMappingVisitor($this->groupId);
        $item->accept($v);
        return $v->getItemMapping();
    }

    /**
     * @return Docman_Item|null
     */
    public function getRoot($group_id)
    {
        if (! isset($this->rootItems[$group_id])) {
            $dao                        = $this->_getItemDao();
            $id                         = $dao->searchRootIdForGroupId($group_id);
            $this->rootItems[$group_id] = $this->getItemFromDb($id);
        }
        return $this->rootItems[$group_id];
    }

    public function isRoot(Docman_Item $item)
    {
        $root = $this->getRoot($item->getGroupId());
        if ($root === null) {
            return false;
        }
        return $item->getId() == $root->getId();
    }

    public function createRoot($group_id, $title)
    {
        $dao  = $this->_getItemDao();
        $root = new Docman_Folder();
        $root->setGroupId($group_id);
        $root->setTitle($title);
        return $dao->createFromRow($root->toRow());
    }

    public function rawCreate($item)
    {
        $dao            = $this->_getItemDao();
        $row            = $item->toRow();
        $row['item_id'] = null;
        return $dao->createFromRow($row);
    }

    /**
     * Copy a subtree.
     */
    public function cloneItems(
        $user,
        $metadataMapping,
        $ugroupsMapping,
        $dataRoot,
        Docman_Item $source_item,
        DestinationCloneItem $destination,
        $ordering = null,
    ) {
        $itemMapping = [];

        $itemFactory = new Docman_ItemFactory($source_item->getGroupId());

        $itemTree = $itemFactory->getItemTree($source_item, $user, false, true);

        if ($itemTree) {
            $parent_id = $destination->getNewParentID();
            $rank      = null;
            if ($ordering !== null) {
                $dao  = $this->_getItemDao();
                $rank = $dao->_changeSiblingRanking($parent_id, $ordering);
            }

            $cloneItemsVisitor = $destination->getCloneItemsVisitor();
            $visitorParams     = ['parentId' => $parent_id,
                'user' => $user,
                'metadataMapping' => $metadataMapping,
                'ugroupsMapping'  => $ugroupsMapping,
                'data_root' => $dataRoot,
                'newRank' => $rank,
                'srcRootId' => $source_item->getId(),
            ];
            $itemTree->accept($cloneItemsVisitor, $visitorParams);
            $itemMapping = $cloneItemsVisitor->getItemMapping();
        }
        return $itemMapping;
    }

    public function setCutPreference($item)
    {
        user_set_preference(
            PLUGIN_DOCMAN_PREF . '_item_cut',
            $item->getId()
        );
    }

    public function setCopyPreference($item)
    {
        user_set_preference(
            PLUGIN_DOCMAN_PREF . '_item_copy',
            $item->getId()
        );
    }

    /**
     * Get the item_id that was cut by the user.
     *
     * If groupId is given, only items that belongs to this groupId will be
     * returned.
     * If no item match, returns false.
     *
     * @param PFUser    $user
     * @param int $groupId
     *
     * @return int|false
     */
    public function getCutPreference($user, $groupId = null)
    {
        if (! isset($this->cutItem[$user->getId()])) {
            $cutId = false;
            $id    = user_get_preference(PLUGIN_DOCMAN_PREF . '_item_cut');
            if ($groupId !== null && $id !== false) {
                $item = $this->getItemFromDb($id);
                if ($item && $item->getGroupId() == $groupId) {
                    $cutId = $id;
                }
            }
            $this->cutItem[$user->getId()] = $cutId;
        }
        return $this->cutItem[$user->getId()];
    }

    public function getCopyPreference($user)
    {
        if (! isset($this->copiedItem[$user->getId()])) {
            $this->copiedItem[$user->getId()] = user_get_preference(PLUGIN_DOCMAN_PREF . '_item_copy');
        }
        return $this->copiedItem[$user->getId()];
    }

    public function delCutPreference()
    {
        user_del_preference(PLUGIN_DOCMAN_PREF . '_item_cut');
    }

    public function delCopyPreference()
    {
        user_del_preference(PLUGIN_DOCMAN_PREF . '_item_copy');
    }

    /**
    * This order deletion of cut preferences of all users set on item identified by $item_id.
    *
    * @param int $item_id identifier of docman item that has been marked as deleted.
    * @return void
    *
    */
    public function delCutPreferenceForAllUsers($item_id)
    {
        $dao = $this->_getItemDao();
        $dao->deleteCutPreferenceForAllUsers($item_id);
    }

    /**
    * This order deletion of copy preferences of all users set on item identified by $item_id.
    *
    * @param int $item_id identifier of docman item that has been marked as deleted.
    * @return void
    *
    */
    public function delCopyPreferenceForAllUsers($item_id)
    {
        $dao = $this->_getItemDao();
        $dao->deleteCopyPreferenceForAllUsers($item_id);
    }

    public function getCurrentWikiVersion($item)
    {
        $version = null;
        if ($this->getItemTypeForItem($item) == PLUGIN_DOCMAN_ITEM_TYPE_WIKI) {
            $wiki_page = $this->getWikiPage($item->getGroupId(), $item->getPagename());

            if ($wiki_page->exist()) {
                $version = $wiki_page->getCurrentVersion();
            }
        }
        return $version;
    }

    /**
     * Returns the folder stats (count + size)
     */
    public function getFolderStats($folder, $user)
    {
        if ($folder instanceof \Docman_Folder && $folder->getId() !== null) {
            $folderSubTree = $this->getItemSubTree($folder, $user, false, true);
            return $this->getFolderTreeStats($folderSubTree);
        } else {
            return null;
        }
    }

    /**
     * Recursive method that takes a subtree and
     * returns the corresponding stats (count + size)
     */
    private function getFolderTreeStats($folder)
    {
        $stats['count'] = 0;
        $stats['size']  = 0;
        $stats['types'] = [];

        if ($folder instanceof \Docman_Folder) {
            $items = $folder->getAllItems();
            foreach ($items->iterator() as $item) {
                $class = $item::class;
                $type  = strtolower(substr(strrchr($class, '_'), 1));

                if (! isset($stats['types'][$type])) {
                    $stats['types'][$type] = 0;
                }

                $stats['types'][$type]++;
                $stats['count']++;
                if ($type == 'file' || $type == 'embeddedfile') {
                    $currentVersion = $item->getCurrentVersion();
                    if ($currentVersion !== null) {
                        $stats['size'] += $currentVersion->getFilesize();
                    }
                } elseif ($type == 'folder') {
                    $childStats = $this->getFolderTreeStats($item);
                    foreach ($childStats['types'] as $k => $v) {
                        if (! isset($stats['types'][$k])) {
                            $stats['types'][$k] = 0;
                        }
                        $stats['types'][$k] += $v;
                    }
                    $stats['count'] += $childStats['count'];
                    $stats['size']  += $childStats['size'];
                }
            }
        }

        return $stats;
    }

    /**
     * Mark item as deleted
     *
     * @param Docman_Item $item
     *
     * @return void
     */
    public function delete($item)
    {
        // The event must be processed before the item is deleted
        $um         = $this->_getUserManager();
        $user       = $um->getCurrentUser();
        $itemParent = $this->getItemFromDb($item->getParentId());
        $item->fireEvent('plugin_docman_event_del', $user, $itemParent);

        // Delete Lock if any
        $lF = $this->getLockFactory();
        if ($lF->itemIsLocked($item)) {
            $lF->unlock($item, $user);
        }

        $item->setDeleteDate(time());
        $this->delCutPreferenceForAllUsers($item->getId());
        $this->delCopyPreferenceForAllUsers($item->getId());
        $this->deleteNotifications($item->getId());
        $dao = $this->_getItemDao();
        $dao->updateFromRow($item->toRow());
        $dao->storeDeletedItem($item->getId());
    }

    public function getLockFactory()
    {
        return new \Docman_LockFactory(new \Docman_LockDao(), new \Docman_Log());
    }

    private function deleteNotifications($item_id)
    {
        $this->getUgroupsToNotifyDao()->deleteByItemId($item_id);
        $this->getUsersToNotifyDao()->deleteByItemId($item_id);
    }

    public function getUgroupsToNotifyDao()
    {
        return new UgroupsToNotifyDao();
    }

    public function getUsersToNotifyDao()
    {
        return new UsersToNotifyDao();
    }

    /**
     * Delete Docman hierarchy for a given project
     *
     * @param int $groupId The project id
     *
     * @return bool success
     */
    public function deleteProjectTree($groupId)
    {
        $deleteStatus = true;
        $root         = $this->getRoot($groupId);
        if ($root) {
            $dPm              = Docman_PermissionsManager::instance($groupId);
            $subItemsWritable = $dPm->currentUserCanWriteSubItems($root->getId());
            if ($subItemsWritable) {
                $rootChildren = $this->getChildrenFromParent($root);
                $user         = $this->_getUserManager()->getCurrentUser();
                try {
                    foreach ($rootChildren as $children) {
                        if (! $this->deleteSubTree($children, $user, true)) {
                            $deleteStatus = false;
                        }
                    }
                } catch (DeleteFailedException $exception) {
                    $GLOBALS['Response']->feedback->log(Feedback::ERROR, $exception->getI18NExceptionMessage());
                }
            } else {
                $deleteStatus = false;
            }
        }
        return $deleteStatus;
    }

    /**
     * Manage deletion of a entire item hierarchy.
     *
     * It's the recommended and official way to delete a file in the docman
     *
     * @param Docman_Item $item        Item to delete
     * @param PFUser      $user        User who performs the delete
     * @param bool        $cascadeWiki If there are wiki documents, do we delete corresponding in wiki page too ?
     *
     * @return bool success
     * @throws DeleteFailedException
     */
    public function deleteSubTree(Docman_Item $item, PFUser $user, $cascadeWiki)
    {
        if ($item && ! $this->isRoot($item)) {
            // Cannot delete one folder if at least on of the document inside
            // cannot be deleted
            $dPm              = Docman_PermissionsManager::instance($item->getGroupId());
            $subItemsWritable = $dPm->userCanDeleteSubItems($user, $item);
            if ($subItemsWritable) {
                $itemSubTree = $this->getItemSubTree($item, $user, false, true);
                if ($itemSubTree) {
                    $deletor = new Docman_ActionsDeleteVisitor();
                    if ($itemSubTree->accept($deletor, ['user'  => $user, 'cascadeWikiPageDeletion' => $cascadeWiki])) {
                        if ($cascadeWiki) {
                            $GLOBALS['Response']->addFeedback(Feedback::INFO, dgettext('tuleap-docman', 'Wiki page successfully deleted from wiki service.'));
                        }
                        return true;
                    }
                }
            } else {
                throw DeleteFailedException::missingPermissionSubItems();
            }
        }
        return false;
    }

    /**
     * List pending items
     *
     * @param int $groupId
     * @param int $offset
     * @param int $limit
     *
     * @return Array
     */
    public function listPendingItems($groupId, $offset, $limit)
    {
        $dao = $this->_getItemDao();
        return $dao->listPendingItems($groupId, $offset, $limit);
    }

    /**
     * Purge deleted items with delete date lower than the given time
     *
     * @param int $time
     *
     * @return bool
     */
    public function purgeDeletedItems($time)
    {
        $dao = $this->_getItemDao();
        $dar = $dao->listItemsToPurge($time);
        if ($dar && ! $dar->isError()) {
            foreach ($dar as $row) {
                $item = new Docman_Item($row);
                $this->purgeDeletedItem($item);
            }
            return true;
        }
        return false;
    }

    /**
     * Mark the deleted item as purged
     *
     * @param Docman_Item $item
     *
     * @return bool
     */
    public function purgeDeletedItem($item)
    {
        $dao = $this->_getItemDao();
        return $dao->setPurgeDate($item->getId(), time());
    }

    /**
     * Restore on item
     *
     * @param Docman_Item $item
     *
     * @return bool
     */
    public function restore($item)
    {
        $dao         = $this->_getItemDao();
        $type        = $this->getItemTypeForItem($item);
        $oneRestored = false;
        $isFile      = false;
        if ($type == PLUGIN_DOCMAN_ITEM_TYPE_FILE || $type == PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE) {
            $isFile   = true;
            $vf       = $this->_getVersionFactory();
            $versions = $vf->listVersionsToPurgeForItem($item);
            if ($versions) {
                foreach ($versions as $version) {
                    $oneRestored |= $vf->restore($version);
                }
            }
        }

        if (! $isFile || $oneRestored) {
            // Log the event
            $user = $this->_getUserManager()->getCurrentUser();
            $this->_getEventManager()->processEvent('plugin_docman_event_restore', [
                'group_id'   => $item->getGroupId(),
                'item'       => $item,
                'user'       => $user,
            ]);
            return $dao->restore($item->getId());
        }
        return false;
    }

    public function createNewLinkVersion(Docman_Link $link, array $version_data)
    {
        $link_version_factory = new Docman_LinkVersionFactory();

        return $link_version_factory->create($link, $version_data['label'], $version_data['changelog'], $_SERVER['REQUEST_TIME'] ?? (new DateTimeImmutable())->getTimestamp());
    }
}
