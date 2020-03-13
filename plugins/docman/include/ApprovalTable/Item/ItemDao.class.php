<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

class Docman_ApprovalTableItemDao extends DataAccessObject
{

    public function getTableByItemId($item_id, $fields = '*')
    {
        $sql = 'SELECT ' . $fields .
            ' FROM plugin_docman_approval' .
            ' WHERE item_id = ' . $this->da->escapeInt($item_id);

        return $this->retrieve($sql);
    }

    public function tableExist($tableId)
    {
        $dar = $this->getTableById($tableId, 'NULL');
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            return true;
        }
        return false;
    }

    /*static*/ public function getTableStatusFields($table = 'app_u')
    {
        $fields = 'COUNT(' . $table . '.reviewer_id) AS nb_reviewers, ' .
            'COUNT(IF(' . $table . '.state = ' . PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED . ',1,NULL)) AS rejected, ' .
            'COUNT(IF(' . $table . '.state = ' . PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED . ',1,NULL)) AS nb_approved, ' .
            'COUNT(IF(' . $table . '.state = ' . PLUGIN_DOCMAN_APPROVAL_STATE_DECLINED . ',1,NULL)) AS nb_declined';
        return $fields;
    }

    /*static*/ public function getTableStatusJoin($tableUser = 'app_u', $tableApproval = 'app')
    {
        $join = 'plugin_docman_approval_user ' . $tableUser
            . ' ON (' . $tableUser . '.table_id = ' . $tableApproval . '.table_id) ';
        return $join;
    }

    /*static*/ public function getTableStatusGroupBy($table = 'app_u')
    {
        $groupBy  = $table . '.table_id ';
        return $groupBy;
    }

    public function getTableWithStatus($status, $fields, $where, $join = '', $orderBy = '', $limit = '')
    {
        $groupBy = '';
        if ($status) {
            $fields  .= ',' . $this->getTableStatusFields();
            $join    .= ' LEFT JOIN ' . $this->getTableStatusJoin();
            $groupBy  = ' GROUP BY ' . $this->getTableStatusGroupBy();
        }

        $sql = ' SELECT ' . $fields .
            ' FROM plugin_docman_approval app' .
            $join .
            ' WHERE ' . $where .
            $groupBy .
            $orderBy .
            $limit;
        return $this->retrieve($sql);
    }

    public function createTable($field, $id, $userId, $description, $date, $status, $notification)
    {
        $sql = 'INSERT INTO plugin_docman_approval' .
            '(' . $field . ', table_owner, date, description, status, notification)' .
            ' VALUES (' .
            $this->da->escapeInt($id) . ', ' .
            $this->da->escapeInt($userId) . ', ' .
            $this->da->escapeInt($date) . ', ' .
            $this->da->quoteSmart($description) . ', ' .
            $this->da->escapeInt($status) . ', ' .
            $this->da->escapeInt($notification) . ')';
        return $this->_createAndReturnId($sql);
    }

    public function deleteTable($tableId)
    {
        $sql = 'DELETE FROM plugin_docman_approval' .
            ' WHERE table_id = ' . $this->da->escapeInt($tableId);
        return $this->update($sql);
    }

    public function updateTable($tableId, $description = null, $status = null, $notification = null, $notificationOccurence = null, $owner = null)
    {
        $_updStmt = '';
        if ($description !== null) {
            $_updStmt .= sprintf(
                'description = %s',
                $this->da->quoteSmart($description)
            );
        }
        if ($status !== null) {
            if ($_updStmt != '') {
                $_updStmt .= ',';
            }
            $_updStmt .= sprintf('status = %d', $status);
        }

        if ($notification !== null) {
            if ($_updStmt != '') {
                $_updStmt .= ',';
            }
            $_updStmt .= sprintf('notification = %d', $notification);
        }

        if ($notificationOccurence !== null) {
            if ($_updStmt != '') {
                $_updStmt .= ',';
            }
            $_updStmt .= sprintf('notification_occurence = %d', $notificationOccurence);
        }

        if ($owner !== null) {
            if ($_updStmt != '') {
                $_updStmt .= ',';
            }
            $_updStmt .= 'table_owner = ' . $this->da->escapeInt($owner);
        }

        if ($_updStmt != '') {
            $_whereStmt = 'table_id = ' . $this->da->escapeInt($tableId);

            $sql = 'UPDATE plugin_docman_approval' .
                ' SET ' . $_updStmt .
                ' WHERE ' . $_whereStmt;

            $res = $this->update($sql);
            if ($res && $this->da->affectedRows() > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return -1;
        }
    }

    public function _createAndReturnId($sql)
    {
        $inserted = $this->update($sql);
        if ($inserted) {
            $dar = $this->retrieve("SELECT LAST_INSERT_ID() AS id");
            if ($row = $dar->getRow()) {
                $inserted = $row['id'];
            } else {
                $inserted = $dar->isError();
            }
        }
        return $inserted;
    }

    /**
     * Get all approval tables that may send reminder notification
     *
     * @return DataAccessResult
     */
    public function getTablesForReminder()
    {
        $sql  = 'SELECT plugin_docman_approval.*, plugin_docman_link_version.item_id AS link_item_id
                 FROM plugin_docman_approval
                 LEFT JOIN plugin_docman_link_version ON (plugin_docman_link_version.id = plugin_docman_approval.link_version_id)
                 WHERE status = 1
                   AND notification != 0
                   AND notification_occurence != 0';
        return $this->retrieve($sql);
    }
}
