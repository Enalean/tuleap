<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 *
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com> 
 *
 * PluginIMMucChangeTopicLogDao
 */

require_once('common/dao/include/DataAccessObject.class.php');

/**
 *  Data Access Object for MucChangeTopicLog 
 */
class PluginIMMucChangeTopicLogDao extends DataAccessObject {
    /**
    * Gets all 'change topic' event logs in the db
    * @return DataAccessResult
    */
    function & searchAll() {
        $sql = "SELECT * FROM ofMucConversationLog WHERE body IS NULL AND subject IS NOT NULL";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches 'change topic' event logs by muc room name 
    * @return DataAccessResult
    */
    function & searchByMucName($muc_name) {
        $sql = sprintf("SELECT cl.*, SUBSTRING_INDEX(cl.sender, '@', 1) AS username  
                        FROM ofMucConversationLog cl, ofMucRoom r
                        WHERE cl.roomID = r.roomID AND
                              r.name = %s AND 
                              cl.body IS NULL AND cl.subject IS NOT NULL
                        ORDER BY logTime ASC",
            $this->da->quoteSmart($muc_name));
        return $this->retrieve($sql);
    }

	/**
    * Searches 'change topic' event logs by muc room name before a date 
    * @return DataAccessResult
    */
    function & searchByMucNameBeforeDate($muc_name, $end_date) {
        $sql = sprintf("SELECT cl.*, SUBSTRING_INDEX(cl.sender, '@', 1) AS username
                        FROM ofMucConversationLog cl, ofMucRoom r
                        WHERE cl.roomID = r.roomID AND
                              r.name = %s AND
                              cl.logTime <=  UNIX_TIMESTAMP(ADDDATE(%s, 1)) * 1000 AND
                              cl.body IS NULL AND cl.subject IS NOT NULL
                        ORDER BY logTime ASC",
            $this->da->quoteSmart($muc_name),
            $this->da->quoteSmart($end_date)
            );
        return $this->retrieve($sql);
    }
    
	/**
    * Searches 'change topic' event logs by muc room name after a date 
    * @return DataAccessResult
    */
    function & searchByMucNameAfterDate($muc_name, $start_date) {
        $sql = sprintf("SELECT cl.*, SUBSTRING_INDEX(cl.sender, '@', 1) AS username  
                        FROM ofMucConversationLog cl, ofMucRoom r
                        WHERE cl.roomID = r.roomID AND
                              r.name = %s AND
                              cl.logTime >=  UNIX_TIMESTAMP(%s) * 1000 AND
                              cl.body IS NULL AND cl.subject IS NOT NULL
                        ORDER BY logTime ASC",
            $this->da->quoteSmart($muc_name),
            $this->da->quoteSmart($start_date)
            );
        return $this->retrieve($sql);
    }
    
    /**
    * Searches 'change topic' event logs by muc room name between two dates 
    * @return DataAccessResult
    */
    function & searchByMucNameBetweenDates($muc_name, $start_date, $end_date) {
        $sql = sprintf("SELECT cl.*, SUBSTRING_INDEX(cl.sender, '@', 1) AS username  
                        FROM ofMucConversationLog cl, ofMucRoom r
                        WHERE cl.roomID = r.roomID AND
                              r.name = %s AND
                              cl.logTime >=  UNIX_TIMESTAMP(%s) * 1000 AND
                              cl.logTime <=  UNIX_TIMESTAMP(ADDDATE(%s, 1)) * 1000 AND
                              cl.body IS NULL AND cl.subject IS NOT NULL
                        ORDER BY logTime ASC",
            $this->da->quoteSmart($muc_name),
            $this->da->quoteSmart($start_date),
            $this->da->quoteSmart($end_date)
            );
        return $this->retrieve($sql);
    }
    
}

?>