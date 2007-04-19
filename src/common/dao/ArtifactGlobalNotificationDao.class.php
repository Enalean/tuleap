<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// 
//

require_once('include/DataAccessObject.class.php');

/**
 *  Data Access Object for ArtifactGlobalNotification 
 */
class ArtifactGlobalNotificationDao extends DataAccessObject {
    /**
    * Constructs the ArtifactGlobalNotificationDao
    * @param $da instance of the DataAccess class
    */
    function ArtifactGlobalNotificationDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    function & searchAll() {
        $sql = "SELECT * FROM artifact_global_notification";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches ArtifactGlobalNotification by Id 
    * @return DataAccessResult
    */
    function & searchById($id) {
        $sql = sprintf("SELECT tracker_id, addresses, all_updates, check_permissions FROM artifact_global_notification WHERE id = %s",
				$this->da->quoteSmart($id));
        return $this->retrieve($sql);
    }

    /**
    * Searches ArtifactGlobalNotification by TrackerId 
    * @return DataAccessResult
    */
    function & searchByTrackerId($trackerId) {
        $sql = sprintf("SELECT id, addresses, all_updates, check_permissions FROM artifact_global_notification WHERE tracker_id = %s ORDER BY id",
				$this->da->quoteSmart($trackerId));
        return $this->retrieve($sql);
    }

    /**
    * Searches ArtifactGlobalNotification by Addresses 
    * @return DataAccessResult
    */
    function & searchByAddresses($addresses) {
        $sql = sprintf("SELECT id, tracker_id, all_updates, check_permissions FROM artifact_global_notification WHERE addresses = %s",
				$this->da->quoteSmart($addresses));
        return $this->retrieve($sql);
    }

    /**
    * Searches ArtifactGlobalNotification by AllUpdates 
    * @return DataAccessResult
    */
    function & searchByAllUpdates($allUpdates) {
        $sql = sprintf("SELECT id, tracker_id, addresses, check_permissions FROM artifact_global_notification WHERE all_updates = %s",
				$this->da->quoteSmart($allUpdates));
        return $this->retrieve($sql);
    }

    /**
    * Searches ArtifactGlobalNotification by CheckPermissions 
    * @return DataAccessResult
    */
    function & searchByCheckPermissions($checkPermissions) {
        $sql = sprintf("SELECT id, tracker_id, addresses, all_updates FROM artifact_global_notification WHERE check_permissions = %s",
				$this->da->quoteSmart($checkPermissions));
        return $this->retrieve($sql);
    }


    /**
    * create a row in the table artifact_global_notification 
    * @return true or id(auto_increment) if there is no error
    */
    function create($tracker_id, $addresses, $all_updates, $check_permissions) {
		$sql = sprintf("INSERT INTO artifact_global_notification (tracker_id, addresses, all_updates, check_permissions) VALUES (%s, %s, %s, %s)",
				$this->da->quoteSmart($tracker_id),
				$this->da->quoteSmart($addresses),
				$this->da->quoteSmart($all_updates),
				$this->da->quoteSmart($check_permissions));
        $inserted = $this->update($sql);
        if ($inserted) {
            $dar =& $this->retrieve("SELECT LAST_INSERT_ID() AS id");
            if ($row = $dar->getRow()) {
                $inserted = $row['id'];
            } else {
                $inserted = $dar->isError();
            }
        } 
        return $inserted;
    }
    
    function modify($id, $values) {
        $updates = array();
        foreach($values as $field => $value) {
            $updates[] = $field .' = '. $this->da->quoteSmart($value);
        }
        $sql = "UPDATE artifact_global_notification SET ". implode(', ', $updates) ." WHERE id = ". $this->da->quoteSmart($id);
        return $this->update($sql);
    }
    
    function delete($id, $tracker_id) {
        $sql = sprintf("DELETE FROM artifact_global_notification WHERE id = %s AND tracker_id = %s",
				$this->da->quoteSmart($id),
				$this->da->quoteSmart($tracker_id));
        return $this->update($sql);
    }
}


?>