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
    var $insertTargetType;
    var $insertSourceType;
    
    
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
       
       // Only for 3.6, need refactoring later
       if($this->refTargetType=='revision_svn'){
           $this->refTargetType='/svn/?func=detailrevision&rev_id=$1&group_id=$group_id';
       }else if($this->refTargetType=='artifact'){
           $this->refTargetType='/tracker/?func=detail&aid=$1&group_id=$group_id';
       }
       if($this->refSourceType=='revision_svn'){
           $this->refSourceType='/svn/?func=detailrevision&rev_id=$1&group_id=$group_id';
       }else if($this->refSourceType=='artifact'){
           $this->refSourceType='/tracker/?func=detail&aid=$1&group_id=$group_id';
       }
       
       $sqlkey = 'SELECT keyword from reference r,reference_group rg where link="'.$this->refSourceType.'" AND r.id = rg.reference_id AND rg.group_id='.$refSourceGid;
	   $reskey = db_query($sqlkey);
	   if (!$reskey || db_numrows($reskey) < 1) {
	   	$this->setError('Cross Reference: Bad link');
		return false;
	   }else{
		$key_array = db_fetch_array($reskey);
		$this->sourceKey= $key_array['keyword'];
	   }
       
       $sqlkey = 'SELECT keyword from reference r,reference_group rg where link="'.$this->refTargetType.'" AND r.id = rg.reference_id AND rg.group_id='.$refSourceGid;
	   $reskey = db_query($sqlkey);
	   if (!$reskey || db_numrows($reskey) < 1) {
	   	$this->setError('Cross Reference: Bad link');
		return false;
	   }else{
		$key_array = db_fetch_array($reskey);
		$this->targetKey= $key_array['keyword'];
	   }
	   
	   if($this->refTargetType=='/svn/?func=detailrevision&rev_id=$1&group_id=$group_id'){
			$this->insertTargetType='revision_svn';
		}else if($this->refTargetType=='/tracker/?func=detail&aid=$1&group_id=$group_id'){
			$this->insertTargetType='artifact';
		}else if($this->refTargetType=='/cvs/?func=detailcommit&commit_id=$1&group_id=$group_id'){
			$this->insertTargetType='commit_cvs';		
		}	
		
		if($this->refSourceType=='/svn/?func=detailrevision&rev_id=$1&group_id=$group_id'){
			$this->insertSourceType='revision_svn';
		}else if($this->refSourceType=='/tracker/?func=detail&aid=$1&group_id=$group_id'){
			$this->insertSourceType='artifact';
		}else if($this->refSourceType=='/cvs/?func=detailcommit&commit_id=$1&group_id=$group_id'){
			$this->insertSourceType='commit_cvs';		
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
    function getInsertSourceType() { return $this->insertSourceType;}
    function getInsertTargetType() { return $this->insertTargetType;}
	
	/** DB functions */
	function createDbCrossRef(){
		
		$sql='INSERT INTO cross_references (created_at, user_id,'.
		'source_type,source_id,source_gid,target_type, target_id,' .
		' target_gid) VALUES ';
		
		$sql .= "(". (time()) .",". db_ei($this->userId) .",'". db_es($this->insertSourceType) ."',".
				 db_ei($this->refSourceId) .",". db_ei($this->refSourceGid) .",'". db_es($this->insertTargetType) ."',".
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
    
    

}
