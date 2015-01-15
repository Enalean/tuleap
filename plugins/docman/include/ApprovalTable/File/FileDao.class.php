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

class Docman_ApprovalTableFileDao extends DataAccessObject {

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
}
