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

require_once 'UGroupDao.class.php';

/**
 *  Data Access Object for UGroup 
 */
class UGroupStaticDao extends UGroupDao {

    function addUser($ugroup_id, $user_id) {
        $sql = "INSERT INTO ugroup_user (ugroup_id, user_id) VALUES(". db_ei($ugroup_id) .", ". db_ei($user_id) .")";
        if ($this->update($sql)) {
            return ($this->da->affectedRows() > 0);
        }
        return false;
    }

}
?>