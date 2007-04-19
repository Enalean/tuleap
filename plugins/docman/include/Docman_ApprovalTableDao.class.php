<?php
/* 
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * $Id$
 */

//require_once('www/project/admin/ugroup_utils.php');
require_once('common/dao/include/DataAccessObject.class.php');

class Docman_ApprovalTableDao  extends DataAccessObject {

    function Docman_ApprovalTableDao(&$da) {
        DataAccessObject::DataAccessObject($da);
    }

    //
    // Table management
    //

    function getTableById($itemId, $fields='*') {
        $sql = sprintf('SELECT '.$fields.
                       ' FROM plugin_docman_approval'.
                       ' WHERE item_id = %d',
                       $itemId);
        return $this->retrieve($sql);
    }

    function tableExist($itemId) {
        $dar = $this->getTableById($itemId, 'NULL');
        if($dar && !$dar->isError() && $dar->rowCount() == 1) {
            return true;
        }
        return false;
    }

    function createTable($itemId, $userId, $description) {
        $sql = sprintf('INSERT INTO plugin_docman_approval'.
                       '(item_id, table_owner, date, description)'.
                       ' VALUES'.
                       '(%d, %d, %d, %s)',
                       $itemId, $userId, time(), $this->da->quoteSmart($description));
        return $this->update($sql);
    }

    function deleteTable($itemId) {
        $sql = sprintf('DELETE FROM plugin_docman_approval'.
                       ' WHERE item_id = %d',
                       $itemId);
        return $this->update($sql);
    }

    function updateTable($itemId, $description=null, $status=null, $notification=null, $description=null) {
        $_updStmt = '';
        if($description !== null) {
            $_updStmt .= sprintf('description = %s',
                                 $this->da->quoteSmart($description));
        }
        if($status !== null) {
            if($_updStmt != '') {
                $_updStmt .= ',';
            }
            $_updStmt .= sprintf('status = %d', $status); 
        }

        if($notification !== null) {
            if($_updStmt != '') {
                $_updStmt .= ',';
            }
            $_updStmt .= sprintf('notification = %d', $notification); 
        }

        if($description !== null) {
            if($_updStmt != '') {
                $_updStmt .= ',';
            }
            $_updStmt .= sprintf('description = %s', $this->da->quoteSmart($description)); 
        }

        if($_updStmt != '') {
            $_whereStmt = sprintf('item_id = %d', $itemId);

            $sql = 'UPDATE plugin_docman_approval'.
                ' SET '.$_updStmt.
                ' WHERE '.$_whereStmt;

            return $this->update($sql);
        }
        else {
            return -1;
        }
    }

    //
    // User management
    // 

    function getUgroupMembers($ugroupId, $groupId) {
        if($ugroupId > 100) {
            $sql = ugroup_db_get_members($ugroupId);
        } else {
            $sql = ugroup_db_get_dynamic_members($ugroupId, false, $groupId);
        }
        return $this->retrieve($sql);
    }

    function getReviewerList($itemId) {
        $sql = sprintf('SELECT * FROM plugin_docman_approval_user au'.
                       ' WHERE item_id = %d'.
                       ' ORDER BY rank',
                       $itemId);
        return $this->retrieve($sql);
    }

    function getReviewerById($itemId, $userId) {
        $sql = sprintf('SELECT * '.
                       ' FROM plugin_docman_approval_user au'.
                       ' WHERE item_id = %d'.
                       '  AND reviewer_id = %d',
                       $itemId, $userId);
        return $this->retrieve($sql);
    }

    function getFirstReviewerByStatus($itemId, $status) {
        $sql = sprintf('SELECT * '.
                       ' FROM plugin_docman_approval_user au'.
                       ' WHERE item_id = %d'.
                       '  AND state = %d'.
                       ' ORDER BY rank'.
                       ' LIMIT 1',
                       $itemId, $status);
        return $this->retrieve($sql);
    }

