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

require_once('Docman_ApprovalTable.class.php');
require_once('Docman_ApprovalTableReviewerFactory.class.php');

/**
 * Define the global behaviour of the approval table.
 *
 * The class hierarchy of approval table is the following:
 * Docman_ApprovalTableFactory
 * |-- Docman_ApprovalTableItemFactory
 * `-- Docman_ApprovalTableVersionnedFactory
 *     |-- Docman_ApprovalTableFileFactory
 *     `-- Docman_ApprovalTableWikiFactory
 *
 * This class define the common methods shared by the 3 concrete approval table
 * factories plus the factory method (getFromItem) to get the right concrete
 * object from the item that hold the table.
 */
/*abstract*/ class Docman_ApprovalTableFactory {
    var $item;
    var $table;
    var $customizable;

    function Docman_ApprovalTableFactory($item) {
        $this->item = $item;
        $this->table = null;
        $this->customizable = true;
    }

    /**
     * Return the right ApprovalTableFactory depending of the item.
     */
    static function getFromItem($item, $version=null) {
        $appTableFactory = null;
        if($item instanceof Docman_File) {
            $appTableFactory = new Docman_ApprovalTableFileFactory($item, $version);
        }
        elseif($item instanceof Docman_Wiki) {
            $appTableFactory = new Docman_ApprovalTableWikiFactory($item, $version);
        }
        elseif($item instanceof Docman_Empty) {
            // there is no approval table for empty documents.
        }
        else {
            $appTableFactory = new Docman_ApprovalTableItemFactory($item);
        }
        return $appTableFactory;
    }

    /**
     * Return the ApprovalTableReviewerFactory that correspond to the item.
     */
     /*static*/ function &getReviewerFactoryFromItem($item) {
        $appTableFactory = Docman_ApprovalTableFactory::getFromItem($item);
        if($appTableFactory !== null) {
            $table =& $appTableFactory->getTable();
            return $appTableFactory->_getReviewerFactory($table, $item);
        }
    }

    /**
     * Update dst table object with the id of the latest version of the doc.
     */
    /*abstract protected*/ function _updateTableWithLastId(&$dstTable) {}

    /**
     * Create a new entry in the database based on the given table
     *
     * @param $table ApprovalTable
     * @return int new table id
     */
    /*abstract protected*/ function _createTable($table) {}

    /**
     * Create a new empty approbal table in database.
     */
    function newTableEmpty($userId) {
        $table = $this->newTable();
        $this->_updateTableWithLastId($table);
        $table->setOwner($userId);
        $table->setDescription('');
        $table->setDate(time());
        $table->setStatus(PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED);
        $table->setNotification(PLUGIN_DOCMAN_APPROVAL_NOTIF_DISABLED);
        return $this->_createTable($table);
    }

    /**
     * Create a new approval table
     */
    /*abstract public*/ function createTable($userId, $import) {}

    /**
     * Create a new table object based on $row content.
     */
    function createTableFromRow($row) {
        $table = $this->newTable();
        $table->initFromRow($row);
        return $table;
    }

    /**
     * Return the table object that correspond to the factory defaults.
     */
    /*abstract protected*/ function _getTable() {}

    /**
     * Return true if their is an approval table for the item the approval
     * table is based on.
     */
    function tableExistsForItem() {
        return ($this->getTable() !== null);
    }

    /**
     * Delete the approval table and all the reviewers that belong to.
     */
    function deleteTable() {
        $deleted = false;
        $table =& $this->getTable();
        if($table !== null) {
            $reviewerFactory =& $this->_getReviewerFactory($table, $this->item);
            $dao =& $this->_getDao();
            $deleted = $dao->deleteTable($table->getId());
            if($deleted) {
                $deleted = $reviewerFactory->deleteTable();
                $table = null;
                $this->table = null;
            }
        }
        return $deleted;
    }

    /**
     * Update table in database
     */
    /*protected*/ function _updateTable($table) {
        $dao =& $this->_getDao();
        return $dao->updateTable($table->getId(),
                                 $table->getDescription(),
                                 $table->getStatus(),
                                 $table->getNotification(),
                                 $table->getOwner());
    }

    /**
     * Update table in the database and the internal table object
     * - status
     * - notification
     * - description
     */
    function updateTable($status, $notification, $description, $owner) {
        $table =& $this->getTable();
        if($table !== null) {
            $table->setStatus($status);
            $table->setNotification($notification);
            $table->setDescription($description);
            $table->setOwner($owner);
            return $this->_updateTable($table);
        }
        return false;
    }

    /**
     * Return an ApprovalTable object. If the parameter is 'true' (default),
     * it appends the list of reviewers to the table.
     */
    function &getTable($withReviewers = true) {
        if($this->table === null) {
            $this->table = $this->_getTable();
            if($this->table !== null) {
                if($withReviewers)  {
                    $reviewerFactory =& $this->_getReviewerFactory($this->table, $this->item);
                    $reviewerFactory->appendReviewerList();
                }
                $this->table->setCustomizable($this->customizable);
            }
        }
        return $this->table;

    }

    /**
     * @return boolean
     */
    /*abstract*/function userAccessedSinceLastUpdate($user) {}


    function getReviewStateName($state) {
        return $GLOBALS['Language']->getText('plugin_docman', 'approval_review_state_'.$state);
    }

    function getNotificationTypeName($type) {
        return $GLOBALS['Language']->getText('plugin_docman', 'details_approval_notif_'.$type);
    }

    //
    // Class accessor
    //

    function &_getDao() {
        $dao = new Docman_ApprovalTableDao(CodendiDataAccess::instance());
        return $dao;
    }

    function &_getReviewerFactory(&$table, &$item) {
        $reviewerFactory =& new Docman_ApprovalTableReviewerFactory($table, $item);
        return $reviewerFactory;
    }

}

