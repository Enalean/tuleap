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

require_once('include/DataAccessObject.class.php');
require_once('common/user/UserManager.class.php');

class FileModuleMonitorDao extends DataAccessObject {

    function whoIsMonitoringPackageByID($group_id, $package_id) {
        $_package_id = (int) $package_id;
        $_group_id = (int) $group_id;
        
        $sql = sprintf("SELECT u.email,p.name "
              ."FROM user AS u,filemodule_monitor AS fm, frs_package AS p "
              ."WHERE u.user_id = fm.user_id " 
              ."AND fm.filemodule_id = p.package_id "
              ."AND fm.filemodule_id = %s "
              ."AND p.group_id = %s "
              ."AND ( u.status='A' OR u.status='R' )",
              $this->da->quoteSmart($_package_id),
              $this->da->quoteSmart($_group_id));

        return $this->retrieve($sql);
    }

    /**
     * Get the list of users publicly monitoring a package
     *
     * @param Integer $packageId Id of the package
     *
     * @return DataAccessResult
     */
    function whoIsPubliclyMonitoringPackage($packageId) {
        $packageId = $this->da->quoteSmart($packageId);

        $sql = "SELECT u.user_id
                FROM user AS u,filemodule_monitor AS fm
                WHERE u.user_id = fm.user_id
                  AND fm.filemodule_id = ".$packageId."
                  AND u.status IN ('A', 'R')
                  AND fm.anonymous = 0";
        return $this->retrieve($sql);
    }

    function searchById($id) {
        $_id = (int) $id;
        return $this->_search(' fm.filemodule_id = '.$this->da->escapeInt($_id), '', ' ORDER BY filemodule_id DESC');
    }
    

    /**
     * Check user's monitoring of the given package.
     *
     * @param Integer $package_id Id of the package
     * @param User    $user       The user
     * @param Boolean $publicly   If true check if the user is monitoring publicly
     *
     * @return DataAccessResult
     */
    function searchMonitoringFileByUserAndPackageId($package_id, User $user, $publicly = false) {
        $option = "";
        if ($publicly) {
            $option = "AND anonymous = 0";
        }
        $_package_id = (int) $package_id;
        $_user_id = $user->getID();
        
        return $this->_search(' fm.filemodule_id = '.$this->da->escapeInt($_package_id).' AND fm.user_id ='.$this->da->escapeInt($_user_id).' '.$option, '', ' ORDER BY filemodule_id DESC');
    }
    
    function _search($where, $group = '', $order = '', $from = array()) {
        $sql = 'SELECT fm.* '
            .' FROM filemodule_monitor AS fm '
            .(count($from) > 0 ? ', '.implode(', ', $from) : '') 
            .(trim($where) != '' ? ' WHERE '.$where.' ' : '') 
            .$group
            .$order;
        return $this->retrieve($sql);
    }
    
    /**
     * Create a row in the table filemodule_monitor
     *
     * @param Integer $filemodule_id Id of the package
     * @param User    $user          The user
     * @param Boolean $anonymous     True if the user i monitoring anonymously
     *
     * @return true or id(auto_increment) if there is no error
     */
    function create($filemodule_id, User $user, $anonymous = true) {

        $arg      = array();
        $values   = array();

        $arg[]    = 'filemodule_id';
        $values[] = ($this->da->escapeInt($filemodule_id));

        $arg[]    = 'user_id';
        $values[] = $this->da->escapeInt($user->getID());

        $arg[]    = 'anonymous';
        $values[] = ($this->da->escapeInt($anonymous));

        $sql      = "INSERT INTO filemodule_monitor
                     (".implode(", ", $arg).")
                     VALUES (".implode(", ", $values).")";
        return $this->update($sql);
    }

    
     /**
     * Delete entry that match $package_id and $user_id (current user) in filemodule_monitor
     *
     * @param Integer $filemodule_id Id of the package
     * @param User    $user       The user
     * @param Boolean $onlyPublic If true delete only user publicly monitoring the package
     *
     * @return true if there is no error
     */
    function delete($filemodule_id, User $user, $onlyPublic = false) {
        $option = "";
        if ($onlyPublic) {
            $option = "AND anonymous = 0";
        }
        $sql = "DELETE FROM filemodule_monitor
                WHERE filemodule_id = ".$this->da->escapeInt($filemodule_id)."
                  AND user_id = ".$this->da->escapeInt($user->getID())."
                  ".$option;

        $deleted = $this->update($sql);
        return $deleted;
    }
}

?>