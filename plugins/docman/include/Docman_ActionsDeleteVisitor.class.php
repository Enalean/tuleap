<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\Docman\DeleteFailedException;
use Tuleap\Docman\DocumentDeletion\DocmanWikiDeletor;
use Tuleap\Docman\Item\ItemVisitor;

class Docman_ActionsDeleteVisitor implements ItemVisitor
{
    protected $user;
    protected $response;

    public function __construct()
    {
        //More coherent to have only one delete date for a whole hierarchy.
        $this->deleteDate = time();
    }

    /**
     *
     * Enter description here ...
     *
     * @param               $params
     *
     * @throws DeleteFailedException
     */
    public function visitFolder(Docman_Folder $item, $params = array())
    {
        //delete all sub items before
        $items = $item->getAllItems();
        if (isset($params['parent'])) {
            $parent = $params['parent'];
        } else {
            $parent = $this->_getItemFactory()->getItemFromDb($item->getParentId());
        }
        $one_item_has_not_been_deleted = false;
        if ($items->size()) {
            $it = $items->iterator();
            while ($it->valid()) {
                $o = $it->current();
                $params['parent'] = $item;
                if (!$o->accept($this, $params)) {
                    $one_item_has_not_been_deleted = true;
                }
                $it->next();
            }
        }

        if ($one_item_has_not_been_deleted) {
            throw DeleteFailedException::fromFolder($item);
        } else {
            //Mark the folder as deleted;
            $params['parent'] = $parent;
            return $this->_deleteItem($item, $params);
        }
    }

    /**
     * @throws DeleteFailedException
     */
    public function visitDocument($item, $params = array())
    {
        //Mark the document as deleted
        return $this->_deleteItem($item, $params);
    }

    /**
     * Handles wiki page deletion with two different behaviors:
     * 1- User decides to keep wiki page in wiki service. In this case, we restrict access to that wiki page to wiki
     * admins only.
     * 2- User decides to cascade deletion of the wiki page to wiki service too. In that case, we completely remove the
     * wiki page from wiki service.
     *
     * @param Docman_Wiki $item
     * @param array       $params params.
     *
     * @return bool $deleted. True if there is no error.  False otherwise.
     * @throws DeleteFailedException
     */
    public function visitWiki(Docman_Wiki $wiki, array $params = [])
    {
        $should_propagate_deletion = $params['cascadeWikiPageDeletion'];
        $user                      = $params['user'];

        return (new DocmanWikiDeletor(
            new \Tuleap\Docman\DocmanReferencedWikiPageRetriever(),
            $this->getPermissionManager($wiki->getGroupId()),
            $this->_getItemFactory(),
            $this->_getItemDao(),
            $this->_getEventManager()
        ))->deleteWiki($wiki, $user, $should_propagate_deletion);
    }

    public function visitLink(Docman_Link $item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }

    /**
     * @throws DeleteFailedException
     */
    public function visitFile(Docman_File $item, $params = array())
    {
        if ($this->getPermissionManager($item->getGroupId())->userCanDelete($params['user'], $item)) {
            if (isset($params['version']) && $params['version'] !== false) {
                return $this->_deleteVersion($item, $params['version'], $params['user']);
            } else {
                return $this->_deleteFile($item, $params);
            }
        } else {
            throw DeleteFailedException::fromFile($item);
        }
    }

    /**
     * @throws DeleteFailedException
     */
    public function visitEmbeddedFile(Docman_EmbeddedFile $item, $params = array())
    {
        return $this->visitFile($item, $params);
    }

    /**
     * @throws DeleteFailedException
     */
    public function visitEmpty(Docman_Empty $item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }

    public function visitItem(Docman_Item $item, array $params = [])
    {
        return null;
    }

    /**
     * @throws DeleteFailedException
     */
    public function _deleteItem($item, $params)
    {
        if ($this->getPermissionManager($item->getGroupId())->userCanDelete($params['user'], $item)) {
            $dIF = $this->_getItemFactory();
            $dIF->delete($item);
            return true;
        } else {
            throw DeleteFailedException::fromItem($item);
        }
    }

    /**
     * Delete a file (all versions of the file)
     *
     * @param Array       $params
     *
     * @return bool
     * @throws DeleteFailedException
     */
    public function _deleteFile(Docman_File $item, $params)
    {
        // Delete all versions before
        $version_factory = $this->_getVersionFactory();
        if ($versions = $version_factory->getAllVersionForItem($item)) {
            if (count($versions)) {
                $um = UserManager::instance();
                $user = $um->getCurrentUser();
                foreach ($versions as $version) {
                    $this->_deleteVersion($item, $version, $user);
                }
            }
        }
        return $this->visitDocument($item, $params);
    }

    /**
     * Delete a version of a file
     *
     *
     * @return bool
     */
    public function _deleteVersion(Docman_File $item, Docman_Version $version, PFUser $user)
    {
        // Proceed to deletion
        $version_factory = $this->_getVersionFactory();
        return $version_factory->deleteSpecificVersion($item, $version->getNumber());
    }

    public function _getEventManager()
    {
        return EventManager::instance();
    }

    public $version_factory;
    public function _getVersionFactory()
    {
        if (!$this->version_factory) {
            $this->version_factory = new Docman_VersionFactory();
        }
        return $this->version_factory;
    }

    public $item_factory;
    public function _getItemFactory()
    {
        if (!$this->item_factory) {
            $this->item_factory = new Docman_ItemFactory();
        }
        return $this->item_factory;
    }

    public $lock_factory;
    public function _getLockFactory()
    {
        if (!$this->lock_factory) {
            $this->lock_factory = new \Docman_LockFactory(new \Docman_LockDao(), new \Docman_Log());
        }
        return $this->lock_factory;
    }

    public function _getFileStorage()
    {
        return new Docman_FileStorage();
    }

    public function _getItemDao()
    {
        return new Docman_ItemDao(CodendiDataAccess::instance());
    }

    public function getPermissionManager($groupId)
    {
        return Docman_PermissionsManager::instance($groupId);
    }
}
