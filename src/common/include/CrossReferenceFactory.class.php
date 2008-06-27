<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
 * 
 * 
 *
 * Cross Reference Factory class
 */

require_once('common/include/CrossReference.class.php');

class CrossReferenceFactory {
    
    var $entity_id;
    var $entity_gid;
    var $entity_type;
    var $source_refs_datas;
    var $target_refs_datas;
    
    /** 
     * Constructor 
     * Note that we need a valid reference parameter 
     */
    function CrossReferenceFactory($entity_id,$entity_type,$entity_group_id) {
       $this->entity_id=$entity_id;
       $this->entity_type=$entity_type;
       $this->entity_gid=$entity_group_id;
              
    }
	
	function fetchDatas(){
		$sql = 'SELECT * from cross_references where ' .
				'( target_gid='.$this->entity_gid.' AND target_id='.$this->entity_id.' AND target_type="'.$this->entity_type.'" ) ' .
				'OR (source_gid='.$this->entity_gid.' AND source_id='.$this->entity_id.' AND source_type="'.$this->entity_type.'" )';
		$res = db_query($sql);
		if (!$res || db_numrows($res) < 1) {
			
		
		}else{
			$this->source_refs_datas=array();
			$this->target_refs_datas=array();
			$itarget=0;
			$isource=0;
			
	    	while ($field_array = db_fetch_array($res)) {
	    		
	    		
	    		$target_id=$field_array['target_id'];
	    	    $target_gid=$field_array['target_gid'];
	    	    $target_type=$field_array['target_type'];
	    	    
				$source_id=$field_array['source_id'];
	    	    $source_gid=$field_array['source_gid'];
	    	    $source_type=$field_array['source_type'];
	    	    $user_id=$field_array['user_id'];
	    	    $created_at=$field_array['created_at'];
	    	    
	    	    if(($target_id==$this->entity_id)&&
	    	    	($target_gid==$this->entity_gid)&&
	    	    	($target_type==$this->entity_type)
	    	    	){
	    	    	$this->source_refs_datas[$itarget]=new CrossReference($source_id,$source_gid,$source_type,$target_id,$target_gid,$target_type,$user_id);
	    	    	$itarget++;
	    	    }
	    	    if(($source_id==$this->entity_id)&&
	    	    	($source_gid==$this->entity_gid)&&
	    	    	($source_type==$this->entity_type)
	    	    	){
	    	    	$this->target_refs_datas[$isource]=new CrossReference($source_id,$source_gid,$source_type,$target_id,$target_gid,$target_type,$user_id);
	    	    	$isource++;
	    	    }
	    	}
	    	
		}
	}

	function getNbReferences(){
		return (sizeof($this->target_refs_datas) +sizeof($this->source_refs_datas));
	}

    /** Accessors */
    function getRefSource() { return $this->source_refs_datas;}
    function getRefTarget() { return $this->target_refs_datas;}
    
    /**Display function */
    function DisplayCrossRefs() {
    	echo $this->getHTMLDisplayCrossRefs();
    }
    
