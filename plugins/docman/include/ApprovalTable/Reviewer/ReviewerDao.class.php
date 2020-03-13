<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
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

class Docman_ApprovalTableReviewerDao extends Docman_ApprovalTableItemDao
{
    public function getUgroupMembers($ugroupId, $groupId)
    {
        require_once __DIR__ . '/../../../../../src/www/project/admin/ugroup_utils.php';
        if ($ugroupId > 100) {
            $sql = ugroup_db_get_members($ugroupId);
        } else {
            $sql = ugroup_db_get_dynamic_members($ugroupId, false, $groupId);
        }
        if ($sql === null) {
            return false;
        }
        return $this->retrieve($sql);
    }

    public function getReviewerList($tableId)
    {
        $sql = sprintf(
            'SELECT * FROM plugin_docman_approval_user au' .
                       ' WHERE table_id = %d' .
                       ' ORDER BY rank',
            $tableId
        );
        return $this->retrieve($sql);
    }

    public function getReviewerById($tableId, $userId)
    {
        $sql = sprintf(
            'SELECT * ' .
                       ' FROM plugin_docman_approval_user au' .
                       ' WHERE table_id = %d' .
                       '  AND reviewer_id = %d',
            $tableId,
            $userId
        );
        return $this->retrieve($sql);
    }

    public function getFirstReviewerByStatus($tableId, $status)
    {
        if (is_array($status)) {
            $_status = array_map("intval", $status);
            $state = 'state IN (' . implode(',', $status) . ')';
        } else {
            $state = 'state = ' . intval($status);
        }

        $sql = sprintf(
            'SELECT * ' .
                       ' FROM plugin_docman_approval_user au' .
                       ' WHERE table_id = %d' .
                       '  AND ' . $state .
                       ' ORDER BY rank' .
                       ' LIMIT 1',
            $tableId
        );
        return $this->retrieve($sql);
    }

    public function prepareUserRanking($tableId, $userId, $rank)
    {
        return parent::prepareRanking('plugin_docman_approval_user', $userId, $tableId, $rank, 'reviewer_id', 'table_id');
    }

    public function addUser($tableId, $userId)
    {
        $newRank = $this->prepareUserRanking($tableId, $userId, 'end');
        $sql = sprintf(
            'INSERT INTO plugin_docman_approval_user' .
                       '(table_id, reviewer_id, rank)' .
                       ' VALUES' .
                       '(%d, %d, %d)',
            $tableId,
            $userId,
            $newRank
        );
        return $this->update($sql);
    }

    public function updateUser($tableId, $userId, $rank)
    {
        $newRank = $this->prepareUserRanking($tableId, $userId, $rank);
        if ($newRank !== false) {
            $sql = sprintf(
                'UPDATE plugin_docman_approval_user' .
                           ' SET rank = %d' .
                           ' WHERE table_id = %d' .
                           ' AND reviewer_id = %d',
                $newRank,
                $tableId,
                $userId
            );
            return $this->update($sql);
        } else {
            return true;
        }
    }

    public function delUser($tableId, $userId)
    {
        $sql = sprintf(
            'DELETE FROM plugin_docman_approval_user' .
                       ' WHERE table_id = %d' .
                       ' AND reviewer_id = %d',
            $tableId,
            $userId
        );
        return $this->update($sql);
    }

    public function truncateTable($tableId)
    {
        $sql = sprintf(
            'DELETE FROM plugin_docman_approval_user' .
                       ' WHERE table_id = %d',
            $tableId
        );
        return $this->update($sql);
    }

    public function updateReview($tableId, $userId, $date, $state, $comment, $version)
    {
        $_stmtDate = sprintf('date = %d,', $date);
        if ($date === null) {
            $_stmtDate = 'date = NULL,';
        }
        $_stmtVersion = sprintf('version = %d', $version);
        if ($version === null) {
            $_stmtVersion = 'version = NULL';
        }

        $sql = sprintf(
            'UPDATE plugin_docman_approval_user' .
                       ' SET state = %d,' .
                       '  comment = %s,' .
                       $_stmtDate .
                       $_stmtVersion .
                       ' WHERE table_id = %d' .
                       '  AND reviewer_id = %d',
            $state,
            $this->da->quoteSmart($comment),
            $tableId,
            $userId
        );
        return $this->update($sql);
    }

    public function copyReviews($srcTableId, $dstTableId)
    {
        return $this->_copyReviewers($srcTableId, $dstTableId, 'date', 'state', 'comment', 'version');
    }

    public function copyReviewers($srcTableId, $dstTableId)
    {
        return $this->_copyReviewers($srcTableId, $dstTableId, 'NULL', 0, "''", 'NULL');
    }

