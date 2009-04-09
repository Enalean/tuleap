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

require_once('common/dao/include/DataAccessObject.class.php');
require_once('Docman_ItemDao.class.php');

/*abstract*/ class Docman_ApprovalTableDao extends DataAccessObject {

    function Docman_ApprovalTableDao(&$da) {
        DataAccessObject::DataAccessObject($da);
    }

    function getTableByTableId($tableId, $fields='*') {
        $sql = 'SELECT '.$fields.
            ' FROM plugin_docman_approval'.
            ' WHERE table_id = '.$this->da->escapeInt($tableId);
        return $this->retrieve($sql);
    }

    function tableExist($tableId) {
        $dar = $this->getTableById($tableId, 'NULL');
        if($dar && !$dar->isError() && $dar->rowCount() == 1) {
            return true;
        }
        return false;
    }

    /*static*/ function getTableStatusFields($table='app_u') {
        $fields = 'COUNT('.$table.'.reviewer_id) AS nb_reviewers, '.
            'COUNT(IF('.$table.'.state = '.PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED.',1,NULL)) AS rejected, '.
            'COUNT(IF('.$table.'.state = '.PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED.',1,NULL)) AS nb_approved, '.
            'COUNT(IF('.$table.'.state = '.PLUGIN_DOCMAN_APPROVAL_STATE_DECLINED.',1,NULL)) AS nb_declined';
        return $fields;
    }

    /*static*/ function getTableStatusJoin($tableUser='app_u', $tableApproval='app') {
        $join = 'plugin_docman_approval_user '.$tableUser
            .' ON ('.$tableUser.'.table_id = '.$tableApproval.'.table_id) ';
        return $join;
    }

    /*static*/ function getTableStatusGroupBy($table='app_u') {
        $groupBy  = $table.'.table_id ';
        return $groupBy;
    }

    function getTableWithStatus($status, $fields, $where, $join='', $orderBy='', $limit='') {
        $groupBy = '';
        if($status) {
            $fields  .= ','.$this->getTableStatusFields();
            $join    .= ' LEFT JOIN '.$this->getTableStatusJoin();
            $groupBy  = ' GROUP BY '.$this->getTableStatusGroupBy();
        }

        $sql = ' SELECT '.$fields.
            ' FROM plugin_docman_approval app'.
            $join.
            ' WHERE '.$where.
            $groupBy.
            $orderBy.
            $limit;
        return $this->retrieve($sql);
    }

    function createTable($field, $id, $userId, $description, $date, $status, $notification) {
        $sql = 'INSERT INTO plugin_docman_approval'.
            '('.$field.', table_owner, date, description, status, notification)'.
            ' VALUES ('.
            $this->da->escapeInt($id).', '.
            $this->da->escapeInt($userId).', '.
            $this->da->escapeInt($date).', '.
            $this->da->quoteSmart($description).', '.
            $this->da->escapeInt($status).', '.
            $this->da->escapeInt($notification).')';
        return $this->_createAndReturnId($sql);
    }

    function deleteTable($tableId) {
        $sql = 'DELETE FROM plugin_docman_approval'.
            ' WHERE table_id = '.$this->da->escapeInt($tableId);
        return $this->update($sql);
    }

    function updateTable($tableId, $description=null, $status=null, $notification=null, $owner=null) {
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

        if($owner !== null) {
            if($_updStmt != '') {
                $_updStmt .= ',';
            }
            $_updStmt .= 'table_owner = '.$this->da->escapeInt($owner);
        }

        if($_updStmt != '') {
            $_whereStmt = 'table_id = '.$this->da->escapeInt($tableId);

            $sql = 'UPDATE plugin_docman_approval'.
                ' SET '.$_updStmt.
                ' WHERE '.$_whereStmt;

            $res = $this->update($sql);
            if($res && $this->da->affectedRows() > 0) {
                return true;
            } else {
                return false;
            }
        }
        else {
            return -1;
        }
    }

    function _createAndReturnId($sql) {
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
}

/**
 *
 */
class Docman_ApprovalTableItemDao extends Docman_ApprovalTableDao {

    function Docman_ApprovalTableItemDao(&$da) {
        parent::Docman_ApprovalTableDao($da);
    }

    function getTableById($itemId, $fields='*') {
        $sql = 'SELECT '.$fields.
            ' FROM plugin_docman_approval'.
            ' WHERE item_id = '.$this->da->escapeInt($itemId);
        return $this->retrieve($sql);
    }

    function createTable($itemId, $userId, $description, $date, $status, $notification) {
        return parent::createTable('item_id', $itemId, $userId, $description, $date, $status, $notification);
    }
}

/**
 *
 */
class Docman_ApprovalTableFileDao extends Docman_ApprovalTableDao {

    function Docman_ApprovalTableFileDao(&$da) {
        parent::Docman_ApprovalTableDao($da);
    }

    function getTableById($versionId, $fields='*') {
        $sql = 'SELECT '.$fields.
            ' FROM plugin_docman_approval'.
            ' WHERE version_id = '.$this->da->escapeInt($versionId);
        return $this->retrieve($sql);
    }

    function getTableByItemId($itemId, $fields='*') {
        return $this->getLatestTableByItemId($itemId, $fields);
    }

    function getLatestTableByItemId($itemId, $fields='app.*') {
        return $this->getApprovalTableItemId($itemId, $fields, ' LIMIT 1');
    }

    function getApprovalTableItemId($itemId, $fields='app.*', $limit='', $tableStatus=false) {
        $fields .= ', ver.number as version_number';
        $where = ' ver.item_id = '.$this->da->escapeInt($itemId).
            ' AND app.wiki_version_id IS NULL';
        $join = ' JOIN plugin_docman_version ver ON (ver.id = app.version_id)';
        $orderBy = ' ORDER BY ver.number DESC ';

        return $this->getTableWithStatus($tableStatus, $fields, $where, $join, $orderBy, $limit);
    }
    function createTable($versionId, $userId, $description, $date, $status, $notification) {
        return parent::createTable('version_id', $versionId, $userId, $description, $date, $status, $notification);
    }

}

/**
 *
 */
class Docman_ApprovalTableWikiDao extends Docman_ApprovalTableDao {

    function Docman_ApprovalTableWikiDao(&$da) {
        parent::Docman_ApprovalTableDao($da);
    }

    function getTableById($itemId, $wikiVersionId, $fields='*') {
        $sql = 'SELECT '.$fields.
            ' FROM plugin_docman_approval'.
            ' WHERE item_id = '.$this->da->escapeInt($itemId).
            ' AND wiki_version_id = '.$this->da->escapeInt($wikiVersionId);
        return $this->retrieve($sql);
    }

    /**
     * Last approval table created for the given itemId
     */
    function getLatestTableByItemId($itemId, $fields='app.*') {
        return $this->getApprovalTableItemId($itemId, $fields, ' LIMIT 1');
    }

    function getApprovalTableItemId($itemId, $fields='app.*', $limit='', $tableStatus=false) {
        $where = 'app.item_id = '.$this->da->escapeInt($itemId).
            ' AND app.wiki_version_id IS NOT NULL';
        $join  = '';
        $orderBy = ' ORDER BY app.wiki_version_id DESC ';
        return $this->getTableWithStatus($tableStatus, $fields, $where, $join, $orderBy, $limit);
    }

    /**
     * Last wiki version id bound to an approval table for the given itemId
     */
    function getLastTableVersionIdByItemId($itemId) {
        $sql = 'SELECT wiki_version_id '.
            ' FROM plugin_docman_approval'.
            ' WHERE item_id = '.$itemId.
            ' AND wiki_version_id IS NOT NULL'.
            ' ORDER BY wiki_version_id DESC'.
            ' LIMIT 1';
        $dar = $this->retrieve($sql);
        if($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->getRow();
            return $row['wiki_version_id'];
        } else {
            return false;
        }
    }

    /**
     * Last version for the wiki page referenced by the given item id.
     */
    function getLastWikiVersionIdByItemId($itemId) {
        $sql = 'SELECT MAX(wv.version) version'.
            ' FROM wiki_version wv'.
            '   JOIN wiki_page wp'.
            '     ON (wp.id = wv.id)'.
            '   JOIN plugin_docman_item i'.
            '     ON (i.wiki_page = wp.pagename'.
            '         AND i.group_id = wp.group_id)'.
            ' WHERE i.item_id = '.$itemId;
        $dar = $this->retrieve($sql);
        if($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->getRow();
            if($row['version'] !== null) {
                return $row['version'];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    function createTable($itemId, $wikiVersionId, $userId, $description, $date, $status, $notification) {
        $sql = 'INSERT INTO plugin_docman_approval'.
            '(item_id, wiki_version_id, table_owner, date, description, status, notification)'.
            ' VALUES ('.
            $this->da->escapeInt($itemId).', '.
            $this->da->escapeInt($wikiVersionId).', '.
            $this->da->escapeInt($userId).', '.
            $this->da->escapeInt($date).', '.
            $this->da->quoteSmart($description).', '.
            $this->da->escapeInt($status).', '.
            $this->da->escapeInt($notification).')';
        return $this->_createAndReturnId($sql);
    }

    /**
     * Did user access the wiki since the given version was published.
     */
    function userAccessedSince($userId, $pageName, $groupId, $versionId) {
        $sql  = 'SELECT NULL'.
            ' FROM wiki_log wl'.
            ' WHERE pagename = '.$this->da->quoteSmart($pageName).
            ' AND group_id = '.$this->da->escapeInt($groupId).
            ' AND user_id = '.$this->da->escapeInt($userId).
            ' AND time > ('.
            '   SELECT mtime '.
            '   FROM wiki_version wv'.
            '     JOIN wiki_page wp'.
            '       ON (wp.id = wv.id)'.
            '   WHERE wp.pagename = wl.pagename'.
            '   AND wp.group_id = wl.group_id'.
            '   AND wv.version = '.$this->da->escapeInt($versionId).
            '   )'.
            ' LIMIT 1';
        $dar = $this->retrieve($sql);
        return ($dar && !$dar->isError() && $dar->rowCount() == 1);
    }
}

?>
