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

class Docman_ApprovalTableWikiDao extends Docman_ApprovalTableItemDao
{

    public function getTableById($itemId, $wikiVersionId, $fields = '*')
    {
        $sql = 'SELECT ' . $fields .
            ' FROM plugin_docman_approval' .
            ' WHERE item_id = ' . $this->da->escapeInt($itemId) .
            ' AND wiki_version_id = ' . $this->da->escapeInt($wikiVersionId);
        return $this->retrieve($sql);
    }

    /**
     * Last approval table created for the given itemId
     */
    public function getLatestTableByItemId($itemId, $fields = 'app.*')
    {
        return $this->getApprovalTableItemId($itemId, $fields, ' LIMIT 1', true);
    }

    public function getApprovalTableItemId($itemId, $fields = 'app.*', $limit = '', $tableStatus = false)
    {
        $where = 'app.item_id = ' . $this->da->escapeInt($itemId) .
            ' AND app.wiki_version_id IS NOT NULL';
        $join  = '';
        $orderBy = ' ORDER BY app.wiki_version_id DESC ';
        return $this->getTableWithStatus($tableStatus, $fields, $where, $join, $orderBy, $limit);
    }

    /**
     * Last wiki version id bound to an approval table for the given itemId
     */
    public function getLastTableVersionIdByItemId($itemId)
    {
        $sql = 'SELECT wiki_version_id ' .
            ' FROM plugin_docman_approval' .
            ' WHERE item_id = ' . $itemId .
            ' AND wiki_version_id IS NOT NULL' .
            ' ORDER BY wiki_version_id DESC' .
            ' LIMIT 1';
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->getRow();
            return $row['wiki_version_id'];
        } else {
            return false;
        }
    }

    /**
     * Last version for the wiki page referenced by the given item id.
     */
    public function getLastWikiVersionIdByItemId($itemId)
    {
        $sql = 'SELECT MAX(wv.version) version' .
            ' FROM wiki_version wv' .
            '   JOIN wiki_page wp' .
            '     ON (wp.id = wv.id)' .
            '   JOIN plugin_docman_item i' .
            '     ON (i.wiki_page = wp.pagename' .
            '         AND i.group_id = wp.group_id)' .
            ' WHERE i.item_id = ' . $itemId;
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->getRow();
            if ($row['version'] !== null) {
                return $row['version'];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function createTable($itemId, $wikiVersionId, $userId, $description, $date, $status, $notification)
    {
        $sql = 'INSERT INTO plugin_docman_approval' .
            '(item_id, wiki_version_id, table_owner, date, description, status, notification)' .
            ' VALUES (' .
            $this->da->escapeInt($itemId) . ', ' .
            $this->da->escapeInt($wikiVersionId) . ', ' .
            $this->da->escapeInt($userId) . ', ' .
            $this->da->escapeInt($date) . ', ' .
            $this->da->quoteSmart($description) . ', ' .
            $this->da->escapeInt($status) . ', ' .
            $this->da->escapeInt($notification) . ')';
        return $this->_createAndReturnId($sql);
    }

    /**
     * Did user access the wiki since the given version was published.
     */
    public function userAccessedSince($userId, $pageName, $groupId, $versionId)
    {
        $sql  = 'SELECT NULL' .
            ' FROM wiki_log wl' .
            ' WHERE pagename = ' . $this->da->quoteSmart($pageName) .
            ' AND group_id = ' . $this->da->escapeInt($groupId) .
            ' AND user_id = ' . $this->da->escapeInt($userId) .
            ' AND time > (' .
            '   SELECT mtime ' .
            '   FROM wiki_version wv' .
            '     JOIN wiki_page wp' .
            '       ON (wp.id = wv.id)' .
            '   WHERE wp.pagename = wl.pagename' .
            '   AND wp.group_id = wl.group_id' .
            '   AND wv.version = ' . $this->da->escapeInt($versionId) .
            '   )' .
            ' LIMIT 1';
        $dar = $this->retrieve($sql);
        return ($dar && !$dar->isError() && $dar->rowCount() == 1);
    }
}
