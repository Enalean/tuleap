<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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

class FileModuleMonitorDao extends DataAccessObject
{

    public function whoIsMonitoringPackageByID($group_id, $package_id)
    {
        $_package_id = (int) $package_id;
        $_group_id = (int) $group_id;

        $sql = sprintf(
            "SELECT u.email,u.user_id,p.name "
              . "FROM user AS u,filemodule_monitor AS fm, frs_package AS p "
              . "WHERE u.user_id = fm.user_id "
              . "AND fm.filemodule_id = p.package_id "
              . "AND fm.filemodule_id = %s "
              . "AND p.group_id = %s "
              . "AND ( u.status='A' OR u.status='R' )",
            $this->da->quoteSmart($_package_id),
            $this->da->quoteSmart($_group_id)
        );

        return $this->retrieve($sql);
    }

    /**
     * Get the list of users publicly monitoring a package
     *
     * @param int $packageId Id of the package
     *
     * @return DataAccessResult
     */
    public function whoIsPubliclyMonitoringPackage($packageId)
    {
        $packageId = $this->da->quoteSmart($packageId);

        $sql = "SELECT u.user_id
                FROM user AS u,filemodule_monitor AS fm
                WHERE u.user_id = fm.user_id
                  AND fm.filemodule_id = " . $packageId . "
                  AND u.status IN ('A', 'R')
                  AND fm.anonymous = 0";
        return $this->retrieve($sql);
    }

    public function searchById($id)
    {
        $_id = (int) $id;
        return $this->_search(' fm.filemodule_id = ' . $this->da->escapeInt($_id), '', ' ORDER BY filemodule_id DESC');
    }


    /**
     * Check user's monitoring of the given package.
     *
     * @param int $package_id Id of the package
     * @param PFUser    $user       The user
     * @param bool $publicly If true check if the user is monitoring publicly
     *
     * @return DataAccessResult
     */
    public function searchMonitoringFileByUserAndPackageId($package_id, PFUser $user, $publicly = false)
    {
        $option = "";
        if ($publicly) {
            $option = "AND anonymous = 0";
        }
        $_package_id = (int) $package_id;
        $_user_id = $user->getID();

        return $this->_search(' fm.filemodule_id = ' . $this->da->escapeInt($_package_id) . ' AND fm.user_id =' . $this->da->escapeInt($_user_id) . ' ' . $option, '', ' ORDER BY filemodule_id DESC');
    }

    public function _search($where, $group = '', $order = '', $from = array())
    {
        $sql = 'SELECT fm.* '
            . ' FROM filemodule_monitor AS fm '
            . (count($from) > 0 ? ', ' . implode(', ', $from) : '')
            . (trim($where) != '' ? ' WHERE ' . $where . ' ' : '')
            . $group
            . $order;
        return $this->retrieve($sql);
    }

    /**
     * Create a row in the table filemodule_monitor
     *
     * @param int $filemodule_id Id of the package
     * @param PFUser    $user          The user
     * @param bool $anonymous True if the user i monitoring anonymously
     *
     * @return true or id(auto_increment) if there is no error
     */
    public function create($filemodule_id, PFUser $user, $anonymous = true)
    {
        $arg      = array();
        $values   = array();

        $arg[]    = 'filemodule_id';
        $values[] = ($this->da->escapeInt($filemodule_id));

        $arg[]    = 'user_id';
        $values[] = $this->da->escapeInt($user->getID());

        $arg[]    = 'anonymous';
        $values[] = ($this->da->escapeInt($anonymous));

        $sql      = "INSERT INTO filemodule_monitor
                     (" . implode(", ", $arg) . ")
                     VALUES (" . implode(", ", $values) . ")";
        return $this->update($sql);
    }


     /**
     * Delete entry that match $package_id and $user_id (current user) in filemodule_monitor
     *
     * @param int $filemodule_id Id of the package
     * @param PFUser    $user       The user
     * @param bool $onlyPublic If true delete only user publicly monitoring the package
     *
     * @return true if there is no error
     */
    public function delete($filemodule_id, PFUser $user, $onlyPublic = false)
    {
        $option = "";
        if ($onlyPublic) {
            $option = "AND anonymous = 0";
        }
        $sql = "DELETE FROM filemodule_monitor
                WHERE filemodule_id = " . $this->da->escapeInt($filemodule_id) . "
                  AND user_id = " . $this->da->escapeInt($user->getID()) . "
                  " . $option;

        $deleted = $this->update($sql);
        return $deleted;
    }
}
