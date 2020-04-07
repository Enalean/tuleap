<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

/**
 * ApprovalTableFactory for Files and Embedded Files.
 *
 * The code is designed to handle an approval table per file version. Once you
 * create an approval table for a File version, the next ones are, by default,
 * proposed with an approval table. However, an approval owner can decide to
 * delete one table (attached to a version).
 */
class Docman_ApprovalTableFileFactory extends Docman_ApprovalTableVersionnedFactory
{
    public $itemVersion;

    public function __construct($item, $versionNumber = null)
    {
        parent::__construct($item);

        $dao = $this->_getDao();
        $vFactory = new Docman_VersionFactory();

        $dar = $dao->getLatestTableByItemId($item->getId(), 'ver.number');
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->getRow();
            $lastVersionNumber = $row['number'];
            $lastItemVersion = $vFactory->getSpecificVersion($item, $lastVersionNumber);

            if (
                $versionNumber !== null
                && $lastItemVersion->getNumber() != $versionNumber
            ) {
                $this->itemVersion = $vFactory->getSpecificVersion($item, $versionNumber);
                $this->customizable = false;
            } else {
                $this->itemVersion = $lastItemVersion;
            }
        } elseif ($item instanceof Docman_File || $item instanceof Docman_Link) {
            $this->itemVersion = $item->getCurrentVersion();
        }
    }

    /**
     * Create a new Docman_ApprovalTable object.
     */
    public function newTable()
    {
        return new Docman_ApprovalTableFile();
    }

    /**
     * Create a new entry in the database based on the given table
     *
     * @param $table ApprovalTable
     * @return int new table id
     */
    public function _createTable($table)
    {
        return $this->_getDao()->createTable(
            'version_id',
            $table->getVersionId(),
            $table->getOwner(),
            $table->getDescription(),
            $table->getDate(),
            $table->getStatus(),
            $table->getNotification()
        );
    }

    protected function _updateTableWithLastId($dstTable)
    {
        $currentVersion = $this->item->getCurrentVersion();
        $dstTable->setVersionId($currentVersion->getId());
    }

    public function _getTable()
    {
        return $this->getTableFromVersion($this->itemVersion);
    }

    public function getTableFromVersion($version)
    {
        $table = null;
        if ($version !== null) {
            $dao = $this->_getDao();
            $dar = $dao->getTableById($version->getId());
            if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
                $row = $dar->current();
                $table = $this->createTableFromRow($row);
                $table->setVersionNumber($version->getNumber());
            }
        }
        return $table;
    }

    public function getLastDocumentVersionNumber()
    {
        $currentItemVersion = $this->item->getCurrentVersion();
        return $currentItemVersion->getNumber();
    }

    public function userAccessedSinceLastUpdate($user)
    {
        $log = new Docman_Log();
        return $log->userAccessedSince($user->getId(), $this->item->getId(), $this->itemVersion->getDate());
    }

    // Class accessor
    public function _getDao()
    {
        return new Docman_ApprovalTableFileDao(CodendiDataAccess::instance());
    }
}
