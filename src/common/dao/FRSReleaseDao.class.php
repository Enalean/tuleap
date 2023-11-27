<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

class FRSReleaseDao extends DataAccessObject
{
    public const INCLUDE_DELETED = 0x0001;

    public $STATUS_DELETED;

    public function __construct($da, $status_deleted)
    {
        parent::__construct($da);
        $this->STATUS_DELETED = $status_deleted;
    }

    /**
     * Return the array that match given id.
     *
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
     */
    public function searchById($id, $extraFlags = 0)
    {
        $_id = (int) $id;
        return $this->_search(' r.release_id = ' . $this->da->escapeInt($_id), '', ' ORDER BY release_date DESC LIMIT 1', [], $extraFlags);
    }

    public function searchInGroupById($id, $group_id, $extraFlags = 0)
    {
        $_id       = (int) $id;
        $_group_id = (int) $group_id;
        return $this->_search(' p.group_id=' . $this->da->escapeInt($_group_id) . ' AND r.release_id=' . $this->da->escapeInt($_id) . ' AND r.package_id=p.package_id AND p.status_id!=' . db_ei($this->STATUS_DELETED), '', ' ORDER BY release_date DESC LIMIT 1', [
            'frs_package AS p',
        ], $extraFlags);
    }

    public function searchByGroupPackageReleaseID($release_id, $group_id, $package_id, $extraFlags = 0)
    {
        $_id         = (int) $release_id;
        $_group_id   = (int) $group_id;
        $_package_id = (int) $package_id;

        return $this->_search(' p.package_id=' . $this->da->escapeInt($_package_id) . ' AND p.group_id=' . $this->da->escapeInt($_group_id) . ' AND r.release_id=' . $this->da->escapeInt($_id) .
        ' AND r.package_id=p.package_id AND p.status_id!=' . $this->da->escapeInt($this->STATUS_DELETED), '', 'ORDER BY release_date DESC LIMIT 1', [
            'frs_package AS p',
        ], $extraFlags);
    }

    public function searchByGroupPackageID($group_id, $package_id = null)
    {
        $_group_id = (int) $group_id;
        if ($package_id) {
            $_package_id = (int) $package_id;
        } else {
            $_package_id = null;
        }
        $sql = sprintf("SELECT r.release_id, p.name AS package_name, p.package_id, r.name AS release_name, " .
        "r.status_id " .
        "FROM frs_release AS r, frs_package AS p " .
        "WHERE p.status_id != " . $this->da->escapeInt($this->STATUS_DELETED) . " AND r.status_id != " . $this->da->escapeInt($this->STATUS_DELETED) . " AND p.group_id= %s " .
        "AND r.package_id = p.package_id " .
         ($package_id ? "AND p.package_id = %s " : ""), $this->da->quoteSmart($_group_id), $this->da->quoteSmart($_package_id));
        return $this->retrieve($sql);
    }

    public function searchByIdList($idList)
    {
        if (is_array($idList) && count($idList) > 0) {
            $sql_where = sprintf(' r.release_id IN (%s)', implode(', ', $idList));
        }
        return $this->_search($sql_where, '', '');
    }

    /**
     * Return the list of releases for a given package according to filters
     *
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
     */
    public function searchByPackageId($id)
    {
        $_id = (int) $id;
        return $this->_search(' package_id=' . $this->da->escapeInt($_id), '', ' ORDER BY release_date DESC, release_id DESC ');
    }