/**
 * ApprovalTableFactory for Items (neither File or Wiki).
 *
 * The approval table for non versionned items (only Links as of today because
 * we don't support approval table for empty documents).
 * It's pretty simple as there is only one approval table bound to one item.
 */
class Docman_ApprovalTableItemFactory
extends Docman_ApprovalTableFactory {

    function Docman_ApprovalTableItemFactory($item) {
        parent::Docman_ApprovalTableFactory($item);
    }

    function newTable() {
        $table = new Docman_ApprovalTableItem();
        return $table;
    }

    /*protected*/ function _updateTableWithLastId(&$dstTable) {
        $dstTable->setItemId($this->item->getId());
    }

    /**
     * Create a new approval table
     */
    function createTable($userId, $import) {
        return $this->newTableEmpty($userId);
    }

    /**
     * Create a new entry in the database based on the given table
     *
     * @param $table ApprovalTable
     * @return int new table id
     */
    function _createTable($table) {
        $dao =& $this->_getDao();
        return $dao->createTable($table->getItemId(),
                                 $table->getOwner(),
                                 $table->getDescription(),
                                 $table->getDate(),
                                 $table->getStatus(),
                                 $table->getNotification());
    }

    function _getTable() {
        $table = null;
        $dao =& $this->_getDao();
        $dar = $dao->getTableById($this->item->getId());
        if($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->current();
            $table = $this->createTableFromRow($row);
        }
        return $table;
    }

    function userAccessedSinceLastUpdate($user) {
        return true;
    }

    //
    // Class accessor
    //

    function &_getDao() {
        $dao = new Docman_ApprovalTableItemDao(CodendiDataAccess::instance());
        return $dao;
    }
}

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
/*abstract*/ class Docman_ApprovalTableVersionnedFactory
extends Docman_ApprovalTableFactory {

    function Docman_ApprovalTableVersionnedFactory($item, $versionNumber=null) {
        parent::Docman_ApprovalTableFactory($item);
    }

    /**
     * Create $dstTable based on $srcTable.
     *
     * This method creates a new approval table as defined in $dstTable. If the
     * $type is 'copy': it imports reviewers "as is" (the very same content).
     * $type is 'reset': it imports reviewers only (not their comments).
     * Finally, the source table is Closed.
     */
    /*protected*/ function importTable($srcTable, $dstTable, $type) {
        if($srcTable->getStatus() == PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED) {
            $dstTable->setStatus(PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED);
        }

        $newTableId = $this->_createTable($dstTable);
        if($newTableId) {
            // Copy reviewers
            $reviewerFactory =& $this->_getReviewerFactory($dstTable, $this->item);
            if($type == 'copy') {
                $reviewerFactory->newTableCopy($newTableId);
            } elseif($type == 'reset') {
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
    function newTableCopy($srcTable, $dstTable, $userId) {
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
    function newTableReset($srcTable, $dstTable, $userId) {
        $dstTable->setOwner($userId);
        $dstTable->setDate(time());
        return $this->importTable($srcTable, $dstTable, 'reset');
    }

    /**
     * Create a new approval table based on the last active one.
     */
    function createTable($userId, $import) {
        $tableCreated = false;
        if($import == 'copy' || $import == 'reset' || $import == 'empty') {
            $srcTable = $this->getLastTableForItem();
            if($import == 'copy' || $import == 'reset') {
                $dstTable = clone $srcTable;
                $this->_updateTableWithLastId($dstTable);
                if($import == 'copy') {
                    $tableCreated = $this->newTableCopy($srcTable, $dstTable, $userId);
                } else {
                    $tableCreated = $this->newTableReset($srcTable, $dstTable, $userId);
                }
            } else {
                $tableCreated = $this->newTableEmpty($userId);
            }
            // Close source table
            if(!$srcTable->isClosed()) {
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
     * @return ApprovalTable object
     */
    function getLastTableForItem() {
        $table = null;
        $dao =& $this->_getDao();
        $dar = $dao->getLatestTableByItemId($this->item->getId());
        if($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->current();
            $table = $this->createTableFromRow($row);
        }
        return $table;
    }

    /**
     * Return all the approval table of for the item
     */
    function getAllApprovalTable() {
        $tableArray = array();
        $dao =& $this->_getDao();
        $dar = $dao->getApprovalTableItemId($this->item->getId(), 'app.*', '', true);
        if($dar && !$dar->isError()) {
            while($row = $dar->getRow()) {
                $tableArray[] = $this->createTableFromRow($row);
            }
        }
        return $tableArray;
    }

    /*abstract*/function getLastDocumentVersionNumber() {}
}

/**
 * ApprovalTableFactory for Files and Embedded Files.
 *
 * The code is designed to handle an approval table per file version. Once you
 * create an approval table for a File version, the next ones are, by default,
 * proposed with an approval table. However, an approval owner can decide to
 * delete one table (attached to a version).
 */
class Docman_ApprovalTableFileFactory
extends Docman_ApprovalTableVersionnedFactory {
    var $itemVersion;

    /**
     *
     */
    function Docman_ApprovalTableFileFactory($item, $versionNumber=null) {
        parent::Docman_ApprovalTableVersionnedFactory($item);

        $dao = $this->_getDao();
        $vFactory =& new Docman_VersionFactory();

        $dar = $dao->getLatestTableByItemId($item->getId(), 'ver.number');
        if($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->getRow();
            $lastVersionNumber = $row['number'];
            $lastItemVersion = $vFactory->getSpecificVersion($item, $lastVersionNumber);

            if($versionNumber !== null
               && $lastItemVersion->getNumber() != $versionNumber) {
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
    function newTable() {
        $table = new Docman_ApprovalTableFile();
        return $table;
    }

    /**
     * Create a new entry in the database based on the given table
     *
     * @param $table ApprovalTable
     * @return int new table id
     */
    function _createTable($table) {
        $dao =& $this->_getDao();
        return $dao->createTable($table->getVersionId(),
                                 $table->getOwner(),
                                 $table->getDescription(),
                                 $table->getDate(),
                                 $table->getStatus(),
                                 $table->getNotification());
    }

    /*protected*/ function _updateTableWithLastId(&$dstTable) {
        $currentVersion = $this->item->getCurrentVersion();
        $dstTable->setVersionId($currentVersion->getId());
    }

    function _getTable() {
        return $this->getTableFromVersion($this->itemVersion);
    }

    function getTableFromVersion($version) {
        $table = null;
        if($version !== null) {
            $dao =& $this->_getDao();
            $dar = $dao->getTableById($version->getId());
            if($dar && !$dar->isError() && $dar->rowCount() == 1) {
                $row = $dar->current();
                $table = $this->createTableFromRow($row);
                $table->setVersionNumber($version->getNumber());
            }
        }
        return $table;
    }

    function getLastDocumentVersionNumber() {
        $currentItemVersion = $this->item->getCurrentVersion();
        return $currentItemVersion->getNumber();
    }

    function userAccessedSinceLastUpdate($user) {
        $log = new Docman_Log();
        return $log->userAccessedSince($user->getId(), $this->item->getId(), $this->itemVersion->getDate());
    }

    //
    // Class accessor
    //

    function &_getDao() {
        $dao = new Docman_ApprovalTableFileDao(CodendiDataAccess::instance());
        return $dao;
    }

}

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
class Docman_ApprovalTableWikiFactory
extends Docman_ApprovalTableVersionnedFactory {
    var $wikiVersionId;

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
    function Docman_ApprovalTableWikiFactory($item, $versionNumber=null) {
        parent::Docman_ApprovalTableVersionnedFactory($item);

        $dao = $this->_getDao();
        $lastTableVersionId = $dao->getLastTableVersionIdByItemId($item->getId());

        if($versionNumber !== null) {
            $this->wikiVersionId = $versionNumber;

            if($versionNumber == $lastTableVersionId) {
                $this->customizable = true;
            } else {
                $this->customizable = false;
            }

        } else {
            // Works on the last available version, so is customizable.
            $this->customizable = true;

            if($lastTableVersionId !== false) {
                $this->wikiVersionId = $lastTableVersionId;
            } else {
                // If there is no table attached to the item yet, just get the list version id.
                $lastWikiVersion = $dao->getLastWikiVersionIdByItemId($item->getId());
                if($lastWikiVersion !== false) {
                    $this->wikiVersionId = $lastWikiVersion;
                } else {
                    // If the page doesn't exists yet, default to zero.
                    $this->wikiVersionId = 0;
                }
            }
        }
    }

    function newTable() {
        $table = new Docman_ApprovalTableWiki();
        return $table;
    }

    function _createTable($table) {
        $dao =& $this->_getDao();
        return $dao->createTable($table->getItemId(),
                                 $table->getWikiVersionId(),
                                 $table->getOwner(),
                                 $table->getDescription(),
                                 $table->getDate(),
                                 $table->getStatus(),
                                 $table->getNotification());
    }

    /*protected*/ function _updateTableWithLastId(&$dstTable) {
        $dao =& $this->_getDao();
        $wikiVersionId = $dao->getLastWikiVersionIdByItemId($this->item->getId());
        $dstTable->setItemId($this->item->getId());
        $dstTable->setWikiVersionId($wikiVersionId);
    }

    function _getTable() {
        return $this->getTableFromVersion($this->item->getId(), $this->wikiVersionId);
    }

    function getTableFromVersion($itemId, $version) {
        $table = null;
        if($version !== null) {
            $dao =& $this->_getDao();
            $dar = $dao->getTableById($itemId, $version);
            if($dar && !$dar->isError() && $dar->rowCount() == 1) {
                $row = $dar->current();
                $table = $this->createTableFromRow($row);
            }
        }
        return $table;
    }

    function getLastDocumentVersionNumber() {
        $dao =& $this->_getDao();
        $lastVersionId = $dao->getLastWikiVersionIdByItemId($this->item->getId());
        return $lastVersionId;
    }

    function userAccessedSinceLastUpdate($user) {
        $dao =& $this->_getDao();
        return $dao->userAccessedSince($user->getId(), $this->item->getPagename(), $this->item->getGroupId(), $this->wikiVersionId);
    }

    //
    // Class accessor
    //

    function &_getDao() {
        $dao = new Docman_ApprovalTableWikiDao(CodendiDataAccess::instance());
        return $dao;
    }
}

?>
