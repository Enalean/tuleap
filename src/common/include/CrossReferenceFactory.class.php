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
       $this->source_refs_datas=0;
       $this->target_refs_datas=0;
    }
	
	function fetchDatas(){
		$sql = 'SELECT * from cross_references where ' .
				'( target_gid='.$this->entity_gid.' AND target_id='.$this->entity_id.' AND target_type="'.$this->entity_type.'" ) ' .
				'OR (source_gid='.$this->entity_gid.' AND source_id='.$this->entity_id.' AND source_type="'.$this->entity_type.'" )';
		$res = db_query($sql);
		if (!$res || db_numrows($res) < 1) {
			$this->source_refs_datas=0;
			$this->target_refs_datas=0;
			
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
	    	    }else{
	    	    	$this->target_refs_datas[$isource]=new CrossReference($source_id,$source_gid,$source_type,$target_id,$target_gid,$target_type,$user_id);
	    	    	$isource++;
	    	    }
	    	}
		}
	}

    /** Accessors */
    function getRefSource() { return $this->source_refs_datas;}
    function getRefTarget() { return $this->target_refs_datas;}
    
    /**Display function */
    function DisplayCrossRefs() {
    	echo $this->getHTMLDisplayCrossRefs();
    }
    function getHTMLDisplayCrossRefs() {
    	$display = "Reference to : <br/>";
    	if($this->target_refs_datas!=0){
		    for($i=0;$i<sizeof($this->target_refs_datas);$i++){
		    	
		    	$display.= "<a href='".$this->target_refs_datas[$i]->getRefTargetUrl()."'>";
		    	$display.= "-> ".$this->target_refs_datas[$i]->getRefTargetKey();
		    	$display.= " #".$this->target_refs_datas[$i]->getRefTargetId()."</a><br/>";
		    }
    	}
    	$display.= "Referenced from : <br/>";
    	if($this->source_refs_datas!=0){
		    for($i=0;$i<sizeof($this->source_refs_datas);$i++){
		    	
		    	$display.= "<a href='".$this->source_refs_datas[$i]->getRefSourceUrl()."'>";
		    	$display.= "<- ".$this->source_refs_datas[$i]->getRefSourceKey();
		    	$display.= " #".$this->source_refs_datas[$i]->getRefSourceId()."</a><br/>";
		    }
    	}
    	return $display;
    }
    
    
    

}
