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

class Docman_ApprovalTableItemFactory extends Docman_ApprovalTableFactory
{


    public function newTable()
    {
        return new Docman_ApprovalTableItem();
    }

    protected function _updateTableWithLastId($dstTable)
    {
        $dstTable->setItemId($this->item->getId());
    }

    /**
     * Create a new approval table
     */
    public function createTable($userId, $import)
    {
        return $this->newTableEmpty($userId);
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
            'item_id',
            $table->getItemId(),
            $table->getOwner(),
            $table->getDescription(),
            $table->getDate(),
            $table->getStatus(),
            $table->getNotification()
        );
    }

    public function _getTable()
    {
        $table = null;
        $dao = $this->_getDao();
        $dar = $dao->getTableByItemId($this->item->getId());
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->current();
            $table = $this->createTableFromRow($row);
        }
        return $table;
    }

    public function userAccessedSinceLastUpdate($user)
    {
        return true;
    }

    // Class accessor
    public function _getDao()
    {
        return new Docman_ApprovalTableItemDao(CodendiDataAccess::instance());
    }
}
