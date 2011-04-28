<?php
//
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
// 
//

require_once('common/dao/include/DataAccessObject.class.php');

/**
 *  Data Access Object for PluginSalomeProxy 
 */
class PluginSalomeProxyDao extends DataAccessObject {
    /**
    * Constructs the PluginSalomeProxyDao
    * @param $da instance of the DataAccess class
    */
    function PluginSalomeProxyDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Gets all proxies in the db
    * @return DataAccessResult
    */
    function & searchAll() {
        $sql = "SELECT * FROM plugin_salome_proxy";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches PluginSalomeProxy by Codendi user ID 
    * @return DataAccessResult
    */
    function & searchByCodendiUserID($codendi_user_id) {
        $sql = sprintf("SELECT *  
                        FROM plugin_salome_proxy
                        WHERE user_id = %s",
            $this->da->quoteSmart($codendi_user_id));
        return $this->retrieve($sql);
    }

    /**
    * create a row in the table plugin_salome_proxy 
    * @return true if there is no error
    */
    function createProxy($codendi_user_id, $proxy, $proxy_user, $proxy_password, $proxy_enabled) {
        $sql = sprintf("INSERT INTO plugin_salome_proxy (user_id, proxy, proxy_user, proxy_password, active) VALUES (%s, %s, %s, %s, %s)",
                $this->da->quoteSmart($codendi_user_id),
                $this->da->quoteSmart($proxy),
                $this->da->quoteSmart($proxy_user),
                $this->da->quoteSmart($proxy_password),
                ($proxy_enabled)?1:0);
        $ok = $this->update($sql);
		return $ok;
    }
    
    function updateProxy($codendi_user_id, $proxy, $proxy_user, $proxy_password, $proxy_enabled) {
        $sql = sprintf("UPDATE plugin_salome_proxy SET proxy = %s, proxy_user = %s, proxy_password = %s, active = %s WHERE user_id = %s",
           		$this->da->quoteSmart($proxy),
                $this->da->quoteSmart($proxy_user),
                $this->da->quoteSmart($proxy_password),
                ($proxy_enabled)?1:0,
                $this->da->quoteSmart($codendi_user_id));
        $updated = $this->update($sql);
        return $updated;
    }

}


?>