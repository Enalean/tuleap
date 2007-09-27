<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
//

require_once('db/DataAccessObjectDbx.class.php');

/**
 *  Data Access Object for PluginCvstodimensionsParameters 
 */
class PluginCvstodimensionsP26CDao extends DataAccessObjectDbx {
    /**
    * Constructs the PluginCvstodimensionsP26CDao
    * @param $da instance of the DataAccess class
    */
    function PluginCvstodimensionsP26CDao( & $da ) {
        DataAccessObjectDbx::DataAccessObjectDbx($da);
    }
    
    /**
    * Gets product by product name in the db
    * @return DataAccessResult
    */
    function & searchProductByName($product) {
        $sql = sprintf("SELECT * FROM PCMS_PART_DATA WHERE PRODUCT_ID=%s and part_id = %s",
                    $this->da->quoteSmart($product),
                    $this->da->quoteSmart($product));      
        return $this->retrieve($sql);
    }
    
    /**
    * Gets design parts by product name in the db
    * @return DataAccessResult
    */
    function & searchDesignPartsByProduct($product) {
        $sql = sprintf("SELECT PART_ID FROM PCMS_PART_DATA WHERE PRODUCT_ID=%s",
                    $this->da->quoteSmart($product));  
        return $this->retrieve($sql);
    }
    
    /**
    * Gets role by user and product name in the db
    * @return DataAccessResult
    */
    function & searchRoleByProductAndUser($product, $user_name) {
        $sql = sprintf("SELECT ROLE FROM PCMS_USER_ROLES WHERE USER_NAME=%s AND PRODUCT_ID=%s",
                    $this->da->quoteSmart($user_name),
                    $this->da->quoteSmart($product));        
        return $this->retrieve($sql);
    }
    
    /**
    * Gets worksets by product name in the db
    * @return DataAccessResult
    */
    function & searchWorksetByProduct($product) {
        $sql = sprintf("SELECT WORKSET_NAME FROM PCMS_WORKSET_INFO WHERE PRODUCT_ID=%s",
                    $this->da->quoteSmart($product));        
        return $this->retrieve($sql);
    }
    
    /**
    * Gets worksets elements by product name and workset name in the db
    * @return DataAccessResult
    */
    function & searchWorksetElementByProductAndWorkset($product, $workset) {
        $sql = sprintf("SELECT DISTINCT PCMS_WS_ITEMS.WS_FILENAME AS filename, 
                                        PCMS_WS_ITEMS.DIR_FULLPATH AS path,
                                        PCMS_WS_ITEMS.ITEM_ID AS id,
                                        PCMS_WS_ITEMS.VARIANT as variant,
                                        PCMS_WS_ITEMS.ITEM_TYPE as type,
                                        PCMS_WS_ITEMS.CURRENT_REVISION as revision        
                        FROM PCMS_WS_ITEMS, PCMS_WORKSET_INFO 
                        WHERE PCMS_WS_ITEMS.PRODUCT_ID=%s 
                                AND PCMS_WS_ITEMS.WORKSET_UID=PCMS_WORKSET_INFO.WORKSET_UID 
                                AND PCMS_WORKSET_INFO.WORKSET_NAME=%s",
                    $this->da->quoteSmart($product),
                    $this->da->quoteSmart($workset));        
        return $this->retrieve($sql);
    }
    
    
    /**
    * Gets last baseline by product name in the db
    * @return DataAccessResult
    */
    function & searchLastBaselineByProduct($product) {
        $sql = sprintf("SELECT BASELINE_ID FROM PCMS_BASELINE_INFO WHERE PRODUCT_ID=%s 
                AND DATE_TIME=(SELECT MAX(DATE_TIME) FROM PCMS_BASELINE_INFO WHERE PRODUCT_ID=%s)",
                    $this->da->quoteSmart($product),
                    $this->da->quoteSmart($product));        
        return $this->retrieve($sql);
    }

}


?>