    /**
     * Internal method to search releases
     *
     * This method use bitwise masks to manage extraFlags.
     * $extraFlags variable will be set with one or more parameters (only one
     * defined yet):
     * $extraFlags = FRSReleaseDao::INCLUDE_DELETED;
     * $extraFlags = FRSReleaseDao::INCLUDE_DELETED | FRSReleaseDao::INCLUDE_HIDDEN;
     *
     * Then, in this method we are doing a bitwise mask to check which values where set:
     * if (($extraFlags & self::INCLUDE_DELETED) != 0) {
     *     // Include deleted releases
     * }
     * if (($extraFlags & self::INCLUDE_HIDDEN) != 0) {
     *     // Include hidden releases
     * }
     *
     * More info: http://stackoverflow.com/questions/261062/when-to-use-bitwise-operators-during-webdevelopment/261227#261227
     *
     * @param $where
     * @param $group
     * @param $order
     * @param $from
     * @param $extraFlags
     */
    public function _search($where, $group = '', $order = '', $from = [], $extraFlags = 0)
    {
        $sql = 'SELECT r.* ' .
        ' FROM frs_release AS r ' .
        (count($from) > 0 ? ', ' . implode(', ', $from) : '');
        if (trim($where) != '') {
            $sql .= ' WHERE ' . $where . ' ';
            if (($extraFlags & self::INCLUDE_DELETED) == 0) {
                $sql .= ' AND r.status_id!= ' . $this->da->escapeInt($this->STATUS_DELETED) . ' ';
            }
        }
        $sql .= $group . $order;
        return $this->retrieve($sql);
    }

    public function searchActiveReleasesByPackageId($id, $status_active)
    {
        $_id = (int) $id;
        return $this->_search(' package_id=' . $this->da->escapeInt($_id) . ' AND status_id = ' . $this->da->escapeInt($status_active), '', 'ORDER BY release_date DESC, release_id DESC');
    }

    public function searchPaginatedActiveReleasesByPackageId($package_id, $limit, $offset)
    {
        $package_id = $this->da->escapeInt($package_id);
        $limit      = $this->da->escapeInt($limit);
        $offset     = $this->da->escapeInt($offset);
        $status_id  = $this->da->escapeInt(FRSRelease::STATUS_ACTIVE);

        $sql = "SELECT SQL_CALC_FOUND_ROWS *
                FROM frs_release
                WHERE package_id = $package_id
                  AND status_id  = $status_id
                ORDER BY release_date DESC, release_id DESC
                LIMIT $offset, $limit";

        return $this->retrieve($sql);
    }

    public function searchReleaseByName($release_name, $package_id)
    {
        $_package_id = (int) $package_id;
        return $this->_search(' package_id=' . $this->da->escapeInt($_package_id) .
        ' AND name=' . $this->da->quoteSmart(htmlspecialchars($release_name), ['force_string' => true]), '', '');
    }

    /**
     * create a row in the table frs_release
     *
     * @return true or id(auto_increment) if there is no error
     */
    public function create($package_id = null, $name = null, $notes = null, $changes = null, $status_id = null, $preformatted = 1, $release_date = null)
    {
        $arg    =  [];
        $values =  [];

        if ($package_id !== null) {
            $arg[]    = 'package_id';
            $values[] = ((int) $package_id);
        }

        if ($name !== null) {
            $arg[]    = 'name';
            $values[] = $this->da->quoteSmart($name, ['force_string' => true]);
        }

        if ($notes !== null) {
            $arg[]    = 'notes';
            $values[] = $this->da->quoteSmart($notes);
        }

        if ($changes !== null) {
            $arg[]    = 'changes';
            $values[] = $this->da->quoteSmart($changes);
        }

        if ($status_id !== null) {
            $arg[]    = 'status_id';
            $values[] = ($this->da->escapeInt($status_id));
        }

        if ($preformatted !== null) {
            $arg[]    = 'preformatted';
            $values[] = ($this->da->escapeInt($preformatted));
        }

        if ($release_date !== null) {
            $arg[]    = 'release_date';
            $values[] = ($this->da->escapeInt($release_date));
        } else {
            $arg[]    = 'release_date';
            $values[] = ($this->da->escapeInt(time()));
        }

        $um       = & UserManager::instance();
        $user     = & $um->getCurrentUser();
        $arg[]    = 'released_by';
        $values[] = $this->da->quoteSmart($user->getID());

        $sql = 'INSERT INTO frs_release' .
        '(' . implode(', ', $arg) . ')' .
        ' VALUES (' . implode(', ', $values) . ')';
        return $this->_createAndReturnId($sql);
    }

