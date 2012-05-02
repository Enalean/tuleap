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
 *  Data Access Object for Tracker_GlobalNotification 
 */
class Tracker_GlobalNotificationDao extends DataAccessObject {
    function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_global_notification';
    }
   
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    function searchAll() {
        $sql = "SELECT * FROM $this->table_name";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches Tracker_GlobalNotification by Id 
    * @return DataAccessResult
    */
    function searchById($id) {
        $sql = sprintf("SELECT tracker_id, addresses, all_updates, check_permissions FROM $this->table_name WHERE id = %s",
            $this->da->quoteSmart($id));
        return $this->retrieve($sql);
    }

    /**
    * Searches Tracker_GlobalNotification by TrackerId 
    * @return DataAccessResult
    */
    function searchByTrackerId($trackerId) {
        $sql = sprintf("SELECT id, addresses, all_updates, check_permissions FROM $this->table_name WHERE tracker_id = %s ORDER BY id",
            $this->da->quoteSmart($trackerId));
        return $this->retrieve($sql);
    }

    /**
    * Searches Tracker_GlobalNotification by Addresses 
    * @return DataAccessResult
    */
    function searchByAddresses($addresses) {
        $sql = sprintf("SELECT id, tracker_id, all_updates, check_permissions FROM $this->table_name WHERE addresses = %s",
				$this->da->quoteSmart($addresses));
        return $this->retrieve($sql);
    }

    /**
    * Searches Tracker_GlobalNotification by AllUpdates 
    * @return DataAccessResult
    */
    function searchByAllUpdates($allUpdates) {
        $sql = sprintf("SELECT id, tracker_id, addresses, check_permissions FROM $this->table_name WHERE all_updates = %s",
				$this->da->quoteSmart($allUpdates));
        return $this->retrieve($sql);
    }

    /**
    * Searches Tracker_GlobalNotification by CheckPermissions 
    * @return DataAccessResult
    */
    function searchByCheckPermissions($checkPermissions) {
        $sql = sprintf("SELECT id, tracker_id, addresses, all_updates FROM $this->table_name WHERE check_permissions = %s",
				$this->da->quoteSmart($checkPermissions));
        return $this->retrieve($sql);
    }


    /**
    * create a row in the table tracker_global_notification 
    * @return true or id(auto_increment) if there is no error
    */
    function create($tracker_id, $addresses, $all_updates, $check_permissions) {
		$sql = sprintf("INSERT INTO $this->table_name (tracker_id, addresses, all_updates, check_permissions) VALUES (%s, %s, %s, %s)",
				$this->da->quoteSmart($tracker_id),
				$this->da->quoteSmart($addresses),
				$this->da->quoteSmart($all_updates),
				$this->da->quoteSmart($check_permissions));
        return $this->updateAndGetLastId($sql);
    }
    
    function modify($id, $values) {
        $updates = array();
        foreach($values as $field => $value) {
            $updates[] = $field .' = '. $this->da->quoteSmart($value);
        }
        $sql = "UPDATE $this->table_name SET ". implode(', ', $updates) ." WHERE id = ". $this->da->quoteSmart($id);
        return $this->update($sql);
    }
    
    function delete($id, $tracker_id) {
        $sql = sprintf("DELETE FROM $this->table_name WHERE id = %s AND tracker_id = %s",
				$this->da->quoteSmart($id),
				$this->da->quoteSmart($tracker_id));
        return $this->update($sql);
    }
    
    function duplicate($from_tracker_id, $to_tracker_id) {
        $from_tracker_id = $this->da->escapeInt($from_tracker_id);
        $to_tracker_id   = $this->da->escapeInt($to_tracker_id);
        $sql = "INSERT INTO $this->table_name (tracker_id, addresses, all_updates, check_permissions)
                SELECT $to_tracker_id, addresses, all_updates, check_permissions
                FROM $this->table_name
                WHERE tracker_id = $from_tracker_id";
        return $this->update($sql);
    }
}


?>