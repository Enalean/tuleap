<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 *  Data Access Object for Docman_VersionDao
 */
class Docman_VersionDao extends DataAccessObject
{
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    public function searchAll()
    {
        $sql = "SELECT * FROM plugin_docman_version";
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_VersionDao by Id
    * @return DataAccessResult
    */
    public function searchById($id, $table = 'plugin_docman_version')
    {
        $sql = sprintf(
            "SELECT item_id, number, user_id, label, changelog, filename, filesize, filetype, path FROM %s WHERE id = %s",
            $table,
            $this->da->quoteSmart($id)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_VersionDao by ItemId
    * @return DataAccessResult
    */
    public function searchByItemId($itemId)
    {
        $sql = sprintf(
            "SELECT id, number, item_id, user_id, label, changelog, date, filename, filesize, filetype, path FROM plugin_docman_version WHERE item_id = %s ORDER BY number DESC",
            $this->da->quoteSmart($itemId)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_VersionDao by Number
    * @return DataAccessResult
    */
    public function searchByNumber($item_id, $number)
    {
        $sql = sprintf(
            "SELECT id, item_id, number, user_id, label, changelog, date, filename, filesize, filetype, path FROM plugin_docman_version WHERE number = %s AND item_id = %s",
            $this->da->quoteSmart($number),
            $this->da->quoteSmart($item_id)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_VersionDao by UserId
    * @return DataAccessResult
    */
    public function searchByUserId($userId)
    {
        $sql = sprintf(
            "SELECT id, item_id, number, label, changelog, date, filename, filesize, filetype, path FROM plugin_docman_version WHERE user_id = %s",
            $this->da->quoteSmart($userId)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_VersionDao by Label
    * @return DataAccessResult
    */
    public function searchByLabel($label)
    {
        $sql = sprintf(
            "SELECT id, item_id, number, user_id, changelog, date, filename, filesize, filetype, path FROM plugin_docman_version WHERE label = %s",
            $this->da->quoteSmart($label)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_VersionDao by Changelog
    * @return DataAccessResult
    */
    public function searchByChangelog($changelog)
    {
        $sql = sprintf(
            "SELECT id, item_id, number, user_id, label, date, filename, filesize, filetype, path FROM plugin_docman_version WHERE changelog = %s",
            $this->da->quoteSmart($changelog)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_VersionDao by Date
    * @return DataAccessResult
    */
    public function searchByDate($date)
    {
        $sql = sprintf(
            "SELECT id, item_id, number, user_id, label, changelog, filename, filesize, filetype, path FROM plugin_docman_version WHERE date = %s",
            $this->da->quoteSmart($date)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_VersionDao by Filename
    * @return DataAccessResult
    */
    public function searchByFilename($filename)
    {
        $sql = sprintf(
            "SELECT id, item_id, number, user_id, label, changelog, date, filesize, filetype, path FROM plugin_docman_version WHERE filename = %s",
            $this->da->quoteSmart($filename)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_VersionDao by Filesize
    * @return DataAccessResult
    */
    public function searchByFilesize($filesize)
    {
        $sql = sprintf(
            "SELECT id, item_id, number, user_id, label, changelog, date, filename, filetype, path FROM plugin_docman_version WHERE filesize = %s",
            $this->da->quoteSmart($filesize)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_VersionDao by Filetype
    * @return DataAccessResult
    */
    public function searchByFiletype($filetype)
    {
        $sql = sprintf(
            "SELECT id, item_id, number, user_id, label, changelog, date, filename, filesize, path FROM plugin_docman_version WHERE filetype = %s",
            $this->da->quoteSmart($filetype)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches Docman_VersionDao by Path
    * @return DataAccessResult
    */
    public function searchByPath($path)
    {
        $sql = sprintf(
            "SELECT id, item_id, number, user_id, label, changelog, date, filename, filesize, filetype FROM plugin_docman_version WHERE path = %s",
            $this->da->quoteSmart($path)
        );
        return $this->retrieve($sql);
    }

    /**
     * Find the greater version number between plugin_docman_version and plugin_docman_version_deleted tables and add 1
     *
     * Return false if no previous version found
     *
     * @param int $itemId
     *
     * @return int|false
     */
    public function searchNextVersionNumber($itemId)
    {
        $sql = 'SELECT * FROM' .
               ' (SELECT MAX(number) AS v_max FROM plugin_docman_version WHERE item_id = ' . $this->da->escapeInt($itemId) . ') AS v,' .
               ' (SELECT MAX(number) AS d_max FROM plugin_docman_version_deleted WHERE item_id = ' . $this->da->escapeInt($itemId) . ') AS d';
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError()) {
            $row = $dar->getRow();
            if ($row['v_max'] === null && $row['d_max'] === null) {
                return false;
            } else {
                return max($row) + 1;
            }
        }
        return false;
    }

    /**
    * create a row in the table plugin_docman_version
    * @return true or id(auto_increment) if there is no error
    */
    public function create($item_id, $number, $user_id, $label, $changelog, $date, $filename, $filesize, $filetype, $path)
    {
        $sql = sprintf(
            "INSERT INTO plugin_docman_version (item_id, number, user_id, label, changelog, date, filename, filesize, filetype, path) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
            $this->da->quoteSmart($item_id),
            $this->da->quoteSmart($number),
            $this->da->quoteSmart($user_id),
            $this->da->quoteSmart($label, array('force_string' => true)),
            $this->da->quoteSmart($changelog),
            $this->da->quoteSmart($date),
            $this->da->quoteSmart($filename),
            $this->da->quoteSmart($filesize),
            $this->da->quoteSmart($filetype),
            $this->da->quoteSmart($path)
        );
        return $this->_createAndReturnId($sql);
    }
    public function createFromRow($row)
    {
        if (!isset($row['date']) || $row['date'] == '') {
            $row['date'] = time();
        }
        $arg    = array();
        $values = array();
        $params = array('force_string' => false);
        $cols   = array('item_id', 'number', 'user_id', 'label', 'changelog', 'date', 'filename', 'filesize', 'filetype', 'path');
        foreach ($row as $key => $value) {
            if (in_array($key, $cols)) {
                $arg[]    = $key;
                $params['force_string'] = ($key == 'label');
                $values[] = $this->da->quoteSmart($value, $params);
            }
        }
        if (count($arg)) {
            $sql = 'INSERT INTO plugin_docman_version '
                . '(' . implode(', ', $arg) . ')'
                . ' VALUES (' . implode(', ', $values) . ')';
            return $this->_createAndReturnId($sql);
        } else {
            return false;
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
     * Update the path for stored files when a project is being renamed
     * @param  String  $docman_path
     * @param  Project $project
     * @param  String  $new_name
     * @return bool
     */
    public function renameProject($docman_path, $project, $new_name)
    {
        $sql_update = 'UPDATE plugin_docman_version ' .
                      'SET path = REPLACE (path,' . $this->da->quoteSmart($docman_path . $project->getUnixName(true) . '/') . ' ,' . $this->da->quoteSmart($docman_path . strtolower($new_name) . '/') . ') ' .
                      'WHERE path LIKE ' . $this->da->quoteLikeValueSurround($docman_path . $project->getUnixName(true) . '/');
        return $this->update($sql_update);
    }

    /**
     * Delete given version of document and save the entry on plugin_docman_version_deleted
     * in order to ease the restore later
     *
     * @param int $itemId
     * @param int $number
     *
     * @return bool
     */
    public function deleteSpecificVersion($itemId, $number)
    {
        $sql = 'INSERT INTO plugin_docman_version_deleted (id, item_id, number, user_id, label, ' .
                        ' changelog, create_date,  ' .
                        ' filename, filesize, filetype, path, delete_date) ' .
                        ' SELECT id, item_id, number, user_id, label, ' .
                        ' changelog, date, ' .
                        ' filename, filesize, filetype, path , ' . $_SERVER['REQUEST_TIME'] . ' FROM plugin_docman_version ' .
                        ' WHERE item_id=' . $this->da->quoteSmart($itemId) . ' AND number=' . $this->da->quoteSmart($number);
        if ($this->update($sql)) {
            $sql = 'DELETE FROM plugin_docman_version WHERE item_id=' . $this->da->quoteSmart($itemId) . ' AND number=' . $this->da->quoteSmart($number);
            return $this->update($sql);
        }
        return false;
    }

    /**
     * Restore one version of an item
     *
     * @param int $itemId
     * @param int $number
     *
     * @return bool
     */
    public function restore($itemId, $number)
    {
        $sql = 'INSERT INTO plugin_docman_version (id, item_id, number, user_id, label, ' .
                        ' changelog, date,  ' .
                        ' filename, filesize, filetype, path) ' .
                        ' SELECT id, item_id, number, user_id, label, ' .
                        ' changelog, create_date, ' .
                        ' filename, filesize, filetype, path FROM plugin_docman_version_deleted ' .
                        ' WHERE item_id=' . $this->da->quoteSmart($itemId) . ' AND number=' . $this->da->quoteSmart($number);
        if ($this->update($sql)) {
            $sql = 'DELETE FROM plugin_docman_version_deleted WHERE item_id=' . $this->da->quoteSmart($itemId) . ' AND number=' . $this->da->quoteSmart($number);
            return $this->update($sql);
        }
        return false;
    }

    /**
     * List pending versions ( marked as deleted but not physically removed yet)
     * in order to ease the restore
     *
     * @param int $groupId
     * @param int $offset
     * @param int $limit
     *
     * @return Array
     */
    public function listPendingVersions($groupId, $offset, $limit)
    {
        $sql = ' SELECT SQL_CALC_FOUND_ROWS id, title, number,label,' .
             '        plugin_docman_version_deleted.delete_date  as date, ' .
             '        plugin_docman_version_deleted.item_id as item_id ' .
             ' FROM plugin_docman_item, plugin_docman_version_deleted ' .
             ' WHERE plugin_docman_item.item_id = plugin_docman_version_deleted.item_id ' .
             '        AND group_id=' . db_ei($groupId) .
             '        AND plugin_docman_version_deleted.delete_date <= ' . $_SERVER['REQUEST_TIME'] .
             '        AND plugin_docman_version_deleted.purge_date IS NULL ' .
             '        AND plugin_docman_item.delete_date IS NULL' .
             ' ORDER BY plugin_docman_version_deleted.delete_date DESC ' .
             ' LIMIT ' . db_ei($offset) . ', ' . db_ei($limit);

        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
            $pendings = array();
            foreach ($dar as $row) {
                $pendings[] = $row;
            }

            $sql = 'SELECT FOUND_ROWS() as nb';
            $resNumrows = $this->retrieve($sql);
            if ($resNumrows === false) {
                return [];
            }
            $row = $resNumrows->getRow();
            return array('versions' => $pendings, 'nbVersions' => $row['nb']);
        }
        return array();
    }

    /**
     * List versions of the item that are deleted but not already purged
     *
     * @param int $itemId
     *
     * @return DataAccessResult|false
     */
    public function listVersionsToPurgeByItemId($itemId)
    {
        $sql = 'SELECT v.id, v.number, v.item_id, v.user_id, v.label, v.changelog,' .
               ' v.create_date as date, v.filename, v.filesize, v.filetype, v.path ' .
               ' FROM plugin_docman_version_deleted v ' .
               ' WHERE v.item_id = ' . $this->da->quoteSmart($itemId);
               ' AND purge_date IS NULL';
        return $this->retrieve($sql);
    }

    /**
     * List all pending versions in order to delete them physically
     *
     * @param int $time
     *
     * @return DataAccessResult|false
     */
    public function listVersionsToPurge($time)
    {
        $sql = ' SELECT id, item_id, number, user_id, label, changelog,' .
             ' create_date AS date, filename, filesize, filetype, path ' .
             ' FROM plugin_docman_version_deleted ' .
             ' WHERE delete_date < ' . $this->da->quoteSmart($time) .
             ' AND purge_date IS NULL ';

        return $this->retrieve($sql);
    }

    /**
     * Search for a deleted version
     *
     * @param $itemId
     * @param $number
     *
     * @return DataAccessResult
     */
    public function searchDeletedVersion($itemId, $number)
    {
        $sql = 'SELECT * ' .
               ' FROM plugin_docman_version_deleted' .
               ' WHERE item_id = ' . $this->da->escapeInt($itemId) .
               ' AND number = ' . $this->da->escapeInt($number);
        return $this->retrieve($sql);
    }

    /**
     * Save the purge date of a deleted version
     *
     * @param int $id
     * @param int $time
     *
     * @return bool
     */
    public function setPurgeDate($id, $time)
    {
        $sql = 'UPDATE plugin_docman_version_deleted' .
               ' SET purge_date = ' . $this->da->escapeInt($time) .
               ' WHERE id = ' . $this->da->escapeInt($id);
        return $this->update($sql);
    }
}
