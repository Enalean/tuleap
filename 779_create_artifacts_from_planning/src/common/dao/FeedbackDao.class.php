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
 *  Data Access Object for Feedback 
 */
class FeedbackDao extends DataAccessObject {
    /**
    * Searches Feedback 
    * @return DataAccessResult
    */
    function search($session_hash) {
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