    function prepareUserRanking($itemId, $userId, $rank) {
        $newRank = null;

        $sql = sprintf('SELECT NULL'.
                       ' FROM plugin_docman_approval_user'.
                       ' WHERE item_id = %d',
                       $itemId);
        $dar = $this->retrieve($sql);
        if($dar && !$dar->isError() && $dar->rowCount() == 0) {
            // Empty
            $newRank = 0;
        }
        else {
            switch($rank) {
            case 'end':
                $sql = sprintf('SELECT MAX(rank)+1 as rank'.
                               ' FROM plugin_docman_approval_user'.
                               ' WHERE item_id = %d',
                               $itemId);
                $dar = $this->retrieve($sql);
                if($dar && !$dar->isError()) {
                    $row = $dar->current();
                    $newRank = $row['rank'];
                }
                break;

            case 'up':
            case 'down':
                if ($rank == 'down') {
                    $op    = '>';
                    $order = 'ASC';
                } else {
                    $op    = '<';
                    $order = 'DESC';
                }
                $sql = sprintf('SELECT au1.reviewer_id as reviewer_id, au1.rank as rank '.
                               ' FROM  plugin_docman_approval_user au1'.
                               '  INNER JOIN plugin_docman_approval_user au2 USING (item_id)'.
                               ' WHERE au2.item_id = %d'.
                               '  AND au2.reviewer_id = %d'.
                               '  AND au1.rank %s au2.rank'.
                               ' ORDER BY au1.rank %s'.
                               ' LIMIT 1',
                               $itemId,
                               $userId,
                               $op,
                               $order);
                $dar = $this->retrieve($sql);
                if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
                    $row = $dar->current();
                    $sql = sprintf('UPDATE plugin_docman_approval_user au1, plugin_docman_approval_user au2'.
                                   ' SET au1.rank = au2.rank, au2.rank = %d'.
                                   ' WHERE au1.item_id = %d '.
                                   '  AND au1.reviewer_id = %d'.
                                   '  AND au2.item_id = au1.item_id'.
                                   '  AND au2.reviewer_id = %d',
                                   $row['rank'],
                                   $itemId,
                                   $row['reviewer_id'],
                                   $userId);
                    //print $sql;
                    $this->update($sql);
                    $newRank = false;
                }
                break;

            case 'beg':
                $sql = sprintf('SELECT MIN(rank) as rank'.
                               ' FROM plugin_docman_approval_user'.
                               ' WHERE item_id = %d',
                               $itemId);
                $dar = $this->retrieve($sql);
                if($dar && !$dar->isError()) {
                    $row = $dar->current();
                    $rank = $row['rank'];
                }
                // no break;

            default:
                $sql = sprintf('UPDATE plugin_docman_approval_user'.
                               ' SET rank = rank + 1'.
                               ' WHERE item_id = %d'.
                               '  AND rank >= %d',
                               $itemId, $rank);
                $updated = $this->update($sql);
                if($updated) {
                    $newRank = $rank;
                }
            }
        }
        return $newRank;
    }

    function addUser($itemId, $userId) {
        $newRank = $this->prepareUserRanking($itemId, $userId, 'end');
        $sql = sprintf('INSERT INTO plugin_docman_approval_user'.
                       '(item_id, reviewer_id, rank)'.
                       ' VALUES'.
                       '(%d, %d, %d)',
                       $itemId, $userId, $newRank);
        return $this->update($sql);
    }

    function updateUser($itemId, $userId, $rank) {
        $newRank = $this->prepareUserRanking($itemId, $userId, $rank);
        if($newRank !== false) {
            $sql = sprintf('UPDATE plugin_docman_approval_user'.
                           ' SET rank = %d'.
                           ' WHERE item_id = %d'.
                           ' AND reviewer_id = %d',
                           $newRank, $itemId, $userId);
            return $this->update($sql);
        }
        else {
            return true;
        }
    }

    function delUser($itemId, $userId) {
        $sql = sprintf('DELETE FROM plugin_docman_approval_user'.
                       ' WHERE item_id = %d'.
                       ' AND reviewer_id = %d',
                       $itemId, $userId);
        return $this->update($sql);
    }

    function truncateTable($itemId) {
        $sql = sprintf('DELETE FROM plugin_docman_approval_user'.
                       ' WHERE item_id = %d',
                       $itemId);
        return $this->update($sql);
    }

    function updateReview($itemId, $userId, $date, $state, $comment, $version) {
        $_stmtDate = sprintf('date = %d,', $date);
        if($date === null) {
            $_stmtDate = 'date = NULL,';
        }
        $_stmtVersion = sprintf('version = %d', $version);
        if($version === null) {
            $_stmtVersion = 'version = NULL';
        }

        $sql = sprintf('UPDATE plugin_docman_approval_user'.
                       ' SET state = %d,'.
                       '  comment = %s,'.
                       $_stmtDate.
                       $_stmtVersion.
                       ' WHERE item_id = %d'.
                       '  AND reviewer_id = %d',
                       $state, $this->da->quoteSmart($comment),
                       $itemId, $userId);
        return $this->update($sql);
    }

    function getAllReviewsForUserByState($userId, $state) {
        $sql = sprintf('SELECT u.item_id, i.group_id, t.date, i.title, g.group_name'.
                       ' FROM plugin_docman_approval_user AS u, '.
                       '  plugin_docman_approval AS t, '.
                       '  plugin_docman_item AS i, '.
                       '  groups AS g'.
                       ' WHERE u.reviewer_id = %d'.
                       ' AND u.state = %d'.
                       ' AND t.item_id = u.item_id'.
                       ' AND t.status = %d'.
                       ' AND i.item_id = u.item_id'.
                       ' AND i.delete_date IS NULL '.
                       ' AND g.group_id = i.group_id'.
                       ' AND g.status = \'A\''.
                       ' ORDER BY i.group_id ASC, t.date ASC',
                       $userId, $state, PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED);
        return $this->retrieve($sql);
    }
     
}

?>