    public function createFromArray($data_array)
    {
        $arg     =  [];
        $values  =  [];
        $cols    =  [
            'package_id',
            'name',
            'notes',
            'changes',
            'status_id',
            'release_date',
        ];
        $is_date = false;
        foreach ($data_array as $key => $value) {
            if (in_array($key, $cols)) {
                if ($key == 'release_date') {
                    $is_date = true;
                }
                $arg[]    = $key;
                $values[] = $this->da->quoteSmart($value, ['force_string' => ($key == 'name')]);
            }
        }

        $arg[]    = 'preformatted';
        $values[] = 1;

        if (! $is_date) {
            $arg[]    = 'release_date';
            $values[] = $this->da->quoteSmart(time());
        }

        $arg[]    = 'released_by';
        $um       = UserManager::instance();
        $user     = $um->getCurrentUser();
        $values[] = $this->da->quoteSmart($user->getID());

        if (count($arg)) {
            $sql = 'INSERT INTO frs_release ' .
            '(' . implode(', ', $arg) . ')' .
            ' VALUES (' . implode(', ', $values) . ')';
            return $this->_createAndReturnId($sql);
        } else {
            return false;
        }
    }

    public function _createAndReturnId($sql)
    {
        return $this->updateAndGetLastId($sql);
    }

    /**
     * Update a row in the table frs_release
     *
     * @return true if there is no error
     */
    public function updateById($release_id, $package_id = null, $name = null, $notes = null, $changes = null, $status_id = null, $preformatted = null, $release_date = null)
    {
        $argArray =  [];

        if ($package_id !== null) {
            $argArray[] = 'package_id=' . ($this->da->escapeInt($package_id));
        }

        if ($name !== null) {
            $argArray[] = 'name=' . $this->da->quoteSmart($name, ['force_string' => true]);
        }

        if ($notes !== null) {
            $argArray[] = 'notes=' . $this->da->quoteSmart($notes);
        }

        if ($changes !== null) {
            $argArray[] = 'changes=' . $this->da->quoteSmart($changes);
        }

        if ($status_id !== null) {
            $argArray[] = 'status_id=' . ($this->da->escapeInt($status_id));
        }

        if ($preformatted !== null) {
            $argArray[] = 'preformatted=' . ($this->da->escapeInt($preformatted));
        }

        if ($release_date !== null) {
            $argArray[] = 'release_date=' . ($this->da->escapeInt($release_date));
        }

        $sql = 'UPDATE frs_release' .
        ' SET ' . implode(', ', $argArray) .
        ' WHERE status_id != ' . $this->da->escapeInt($this->STATUS_DELETED) . ' AND release_id=' . ($this->da->escapeInt($release_id));

        $inserted = $this->update($sql);
        return $inserted;
    }

    public function updateFromArray($data_array)
    {
        $updated = false;
        $id      = false;
        if (isset($data_array['release_id'])) {
            $release_id = $data_array['release_id'];
        }
        if ($release_id) {
            $dar = $this->searchById($release_id);
            if (! $dar->isError() && $dar->valid()) {
                $current   = $dar->current();
                $set_array =  [];
                foreach ($data_array as $key => $value) {
                    if ($key != 'release_id' && $key != 'released_by' && $value != $current[$key]) {
                        $set_array[] = $key . ' = ' . $this->da->quoteSmart($value);
                    }
                }
                if (count($set_array)) {
                    $sql     = 'UPDATE frs_release' .
                    ' SET ' . implode(' , ', $set_array) .
                    ' WHERE release_id=' . $this->da->quoteSmart($release_id);
                    $updated = $this->update($sql);
                }
                if (count($set_array) == 0) {
                    $updated = true;
                }
            }
        }
        return $updated;
    }

    /**
     * Delete entry that match $release_id in frs_release
     *
     * @param $release_id int
     * @return true if there is no error
     */
    public function delete($release_id, $status_deleted)
    {
        $sql = sprintf("UPDATE frs_release SET status_id = " . $this->da->escapeInt($status_deleted) . " WHERE release_id=%d", $this->da->escapeInt($release_id));

        $deleted = $this->update($sql);
        return $deleted;
    }
}
