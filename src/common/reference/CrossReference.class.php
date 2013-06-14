<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
 * 
 * 
 *
 * Cross Reference class
 * Stores a Cross Reference as extracted from some user text.
 */

require_once 'common/include/Error.class.php';
require_once 'utils.php';

class CrossReference extends Error {
    var $id; 
    var $userId;
    var $createdAt;
    
    var $refSourceId;
    var $refSourceGid;
    var $refSourceType;
    var $sourceUrl;
    var $sourceKey;
    
    var $refTargetId;
    var $refTargetGid;
    var $refTargetType;
  	var $targetUrl;
    var $targetKey;
    var $insertTargetType;
    var $insertSourceType;
    
    
    /** 
     * Constructor 
     * 
     */
    function CrossReference($refSourceId, $refSourceGid, $refSourceType, $refSourceKey, $refTargetId, $refTargetGid, $refTargetType, $refTargetKey, $userId) {
       $this->refSourceId=$refSourceId;
       $this->refSourceGid=$refSourceGid;
       $this->refSourceType=$refSourceType;
       $this->refTargetId= $refTargetId;
       $this->refTargetGid= $refTargetGid;
       $this->refTargetType= $refTargetType;
       $this->userId=$userId;
       $this->sourceUrl='';
       $this->targetUrl='';
       
       $this->sourceKey= $refSourceKey;
       $this->insertSourceType = $refSourceType;
       $this->targetKey = $refTargetKey;
       $this->insertTargetType = $refTargetType;
       
	   $this->computeUrls();
       
    }
    
    /** Accessors */
    function getRefSourceId() { return $this->refSourceId;}
    function getRefSourceGid() { return $this->refSourceGid;}
    function getRefSourceType() { return $this->refSourceType;}
    function getRefTargetId() { return $this->refTargetId;}
    function getRefTargetGid() { return $this->refTargetGid;}
    function getRefTargetType() { return $this->refTargetType;}
    function getUserId() { return $this->userId;}
    function getRefTargetUrl() { return $this->targetUrl;}
    function getRefSourceUrl() { return $this->sourceUrl;}
    function getRefSourceKey() { return $this->sourceKey;}
    function getRefTargetKey() { return $this->targetKey;}
    function getCreatedAt() { return $this->createdAt;}
    function getInsertSourceType() { return $this->insertSourceType;}
    function getInsertTargetType() { return $this->insertTargetType;}
	
    
    /**
     * Return true if current CrossReference is really "cross referenced" with $crossref
     *
     * @param CrossReference $crossref
     * @return boolean true if current CrossReference is really "cross referenced" with $crossref
     */
    function isCrossReferenceWith($crossref) {
        return $this->getRefSourceId() == $crossref->getRefTargetId() &&
               $this->getRefSourceGid() == $crossref->getRefTargetGid() &&
               $this->getRefSourceType() == $crossref->getRefTargetType() &&
               $crossref->getRefSourceId() == $this->getRefTargetId() &&
               $crossref->getRefSourceGid() == $this->getRefTargetGid() &&
               $crossref->getRefSourceType() == $this->getRefTargetType();
    }
    
	/** DB functions */
	function createDbCrossRef() {
		
		$sql='INSERT INTO cross_references (created_at, user_id,'.
		'source_type,source_keyword,source_id,source_gid,target_type,target_keyword, target_id,' .
		' target_gid) VALUES ';
		
		$sql .= "(". (time()) .",". db_ei($this->userId) .", '". db_es($this->insertSourceType) ."', '".
				 db_es($this->sourceKey) ."' ,'".db_es($this->refSourceId) ."' ,". db_ei($this->refSourceGid) .", '". db_es($this->insertTargetType) ."', '".
	 			db_es($this->targetKey) ."' ,'".db_es($this->refTargetId) ."', ".db_ei($this->refTargetGid) . ")";
    	$res = db_query($sql);
      	if ($res) {
			return true;
      	} else {
			return false;
      	}
	
	}

    function existInDb(){
    	
    	$sql="SELECT * from cross_references WHERE source_id='". db_es($this->refSourceId)."'" .
    		" AND target_id='". db_es($this->refTargetId)."' AND source_gid='". db_ei($this->refSourceGid)."' ".
    		"AND target_gid='". db_ei($this->refTargetGid)."' AND source_type='". db_es($this->insertSourceType) ."' AND target_type='". db_es($this->insertTargetType) ."'";
    	$res = db_query($sql);
    	if (!$res || db_numrows($res) < 1) {
    		return false;
    	}else{
    		return true;
    	}
    }
    
    function computeUrls(){
    	$group_param = '';
		if ($this->refTargetGid!=100) { $group_param="&group_id=".$this->refTargetGid;}
        $this->targetUrl=get_server_url()."/goto?key=".urlencode($this->targetKey)."&val=".urlencode($this->refTargetId).$group_param;
		$group_param = '';
		if ($this->refSourceGid!=100) { $group_param="&group_id=".$this->refSourceGid;}
        $this->sourceUrl=get_server_url()."/goto?key=".urlencode($this->sourceKey)."&val=".urlencode($this->refSourceId).$group_param;
	
    }    
    
    function deleteCrossReference(){
        $sql = "DELETE FROM cross_references 
		        WHERE ((target_gid=" . $this->refTargetGid . " AND target_id='" . $this->refTargetId . "' AND target_type='" . $this->refTargetType. "' ) 
				     AND (source_gid=" .$this->refSourceGid." AND source_id='" .$this->refSourceId . "' AND source_type='" . $this->refSourceType. "' )) 
                     OR ((target_gid=" . $this->refSourceGid . " AND target_id='" . $this->refSourceId . "' AND target_type='" . $this->refSourceType. "' ) 
				     AND (source_gid=" . $this->refTargetGid." AND source_id='" .$this->refTargetId  . "' AND source_type='" . $this->refTargetType. "' ))";
        $res = db_query($sql);
        if ($res) {
            return true;
      	} else {
            return false;
      	}
    }
    

}

?>