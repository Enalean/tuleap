<?php
/**
 * Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
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
    * Constructs the PluginIMMucConversationLogDao
    * @param $da instance of the DataAccess class
    */
    function PluginIMMucConversationLogDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Gets all conversation logs in the db
    * @return DataAccessResult
    */
    function & searchAll() {
        $sql = "SELECT * FROM mucConversationLog";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches conversation logs by muc room name 
    * @return DataAccessResult
    */
    function & searchByMucName($muc_name) {
        $sql = sprintf("SELECT cl.*  
                        FROM mucConversationLog cl, mucRoom r
                        WHERE cl.roomID = r.roomID AND
                              r.name = %s",
            $this->da->quoteSmart($muc_name));
        return $this->retrieve($sql);
    }

}

?>