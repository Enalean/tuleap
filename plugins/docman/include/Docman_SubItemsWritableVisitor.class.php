<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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

/**
 * Check if all the sub items are writable by given user.
 * @template-implements ItemVisitor<bool>
 */
class Docman_SubItemsWritableVisitor implements ItemVisitor
{
    public $dpm;
    public $user;
    public $docIdList;
    public $fldIdList;
    public $docCounter;
    public $fldCounter;

    public function __construct($groupId, $user)
    {
        $this->dpm = Docman_PermissionsManager::instance($groupId);
        $this->user = $user;
        $this->docIdList = array();
        $this->fldIdList = array();
        $this->docCounter = 0;
        $this->fldCounter = 0;
    }

    public function visitFolder(Docman_Folder $item, array $params = array())
    {
        // Recurse
        $canWrite = true;
        $this->fldCounter++;

        if ($this->_itemIsWritable($item, $params)) {
            $this->fldIdList[] = $item->getId();
            $items = $item->getAllItems();
            if ($items && $items->size() > 0) {
                $iter = $items->iterator();
                $iter->rewind();
                while ($iter->valid()) {
                    $child = $iter->current();
                    $canWrite = ($canWrite && $child->accept($this, $params));
                    $iter->next();
                }
            }
        } else {
            $canWrite = false;
        }
        return $canWrite;
    }

    public function visitDocument(Docman_Document $item, array $params = array())
    {
        $this->docCounter++;
        if ($this->_itemIsWritable($item, $params)) {
            $this->docIdList[] = $item->getId();
            return true;
        }
        return false;
    }

    public function visitWiki(Docman_Wiki $item, array $params = array())
    {
        return $this->visitDocument($item, $params);
    }

    public function visitLink(Docman_Link $item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }

    public function visitFile(Docman_File $item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }

    public function visitEmpty(Docman_Empty $item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }

    public function visitItem(Docman_Item $item, array $params = [])
    {
        return false;
    }


    public function _itemIsWritable($item, $params)
    {
        return $this->dpm->userCanWrite($this->user, $item->getId());
    }

    public function getItemIdList()
    {
        return array_merge($this->fldIdList, $this->docIdList);
    }

    public function getFolderIdList()
    {
        return $this->fldIdList;
    }

    public function getDocumentIdList()
    {
        return $this->docIdList;
    }

    public function getDocumentCounter()
    {
        return $this->docCounter;
    }
    public function getFolderCounter()
    {
        return $this->fldCounter;
    }
}
