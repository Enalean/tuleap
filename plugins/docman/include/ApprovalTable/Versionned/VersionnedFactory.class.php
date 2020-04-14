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
 * ApprovalTableFactory for item with several versions.
 *
 * The current docman design doesn't support "several active versions per
 * documents" so the approval table.
 *
 * So the approval table evolves in this way:
 * - As long as there is no approval table... nothing.
 * - Once a table is created, there will always have an active approval table
 *   attached to the document except if all the tables are deleted (back to
 *   step 1).
 * - A table can apply on several document versions.
 * - When a table attached to a version is deleted, the previous one
 *   automaticaly becomes active. Example approval v4 is deleted:
 *   v1                             | v1
 *   v2 -> approval v2              | v2 -> approval v2
 *   v3 -'                          | v3 -'
 *   v4 -> approval v4 (copy of v2) | v4 -'
 */
abstract class Docman_ApprovalTableVersionnedFactory extends Docman_ApprovalTableFactory
{

    /**
     * Create $dstTable based on $srcTable.
     *
     * This method creates a new approval table as defined in $dstTable. If the
     * $type is 'copy': it imports reviewers "as is" (the very same content).
     * $type is 'reset': it imports reviewers only (not their comments).
     * Finally, the source table is Closed.
     */
    protected function importTable($srcTable, $dstTable, $type)
    {
        if ($srcTable->getStatus() == PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED) {
            $dstTable->setStatus(PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED);
        }

        $newTableId = $this->_createTable($dstTable);
        if ($newTableId) {
            // Copy reviewers
            $reviewerFactory = $this->_getReviewerFactory($dstTable, $this->item);
            if ($type == 'copy') {
                $reviewerFactory->newTableCopy($newTableId);
            } elseif ($type == 'reset') {
                $reviewerFactory->newTableReset($newTableId);
            }
            return true;
        }
        return false;
    }

    /**
     * Copy the current approval table into a new one.
     *
     * The new table share the same structure and the same users and the same
     * reviews (comments and commitment)
     * The new table should be transparent for reviewers, they should not see
     * any difference between the 2 tables.
     */
    public function newTableCopy($srcTable, $dstTable, $userId)
    {
        $dstTable->setOwner($userId);
        return $this->importTable($srcTable, $dstTable, 'copy');
    }

    /**
     * Create a new approval table based on the current one.
     *
     * The new table share the same structure and the same users but the user
     * commitment is deleted.
     * It acts like if the table was 'reset' by the admin.
     */
    public function newTableReset($srcTable, $dstTable, $userId)
    {
        $dstTable->setOwner($userId);
        $dstTable->setDate(time());
        return $this->importTable($srcTable, $dstTable, 'reset');
    }

    /**
     * Create a new approval table based on the last active one.
     */
    public function createTable($userId, $import)
    {
        $tableCreated = false;
        if ($import == 'copy' || $import == 'reset' || $import == 'empty') {
            $srcTable = $this->getLastTableForItem();
            if (($import == 'copy' || $import == 'reset') && $srcTable !== null) {
                $dstTable = clone $srcTable;
                $this->_updateTableWithLastId($dstTable);
                if ($import == 'copy') {
                    $tableCreated = $this->newTableCopy($srcTable, $dstTable, $userId);
                } else {
                    $tableCreated = $this->newTableReset($srcTable, $dstTable, $userId);
                }
            } else {
                $tableCreated = $this->newTableEmpty($userId);
            }
            // Close source table
            if ($srcTable !== null && !$srcTable->isClosed()) {
                $srcTable->setStatus(PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED);
                $this->_updateTable($srcTable);
            }
        } else {
            $tableCreated = $this->newTableEmpty($userId);
        }
        return $tableCreated;
    }

    /**
     * Return the last created approval table for the item
     *
     * @return Docman_ApprovalTable|null object
     */
    public function getLastTableForItem()
    {
        $table = null;
        $dao = $this->_getDao();
        $dar = $dao->getLatestTableByItemId($this->item->getId());
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->current();
            $table = $this->createTableFromRow($row);
        }
        return $table;
    }

    /**
     * Return all the approval table of for the item
     */
    public function getAllApprovalTable()
    {
        $tableArray = array();
        $dao = $this->_getDao();
        $dar = $dao->getApprovalTableItemId($this->item->getId(), 'app.*', '', true);
        if ($dar && !$dar->isError()) {
            while ($row = $dar->getRow()) {
                $tableArray[] = $this->createTableFromRow($row);
            }
        }
        return $tableArray;
    }

    abstract public function getLastDocumentVersionNumber();
}
