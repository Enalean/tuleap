<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

class FRSPackageDao extends DataAccessObject
{
    public const INCLUDE_DELETED = 0x0001;

    public $STATUS_DELETED;

    public function __construct($da, $status_deleted)
    {
        parent::__construct($da);
        $this->table_name = 'frs_package';
        $this->STATUS_DELETED = $status_deleted;
    }

    /**
     * Return the array that match given id.
     *
     * @return DataAccessResult
     */
    public function searchById($id, $extraFlags = 0)
    {
        $_id = (int) $id;
        return $this->_search(' p.package_id = ' . $this->da->escapeInt($_id), '', ' ORDER BY rank DESC LIMIT 1', null, $extraFlags);
    }

    public function searchInGroupById($id, $group_id, $extraFlags = 0)
    {
        $_id = (int) $id;
        $_group_id = (int) $group_id;
        return $this->_search(' p.package_id = ' . $this->da->escapeInt($_id) . ' AND p.group_id = ' . $this->da->escapeInt($_group_id), '', ' ORDER BY rank DESC LIMIT 1', null, $extraFlags);
    }

    public function searchByFileId($file_id)
    {
        $_file_id = (int) $file_id;
        return $this->_search(
            ' f.file_id =' . $this->da->escapeInt($_file_id) . ' AND f.release_id = r.release_id AND r.package_id = p.package_id AND r.status_id!=' . $this->da->escapeInt($this->STATUS_DELETED),
            '',
            'ORDER BY rank DESC LIMIT 1',
            array('frs_release AS r','frs_file AS f')
        );
    }

    public function searchInGroupByReleaseId($id, $group_id)
    {
        $_id = (int) $id;
        $_group_id = (int) $group_id;
        return $this->_search(
            'p.group_id = ' . $this->da->escapeInt($_group_id) . ' AND r.release_id = ' . $this->da->escapeInt($_id) . ' AND p.package_id = r.package_id AND r.status_id!=' . $this->da->escapeInt($this->STATUS_DELETED),
            '',
            'ORDER BY rank DESC LIMIT 1',
            array('frs_release AS r')
        );
    }

    public function searchByIdList($idList)
    {
        if (is_array($idList) && count($idList) > 0) {
            $sql_where = sprintf(' p.package_id IN (%s)', implode(', ', $idList));
        }
        return $this->_search($sql_where, '', '');
    }

    /**
     * Return the list of packages for a given projet according to filters
     *
     * @return DataAccessResult
     */
    public function searchByGroupId($id)
    {
        $_id = (int) $id;
        return $this->_search(' p.group_id = ' . $this->da->escapeInt($_id), '', ' ORDER BY rank ASC ');
    }

    public function searchActivePackagesByGroupId($id)
    {
        $id        = $this->da->escapeInt($id);
        $status_id = $this->da->escapeInt(FRSPackage::STATUS_ACTIVE);

        return $this->_search(" group_id = $id AND status_id = $status_id", '', 'ORDER BY rank');
    }

    public function searchPaginatedActivePackagesByGroupId($id, $limit, $offset)
    {
        $id        = $this->da->escapeInt($id);
        $status_id = $this->da->escapeInt(FRSPackage::STATUS_ACTIVE);
        $limit     = $this->da->escapeInt($limit);
        $offset    = $this->da->escapeInt($offset);

        $sql = "SELECT SQL_CALC_FOUND_ROWS *
                FROM frs_package
                WHERE group_id  = $id
                  AND status_id = $status_id
                ORDER BY rank
                LIMIT $offset, $limit";

        return $this->retrieve($sql);
    }

    public function _search($where, $group = '', $order = '', $from = array(), $extraFlags = 0)
    {
        if ($from === null) {
            $from = [];
        }
        $sql = 'SELECT p.* '
            . ' FROM frs_package AS p '
            . (count($from) > 0 ? ', ' . implode(', ', $from) : '');
        if (trim($where) != '') {
            $sql .= ' WHERE ' . $where . ' ';
            if (($extraFlags & self::INCLUDE_DELETED) == 0) {
                $sql .= ' AND p.status_id != ' . $this->da->escapeInt($this->STATUS_DELETED) . ' ';
            }
        }
        $sql .= $group . $order;

        return $this->retrieve($sql);
    }


    public function searchPackageByName($package_name, $group_id)
    {
        $_group_id = (int) $group_id;
        return $this->_search(' group_id=' . $this->da->escapeInt($_group_id) . ' AND name=' . $this->da->quoteSmart(htmlspecialchars($package_name)), '', '');
    }