    function getHTMLDisplayCrossRefs() {
    	global $Language;
        
    	$Language->loadLanguageMsg('references/references');
        
    	$artifact_ref_from=array();
    	$artifact_ref_to=array();
    	$rev_svn_ref_from=array();
    	$rev_svn_ref_to=array();
    	$cvs_commit_ref_from=array();
    	$cvs_commit_ref_to=array();
    	$artIdArray=array();
    	$artCrossArray=array();
    	$artIdCrossArray=array();
    	$revSvnGidIdArray=array();
    	$revSvnCrossArray=array();
    	$revSvnGidIdCrossArray=array();
    	$cvsCommitGidIdArray=array();
    	$cvsCommitCrossArray=array();
    	$cvsCommitGidIdCrossArray=array();
    	    	
    	//first pass to fill *CrossArray
    	for($i=0;$i<sizeof($this->target_refs_datas);$i++){   		
    		if($this->target_refs_datas[$i]->getInsertTargetType()=='artifact'){  			
    			$artIdArray[]=$this->target_refs_datas[$i]->getRefTargetId();
    		}else if($this->target_refs_datas[$i]->getInsertTargetType()=='revision_svn'){
    			$revSvnGidIdArray[]=$this->target_refs_datas[$i]->getRefTargetGid().":".$this->target_refs_datas[$i]->getRefTargetId();
    		}else if($this->target_refs_datas[$i]->getInsertTargetType()=='commit_cvs'){
    			$cvsCommitGidIdArray[]=$this->target_refs_datas[$i]->getRefTargetGid().":".$this->target_refs_datas[$i]->getRefTargetId();
    		}	  		
    	}
    	
    	for($i=0;$i<sizeof($this->source_refs_datas);$i++){
    		if($this->source_refs_datas[$i]->getInsertSourceType()=='artifact'){
    			if(in_array($this->source_refs_datas[$i]->getRefSourceId(),$artIdArray)){
    				$artIdCrossArray[]=$this->source_refs_datas[$i]->getRefSourceId();
    				$artCrossArray[]=$this->source_refs_datas[$i];
    			}else{
    				$artifact_ref_from[]=$this->source_refs_datas[$i];
    			}
    		}else if($this->source_refs_datas[$i]->getInsertSourceType()=='revision_svn'){
    			if(in_array($this->source_refs_datas[$i]->getRefSourceGid().":".$this->source_refs_datas[$i]->getRefSourceId(),$revSvnGidIdArray)){
    				$revSvnGidIdCrossArray[]=$this->source_refs_datas[$i]->getRefSourceGid().":".$this->source_refs_datas[$i]->getRefSourceId();
    				$revSvnCrossArray[]=$this->source_refs_datas[$i];
    			}else{
    				$rev_svn_ref_from[]=$this->source_refs_datas[$i] ;
    			}  
    		}else if($this->source_refs_datas[$i]->getInsertSourceType()=='commit_cvs'){
    			if(in_array($this->source_refs_datas[$i]->getRefSourceGid().":".$this->source_refs_datas[$i]->getRefSourceId(),$cvsCommitGidIdArray)){
    				$cvsCommitGidIdCrossArray[]=$this->source_refs_datas[$i]->getRefSourceGid().":".$this->source_refs_datas[$i]->getRefSourceId();
    				$cvsCommitCrossArray[]=$this->source_refs_datas[$i];
    			}else{
    				$cvs_commit_ref_from[]=$this->source_refs_datas[$i] ;
    			}  
    		}	  		
    	}
    	
    	    	
    	//second pass
    	for($i=0;$i<sizeof($this->target_refs_datas);$i++){   		
    		if($this->target_refs_datas[$i]->getInsertTargetType()=='artifact'){  			
    			if(!in_array($this->target_refs_datas[$i]->getRefTargetId(),$artIdCrossArray)){
    				$artifact_ref_to[]=$this->target_refs_datas[$i] ;
    			}
    		}else if($this->target_refs_datas[$i]->getInsertTargetType()=='revision_svn'){
    			if(!in_array($this->target_refs_datas[$i]->getRefTargetGid().":".$this->target_refs_datas[$i]->getRefTargetId(),$revSvnGidIdCrossArray)){
    				$rev_svn_ref_to[]=$this->target_refs_datas[$i] ;
    			}
    		}else if($this->target_refs_datas[$i]->getInsertTargetType()=='commit_cvs'){    			
    			if(!in_array($this->target_refs_datas[$i]->getRefTargetGid().":".$this->target_refs_datas[$i]->getRefTargetId(),$cvsCommitGidIdCrossArray)){
    				$cvs_commit_ref_to[]=$this->target_refs_datas[$i] ;
    			}
    		}	  		
    	}
    	
    	

    	$display="<p>".$Language->getText('cross_ref_fact_include','legend')."</p>";
    	if((sizeof($artCrossArray)+sizeof($artifact_ref_from)+sizeof($artifact_ref_to))>0){
    		$display.="<p><B>".$Language->getText('cross_ref_fact_include','artifact')."</B>";
    		if(sizeof($artCrossArray)>0){
    			$display.="<br>".$GLOBALS['HTML']->getImage('ic/both_arrows.png', 
    			array( 'alt'=> $Language->getText('cross_ref_fact_include','cross_referenced'),
    				 	'align' => 'top-left',
    				 	'hspace' => '5',
    				 	'title' => $Language->getText('cross_ref_fact_include','cross_referenced') ));
    			for($i=0;$i<sizeof($artCrossArray);$i++){
    				
    				$display.= "<a title='".$Language->getText('cross_ref_fact_include','artifact')."' href='".$artCrossArray[$i]->getRefSourceUrl()."'>";
	    			$display.= "#".$artCrossArray[$i]->getRefSourceId()."</a>";
	    			if($i!=(sizeof($artCrossArray)-1)){
	    				$display.= " , ";
	    			}
	    		}
	    		$display.="</br>";
    		}
    		if(sizeof($artifact_ref_from)>0){
    			$display.="<br>".$GLOBALS['HTML']->getImage('ic/left_arrow.png', 
    			array( 'alt'=> $Language->getText('cross_ref_fact_include','referenced_in'),
    				 	'align' => 'top-left',
    				 	'hspace' => '5',
    				 	'title' => $Language->getText('cross_ref_fact_include','referenced_in')));
    			for($i=0;$i<sizeof($artifact_ref_from);$i++){
    				
    				$display.= "<a title='".$Language->getText('cross_ref_fact_include','artifact')."' href='".$artifact_ref_from[$i]->getRefSourceUrl()."'>";
	    			$display.= "#".$artifact_ref_from[$i]->getRefSourceId()."</a>";
	    			if($i!=(sizeof($artifact_ref_from)-1)){
	    				$display.= " , ";
	    			}
	    		}
	    		$display.="</br>";
    		}
    		if(sizeof($artifact_ref_to)>0){
    			$display.="<br>".$GLOBALS['HTML']->getImage('ic/right_arrow.png', 
    			array( 'alt'=> $Language->getText('cross_ref_fact_include','reference_to'),
    				 	'align' => 'top-left',
    				 	'hspace' => '5',
    				 	'title' => $Language->getText('cross_ref_fact_include','reference_to')));
    			for($i=0;$i<sizeof($artifact_ref_to);$i++){		
    				$display.= "<a title='".$Language->getText('cross_ref_fact_include','artifact')."' href='".$artifact_ref_to[$i]->getRefTargetUrl()."'>";
	    			$display.="#".$artifact_ref_to[$i]->getRefTargetId()."</a>";
	    			if($i!=(sizeof($artifact_ref_to)-1)){
	    				$display.= " , ";
	    			}
	    		}
	    		$display.="</br>";
    		}
    		$display.="</p>";
    		
    	}
    	if((sizeof($revSvnCrossArray)+sizeof($rev_svn_ref_from)+sizeof($rev_svn_ref_to))>0){
    		$display.="<p><B>".$Language->getText('cross_ref_fact_include','svn_revision')."</B>";
    		if(sizeof($revSvnCrossArray)>0){
    			$display.="<br>".$GLOBALS['HTML']->getImage('ic/both_arrows.png', 
    			array( 'alt'=> $Language->getText('cross_ref_fact_include','cross_referenced'),
    				 	'align' => 'top-left',
    				 	'hspace' => '5',
    				 	'title' => $Language->getText('cross_ref_fact_include','cross_referenced') ));

    			for($i=0;$i<sizeof($revSvnCrossArray);$i++){
    				
    				$display.= "<a title='".$Language->getText('cross_ref_fact_include','svn_revision')."' href='".$revSvnCrossArray[$i]->getRefSourceUrl()."'>";
	    			$display.= "#".$revSvnCrossArray[$i]->getRefSourceId()."</a>";
	    			if($i!=(sizeof($revSvnCrossArray)-1)){
	    				$display.= " , ";
	    			}
	    		}
	    		$display.="</br>";
    		}
    		if(sizeof($rev_svn_ref_from)>0){
    			$display.="<br>".$GLOBALS['HTML']->getImage('ic/left_arrow.png', 
    			array( 'alt'=> $Language->getText('cross_ref_fact_include','referenced_in'),
    				 	'align' => 'top-left',
    				 	'hspace' => '5',
    				 	'title' => $Language->getText('cross_ref_fact_include','referenced_in')));

    			for($i=0;$i<sizeof($rev_svn_ref_from);$i++){
    				
    				$display.= "<a title='".$Language->getText('cross_ref_fact_include','svn_revision')."' href='".$rev_svn_ref_from[$i]->getRefSourceUrl()."'>";
	    			$display.="#".$rev_svn_ref_from[$i]->getRefSourceId()."</a>";
	    			if($i!=(sizeof($rev_svn_ref_from)-1)){
	    				$display.= " , ";
	    			}
	    		}
	    		$display.="</br>";
    		}
    		if(sizeof($rev_svn_ref_to)>0){
    			$display.="<br>".$GLOBALS['HTML']->getImage('ic/right_arrow.png', 
    			array( 'alt'=> $Language->getText('cross_ref_fact_include','reference_to'),
    				 	'align' => 'top-left',
    				 	'hspace' => '5',
    				 	'title' => $Language->getText('cross_ref_fact_include','reference_to')));

    			for($i=0;$i<sizeof($rev_svn_ref_to);$i++){		
    				$display.= "<a title='".$Language->getText('cross_ref_fact_include','svn_revision')."' href='".$rev_svn_ref_to[$i]->getRefTargetUrl()."'>";
	    			$display.="#".$rev_svn_ref_to[$i]->getRefTargetId()."</a>";
	    			if($i!=(sizeof($rev_svn_ref_to)-1)){
	    				$display.= " , ";
	    			}
	    		}
	    		$display.="</br>";
    		}
    		$display.="</p>";
    	}
    	
    	if((sizeof($cvsCommitCrossArray)+sizeof($cvs_commit_ref_from)+sizeof($cvs_commit_ref_to))>0){
    		$display.="<p><B>".$Language->getText('cross_ref_fact_include','cvs_commit')."</B>";
    		if(sizeof($cvsCommitCrossArray)>0){
    			$display.="<br>".$GLOBALS['HTML']->getImage('ic/both_arrows.png', 
    			array( 'alt'=> $Language->getText('cross_ref_fact_include','cross_referenced'),
    				 	'align' => 'top-left',
    				 	'hspace' => '5',
    				 	'title' => $Language->getText('cross_ref_fact_include','cross_referenced') ));

    			for($i=0;$i<sizeof($cvsCommitCrossArray);$i++){
    				
    				$display.= "<a title='".$Language->getText('cross_ref_fact_include','cvs_commit')."' href='".$cvsCommitCrossArray[$i]->getRefSourceUrl()."'>";
	    			$display.= "#".$cvsCommitCrossArray[$i]->getRefSourceId()."</a>";
	    			if($i!=(sizeof($cvsCommitCrossArray)-1)){
	    				$display.= " , ";
	    			}
	    		}
	    		$display.="</br>";
    		}
    		if(sizeof($cvs_commit_ref_from)>0){
    			$display.="<br>".$GLOBALS['HTML']->getImage('ic/left_arrow.png', 
    			array( 'alt'=> $Language->getText('cross_ref_fact_include','referenced_in'),
    				 	'align' => 'top-left',
    				 	'hspace' => '5',
    				 	'title' => $Language->getText('cross_ref_fact_include','referenced_in')));

    			for($i=0;$i<sizeof($cvs_commit_ref_from);$i++){
    				
    				$display.= "<a title='".$Language->getText('cross_ref_fact_include','cvs_commit')."' href='".$cvs_commit_ref_from[$i]->getRefSourceUrl()."'>";
	    			$display.="#".$cvs_commit_ref_from[$i]->getRefSourceId()."</a>";
	    			if($i!=(sizeof($cvs_commit_ref_from)-1)){
	    				$display.= " , ";
	    			}
	    		}
	    		$display.="</br>";
    		}
    		if(sizeof($cvs_commit_ref_to)>0){
    			$display.="<br>".$GLOBALS['HTML']->getImage('ic/right_arrow.png', 
    			array( 'alt'=> $Language->getText('cross_ref_fact_include','reference_to'),
    				 	'align' => 'top-left',
    				 	'hspace' => '5',
    				 	'title' => $Language->getText('cross_ref_fact_include','reference_to')));

    			for($i=0;$i<sizeof($cvs_commit_ref_to);$i++){		
    				$display.= "<a title='".$Language->getText('cross_ref_fact_include','cvs_commit')."' href='".$cvs_commit_ref_to[$i]->getRefSourceUrl()."'>";
	    			$display.="#".$cvs_commit_ref_to[$i]->getRefTargetId()."</a>";
	    			if($i!=(sizeof($cvs_commit_ref_to)-1)){
	    				$display.= " , ";
	    			}
	    		}
	    		$display.="</br>";
    		}
    		$display.="</p>";
    	}
    	
    	
    	return $display;
    }
    
    
    

}
