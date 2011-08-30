<?php
//
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
//

require_once('db/DataAccessObjectDbx.class.php');

/**
 *  Data Access Object for PluginSvntodimensionsParameters 
 */
class PluginSvntodimensionsP26CDao extends DataAccessObjectDbx {
    /**
    * Constructs the PluginSvntodimensionsP26CDao
    * @param $da instance of the DataAccess class
    */
    function __construct($da) {
        parent::__construct($da);
    }
    
    /**
    * Get product by product name in the db
    * @return DataAccessResult
    */
    function & searchProductByName($product) {
        $sql = sprintf("SELECT * FROM PCMS_PART_DATA WHERE PRODUCT_ID=%s and part_id = %s",
                    $this->da->quoteSmart($product),
                    $this->da->quoteSmart($product));      
        return $this->retrieve($sql);
    }
    
    /**
    * Get design parts by product name in the db
    * @return DataAccessResult
    */
    function & searchDPByProductName($product) {
        $sql = sprintf("SELECT PART_ID FROM DP_PARTS WHERE PRODUCT_ID = %s",
                    $this->da->quoteSmart($product));      
        return $this->retrieve($sql);
    }
    
    /**
    * Get design parts by product name in the db
    * @return DataAccessResult
    */
    function & searchDesignPartsByProduct($product) {
        $sql = sprintf("SELECT PART_ID FROM PCMS_PART_DATA WHERE PRODUCT_ID=%s",
                    $this->da->quoteSmart($product));  
        return $this->retrieve($sql);
    }
    
    /**
    * Get role by user and product name in the db
    * @return DataAccessResult
    */
    function & searchRoleByProductAndUser($product, $user_name) {
        $sql = sprintf("SELECT ROLE FROM PCMS_USER_ROLES WHERE USER_NAME=%s AND PRODUCT_ID=%s",
                    $this->da->quoteSmart($user_name),
                    $this->da->quoteSmart($product));        
        return $this->retrieve($sql);
    }
    
    /**
    * Get worksets by product name in the db
    * @return DataAccessResult
    */
    function & searchWorksetByProduct($product) {
        $sql = sprintf("SELECT WORKSET_NAME FROM PCMS_WORKSET_INFO WHERE PRODUCT_ID=%s",
                    $this->da->quoteSmart($product));        
        return $this->retrieve($sql);
    }
    
    /**
    * Get worksets elements by product name and workset name in the db
    * @return DataAccessResult
    */
    function & searchWorksetElements($product, $design_part, $workset) {
        $sql = sprintf("SELECT DISTINCT WS_FILENAME AS filename, 
                                        DIR_FULLPATH AS path,
                                        ITEM_ID AS id,
                                        VARIANT as variant,
                                        ITEM_TYPE as type,
                                        CURRENT_REVISION as revision        
                        FROM GCL_SVN_ITEMS 
                        WHERE PRODUCT_ID=%s 
                                AND PART_ID=%s 
                                AND WORKSET_NAME=%s",
                    $this->da->quoteSmart($product),
                    $this->da->quoteSmart($design_part),
                    $this->da->quoteSmart($workset));        
        return $this->retrieve($sql);
    }
    
     /**
    * Get baseline elements by baseline id in the db
    * @return DataAccessResult
    */
    function & searchBaselineElements($baseline) {
        $sql = sprintf("SELECT ITEM_ID AS item_id, 
                               ITEM_TYPE AS item_type,
                               VARIANT AS variant,
                               REVISION AS revision   
                        FROM PCMS_ITEM_DATA 
                        WHERE ITEM_UID IN (SELECT ITEM_UID 
                                           FROM PCMS_BASELINE_ITEMS 
                                                WHERE BASE_SEQ_NO=%s)",
                    $this->da->quoteSmart($baseline));        
        return $this->retrieve($sql);
    }
    
    /**
    * Get last baseline by product name in the db
    * @return DataAccessResult
    */
    function & searchLastBaselineByProduct($product, $design_part) {
        $sql = sprintf("SELECT BASELINE_ID, BASE_SEQ_NO FROM PCMS_BASELINE_INFO WHERE PRODUCT_ID=%s 
                    AND TOP_NODE_PART_ID=%s
                    AND DATE_TIME=(SELECT MAX(DATE_TIME) FROM PCMS_BASELINE_INFO WHERE PRODUCT_ID=%s 
                    AND TOP_NODE_PART_ID=%s)",
                    $this->da->quoteSmart($product),
                    $this->da->quoteSmart($design_part),
                    $this->da->quoteSmart($product),
                    $this->da->quoteSmart($design_part));        
        return $this->retrieve($sql);
    }
    
    

}


?>
