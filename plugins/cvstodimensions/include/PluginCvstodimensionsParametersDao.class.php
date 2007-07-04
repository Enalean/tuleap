<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
//

require_once('common/dao/include/DataAccessObject.class.php');

/**
 *  Data Access Object for PluginCvstodimensionsParameters 
 */
class PluginCvstodimensionsParametersDao extends DataAccessObject {
    /**
    * Constructs the PluginCvstodimensionsParametersDao
    * @param $da instance of the DataAccess class
    */
    function PluginCvstodimensionsParametersDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    function & searchAll() {
        $sql = "SELECT * FROM plugin_cvstodimensions_parameters";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches PluginCvstodimensionsParameters by GroupId 
    * @return DataAccessResult
    */
    function & searchByGroupId($groupId) {
        $sql = sprintf("SELECT product, dimensions_db, status FROM plugin_cvstodimensions_parameters WHERE group_id = %s",
				$this->da->quoteSmart($groupId));
        return $this->retrieve($sql);
    }
    
    /**
    * Searches PluginCvstodimensionsParameters by Product 
    * @return DataAccessResult
    */
    function & searchByDimensionsDb($dimensionsDb) {
        $sql = sprintf("SELECT group_id, product, status FROM plugin_cvstodimensions_parameters WHERE dimensions_db = %s",
				$this->da->quoteSmart($dimensionsDb));
        return $this->retrieve($sql);
    }

    /**
    * Searches PluginCvstodimensionsParameters by DimensionsDB 
    * @return DataAccessResult
    */
    function & searchByProduct($product) {
        $sql = sprintf("SELECT group_id, dimensions_db, status FROM plugin_cvstodimensions_parameters WHERE product = %s",
				$this->da->quoteSmart($product));
        return $this->retrieve($sql);
    }

    /**
    * Searches PluginCvstodimensionsParameters by Status 
    * @return DataAccessResult
    */
    function & searchByStatus($status) {
        $sql = sprintf("SELECT group_id, product, dimensions_db FROM plugin_cvstodimensions_parameters WHERE status = %s",
				$this->da->quoteSmart($status));
        return $this->retrieve($sql);
    }


    /**
    * create a row in the table plugin_cvstodimensions_parameters 
    * @return true or id(auto_increment) if there is no error
    */
    function create($group_id, $product, $dimensions_db, $status='0' ) {
		$sql = sprintf("INSERT INTO plugin_cvstodimensions_parameters (group_id, product, dimensions_db, status) VALUES (%s, %s, %s, %s)",
				$this->da->quoteSmart($group_id),
				$this->da->quoteSmart($product),
				$this->da->quoteSmart($dimensions_db),
				$this->da->quoteSmart($status));
        $inserted = $this->update($sql);
 
        return $inserted;
    }
    
    /**
    * update a row in the table plugin_cvstodimensions_parameters 
    * @return true or id(auto_increment) if there is no error
    */
    function updateByGroupId($group_id, $product, $dimensions_db, $status='0') {
		$sql = "UPDATE plugin_cvstodimensions_parameters SET product = '".$product."', dimensions_db ='"
				.$dimensions_db."', status = ".$status." WHERE group_id = ".$group_id;
        $inserted = $this->update($sql);
        return $inserted;
    }
    
    

}


?>