    public function _copyReviewers($srcTableId, $dstTableId, $date, $state, $comment, $version)
    {
        $sql = 'INSERT INTO plugin_docman_approval_user' .
            '(table_id, reviewer_id, rank, date, state, comment, version) ' .
            'SELECT ' .
            $this->da->escapeInt($dstTableId) . ',' .
            ' reviewer_id, rank, ' . $date . ', ' . $state . ', ' .
            $comment . ', ' . $version .
            ' FROM plugin_docman_approval_user' .
            ' WHERE table_id = ' . $srcTableId;
        return $this->update($sql);
    }

    public function getAllReviewsForUserByState($userId, $state)
    {
        // Item
        $sql_item = 'SELECT u.table_id, i.item_id, i.group_id, t.date, i.title, g.group_name' .
            ' FROM plugin_docman_approval_user u ' .
            '   JOIN plugin_docman_approval t' .
            '     ON (t.table_id = u.table_id)' .
            '   JOIN plugin_docman_item AS i' .
            '     ON (i.item_id = t.item_id)' .
            '   JOIN groups g' .
            '     ON (g.group_id = i.group_id)' .
            ' WHERE u.reviewer_id = ' . $this->da->escapeInt($userId) .
            ' AND u.state = ' . $this->da->escapeInt($state) .
            ' AND t.status = ' . PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED .
            ' AND t.item_id IS NOT NULL' .
            ' AND ' . Docman_ItemDao::getCommonExcludeStmt('i') .
            ' AND g.status = \'A\'';

        // Version
        $sql_ver = 'SELECT u.table_id, i.item_id, i.group_id, t.date, i.title, g.group_name' .
            ' FROM plugin_docman_approval_user u ' .
            '   JOIN plugin_docman_approval t' .
            '     ON (t.table_id = u.table_id)' .
            '   JOIN plugin_docman_version v' .
            '     ON (v.id = t.version_id)' .
            '   JOIN plugin_docman_item AS i' .
            '     ON (i.item_id = v.item_id)' .
            '   JOIN groups g' .
            '     ON (g.group_id = i.group_id)' .
            ' WHERE u.reviewer_id = ' . $this->da->escapeInt($userId) .
            ' AND u.state = ' . $this->da->escapeInt($state) .
            ' AND t.status = ' . PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED .
            ' AND t.version_id IS NOT NULL' .
            ' AND ' . Docman_ItemDao::getCommonExcludeStmt('i') .
            ' AND g.status = \'A\'';

        $sql = '(' . $sql_item . ') UNION ALL (' . $sql_ver . ') ORDER BY group_name ASC, date ASC';
        //        print $sql;
        return $this->retrieve($sql);
    }

    public function getAllApprovalTableForUser($userId)
    {
        // Item
        $sql_item = 'SELECT t.table_id, i.item_id, i.group_id, t.date, i.title, g.group_name, t.status' .
            ',' . parent::getTableStatusFields() .
            ' FROM plugin_docman_approval t' .
            '  LEFT JOIN ' . parent::getTableStatusJoin('app_u', 't') .
            '  JOIN plugin_docman_item i ON (i.item_id = t.item_id)' .
            '  JOIN groups g ON (g.group_id = i.group_id)' .
            ' WHERE t.table_owner = ' . $this->da->escapeInt($userId) .
            ' AND t.status IN (' . PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED . ', ' . PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED . ')' .
            ' AND ' . Docman_ItemDao::getCommonExcludeStmt('i') .
            ' AND g.status = \'A\'' .
            ' GROUP BY ' . parent::getTableStatusGroupBy('t');

        // Version
        $sql_ver = 'SELECT t.table_id, i.item_id, i.group_id, t.date, i.title, g.group_name, t.status' .
            ',' . parent::getTableStatusFields() .
            ' FROM plugin_docman_approval t' .
            '  LEFT JOIN ' . parent::getTableStatusJoin('app_u', 't') .
            '   JOIN plugin_docman_version v' .
            '     ON (v.id = t.version_id)' .
            '   JOIN plugin_docman_item AS i' .
            '     ON (i.item_id = v.item_id)' .
            '   JOIN groups g' .
            '     ON (g.group_id = i.group_id)' .
            ' WHERE t.table_owner = ' . $this->da->escapeInt($userId) .
            ' AND t.status IN (' . PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED . ', ' . PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED . ')' .
            ' AND ' . Docman_ItemDao::getCommonExcludeStmt('i') .
            ' AND g.status = \'A\'' .
            ' GROUP BY ' . parent::getTableStatusGroupBy('t');

        $sql = '(' . $sql_item . ') UNION ALL (' . $sql_ver . ') ORDER BY group_name ASC, date ASC';
        //echo $sql;
        return $this->retrieve($sql);
    }
}
