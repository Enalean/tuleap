<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
 * 
 * 
 *
 * Cross Reference class
 * Stores a Cross Reference as extracted from some user text.
 */


class CrossReference extends Error{
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
    
    
    
    /** 
     * Constructor 
     * 
     */
    function CrossReference($refSourceId, $refSourceGid, $refSourceType, $refTargetId, $refTargetGid, $refTargetType, $userId) {
       $this->refSourceId=$refSourceId;
       $this->refSourceGid=$refSourceGid;
       $this->refSourceType=$refSourceType;
       $this->refTargetId= $refTargetId;
       $this->refTargetGid= $refTargetGid;
       $this->refTargetType= $refTargetType;
       $this->userId=$userId;
       $this->sourceUrl='';
       $this->targetUrl='';
       
       $sqlkey = 'SELECT keyword from reference where service_short_name="'.$this->refSourceType.'"';
	   $reskey = db_query($sqlkey);
	   if (!$reskey || db_numrows($reskey) < 1) {
	   	$this->setError('Cross Reference: Bad service_short_name');
		return false;
	   }else{
		$key_array = db_fetch_array($reskey);
		$this->sourceKey= $key_array['keyword'];
	   }
       
       $sqlkey = 'SELECT keyword from reference where service_short_name="'.$this->refTargetType.'"';
	   $reskey = db_query($sqlkey);
	   if (!$reskey || db_numrows($reskey) < 1) {
	   	$this->setError('Cross Reference: Bad service_short_name');
		return false;
	   }else{
		$key_array = db_fetch_array($reskey);
		$this->targetKey= $key_array['keyword'];
	   }
	   
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
    
	
	/** DB functions */
	function createDbCrossRef(){
		$sql='INSERT INTO cross_references (created_at, user_id,'.
		'source_type,source_id,source_gid,target_type, target_id,' .
		' target_gid) VALUES ';
		
		$sql .= "(". (time()) .",". db_ei($this->userId) .",'". db_es($this->refSourceType) ."',".
				 db_ei($this->refSourceId) .",". db_ei($this->refSourceGid) .",'". db_es($this->refTargetType) ."',".
	 			db_ei($this->refTargetId) .",".db_ei($this->refTargetGid) . ")";
    	$res = db_query($sql);
      	if ($res) {
			return true;
      	} else {
			return false;
      	}
	
	}

    function existInDb(){
    	$sql="SELECT * from cross_references WHERE source_id='". db_ei($this->refSourceId)."'" .
    		" AND target_id='". db_ei($this->refTargetId)."' AND source_gid='". db_ei($this->refSourceGid)."' ".
    		"AND target_gid='". db_ei($this->refTargetGid)."' AND source_type='". db_es($this->refSourceType) ."' AND target_type='". db_es($this->refTargetType) ."'";
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
    
    

}
