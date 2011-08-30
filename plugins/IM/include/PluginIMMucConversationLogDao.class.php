<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 *
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com> 
 *
 * PluginIMMucConversationLogDao
 */

require_once('common/dao/include/DataAccessObject.class.php');

/**
 *  Data Access Object for MucConversationLog 
 */
class PluginIMMucConversationLogDao extends DataAccessObject {
    /**
    * Gets all conversation logs in the db
    * @return DataAccessResult
    */
    function & searchAll() {
        $sql = "SELECT * FROM ofConversation";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches conversation logs by muc room name 
    * @return DataAccessResult
    */
    function & searchByMucName($muc_name) {
        $sql = sprintf("SELECT *, SUBSTRING_INDEX(fromJID, '@', 1) as nickname
                        FROM ofMessageArchive ma
                        WHERE SUBSTRING_INDEX(toJID, '@', 1) = %s
                        ORDER BY sentDate ASC",
            $this->da->quoteSmart($muc_name));
        return $this->retrieve($sql);
    }

	/**
    * Searches conversation logs by muc room name before a date 
    * @return DataAccessResult
    */
    function & searchByMucNameBeforeDate($muc_name, $end_date) {
        $sql = sprintf("SELECT *, SUBSTRING_INDEX(fromJID, '@', 1) as nickname
                        FROM ofMessageArchive ma
                        WHERE SUBSTRING_INDEX(toJID, '@', 1) = %s AND
                              sentDate <=  UNIX_TIMESTAMP(ADDDATE(%s, 1)) * 1000
                        ORDER BY sentDate ASC",
            $this->da->quoteSmart($muc_name),
            $this->da->quoteSmart($end_date)
            );
        return $this->retrieve($sql);
    }
    
	/**
    * Searches conversation logs by muc room name after a date 
    * @return DataAccessResult
    */
    function & searchByMucNameAfterDate($muc_name, $start_date) {
        $sql = sprintf("SELECT *, SUBSTRING_INDEX(fromJID, '@', 1) as nickname
                        FROM ofMessageArchive ma
                        WHERE SUBSTRING_INDEX(toJID, '@', 1) = %s AND
                              sentDate >=  UNIX_TIMESTAMP(%s) * 1000
                        ORDER BY sentDate ASC",
            $this->da->quoteSmart($muc_name),
            $this->da->quoteSmart($start_date)
            );
        return $this->retrieve($sql);
    }
    
    /**
    * Searches conversation logs by muc room name between two dates 
    * @return DataAccessResult
    */
    function & searchByMucNameBetweenDates($muc_name, $start_date, $end_date) {
        $sql = sprintf("SELECT *, SUBSTRING_INDEX(fromJID, '@', 1) as nickname
                        FROM ofMessageArchive ma
                        WHERE SUBSTRING_INDEX(toJID, '@', 1) = %s AND
                              sentDate >=  UNIX_TIMESTAMP(%s) * 1000 AND
                              sentDate <=  UNIX_TIMESTAMP(ADDDATE(%s, 1)) * 1000
                        ORDER BY sentDate ASC",
            $this->da->quoteSmart($muc_name),
            $this->da->quoteSmart($start_date),
            $this->da->quoteSmart($end_date)
            );
        return $this->retrieve($sql);
    }
    
}

?>