    /**
     * create a row in the table frs_package
     *
     * @return true or id(auto_increment) if there is no error
     */
    public function create(
        $group_id = null,
        $name = null,
        $status_id = null,
        $rank = null,
        $approve_license = null
    ) {
        $arg    = array();
        $values = array();

        if ($group_id !== null) {
            $arg[] = 'group_id';
            $values[] = ($this->da->escapeInt($group_id));
        }

        if ($name !== null) {
            $arg[] = 'name';
            $values[] = $this->da->quoteSmart($name, array('force_string' => true));
        }

        if ($status_id !== null) {
            $arg[] = 'status_id';
            $values[] = ($this->da->escapeInt($status_id));
        }

        if ($rank !== null) {
            $arg[] = 'rank';
            $values[] = $this->prepareRanking('frs_package', 0, $group_id, $rank, 'package_id', 'group_id');
        }

        if ($approve_license !== null) {
            $arg[] = 'approve_license';
            $values[] = ($approve_license ? 1 : 0);
        }

        $sql = 'INSERT INTO frs_package'
            . '(' . implode(', ', $arg) . ')'
            . ' VALUES (' . implode(', ', $values) . ')';
        return $this->_createAndReturnId($sql);
    }

    public function createFromArray($data_array)
    {
        $arg    = array();
        $values = array();
        $cols   = array('group_id', 'name', 'status_id', 'rank', 'approve_license');
        foreach ($data_array as $key => $value) {
            if ($key == 'rank') {
                $value = $this->prepareRanking('frs_package', 0, $data_array['group_id'], $value, 'package_id', 'group_id');
            }
            if (in_array($key, $cols)) {
                $arg[]    = $key;
                $values[] = $this->da->quoteSmart($value, array('force_string' => ($key == 'name')));
            }
        }
        if (count($arg)) {
            $sql = 'INSERT INTO frs_package '
                . '(' . implode(', ', $arg) . ')'
                . ' VALUES (' . implode(', ', $values) . ')';
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
     * Update a row in the table frs_package
     *
     * @return true if there is no error
     */
    public function updateById(
        $package_id,
        $group_id,
        $name = null,
        $status_id = null,
        $rank = null,
        $approve_license = null
    ) {
        $argArray = array();

        if ($group_id !== null) {
            $argArray[] = 'group_id=' . ($this->da->escapeInt($group_id));
        }

        if ($name !== null) {
            $argArray[] = 'name=' . $this->da->quoteSmart($name, array('force_string' => true));
        }

        if ($status_id !== null) {
            $argArray[] = 'status_id=' . ($this->da->escapeInt($status_id));
        }

        if ($rank !== null) {
            $argArray[] = 'rank=' . $this->prepareRanking('frs_package', $package_id, $group_id, $rank, 'package_id', 'group_id');
        }

        if ($approve_license !== null) {
            $argArray[] = 'approve_license=' . ($approve_license ? 1 : 0);
        }

        $sql = 'UPDATE frs_package'
            . ' SET ' . implode(', ', $argArray)
            . ' WHERE  status_id != ' . $this->da->escapeInt($this->STATUS_DELETED) . ' AND package_id=' . ($this->da->escapeInt($package_id));

        $inserted = $this->update($sql);
        return $inserted;
    }

    public function updateFromArray($data_array)
    {
        $updated = false;
        $id = false;
        if (isset($data_array['package_id'])) {
            $package_id = $data_array['package_id'];
        }
        if ($package_id) {
            $dar = $this->searchById($package_id);
            if (!$dar->isError() && $dar->valid()) {
                $current = $dar->current();
                $set_array = array();
                foreach ($data_array as $key => $value) {
                    if ($key != 'package_id' && $value != $current[$key]) {
                        if ($key == 'rank') {
                            $value = $this->prepareRanking('frs_package', $package_id, $current['group_id'], $value, 'package_id', 'group_id');
                        }
                        $set_array[] = $key . ' = ' . $this->da->quoteSmart($value, array('force_string' => ($key == 'name')));
                    }
                }
                if (count($set_array)) {
                    $sql = 'UPDATE frs_package'
                        . ' SET ' . implode(' , ', $set_array)
                        . ' WHERE package_id=' . $this->da->quoteSmart($package_id);
                    $updated = $this->update($sql);
                }
            }
        }
        return $updated;
    }

    /**
     * Delete entry that match $package_id in frs_package
     *
     * @param $package_id int
     * @return true if there is no error
     */
    public function delete($package_id, $status_deleted)
    {
        $sql = sprintf(
            "UPDATE frs_package SET status_id= " . $this->da->escapeInt($status_deleted) . " WHERE package_id=%d",
            $this->da->escapeInt($package_id)
        );

        $deleted = $this->update($sql);
        return $deleted;
    }
}
