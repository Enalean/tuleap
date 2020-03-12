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
abstract class Docman_ApprovalTableFactory
{
    public $item;
    public $table;
    public $customizable;

    public function __construct(Docman_Item $item)
    {
        $this->item = $item;
        $this->table = null;
        $this->customizable = true;
    }

    /**
     * Return the ApprovalTableReviewerFactory that correspond to the item.
     */
    public static function getReviewerFactoryFromItem($item)
    {
        $appTableFactory = Docman_ApprovalTableFactoriesFactory::getFromItem($item);
        if ($appTableFactory !== null) {
            $table = $appTableFactory->getTable();
            return $appTableFactory->_getReviewerFactory($table, $item);
        }
    }

    /**
     * Update dst table object with the id of the latest version of the doc.
     */
    abstract protected function _updateTableWithLastId($dstTable);

    /**
     * Create a new entry in the database based on the given table
     *
     * @param $table ApprovalTable
     * @return int new table id
     */
    abstract protected function _createTable($table);

    /**
     * Create a new empty approbal table in database.
     */
    public function newTableEmpty($userId)
    {
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
    abstract public function createTable($userId, $import);

    /**
     * Create a new table object based on $row content.
     */
    public function createTableFromRow($row)
    {
        $table = $this->newTable();
        $table->initFromRow($row);
        return $table;
    }

    /**
     * Return the table object that correspond to the factory defaults.
     */
    abstract protected function _getTable();

    /**
     * Return true if their is an approval table for the item the approval
     * table is based on.
     */
    public function tableExistsForItem()
    {
        return ($this->getTable() !== null);
    }

    /**
     * Delete the approval table and all the reviewers that belong to.
     */
    public function deleteTable()
    {
        $deleted = false;
        $table = $this->getTable();
        if ($table !== null) {
            $reviewerFactory = $this->_getReviewerFactory($table, $this->item);
            $dao = $this->_getDao();
            $deleted = $dao->deleteTable($table->getId());
            if ($deleted) {
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
    protected function _updateTable($table)
    {
        $dao = $this->_getDao();
        return $dao->updateTable(
            $table->getId(),
            $table->getDescription(),
            $table->getStatus(),
            $table->getNotification(),
            $table->getNotificationOccurence(),
            $table->getOwner()
        );
    }

    /**
     * Update table in the database and the internal table object
     * - status
     * - notification
     * - notificationOccurence
     * - description
     */
    public function updateTable($status, $notification, $notificationOccurence, $description, $owner)
    {
        $table = $this->getTable();
        if ($table !== null) {
            $table->setStatus($status);
            $table->setNotification($notification);
            $table->setNotificationOccurence($notificationOccurence);
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
    public function getTable($withReviewers = true)
    {
        if ($this->table === null) {
            $this->table = $this->_getTable();
            if ($this->table !== null) {
                if ($withReviewers) {
                    $reviewerFactory = $this->_getReviewerFactory($this->table, $this->item);
                    $reviewerFactory->appendReviewerList();
                }
                $this->table->setCustomizable($this->customizable);
            }
        }
        return $this->table;
    }

    /**
     * @return bool
     */
    abstract public function userAccessedSinceLastUpdate($user);


    public function getReviewStateName($state)
    {
        switch ($state) {
            case PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET:
                return dgettext('tuleap-docman', 'Not Yet');
            case PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED:
                return dgettext('tuleap-docman', 'Approved');
            case PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED:
                return dgettext('tuleap-docman', 'Rejected');
            case PLUGIN_DOCMAN_APPROVAL_STATE_COMMENTED:
                return dgettext('tuleap-docman', 'Comment only');
            case PLUGIN_DOCMAN_APPROVAL_STATE_DECLINED:
                return dgettext('tuleap-docman', 'Will not review');
        }
        return '';
    }

    public function getNotificationTypeName($type)
    {
        switch ($type) {
            case PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED:
                return dgettext('tuleap-docman', 'Disabled');
            case PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED:
                return dgettext('tuleap-docman', 'All at once');
            case PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED:
                return dgettext('tuleap-docman', 'Sequential');
        }
        return '';
    }

    // Class accessor
    protected function _getDao()
    {
        return new Docman_ApprovalTableItemDao(CodendiDataAccess::instance());
    }

    public function _getReviewerFactory($table, $item)
    {
        return new Docman_ApprovalTableReviewerFactory($table, $item);
    }
}
