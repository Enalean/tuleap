<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// $Id$
//

require_once('include/DataAccessObject.class.php');

/**
 *  Data Access Object for Feedback 
 */
class FeedbackDao extends DataAccessObject {
    /**
    * Constructs the FeedbackDao
    * @param $da instance of the DataAccess class
    */
    function FeedbackDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    
    
    /**
    * Searches Feedback 
    * @return DataAccessResult
    */
    function & search($session_hash) {
        $sql = sprintf("SELECT * FROM feedback WHERE session_hash = %s",
				$this->da->quoteSmart($session_hash));
        return $this->retrieve($sql);
    }


    /**
    * create a row in the table Feedback 
    * @return true if there is no error
    */
    function create($session_hash, $serialized_feedback) {
        $sql = sprintf("REPLACE INTO feedback (session_hash, feedback, created_at) VALUES (%s, %s, NOW())",
				$this->da->quoteSmart($session_hash),
				$this->da->quoteSmart($serialized_feedback));
        return $this->update($sql);
    }
    
    /**
    * delete a row in the table Feedback 
    * @return true if there is no error
    */
    function delete($session_hash) {
		$sql = sprintf("DELETE FROM feedback WHERE session_hash = %s",
				$this->da->quoteSmart($session_hash));
        return $this->update($sql);
    }
}


?>