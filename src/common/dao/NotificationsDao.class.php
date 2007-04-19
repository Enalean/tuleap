<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// $Id$
//

require_once('include/DataAccessObject.class.php');

/**
 *  Data Access Object for Notifications 
 */
class NotificationsDao extends DataAccessObject {
    /**
    * Constructs the NotificationsDao
    * @param $da instance of the DataAccess class
    */
    function NotificationsDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    
    
    /**
    * Searches Notifications 
    * @return DataAccessResult
    */
    function & search($user_id, $object_id, $type) {
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
    function & searchByUserId($user_id) {
        $sql = sprintf("SELECT user_id, object_id, type FROM notifications WHERE user_id = %s",
				$this->da->quoteSmart($user_id));
        return $this->retrieve($sql);
    }

    /**
    * Searches Notifications by UserId 
    * @return DataAccessResult
    */
    function & searchByObjectId($object_id) {
        $sql = sprintf("SELECT user_id, object_id, type FROM notifications WHERE object_id = %s",
				$this->da->quoteSmart($object_id));
        return $this->retrieve($sql);
    }

    /**
    * Searches Notifications by UserId 
    * @return DataAccessResult
    */
    function & searchUserIdByObjectIdAndType($object_id, $type) {
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