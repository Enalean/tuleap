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
 * ApprovalTableFactory for Links.
 *
 * The code follow the exact same patterns (and data structure than File)
 */
class Docman_ApprovalTableLinkFactory extends Docman_ApprovalTableVersionnedFactory
{

    /** @var Docman_LinkVersion */
    private $itemVersion;

    /** @var Docman_ApprovalTableLinkDao */
    private $dao;

    public function __construct(Docman_Link $item, $versionNumber = null)
    {
        parent::__construct($item);

        $this->dao = new Docman_ApprovalTableLinkDao();
        $vFactory = new Docman_LinkVersionFactory();

        $dar = $this->dao->getLatestTableByItemId($item->getId(), 'ver.number');
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
        } else {
            $this->itemVersion = $item->getCurrentVersion();
        }
    }

    /**
     * Create a new Docman_ApprovalTable object.
     */
    public function newTable()
    {
        return new Docman_ApprovalTableLink();
    }

    /**
     * Create a new entry in the database based on the given table
     *
     * @param $table ApprovalTable
     * @return int new table id
     */
    public function _createTable($table)
    {
        return $this->dao->createTable(
            'link_version_id',
            $table->getVersionId(),
            $table->getOwner(),
            $table->getDescription(),
            $table->getDate(),
            $table->getStatus(),
            $table->getNotification()
        );
    }

    public function _updateTableWithLastId($dstTable)
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
            $dar = $this->dao->getTableById($version->getId());
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

    public function _getDao()
    {
        return $this->dao;
    }
}
