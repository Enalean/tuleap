<?php
/**
 * Copyright (c) Enalean, 2011 - 2019. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
use Tuleap\Docman\Upload\Version\DocumentOnGoingVersionToUploadDAO;
use Tuleap\Docman\Upload\Version\VersionOngoingUploadRetriever;

class Docman_View_GetMenuItemsVisitor implements ItemVisitor
{
    public $actions;
    private $user;

    public function __construct(&$user, $groupId)
    {
        $this->dPm = Docman_PermissionsManager::instance($groupId);
        $this->user = $user;
        $this->if = Docman_ItemFactory::instance($groupId);
        $this->actions = array();
    }

    public function visitItem(Docman_Item $item, $params = array())
    {
        if ($this->dPm->userCanManage($this->user, $item->getId())) {
            $this->actions['canPermissions'] = true;
        }
        // Permissions related stuff:
        // There are 2 permissions to take in account to decide whether
        // someone can move a file or not:
        // - the permission to 'remove' the file from a folder.
        //   - user need to have 'write' perm on both item and parent
        //     folder.
        // - and the permission to 'add' the file in another folder.
        //   - check if there is at least one folder writable in the
        //     docman.
        // But as the first step requires to have one folder writable,
        // we don't need specific test for the second one.
        // The only case we don't take in account is the possibility to
        // have only one file in only one writable folder (so it
        // shouldn't be movable). But this case is not worth the time
        // to develop and compute that case.
        if ($this->if->isMoveable($item) && $this->dPm->userCanWrite($this->user, $item->getId()) && $this->dPm->userCanWrite($this->user, $item->getParentId())) {
            $this->actions['canMove'] = true;
            $this->actions['canCut'] = true;
        }
        if (!$this->if->isRoot($item) && $this->dPm->userCanDelete($this->user, $item)) {
            $this->actions['canDelete'] = true;
        }

        // Lock
        if ($this->dPm->getLockFactory()->itemIsLockedByItemId($item->getId())) {
            $this->actions['canLockInfo'] = true;
            if ($this->dPm->userCanWrite($this->user, $item->getId())) {
                $this->actions['canUnlock'] = true;
            }
            $this->actions['isLocked'] = true;
        } else {
            if ($this->dPm->userCanWrite($this->user, $item->getId())) {
                $this->actions['canLock'] = true;
            }
        }

        // Approval tables
        $this->actions['canApproval'] = true;

        return $this->actions;
    }

    public function visitFolder(Docman_Folder $item, $params = array())
    {
        if ($this->dPm->userCanWrite($this->user, $item->getId())) {
            $this->actions['canNewDocument'] = true;
            $this->actions['canNewFolder']   = true;
            $pasteItemId = $this->if->getCutPreference($this->user, $item->getGroupId());
            $itemFactory = Docman_ItemFactory::instance($item->getGroupId());
            $parents = $itemFactory->getParents($item->getId());
            $this->actions['parents'] = $parents;
            if ($this->if->getCopyPreference($this->user) !== false ||
               $pasteItemId !== false && $pasteItemId != $item->getId() && !(isset($parents[$pasteItemId]) && $parents[$pasteItemId])) {
                $this->actions['canPaste'] = true;
            }
        }
        $actions = $this->visitItem($item, $params);

        // Cannot lock nor unlock a folder yet.
        $this->actions['canUnlock'] = false;
        $this->actions['canLock']   = false;
        return $this->actions;
    }

    public function visitDocument($item, $params = array())
    {
        return $this->visitItem($item, $params);
    }

    public function visitWiki(Docman_Wiki $item, $params = array())
    {
        if ($this->dPm->userCanWrite($this->user, $item->getId())) {
            $this->actions['canUpdate'] = true;
        }
        return $this->visitDocument($item, $params);
    }

    public function visitLink(Docman_Link $item, $params = array())
    {
        if ($this->dPm->userCanWrite($this->user, $item->getId())) {
            $this->actions['canNewVersion'] = true;
        }

        return $this->visitDocument($item, $params);
    }

    public function visitFile(Docman_File $item, $params = array())
    {
        if ($this->dPm->userCanWrite($this->user, $item->getId())) {
            $this->actions['canNewVersion'] = true;
        }

        $retriever = new VersionOngoingUploadRetriever(new DocumentOnGoingVersionToUploadDAO());
        if ($retriever->isThereAlreadyAnUploadOngoing($item, new DateTimeImmutable())) {
            $this->actions['canNewVersion'] = false;
        }
        return $this->visitDocument($item, $params);
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, $params = array())
    {
        return $this->visitFile($item, $params);
    }

    public function visitEmpty(Docman_Empty $item, $params = array())
    {
        if ($this->dPm->userCanWrite($this->user, $item->getId())) {
            $this->actions['canUpdate'] = true;
        }
        $actions = $this->visitDocument($item, $params);
        unset($actions['canApproval']); // No approval table for empty docs
        return $actions;
    }
}
