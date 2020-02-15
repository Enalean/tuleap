<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * ApprovalTableFactory for wiki pages.
 *
 * Wiki pages are rather diffrent of files regarding versions. As there can be
 * a lot of versions between 2 approval tables cycle (a lot of editions either
 * minor or major), we cannot create an approval table per wiki page version.
 *
 * The approval tables are completly manually driven by the approval table
 * owner and he decide when to create an new approval table. Once an approval
 * table is created (and bound to a version of the page), the table is the
 * default one until the admin decide to create a new one.
 */
class Docman_ApprovalTableWikiFactory extends Docman_ApprovalTableVersionnedFactory
{
    public $wikiVersionId;

    /**
     * Initialize the factory for the given item.
     *
     * If there is a versionNumber provided, just use it.
     *
     * If there is no versionNumber provided, the default wiki version id is
     * the one of the latest approval table linked to the item.
     * If there is no approval table linked to the item, pick-up the last
     * version id of the wiki page.
     * If there is no version for the given wiki page, default to 0.
     */
    public function __construct($item, $versionNumber = null)
    {
        parent::__construct($item);

        $dao = $this->_getDao();
        $lastTableVersionId = $dao->getLastTableVersionIdByItemId($item->getId());

        if ($versionNumber !== null) {
            $this->wikiVersionId = $versionNumber;

            if ($versionNumber == $lastTableVersionId) {
                $this->customizable = true;
            } else {
                $this->customizable = false;
            }
        } else {
            // Works on the last available version, so is customizable.
            $this->customizable = true;

            if ($lastTableVersionId !== false) {
                $this->wikiVersionId = $lastTableVersionId;
            } else {
                // If there is no table attached to the item yet, just get the list version id.
                $lastWikiVersion = $dao->getLastWikiVersionIdByItemId($item->getId());
                if ($lastWikiVersion !== false) {
                    $this->wikiVersionId = $lastWikiVersion;
                } else {
                    // If the page doesn't exists yet, default to zero.
                    $this->wikiVersionId = 0;
                }
            }
        }
    }

    public function newTable()
    {
        return new Docman_ApprovalTableWiki();
    }

    public function _createTable($table)
    {
        return $this->_getDao()->createTable(
            $table->getItemId(),
            $table->getWikiVersionId(),
            $table->getOwner(),
            $table->getDescription(),
            $table->getDate(),
            $table->getStatus(),
            $table->getNotification()
        );
    }

    protected function _updateTableWithLastId($dstTable)
    {
        $wikiVersionId = $this->_getDao()->getLastWikiVersionIdByItemId($this->item->getId());
        $dstTable->setItemId($this->item->getId());
        $dstTable->setWikiVersionId($wikiVersionId);
    }

    public function _getTable()
    {
        return $this->getTableFromVersion($this->item->getId(), $this->wikiVersionId);
    }

    public function getTableFromVersion($itemId, $version)
    {
        $table = null;
        if ($version !== null) {
            $dao = $this->_getDao();
            $dar = $dao->getTableById($itemId, $version);
            if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
                $row = $dar->current();
                $table = $this->createTableFromRow($row);
            }
        }
        return $table;
    }

    public function getLastDocumentVersionNumber()
    {
        $lastVersionId = $this->_getDao()->getLastWikiVersionIdByItemId($this->item->getId());
        return $lastVersionId;
    }

    public function userAccessedSinceLastUpdate($user)
    {
        return $this->_getDao()->userAccessedSince($user->getId(), $this->item->getPagename(), $this->item->getGroupId(), $this->wikiVersionId);
    }

    // Class accessor
    public function _getDao()
    {
        return new Docman_ApprovalTableWikiDao(CodendiDataAccess::instance());
    }
}
