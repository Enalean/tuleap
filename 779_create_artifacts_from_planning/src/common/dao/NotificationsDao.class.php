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

/**
 *  Data Access Object for Notifications 
 */
class NotificationsDao extends DataAccessObject {
    /**
    * Searches Notifications 
    * @return DataAccessResult
    */
    function search($user_id, $object_id, $type) {
        $sql = sprintf("SELECT user_id, object_id, type FROM notifications WHERE user_id = %s AND object_id = %s AND type = %s",
				$this->da->quoteSmart($user_id),
				$this->da->quoteSmart($object_id),
				$this->da->quoteSmart($type));
        return $this->retrieve($sql);
    }

    /**
    * Searches Notifications by UserId 
    * @return DataAccessResult
    */
    function searchByUserId($user_id) {
        $sql = sprintf("SELECT user_id, object_id, type FROM notifications WHERE user_id = %s",
				$this->da->quoteSmart($user_id));
        return $this->retrieve($sql);
    }

    /**
    * Searches Notifications by UserId 
    * @return DataAccessResult
    */
    function searchByObjectId($object_id) {
        $sql = sprintf("SELECT user_id, object_id, type FROM notifications WHERE object_id = %s",
				$this->da->quoteSmart($object_id));
        return $this->retrieve($sql);
    }

    /**
    * Searches Notifications by UserId 
    * @return DataAccessResult
    */
    function searchUserIdByObjectIdAndType($object_id, $type) {
        $sql = sprintf("SELECT user_id, object_id, type FROM notifications WHERE object_id = %s AND type = %s",
				$this->da->quoteSmart($object_id),
				$this->da->quoteSmart($type));
        return $this->retrieve($sql);
    }

    /**
    * create a row in the table Notifications 
    * @return true if there is no error
    */
    function create($user_id, $object_id, $type) {
		$sql = sprintf("INSERT INTO notifications (user_id, object_id, type) VALUES (%s, %s, %s)",
				$this->da->quoteSmart($user_id),
				$this->da->quoteSmart($object_id),
				$this->da->quoteSmart($type));
        return $this->update($sql);
    }
    
    /**
    * delete a row in the table Notifications 
    * @return true if there is no error
    */
    function delete($user_id, $object_id, $type) {
		$sql = sprintf("DELETE FROM notifications WHERE user_id = %s AND object_id = %s AND type = %s",
				$this->da->quoteSmart($user_id),
				$this->da->quoteSmart($object_id),
				$this->da->quoteSmart($type));
        return $this->update($sql);
    }
}


?>